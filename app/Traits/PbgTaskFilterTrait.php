<?php

namespace App\Traits;

use App\Enums\PbgTaskStatus;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for PBG task filtering.
 * Used by both RequestAssignmentController (table) and PbgTaskExport (Excel)
 * so row counts are always identical.
 */
trait PbgTaskFilterTrait
{
    /**
     * Apply date range + status filter to a PbgTask query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string  $filter   e.g. 'verified', 'non-verified', 'all', ...
     * @param int     $year     resolved year (never 0)
     * @param bool    $isSemua  true when no specific year was requested
     */
    protected function applyPbgFilter($query, string $filter, int $year, bool $isSemua): void
    {
        // --- Date range pre-processing for simple span/current-year filters ---
        $simpleSpanFilters    = ['waiting-click-dpmptsp', 'process-in-technical-office', 'potention'];
        $currentYearFilters   = ['non-verified', 'non-business', 'business',
                                  'non-business-rab', 'non-business-krk',
                                  'business-rab', 'business-krk', 'business-dlh'];

        if (in_array($filter, $simpleSpanFilters)) {
            if ($isSemua) {
                $query->whereBetween('start_date', [($year - 1) . '-01-01', $year . '-12-31']);
            } else {
                $query->whereYear('start_date', $year);
            }
        } elseif (in_array($filter, $currentYearFilters)) {
            $query->whereYear('start_date', $year);
        }
        // 'all', 'verified', 'issuance-realization-pbg' handle their own date ranges
        // inside the switch below.

        // --- Filter switch ---
        switch ($filter) {

            case 'all':
                if ($isSemua) {
                    $query->whereBetween('start_date', [($year - 1) . '-01-01', $year . '-12-31']);
                } else {
                    $query->whereYear('start_date', $year);
                }
                break;

            // ── Non-verified (Belum Lengkap) ─────────────────────────────────
            case 'non-verified':
                $query->whereIn('status', PbgTaskStatus::getNonVerified());
                break;

            // ── Verified (Berkas Lengkap) ─────────────────────────────────────
            case 'verified':
                if ($isSemua) {
                    // Dashboard scope: full prev+current year span, complex realisasi logic
                    $query->where(function ($q) use ($year) {
                        $q->where(function ($q2) use ($year) {
                            $q2->whereIn('status', PbgTaskStatus::getWaitingClickDpmptsp())
                                ->whereBetween('start_date', [($year - 1) . '-01-01', $year . '-12-31']);
                        })
                        ->orWhere(function ($q2) use ($year) {
                            $q2->whereIn('status', PbgTaskStatus::getProcessInTechnicalOffice())
                                ->whereBetween('start_date', [($year - 1) . '-01-01', $year . '-12-31']);
                        })
                        ->orWhere(function ($q2) use ($year) {
                            // SK Terbit dari tahun lalu yang dokumennya tahun ini
                            $q2->where('status', PbgTaskStatus::SK_PBG_TERBIT->value)
                                ->whereBetween('start_date', [($year - 1) . '-01-01', ($year - 1) . '-12-31'])
                                ->whereNotNull('document_number')
                                ->whereRaw("CAST(RIGHT(REGEXP_SUBSTR(document_number, '[0-9]{8}'), 4) AS UNSIGNED) = ?", [$year]);
                        })
                        ->orWhere(function ($q2) use ($year) {
                            $q2->where('status', PbgTaskStatus::PENERBITAN_SK_PBG->value)
                                ->whereBetween('start_date', [$year . '-01-01', $year . '-12-31']);
                        })
                        ->orWhere(function ($q2) use ($year) {
                            $q2->where('status', PbgTaskStatus::SK_PBG_TERBIT->value)
                                ->whereBetween('start_date', [$year . '-01-01', $year . '-12-31']);
                        });
                    });
                } else {
                    // Specific year: that year's portion of children
                    $currentYear = (int) date('Y');
                    $query->where(function ($q) use ($year, $currentYear) {
                        $q->where(function ($q2) use ($year) {
                            $q2->whereIn('status', PbgTaskStatus::getWaitingClickDpmptsp())
                                ->whereYear('start_date', $year);
                        })
                        ->orWhere(function ($q2) use ($year) {
                            $q2->whereIn('status', PbgTaskStatus::getProcessInTechnicalOffice())
                                ->whereYear('start_date', $year);
                        });

                        if ($year == $currentYear - 1) {
                            $q->orWhere(function ($q2) use ($year) {
                                $q2->where('status', PbgTaskStatus::SK_PBG_TERBIT->value)
                                    ->whereYear('start_date', $year)
                                    ->whereNotNull('document_number')
                                    ->whereRaw("CAST(RIGHT(REGEXP_SUBSTR(document_number, '[0-9]{8}'), 4) AS UNSIGNED) = ?", [$year + 1]);
                            });
                        } elseif ($year == $currentYear) {
                            $q->orWhere(function ($q2) use ($year) {
                                $q2->whereIn('status', [PbgTaskStatus::PENERBITAN_SK_PBG->value, PbgTaskStatus::SK_PBG_TERBIT->value])
                                    ->whereYear('start_date', $year);
                            });
                        }
                    });
                }
                break;

            // ── Potensi ───────────────────────────────────────────────────────
            case 'potention':
                // Date range already applied above (simpleSpanFilters).
                // Add status union: verified children + non-verified
                if ($isSemua) {
                    $query->where(function ($q) use ($year) {
                        $q->where(function ($q2) use ($year) {
                            $q2->whereIn('status', PbgTaskStatus::getWaitingClickDpmptsp())
                                ->whereBetween('start_date', [($year - 1) . '-01-01', $year . '-12-31']);
                        })
                        ->orWhere(function ($q2) use ($year) {
                            $q2->whereIn('status', PbgTaskStatus::getProcessInTechnicalOffice())
                                ->whereBetween('start_date', [($year - 1) . '-01-01', $year . '-12-31']);
                        })
                        ->orWhere(function ($q2) use ($year) {
                            $q2->where('status', PbgTaskStatus::SK_PBG_TERBIT->value)
                                ->whereBetween('start_date', [($year - 1) . '-01-01', ($year - 1) . '-12-31'])
                                ->whereNotNull('document_number')
                                ->whereRaw("CAST(RIGHT(REGEXP_SUBSTR(document_number, '[0-9]{8}'), 4) AS UNSIGNED) = ?", [$year]);
                        })
                        ->orWhere(function ($q2) use ($year) {
                            $q2->where('status', PbgTaskStatus::PENERBITAN_SK_PBG->value)
                                ->whereBetween('start_date', [$year . '-01-01', $year . '-12-31']);
                        })
                        ->orWhere(function ($q2) use ($year) {
                            $q2->where('status', PbgTaskStatus::SK_PBG_TERBIT->value)
                                ->whereBetween('start_date', [$year . '-01-01', $year . '-12-31']);
                        })
                        ->orWhere(function ($q2) use ($year) {
                            $q2->whereIn('status', PbgTaskStatus::getNonVerified())
                                ->whereYear('start_date', $year);
                        });
                    });
                } else {
                    $currentYear = (int) date('Y');
                    $query->where(function ($q) use ($year, $currentYear) {
                        $q->where(function ($q2) use ($year) {
                            $q2->whereIn('status', PbgTaskStatus::getWaitingClickDpmptsp())
                                ->whereYear('start_date', $year);
                        })
                        ->orWhere(function ($q2) use ($year) {
                            $q2->whereIn('status', PbgTaskStatus::getProcessInTechnicalOffice())
                                ->whereYear('start_date', $year);
                        });

                        if ($year == $currentYear - 1) {
                            $q->orWhere(function ($q2) use ($year) {
                                $q2->where('status', PbgTaskStatus::SK_PBG_TERBIT->value)
                                    ->whereYear('start_date', $year)
                                    ->whereNotNull('document_number')
                                    ->whereRaw("CAST(RIGHT(REGEXP_SUBSTR(document_number, '[0-9]{8}'), 4) AS UNSIGNED) = ?", [$year + 1]);
                            });
                        } elseif ($year == $currentYear) {
                            $q->orWhere(function ($q2) use ($year) {
                                $q2->whereIn('status', [PbgTaskStatus::PENERBITAN_SK_PBG->value, PbgTaskStatus::SK_PBG_TERBIT->value])
                                    ->whereYear('start_date', $year);
                            });
                        }

                        $q->orWhere(function ($q2) use ($year) {
                            $q2->whereIn('status', PbgTaskStatus::getNonVerified())
                                ->whereYear('start_date', $year);
                        });
                    });
                }
                break;

            // ── Realisasi Penerbitan PBG ──────────────────────────────────────
            case 'issuance-realization-pbg':
                if ($isSemua) {
                    $query->where(function ($q) use ($year) {
                        $q->where(function ($q2) use ($year) {
                            $q2->where('status', PbgTaskStatus::SK_PBG_TERBIT->value)
                                ->whereBetween('start_date', [($year - 1) . '-01-01', ($year - 1) . '-12-31'])
                                ->whereNotNull('document_number')
                                ->whereRaw("CAST(RIGHT(REGEXP_SUBSTR(document_number, '[0-9]{8}'), 4) AS UNSIGNED) = ?", [$year]);
                        })
                        ->orWhere(function ($q2) use ($year) {
                            $q2->where('status', PbgTaskStatus::PENERBITAN_SK_PBG->value)
                                ->whereBetween('start_date', [$year . '-01-01', $year . '-12-31']);
                        })
                        ->orWhere(function ($q2) use ($year) {
                            $q2->where('status', PbgTaskStatus::SK_PBG_TERBIT->value)
                                ->whereBetween('start_date', [$year . '-01-01', $year . '-12-31']);
                        });
                    });
                } else {
                    $currentYear = (int) date('Y');
                    if ($year == $currentYear - 1) {
                        $query->where('status', PbgTaskStatus::SK_PBG_TERBIT->value)
                            ->whereYear('start_date', $year)
                            ->whereNotNull('document_number')
                            ->whereRaw("CAST(RIGHT(REGEXP_SUBSTR(document_number, '[0-9]{8}'), 4) AS UNSIGNED) = ?", [$year + 1]);
                    } elseif ($year == $currentYear) {
                        $query->whereIn('status', [PbgTaskStatus::PENERBITAN_SK_PBG->value, PbgTaskStatus::SK_PBG_TERBIT->value])
                            ->whereYear('start_date', $year);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }
                break;

            // ── Menunggu Klik DPMPTSP ─────────────────────────────────────────
            case 'waiting-click-dpmptsp':
                // Date range already applied above (simpleSpanFilters).
                $query->whereIn('status', PbgTaskStatus::getWaitingClickDpmptsp());
                break;

            // ── Proses di Dinas Teknis ────────────────────────────────────────
            case 'process-in-technical-office':
                // Date range already applied above (simpleSpanFilters).
                $query->whereIn('status', PbgTaskStatus::getProcessInTechnicalOffice());
                break;

            // ── Non-usaha ─────────────────────────────────────────────────────
            case 'non-business':
                // Date range already applied above (currentYearFilters).
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                               ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                        })->orWhereNull('function_type');
                    })
                    ->whereIn('status', PbgTaskStatus::getNonVerified())
                    ->where(function ($q3) {
                        $q3->whereDoesntHave('pbg_task_detail', fn($q4) => $q4->where('unit', '>', 1))
                           ->orWhereDoesntHave('pbg_task_detail');
                    });
                });
                break;

            // ── Usaha ─────────────────────────────────────────────────────────
            case 'business':
                // Date range already applied above (currentYearFilters).
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%fungsi usaha%'])
                               ->orWhereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%sebagai tempat usaha%']);
                        })->orWhere(function ($q3) {
                            $q3->where(function ($q4) {
                                $q4->where(function ($q5) {
                                    $q5->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                                       ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                                })->orWhereNull('function_type');
                            })->whereHas('pbg_task_detail', fn($q4) => $q4->where('unit', '>', 1));
                        });
                    })
                    ->whereIn('status', PbgTaskStatus::getNonVerified());
                });
                break;

            // ── Non-usaha RAB ─────────────────────────────────────────────────
            case 'non-business-rab':
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                               ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                        })->orWhereNull('function_type');
                    })
                    ->whereIn('status', PbgTaskStatus::getNonVerified())
                    ->where(function ($q3) {
                        $q3->whereDoesntHave('pbg_task_detail', fn($q4) => $q4->where('unit', '>', 1))
                           ->orWhereDoesntHave('pbg_task_detail');
                    });
                })
                ->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('pbg_task_detail_data_lists')
                        ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                        ->where('data_type', 3)
                        ->where('status', '!=', 1);
                });
                break;

            // ── Non-usaha KRK ─────────────────────────────────────────────────
            case 'non-business-krk':
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                               ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                        })->orWhereNull('function_type');
                    })
                    ->whereIn('status', PbgTaskStatus::getNonVerified())
                    ->where(function ($q3) {
                        $q3->whereDoesntHave('pbg_task_detail', fn($q4) => $q4->where('unit', '>', 1))
                           ->orWhereDoesntHave('pbg_task_detail');
                    });
                })
                ->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('pbg_task_detail_data_lists')
                        ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                        ->where('data_type', 2)
                        ->where('status', '!=', 1);
                });
                break;

            // ── Usaha RAB ─────────────────────────────────────────────────────
            case 'business-rab':
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%fungsi usaha%'])
                               ->orWhereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%sebagai tempat usaha%']);
                        })->orWhere(function ($q3) {
                            $q3->where(function ($q4) {
                                $q4->where(function ($q5) {
                                    $q5->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                                       ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                                })->orWhereNull('function_type');
                            })->whereHas('pbg_task_detail', fn($q4) => $q4->where('unit', '>', 1));
                        });
                    })
                    ->whereIn('status', PbgTaskStatus::getNonVerified());
                })
                ->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('pbg_task_detail_data_lists')
                        ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                        ->where('data_type', 3)
                        ->where('status', '!=', 1);
                });
                break;

            // ── Usaha KRK ─────────────────────────────────────────────────────
            case 'business-krk':
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%fungsi usaha%'])
                               ->orWhereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%sebagai tempat usaha%']);
                        })->orWhere(function ($q3) {
                            $q3->where(function ($q4) {
                                $q4->where(function ($q5) {
                                    $q5->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                                       ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                                })->orWhereNull('function_type');
                            })->whereHas('pbg_task_detail', fn($q4) => $q4->where('unit', '>', 1));
                        });
                    })
                    ->whereIn('status', PbgTaskStatus::getNonVerified());
                })
                ->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('pbg_task_detail_data_lists')
                        ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                        ->where('data_type', 2)
                        ->where('status', '!=', 1);
                });
                break;

            // ── Usaha DLH ─────────────────────────────────────────────────────
            case 'business-dlh':
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%fungsi usaha%'])
                               ->orWhereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%sebagai tempat usaha%']);
                        })->orWhere(function ($q3) {
                            $q3->where(function ($q4) {
                                $q4->where(function ($q5) {
                                    $q5->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                                       ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                                })->orWhereNull('function_type');
                            })->whereHas('pbg_task_detail', fn($q4) => $q4->where('unit', '>', 1));
                        });
                    })
                    ->whereIn('status', PbgTaskStatus::getNonVerified());
                })
                ->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('pbg_task_detail_data_lists')
                        ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                        ->where('data_type', 5)
                        ->where('status', '!=', 1);
                });
                break;
        }
    }
}
