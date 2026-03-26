<?php

namespace App\Http\Controllers\Api;

use App\Exports\DistrictPaymentRecapExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\RequestAssignmentResouce;
use App\Models\PbgTask;
use App\Models\PbgTaskGoogleSheet;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Enums\PbgTaskStatus;

class RequestAssignmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Build base query for counting (without relationships to avoid duplicates)
        $baseQuery = PbgTask::query();
        
        // Always filter only valid data (is_valid = true)
        $baseQuery->where('is_valid', true);
        
        // Apply year filter if provided (to match BigdataResume behavior)
        if ($request->has('year') && !empty($request->get('year'))) {
            $year = $request->get('year');
            $baseQuery->whereYear('start_date', $year);
            Log::info('RequestAssignmentController year filter applied', ['year' => $year]);
        }
        
        // Get filter value, default to 'all' if not provided or empty
        $filter = $request->has('filter') && !empty($request->get('filter')) 
            ? strtolower(trim($request->get('filter'))) 
            : 'all';
        
        // Log filter for debugging
        Log::info('RequestAssignmentController filter applied', ['filter' => $filter, 'original' => $request->get('filter')]);

        // Apply filters to base query using single consolidated method
        $this->applyFilter($baseQuery, $filter);
        
        // Get accurate count from base query (without relationships)
        $accurateCount = $baseQuery->count();
        
        // Clone the base query for data fetching with relationships
        $dataQuery = clone $baseQuery;
        
        $dataQuery->with([
            'attachments' => function ($q) {
                $q->whereIn('pbg_type', ['berita_acara', 'bukti_bayar']);
            },
            'pbg_task_retributions',
            'pbg_task_detail',
            'pbg_status'
        ])->orderBy('id', 'desc');
    
        // Log final query count for debugging
        Log::info('RequestAssignmentController final result', [
            'filter' => $filter,
            'search' => $request->get('search'),
            'year' => $request->get('year'),
            'accurate_count' => $accurateCount,
            'request_url' => $request->fullUrl(),
            'all_params' => $request->all()
        ]);
        
        // Cross-validation with BigdataResume logic (for debugging consistency)
        if ($filter !== 'all' && $request->has('year') && !empty($request->get('year'))) {
            $this->validateConsistencyWithBigdataResume($filter, $request->get('year'), $accurateCount);
        }
        
        // Apply search to data query
        if ($request->has('search') && !empty($request->get("search"))) {
            $this->applySearch($dataQuery, $request->get('search'));
        }
        
        // Additional logging for potention filter
        if ($filter === 'potention') {
            $rejectedCount = PbgTask::whereIn('status', PbgTaskStatus::getRejected())->count();
            Log::info('Potention filter details', [
                'potention_count' => $accurateCount,
                'rejected_count' => $rejectedCount,
                'total_all_records' => PbgTask::count(),
                'note' => 'Potention filter excludes rejected data'
            ]);
        }
        
        // Also log to console for immediate debugging
        if ($filter !== 'all') {
            error_log('RequestAssignment Filter Debug: ' . $filter . ' -> Count: ' . $accurateCount);
        }
    
        // Get paginated results with relationships
        $paginatedResults = $dataQuery->paginate();
        
        // Append query parameters to pagination
        $paginatedResults->appends($request->query());
        
        return RequestAssignmentResouce::collection($paginatedResults);
    }

    /**
     * Apply filter logic to the query
     */
    private function applyFilter($query, string $filter)
    {
        switch ($filter) {
            case 'all':
                // No additional filters, just return all valid records
                break;
            case 'non-business':
                // Non-business: function_type NOT LIKE usaha AND (unit IS NULL OR unit <= 1)
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                            ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                        })
                        ->orWhereNull('function_type');
                    })
                    ->whereIn("status", PbgTaskStatus::getNonVerified())
                    // Additional condition: unit IS NULL OR unit <= 1
                    ->where(function ($q3) {
                        $q3->whereDoesntHave('pbg_task_detail', function ($q4) {
                            $q4->where('unit', '>', 1);
                        })
                        ->orWhereDoesntHave('pbg_task_detail');
                    });
                });
                break;

            case 'business':
                // Business: function_type LIKE usaha OR (non-business with unit > 1)
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        // Traditional business: function_type LIKE usaha
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%fungsi usaha%'])
                            ->orWhereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%sebagai tempat usaha%']);
                        })
                        // OR non-business with unit > 1 (becomes business)
                        ->orWhere(function ($q3) {
                            $q3->where(function ($q4) {
                                $q4->where(function ($q5) {
                                    $q5->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                                    ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                                })
                                ->orWhereNull('function_type');
                            })
                            ->whereHas('pbg_task_detail', function ($q4) {
                                $q4->where('unit', '>', 1);
                            });
                        });
                    })
                    ->whereIn("status", PbgTaskStatus::getNonVerified());
                });
                break;

            case 'verified':
                // Match BigdataResume verified logic exactly
                $query->whereIn("status", PbgTaskStatus::getVerified());
                break;

            case 'non-verified':
                // Match BigdataResume non-verified logic exactly
                $query->whereIn("status", PbgTaskStatus::getNonVerified());
                break;

            case 'potention':
                // Match BigdataResume potention logic exactly
                $query->whereIn("status", PbgTaskStatus::getPotention());
                break;
                
            case 'issuance-realization-pbg':
                // Match BigdataResume issuance realization logic exactly
                $query->whereIn("status", PbgTaskStatus::getIssuanceRealizationPbg());
                break;
                
            case 'process-in-technical-office':
                // Match BigdataResume process in technical office logic exactly
                $query->whereIn("status", PbgTaskStatus::getProcessInTechnicalOffice());
                break;
                
            case 'waiting-click-dpmptsp':
                // Match BigdataResume waiting click DPMPTSP logic exactly
                $query->whereIn("status", PbgTaskStatus::getWaitingClickDpmptsp());
                break;

            case 'non-business-rab':
                // Non-business tasks: function_type NOT LIKE usaha AND (unit IS NULL OR unit <= 1)
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                            ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                        })
                        ->orWhereNull('function_type');
                    })
                    ->whereIn("status", PbgTaskStatus::getNonVerified())
                    // Additional condition: unit IS NULL OR unit <= 1
                    ->where(function ($q3) {
                        $q3->whereDoesntHave('pbg_task_detail', function ($q4) {
                            $q4->where('unit', '>', 1);
                        })
                        ->orWhereDoesntHave('pbg_task_detail');
                    });
                })
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('pbg_task_detail_data_lists')
                          ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                          ->where('pbg_task_detail_data_lists.data_type', 3)
                          ->where('pbg_task_detail_data_lists.status', '!=', 1);
                });
                break;

            case 'non-business-krk':
                // Non-business tasks: function_type NOT LIKE usaha AND (unit IS NULL OR unit <= 1)
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                            ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                        })
                        ->orWhereNull('function_type');
                    })
                    ->whereIn("status", PbgTaskStatus::getNonVerified())
                    // Additional condition: unit IS NULL OR unit <= 1
                    ->where(function ($q3) {
                        $q3->whereDoesntHave('pbg_task_detail', function ($q4) {
                            $q4->where('unit', '>', 1);
                        })
                        ->orWhereDoesntHave('pbg_task_detail');
                    });
                })
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('pbg_task_detail_data_lists')
                          ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                          ->where('pbg_task_detail_data_lists.data_type', 2)
                          ->where('pbg_task_detail_data_lists.status', '!=', 1);
                });
                break;

            case 'business-rab':
                // Business tasks: function_type LIKE usaha OR (non-business with unit > 1)
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        // Traditional business: function_type LIKE usaha
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%fungsi usaha%'])
                            ->orWhereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%sebagai tempat usaha%']);
                        })
                        // OR non-business with unit > 1 (becomes business)
                        ->orWhere(function ($q3) {
                            $q3->where(function ($q4) {
                                $q4->where(function ($q5) {
                                    $q5->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                                    ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                                })
                                ->orWhereNull('function_type');
                            })
                            ->whereHas('pbg_task_detail', function ($q4) {
                                $q4->where('unit', '>', 1);
                            });
                        });
                    })
                    ->whereIn("status", PbgTaskStatus::getNonVerified());
                })
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('pbg_task_detail_data_lists')
                          ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                          ->where('pbg_task_detail_data_lists.data_type', 3)
                          ->where('pbg_task_detail_data_lists.status', '!=', 1);
                });
                break;

            case 'business-krk':
                // Business tasks: function_type LIKE usaha OR (non-business with unit > 1)
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        // Traditional business: function_type LIKE usaha
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%fungsi usaha%'])
                            ->orWhereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%sebagai tempat usaha%']);
                        })
                        // OR non-business with unit > 1 (becomes business)
                        ->orWhere(function ($q3) {
                            $q3->where(function ($q4) {
                                $q4->where(function ($q5) {
                                    $q5->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                                    ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                                })
                                ->orWhereNull('function_type');
                            })
                            ->whereHas('pbg_task_detail', function ($q4) {
                                $q4->where('unit', '>', 1);
                            });
                        });
                    })
                    ->whereIn("status", PbgTaskStatus::getNonVerified());
                })
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('pbg_task_detail_data_lists')
                          ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                          ->where('pbg_task_detail_data_lists.data_type', 2)
                          ->where('pbg_task_detail_data_lists.status', '!=', 1);
                });
                break;

            case 'business-dlh':
                // Business tasks: function_type LIKE usaha OR (non-business with unit > 1)
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        // Traditional business: function_type LIKE usaha
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%fungsi usaha%'])
                            ->orWhereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%sebagai tempat usaha%']);
                        })
                        // OR non-business with unit > 1 (becomes business)
                        ->orWhere(function ($q3) {
                            $q3->where(function ($q4) {
                                $q4->where(function ($q5) {
                                    $q5->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                                    ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                                })
                                ->orWhereNull('function_type');
                            })
                            ->whereHas('pbg_task_detail', function ($q4) {
                                $q4->where('unit', '>', 1);
                            });
                        });
                    })
                    ->whereIn("status", PbgTaskStatus::getNonVerified());
                })
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('pbg_task_detail_data_lists')
                          ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                          ->where('pbg_task_detail_data_lists.data_type', 5)
                          ->where('pbg_task_detail_data_lists.status', '!=', 1);
                });
                break;
                
            default:
                // Log unrecognized filter for debugging
                Log::warning('Unrecognized filter value', ['filter' => $filter]);
                break;
        }
    }

    /**
     * Apply search logic to the query
     */
    private function applySearch($query, string $search)
    {
        // Search in pbg_task columns
        $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%$search%")
              ->orWhere('registration_number', 'LIKE', "%$search%")
              ->orWhere('owner_name', 'LIKE', "%$search%")
              ->orWhere('address', 'LIKE', "%$search%");
        });
        
        // If search term exists, also find UUIDs from name_building search
        $namesBuildingUuids = DB::table('pbg_task_details')
            ->where('name_building', 'LIKE', "%$search%")
            ->pluck('pbg_task_uid')
            ->toArray();
        
        // If we found matching name_building records, include them in the search
        if (!empty($namesBuildingUuids)) {
            $query->orWhereIn('uuid', $namesBuildingUuids);
        }
    }

    public function report_payment_recaps(Request $request)
    {
        try {
            // Query dengan group by kecamatan dan sum nilai_retribusi_keseluruhan_simbg
            $query = PbgTaskGoogleSheet::select(
                    'kecamatan',
                    DB::raw('SUM(nilai_retribusi_keseluruhan_simbg) as total')
                )
                ->groupBy('kecamatan')
                ->paginate(10);

            // Return hasil dalam JSON format
            return response()->json([
                'success' => true,
                'data' => $query
            ]);

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function export_excel_pbg_tasks(){
        return Excel::download(new \App\Exports\PbgTaskExport('all', 0), 'data-pbg-' . date('Y-m-d') . '.xlsx');
    }

    public function export_excel_district_payment_recaps(){
        return Excel::download(new DistrictPaymentRecapExport, 'laporan-rekap-data-pembayaran.xlsx');
    }
    public function export_pdf_district_payment_recaps(){
        $data = PbgTaskGoogleSheet::select(
                    'kecamatan',
                    DB::raw('SUM(nilai_retribusi_keseluruhan_simbg) as total')
                )
                ->groupBy('kecamatan')->get();
        $pdf = Pdf::loadView('exports.district_payment_report', compact('data'));
        return $pdf->download('laporan-rekap-data-pembayaran.pdf');
    }
    public function report_pbg_ptsp()
    {
        try {
            // Query dengan group by status dan count total per status
            $query = PbgTask::select(
                    'status',
                    'status_name',
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy('status', 'status_name')
                ->paginate(10);

            // Return hasil dalam JSON format
            return response()->json([
                'success' => true,
                'data' => $query
            ]);

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    
    /**
     * Validate consistency with BigdataResume logic for debugging
     */
    private function validateConsistencyWithBigdataResume(?string $filter, $year, int $actualCount)
    {
        try {
            // Validate input parameters
            if (empty($filter) || empty($year)) {
                Log::info('Skipping consistency validation - empty filter or year', [
                    'filter' => $filter,
                    'year' => $year
                ]);
                return;
            }
            
            // Convert year to integer
            $year = (int) $year;
            if ($year <= 0) {
                Log::warning('Invalid year provided for consistency validation', ['year' => $year]);
                return;
            }
            
            $bigdataResumeCount = null;
            
            // Calculate expected count using BigdataResume logic
            switch ($filter) {
                case 'verified':
                    $bigdataResumeCount = PbgTask::whereIn('status', PbgTaskStatus::getVerified())
                        ->where('is_valid', true)
                        ->whereYear('start_date', $year)
                        ->count();
                    break;
                    
                case 'non-verified':
                    $bigdataResumeCount = PbgTask::whereIn('status', PbgTaskStatus::getNonVerified())
                        ->where('is_valid', true)
                        ->whereYear('start_date', $year)
                        ->count();
                    break;
                    
                case 'business':
                    $bigdataResumeCount = PbgTask::where(function ($q) {
                        $q->where(function ($q2) {
                            // Traditional business: function_type LIKE usaha
                            $q2->where(function ($q3) {
                                $q3->whereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%fungsi usaha%'])
                                ->orWhereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%sebagai tempat usaha%']);
                            })
                            // OR non-business with unit > 1 (becomes business)
                            ->orWhere(function ($q3) {
                                $q3->where(function ($q4) {
                                    $q4->where(function ($q5) {
                                        $q5->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                                        ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                                    })
                                    ->orWhereNull('function_type');
                                })
                                ->whereHas('pbg_task_detail', function ($q4) {
                                    $q4->where('unit', '>', 1);
                                });
                            });
                        })
                        ->whereIn("status", PbgTaskStatus::getNonVerified());
                    })
                    ->where('is_valid', true)
                    ->whereYear('start_date', $year)
                    ->count();
                    break;
                    
                case 'non-business':
                    $bigdataResumeCount = PbgTask::where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->where(function ($q3) {
                                $q3->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                                ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                            })
                            ->orWhereNull('function_type');
                        })
                        ->whereIn("status", PbgTaskStatus::getNonVerified())
                        // Additional condition: unit IS NULL OR unit <= 1
                        ->where(function ($q3) {
                            $q3->whereDoesntHave('pbg_task_detail', function ($q4) {
                                $q4->where('unit', '>', 1);
                            })
                            ->orWhereDoesntHave('pbg_task_detail');
                        });
                    })
                    ->where('is_valid', true)
                    ->whereYear('start_date', $year)
                    ->count();
                    break;
                    
                case 'potention':
                    $bigdataResumeCount = PbgTask::whereIn('status', PbgTaskStatus::getPotention())
                        ->where('is_valid', true)
                        ->whereYear('start_date', $year)
                        ->count();
                    break;
                    
                case 'waiting-click-dpmptsp':
                    $bigdataResumeCount = PbgTask::whereIn('status', PbgTaskStatus::getWaitingClickDpmptsp())
                        ->where('is_valid', true)
                        ->whereYear('start_date', $year)
                        ->count();
                    break;
                    
                case 'issuance-realization-pbg':
                    $bigdataResumeCount = PbgTask::whereIn('status', PbgTaskStatus::getIssuanceRealizationPbg())
                        ->where('is_valid', true)
                        ->whereYear('start_date', $year)
                        ->count();
                    break;
                    
                case 'process-in-technical-office':
                    $bigdataResumeCount = PbgTask::whereIn('status', PbgTaskStatus::getProcessInTechnicalOffice())
                        ->where('is_valid', true)
                        ->whereYear('start_date', $year)
                        ->count();
                    break;

                case 'non-business-rab':
                    $bigdataResumeCount = PbgTask::where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->where(function ($q3) {
                                $q3->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                                ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                            })
                            ->orWhereNull('function_type');
                        })
                        ->whereIn("status", PbgTaskStatus::getNonVerified());
                    })
                    ->where('is_valid', true)
                    ->whereYear('start_date', $year)
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                              ->from('pbg_task_detail_data_lists')
                              ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                              ->where('pbg_task_detail_data_lists.data_type', 3)
                              ->where('pbg_task_detail_data_lists.status', '!=', 1);
                    })
                    ->count();
                    break;

                case 'non-business-krk':
                    $bigdataResumeCount = PbgTask::where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->where(function ($q3) {
                                $q3->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                                ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                            })
                            ->orWhereNull('function_type');
                        })
                        ->whereIn("status", PbgTaskStatus::getNonVerified());
                    })
                    ->where('is_valid', true)
                    ->whereYear('start_date', $year)
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                              ->from('pbg_task_detail_data_lists')
                              ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                              ->where('pbg_task_detail_data_lists.data_type', 2)
                              ->where('pbg_task_detail_data_lists.status', '!=', 1);
                    })
                    ->count();
                    break;

                case 'business-rab':
                    $bigdataResumeCount = PbgTask::where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->whereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%fungsi usaha%'])
                            ->orWhereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%sebagai tempat usaha%']);
                        })
                        ->whereIn("status", PbgTaskStatus::getNonVerified());
                    })
                    ->where('is_valid', true)
                    ->whereYear('start_date', $year)
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                              ->from('pbg_task_detail_data_lists')
                              ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                              ->where('pbg_task_detail_data_lists.data_type', 3)
                              ->where('pbg_task_detail_data_lists.status', '!=', 1);
                    })
                    ->count();
                    break;

                case 'business-krk':
                    $bigdataResumeCount = PbgTask::where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->whereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%fungsi usaha%'])
                            ->orWhereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%sebagai tempat usaha%']);
                        })
                        ->whereIn("status", PbgTaskStatus::getNonVerified());
                    })
                    ->where('is_valid', true)
                    ->whereYear('start_date', $year)
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                              ->from('pbg_task_detail_data_lists')
                              ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                              ->where('pbg_task_detail_data_lists.data_type', 2)
                              ->where('pbg_task_detail_data_lists.status', '!=', 1);
                    })
                    ->count();
                    break;

                case 'business-dlh':
                    $bigdataResumeCount = PbgTask::where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->whereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%fungsi usaha%'])
                            ->orWhereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%sebagai tempat usaha%']);
                        })
                        ->whereIn("status", PbgTaskStatus::getNonVerified());
                    })
                    ->where('is_valid', true)
                    ->whereYear('start_date', $year)
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                              ->from('pbg_task_detail_data_lists')
                              ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                              ->where('pbg_task_detail_data_lists.data_type', 5)
                              ->where('pbg_task_detail_data_lists.status', '!=', 1);
                    })
                    ->count();
                    break;
                    
                default:
                    Log::info('Unknown filter for consistency validation', [
                        'filter' => $filter,
                        'year' => $year
                    ]);
                    return;
            }
            
            if ($bigdataResumeCount !== null) {
                $isConsistent = ($actualCount === $bigdataResumeCount);
                
                Log::info('RequestAssignment vs BigdataResume consistency check', [
                    'filter' => $filter,
                    'year' => $year,
                    'request_assignment_count' => $actualCount,
                    'bigdata_resume_count' => $bigdataResumeCount,
                    'is_consistent' => $isConsistent,
                    'difference' => $actualCount - $bigdataResumeCount
                ]);
                
                if (!$isConsistent) {
                    Log::warning('INCONSISTENCY DETECTED between RequestAssignment and BigdataResume', [
                        'filter' => $filter,
                        'year' => $year,
                        'request_assignment_count' => $actualCount,
                        'bigdata_resume_count' => $bigdataResumeCount,
                        'difference' => $actualCount - $bigdataResumeCount
                    ]);
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error in consistency validation', [
                'error' => $e->getMessage(),
                'filter' => $filter,
                'year' => $year
            ]);
        }
    }
}
