<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pimpinan</title>
    <style>
        body { font-size: 10px; } /* Reduce font size */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 3px; font-size: 9px;  border: 1px solid black;} /* Reduce padding */
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Laporan Pimpinan</h2>
    <table>
        <thead>
            <tr>
                <th>Jumlah Potensi</th>
                <th>Total Potensi</th>
                <th>Jumlah Berkas Belum Terverifikasi</th>
                <th>Total Berkas Belum Terverifikasi</th>
                <th>Jumlah Berkas Terverifikasi</th>
                <th>Total Berkas Terverifikasi</th>
                <th>Jumlah Usaha</th>
                <th>Total Usaha</th>
                <th>Jumlah Non Usaha</th>
                <th>Total Non Usaha</th>
                <th>Jumlah Tata Ruang</th>
                <th>Total Tata Ruang</th>
                <th>Jumlah Berproses di DPMPTSP</th>
                <th>Total Berproses di DPMPTSP</th>
                <th>Jumlah Realisasi SK PBG Terbit</th>
                <th>Total Realisasi SK PBG Terbit</th>
                <th>Jumlah Proses Dinas Teknis</th>
                <th>Total Proses Dinas Teknis</th>
                <th>Tahun</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $item)
            <tr>
                <td>{{ $item->potention_count }}</td>
                <td>{{ $item->potention_sum }}</td>
                <td>{{ $item->non_verified_count }}</td>
                <td>{{ $item->non_verified_sum }}</td>
                <td>{{ $item->verified_count }}</td>
                <td>{{ $item->verified_sum }}</td>
                <td>{{ $item->business_count }}</td>
                <td>{{ $item->business_sum }}</td>
                <td>{{ $item->non_business_count }}</td>
                <td>{{ $item->non_business_sum }}</td>
                <td>{{ $item->spatial_count }}</td>
                <td>{{ $item->spatial_sum }}</td>
                <td>{{ $item->waiting_click_dpmptsp_count }}</td>
                <td>{{ $item->waiting_click_dpmptsp_sum }}</td>
                <td>{{ $item->issuance_realization_pbg_count }}</td>
                <td>{{ $item->issuance_realization_pbg_sum }}</td>
                <td>{{ $item->process_in_technical_office_count }}</td>
                <td>{{ $item->process_in_technical_office_sum }}</td>
                <td>{{ $item->year }}</td>
                <td>{{ $item->created_at->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
