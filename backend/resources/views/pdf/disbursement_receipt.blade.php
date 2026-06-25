<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Penyaluran - SABANA</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 13px;
            color: #2D3748;
            line-height: 1.6;
            padding: 45px 50px;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 35px;
            padding-bottom: 25px;
            border-bottom: 3px solid #1B4332;
        }
        
        .header .logo {
            font-size: 26px;
            font-weight: 800;
            color: #1B4332;
            letter-spacing: 3px;
            margin-bottom: 5px;
        }
        
        .header .brand {
            font-size: 12px;
            color: #718096;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }
        
        .header .title {
            font-size: 18px;
            font-weight: 700;
            color: #1B4332;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
        }
        
        .header .ref-number {
            display: inline-block;
            background: #F0FFF4;
            border: 1px solid #C6F6D5;
            padding: 8px 25px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 700;
            color: #1B4332;
            letter-spacing: 0.5px;
        }

        /* Section */
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 11px;
            font-weight: 700;
            color: #1B4332;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1.5px solid #E2E8F0;
        }

        /* Table */
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-table tr {
            border-bottom: 1px solid #F7FAFC;
        }
        
        .info-table td {
            padding: 9px 12px;
            vertical-align: top;
        }
        
        .info-table td.label {
            width: 35%;
            font-weight: 600;
            color: #718096;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-table td.value {
            color: #2D3748;
            font-weight: 500;
        }
        
        .info-table td.value.highlight {
            font-size: 16px;
            font-weight: 700;
            color: #1B4332;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 40px;
            left: 50px;
            right: 50px;
            text-align: center;
            padding-top: 20px;
            border-top: 1.5px solid #E2E8F0;
            font-size: 10px;
            color: #A0AEC0;
        }

        /* Watermark */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 100px;
            color: rgba(27, 67, 50, 0.02);
            font-weight: 900;
            pointer-events: none;
            white-space: nowrap;
            letter-spacing: 20px;
        }
    </style>
</head>
<body>
    
    {{-- Watermark --}}
    <div class="watermark">SABANA</div>

    {{-- Header --}}
    <div class="header">
        <div class="logo">SABANA</div>
        <div class="brand">Sarana Bantuan Anak Banua — Provinsi Kalimantan Selatan</div>
        <div class="title">Bukti Penyaluran Bantuan Sosial</div>
        <div class="ref-number">No. Referensi: {{ $data['reference_number'] }}</div>
    </div>

    {{-- Data Penerima --}}
    <div class="section">
        <div class="section-title">A. Data Penerima Bantuan</div>
        <table class="info-table">
            <tr>
                <td class="label">Nama Lengkap</td>
                <td class="value">{{ $data['citizen_name'] }}</td>
            </tr>
            <tr>
                <td class="label">Nomor Induk Kependudukan (NIK)</td>
                <td class="value">{{ $data['citizen_nik'] }}</td>
            </tr>
            <tr>
                <td class="label">Nomor Kartu Keluarga (KK)</td>
                <td class="value">{{ $data['family_card_number'] }}</td>
            </tr>
        </table>
    </div>

    {{-- Data Bantuan --}}
    <div class="section">
        <div class="section-title">B. Data Bantuan</div>
        <table class="info-table">
            <tr>
                <td class="label">Program Bantuan</td>
                <td class="value">{{ $data['program_name'] }}</td>
            </tr>
            <tr>
                <td class="label">Nomor Registrasi</td>
                <td class="value">{{ $data['registration_number'] }}</td>
            </tr>
            <tr>
                <td class="label">Jumlah Bantuan</td>
                <td class="value highlight">Rp {{ number_format($data['amount'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Metode Penyaluran</td>
                <td class="value">{{ $data['method'] }}</td>
            </tr>
        </table>
    </div>

    {{-- Data Penyaluran --}}
    <div class="section">
        <div class="section-title">C. Data Penyaluran</div>
        <table class="info-table">
            <tr>
                <td class="label">Tanggal Penyaluran</td>
                <td class="value">{{ $data['disbursed_at'] }}</td>
            </tr>
            <tr>
                <td class="label">Petugas Penyalur</td>
                <td class="value">{{ $data['officer_name'] }}</td>
            </tr>
        </table>
    </div>

    {{-- Footer --}}
    <div class="footer">
        Dicetak pada: {{ $printed_at }} &nbsp;|&nbsp; 
        Dokumen ini diterbitkan secara elektronik oleh sistem SABANA dan sah tanpa tanda tangan basah.
    </div>

</body>
</html>