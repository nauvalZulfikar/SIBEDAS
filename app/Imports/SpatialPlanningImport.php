<?php

namespace App\Imports;

use App\Models\SpatialPlanning;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;
use DateTime;
use Carbon\Carbon;

class SpatialPlanningImport implements ToCollection
{
    protected static $processed = false;

    /**
     * Process each row in the file
     */
    public function collection(Collection $rows)
    {
        if (self::$processed) {
            return;
        }
        self::$processed = true;

        if ($rows->isEmpty())
        {
            return;
        }

        //cari header secara otomatis
        $header = $rows->first();
        $headerIndex = collect($header)->search(fn($value) => !empty($value));

        // Pastikan header ditemukan
        if ($headerIndex === false) {
            return;
        }

        foreach ($rows->skip(1) as $row) {
            $dateValue = trim($row[7]);
            info($dateValue);
            $parsedDate = Carbon::createFromFormat('Y-m-d', $dateValue)->format('Y-m-d');
            info($parsedDate); 

            $dataToInsert[] = [
                'name'=>$row[1],
                'kbli'=>$row[2],
                'activities'=>$row[3],
                'area'=>$row[4],
                'location'=>$row[5],
                'number'=>$row[6],
                'date'=>$parsedDate,
            ];
        }

        if(!empty($dataToInsert)) {
            SpatialPlanning::insert($dataToInsert);
        } else {
            return;
        }
    }
}
