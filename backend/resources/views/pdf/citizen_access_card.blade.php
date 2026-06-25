<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { 
            margin: 0; 
            size: 105mm 74mm landscape; 
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Helvetica', sans-serif; 
            width: 105mm; 
            height: 74mm; 
            overflow: hidden;
        }
        .card { 
            width: 105mm; 
            height: 74mm; 
            background: linear-gradient(135deg, #f8fafc 0%, #f0fdf4 100%);
            display: flex;
            flex-direction: column;
        }
        .header { 
            background: #1B4332;
            color: white;
            text-align: center; 
            padding: 3mm 2mm; 
        }
        .logo { font-size: 12px; font-weight: bold; letter-spacing: 3px; }
        .sub { font-size: 5px; color: #a7c4b5; }
        .title { 
            font-size: 7px; 
            font-weight: bold; 
            text-transform: uppercase; 
            background: #2D6A4F; 
            display: inline-block; 
            padding: 0.5mm 2mm; 
            border-radius: 2px; 
            margin-top: 1mm;
        }
        .body { 
            flex: 1; 
            padding: 2mm 3mm; 
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 2mm;
        }
        .info-grid {
            background: white;
            border-radius: 3px;
            padding: 2mm;
            border: 1px solid #e2e8f0;
        }
        .row { 
            display: flex; 
            padding: 1mm 0;
            border-bottom: 1px dotted #e2e8f0;
        }
        .row:last-child { border-bottom: none; }
        .label { 
            font-size: 5px; 
            color: #64748b; 
            width: 22%; 
            text-transform: uppercase; 
            font-weight: bold;
        }
        .value { 
            font-size: 7px; 
            font-weight: bold; 
            width: 78%; 
            color: #1B4332; 
            text-align: right;
        }
        .pin-box { 
            text-align: center; 
            padding: 2mm; 
            background: #1B4332;
            border-radius: 3px; 
            color: white;
        }
        .pin-label { 
            font-size: 5px; 
            text-transform: uppercase; 
            letter-spacing: 2px;
            color: #a7c4b5;
        }
        .pin { 
            font-size: 20px; 
            font-weight: bold; 
            letter-spacing: 6px; 
        }
        .instruction { 
            font-size: 5px; 
            color: #a7c4b5;
            margin-top: 0.5mm;
        }
        .footer { 
            font-size: 5px; 
            color: #94a3b8; 
            text-align: center; 
            padding: 1.5mm;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div class="logo">S A B A N A</div>
            <div class="sub">Sarana Bantuan Anak Banua</div>
            <div class="title">Kartu Akses</div>
        </div>
        
        <div class="body">
            <div class="info-grid">
                <div class="row">
                    <span class="label">Nama</span>
                    <span class="value">{{ $citizen->full_name }}</span>
                </div>
                <div class="row">
                    <span class="label">NIK</span>
                    <span class="value">{{ $citizen->nik }}</span>
                </div>
                <div class="row">
                    <span class="label">KK</span>
                    <span class="value">{{ $citizen->family_card_number }}</span>
                </div>
            </div>
            
            <div class="pin-box">
                <div class="pin-label">PIN AKSES</div>
                <div class="pin">{{ $accessPin }}</div>
                <div class="instruction">Login &amp; ganti PIN baru Anda</div>
            </div>
        </div>
        
        <div class="footer">
            {{ $date }} WITA | {{ $adminName }}
        </div>
    </div>
</body>
</html>