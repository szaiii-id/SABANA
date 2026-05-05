<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* Pengaturan Kertas agar tidak terpotong */
        @page { margin: 0cm 0cm; }
        
        body { 
            font-family: 'Helvetica', sans-serif; 
            color: #1e293b; 
            line-height: 1.5; 
            margin: 0; 
            padding: 40px; /* Ruang aman agar tidak mepet pinggir kertas */
            background-color: #ffffff;
        }

        .container { 
            width: 100%; 
        }

        .header { 
            text-align: center; 
            border-bottom: 3px solid #2D6A4F; 
            padding-bottom: 20px; 
            margin-bottom: 30px; 
        }

        .logo-main { color: #2D6A4F; font-size: 32px; font-weight: bold; letter-spacing: 3px; margin-bottom: 5px; }
        .sub-logo { font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase; letter-spacing: 4px; }
        .doc-title { margin-top: 20px; font-size: 18px; font-weight: bold; color: #1B4332; text-transform: uppercase; }
        
        /* Pencegah elemen terpotong di tengah baris */
        .section-container {
            page-break-inside: avoid;
            margin-bottom: 25px;
        }

        .section-title { 
            background: #f8fafc; 
            padding: 10px 15px; 
            font-weight: bold; 
            color: #2D6A4F; 
            font-size: 13px; 
            border-left: 6px solid #2D6A4F; 
            margin-bottom: 12px;
            text-transform: uppercase;
        }
        
        .table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; font-size: 12px; vertical-align: top; }
        .label { width: 35%; color: #64748b; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        .value { width: 65%; color: #0f172a; font-weight: bold; }
        
        .status-box { 
            background: #dcfce7; 
            color: #166534; 
            padding: 5px 12px; 
            border-radius: 6px; 
            font-size: 10px; 
            font-weight: bold; 
            display: inline-block; 
            border: 1px solid #bbf7d0;
        }
        
        /* Footer dipaksa di paling bawah jika memungkinkan */
        .footer { 
            margin-top: 50px; 
            border-top: 1px solid #e2e8f0; 
            padding-top: 15px; 
            font-size: 10px; 
            color: #94a3b8; 
            text-align: center;
            position: relative;
        }

        .attachment-list { list-style: none; padding: 0; margin: 0; }
        .attachment-item { 
            font-size: 11px; 
            color: #475569; 
            padding: 5px 15px; 
            background: #fdfdfd; 
            border: 1px solid #f1f5f9;
            margin-bottom: 5px;
            border-radius: 4px;
        }

        /* Utility agar tabel tidak terputus di tengah */
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-main">SABANA</div>
            <div class="sub-logo">Sarana Bantuan Anak Banua</div>
            <div class="doc-title">Surat Bukti Registrasi Pengajuan</div>
            <div style="font-family: monospace; font-size: 14px; margin-top: 8px; color: #2D6A4F; font-weight: bold;">
                No: {{ $submission->registration_number }}
            </div>
        </div>

        <div class="section-container">
            <div class="section-title">I. Data Personal Pendaftar</div>
            <table class="table">
                <tr>
                    <td class="label">Nama Lengkap</td>
                    <td class="value">{{ $submission->citizen->full_name ?? 'Data Tidak Ditemukan' }}</td>
                </tr>
                <tr>
                    <td class="label">NIK</td>
                    <td class="value">{{ $submission->citizen->nik ?? 'Data Tidak Ditemukan' }}</td>
                </tr>
                <tr>
                    <td class="label">Program Bantuan</td>
                    <td class="value">{{ $submission->program->name }}</td>
                </tr>
                <tr>
                    <td class="label">Status Saat Ini</td>
                    <td class="value"><span class="status-box">{{ strtoupper($submission->status) }}</span></td>
                </tr>
            </table>
        </div>

        <div class="section-container">
            <div class="section-title">II. Rincian Informasi Data</div>
            <table class="table">
                @foreach($submission->submission_data as $key => $value)
                    @if(!in_array($key, ['_method', 'id', 'catatan_pencairan']))
                    <tr>
                        <td class="label">{{ str_replace('_', ' ', $key) }}</td>
                        <td class="value">{{ $value }}</td>
                    </tr>
                    @endif
                @endforeach
                <tr>
                    <td class="label">Penyaluran Bantuan</td>
                    <td class="value">{{ $submission->disbursement_method === 'village_cash' ? 'Tunai (Melalui Balai Desa / Kantor Kelurahan)' : 'Transfer (Bank BPD Kalimantan Selatan)' }}</td>
                </tr>
            </table>
        </div>

        <div class="section-container">
            <div class="section-title">III. Wilayah Domisili / Lokasi</div>
            <table class="table">
                <tr>
                    <td class="label">Provinsi</td>
                    <td class="value">{{ $submission->regency->province->name ?? 'KALIMANTAN SELATAN' }}</td>
                </tr>
                <tr>
                    <td class="label">Kabupaten / Kota</td>
                    <td class="value">{{ $submission->regency->name ?? '---' }}</td>
                </tr>
                <tr>
                    <td class="label">Kecamatan</td>
                    <td class="value">{{ $submission->district->name ?? '---' }}</td>
                </tr>
                <tr>
                    <td class="label">Desa / Kelurahan</td>
                    <td class="value">{{ $submission->village->name ?? '---' }}</td>
                </tr>
            </table>
        </div>

        <div class="section-container">
            <div class="section-title">IV. Daftar Lampiran Berkas Digital</div>
            <div style="padding: 0 10px;">
                <ul class="attachment-list">
                    @foreach($submission->evidences as $evidence)
                        <li class="attachment-item">{{ ucwords(str_replace('_', ' ', $evidence->image_type)) }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="footer">
            <p>Dokumen ini diterbitkan secara digital oleh sistem SABANA pada {{ $date }} WITA</p>
            <p>Pastikan data yang Anda berikan benar. Pemalsuan dokumen negara dapat ditindak secara hukum.</p>
        </div>
    </div>
</body>
</html>