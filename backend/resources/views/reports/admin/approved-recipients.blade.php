<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $judul }}</title>
    <style>
        @page { margin: 1.5cm; size: landscape; }
        body { font-family: 'Helvetica', sans-serif; color: #1e293b; line-height: 1.5; margin: 0; padding: 0; background: #fff; }
        .container { width: 100%; }
        .header { text-align: center; border-bottom: 3px solid #2D6A4F; padding-bottom: 15px; margin-bottom: 20px; }
        .logo-main { color: #2D6A4F; font-size: 28px; font-weight: bold; letter-spacing: 3px; }
        .sub-logo { font-size: 10px; color: #64748b; font-weight: bold; text-transform: uppercase; letter-spacing: 4px; }
        .doc-title { margin-top: 12px; font-size: 16px; font-weight: bold; color: #1B4332; text-transform: uppercase; }
        .region-info { font-size: 11px; color: #2D6A4F; font-weight: 500; margin-top: 4px; }
        .filter-info { font-size: 10px; color: #64748b; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #2D6A4F; color: #fff; padding: 8px 6px; text-align: left; font-size: 10px; text-transform: uppercase; }
        td { padding: 6px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge-approved { background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .total-row { font-weight: bold; background: #f0fdf4; }
        .footer { margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 15px; font-size: 10px; color: #64748b; }
        .footer .signature { float: right; text-align: center; width: 220px; }
        .footer .signature .name { margin-top: 60px; font-weight: bold; color: #1e293b; font-size: 11px; }
        .footer .info { float: left; }
        .clearfix { clear: both; }
    </style>
</head>
<body>
    @php
        $roleLabels = [
            'super_admin'     => 'Administrator Provinsi',
            'regency_admin'   => 'Administrator Kabupaten',
            'district_admin'  => 'Administrator Kecamatan',
            'village_officer' => 'Petugas Desa',
        ];
        $roleDisplay = $roleLabels[$admin->role] ?? $admin->role;

        $regionParts = [];
        if ($admin->role === 'super_admin') {
            $regionParts[] = 'Provinsi Kalimantan Selatan';
        } else {
            if ($admin->regency?->name) $regionParts[] = $admin->regency->name;
            if ($admin->district?->name) $regionParts[] = $admin->district->name;
            if ($admin->village?->name) $regionParts[] = $admin->village->name;
        }
        $regionDisplay = implode(', ', $regionParts);
    @endphp

    <div class="container">
        <div class="header">
            <div class="logo-main">SABANA</div>
            <div class="sub-logo">Sarana Bantuan Anak Banua</div>
            <div class="doc-title">{{ $judul }}</div>
            @if($regionDisplay)
            <div class="region-info">{{ $regionDisplay }}</div>
            @endif
            @if(!empty($filters['tgl_mulai']) || !empty($filters['tgl_akhir']))
            <div class="filter-info">Periode Keputusan: {{ $filters['tgl_mulai'] ?? '...' }} s/d {{ $filters['tgl_akhir'] ?? '...' }}</div>
            @endif
        </div>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>NIK</th>
                    <th>Nama</th>
                    <th>Desa</th>
                    <th>Program</th>
                    <th class="text-right">Jumlah (Rp)</th>
                    <th>Tgl Keputusan</th>
                    <th>Pemutus</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @php $total = 0; @endphp
                @foreach($data as $i => $row)
                @php $total += $row['amount']; @endphp
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $row['nik'] }}</td>
                    <td>{{ $row['full_name'] }}</td>
                    <td>{{ $row['desa'] ?? '—' }}</td>
                    <td>{{ $row['program'] }}</td>
                    <td class="text-right">{{ number_format($row['amount'], 0, ',', '.') }}</td>
                    <td>{{ $row['tgl_keputusan'] }}</td>
                    <td>{{ $row['pemutus'] ?? '—' }}</td>
                    <td><span class="badge-approved">Tetap Menerima</span></td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="5" class="text-right"><strong>TOTAL: {{ count($data) }} orang</strong></td>
                    <td class="text-right"><strong>{{ number_format($total, 0, ',', '.') }}</strong></td>
                    <td colspan="3"></td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <div class="info">
                <p>Dicetak oleh : {{ $admin->name }}</p>
                <p>Jabatan &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $roleDisplay }}</p>
                <p>Tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ now()->format('d F Y, H:i') }} WITA</p>
                <p style="margin-top:10px;font-style:italic;">Dokumen ini diterbitkan secara digital oleh sistem SABANA.</p>
            </div>
            <div class="signature">
                <p>Penanggung Jawab,</p>
                <p class="name">{{ $admin->name }}</p>
                <p style="font-size:10px;color:#64748b;">{{ $roleDisplay }}</p>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont('Helvetica', 'normal');
            $size = 9;
            $text = "Halaman {PAGE_NUM} dari {PAGE_COUNT}";
            $x = (($pdf->get_width() - $fontMetrics->getTextWidth($text, $font, $size)) / 2) + 55;
            $y = $pdf->get_height() - 45;
            $pdf->page_text($x, $y, $text, $font, $size, [0.369, 0.369, 0.369]);
        }
    </script>
</body>
</html>