<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pariwisata</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid black; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Laporan Pariwisata</h2>
    <table>
        <thead>
            <tr>
                <th>Jenis Bisnis Pariwisata</th>
                <th>Jumlah Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $item)
            <tr>
                <td>{{ $item->kbli_title }}</td>
                <td>{{ $item->total_records }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
