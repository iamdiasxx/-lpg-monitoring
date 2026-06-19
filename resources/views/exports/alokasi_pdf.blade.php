<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 18px; font-weight: bold; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #2563eb; color: white; padding: 10px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        .footer { margin-top: 50px; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">PT. ENERGI TERBARUKAN INDONESIA</div>
        <p>Laporan Resmi Distribusi LPG 3KG</p>
    </div>

    <p><strong>ID Distribusi:</strong> #AL-{{ $header->id }}</p>
    <p><strong>Tanggal:</strong> {{ $header->tanggal }}</p>
    <p><strong>Sumber SPBE:</strong> {{ $header->nama_spbe }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Pangkalan</th>
                <th>Alamat</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $index => $d)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $d->nama_pangkalan }}</td>
                <td>{{ $d->alamat }}</td>
                <td>{{ $d->jumlah_alokasi }} Tabung</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak pada: {{ date('d/m/Y H:i') }}</p>
        <br><br><br>
        <p><strong>( Manager Logistik )</strong></p>
    </div>
</body>
</html>