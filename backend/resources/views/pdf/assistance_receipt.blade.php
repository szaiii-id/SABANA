<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0cm 0cm; }
        body { font-family: 'Helvetica', sans-serif; color: #1e293b; line-height: 1.5; margin: 0; padding: 40px; background: #fff; }
        .container { width: 100%; }
        .header { text-align: center; border-bottom: 3px solid #2D6A4F; padding-bottom: 20px; margin-bottom: 30px; }
        .logo-main { color: #2D6A4F; font-size: 32px; font-weight: bold; letter-spacing: 3px; }
        .sub-logo { font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase; letter-spacing: 4px; }
        .doc-title { margin-top: 20px; font-size: 18px; font-weight: bold; color: #1B4332; text-transform: uppercase; }
        .section-container { page-break-inside: avoid; margin-bottom: 25px; }
        .section-title { background: #f8fafc; padding: 10px 15px; font-weight: bold; color: #2D6A4F; font-size: 13px; border-left: 6px solid #2D6A4F; margin-bottom: 12px; text-transform: uppercase; }
        .table { width: 100%; border-collapse: collapse; }
        .table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; font-size: 12px; vertical-align: top; }
        .label { width: 35%; color: #64748b; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        .value { width: 65%; color: #0f172a; font-weight: bold; }
        .status-box { padding: 5px 12px; border-radius: 6px; font-size: 10px; font-weight: bold; display: inline-block; }
        .status-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .status-validated { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status-rejected { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .status-completed { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status-needs_revision { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .footer { margin-top: 50px; border-top: 1px solid #e2e8f0; padding-top: 15px; font-size: 10px; color: #94a3b8; text-align: center; }
        .attachment-list { list-style: none; padding: 0; margin: 0; }
        .attachment-item { font-size: 11px; color: #475569; padding: 5px 15px; background: #fdfdfd; border: 1px solid #f1f5f9; margin-bottom: 5px; border-radius: 4px; }
        .revision-chip { display: inline-block; background: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 20px; font-size: 9px; font-weight: bold; margin: 2px 4px 2px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-main">SABANA</div>
            <div class="sub-logo">Sarana Bantuan Anak Banua</div>
            <div class="doc-title">SURAT BUKTI REGISTRASI PENGAJUAN</div>
            <div style="font-family: monospace; font-size: 14px; margin-top: 8px; color: #2D6A4F; font-weight: bold;">
                No: {{ $submission->registration_number }}
            </div>
            <div style="font-size: 10px; color: #64748b; margin-top: 5px;">
                Dicetak: {{ $date }}
            </div>
        </div>

        <div class="section-container">
            <div class="section-title">I. Data Personal Pendaftar</div>
            <table class="table">
                <tr>
                    <td class="label">Nama Lengkap</td>
                    <td class="value">{{ $submission->citizen?->full_name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">NIK</td>
                    <td class="value">{{ $submission->citizen?->nik ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Program Bantuan</td>
                    <td class="value">{{ $submission->program?->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Status Saat Ini</td>
                    <td class="value">
                        <span class="status-box status-{{ $submission->status }}">
                            {{ ucfirst($submission->status) }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section-container">
            <div class="section-title">II. Rincian Informasi Data</div>
            <table class="table">
                @php
                    $hiddenKeys = ['_method', 'id', 'catatan_pencairan', '_idempotency_key', 'nik', 'full_name', 'family_card_number', 'anomalies'];
                @endphp

                @foreach($submission->submission_data ?? [] as $key => $value)
                    @if(!in_array($key, $hiddenKeys) && !is_null($value) && $value !== '' && !is_array($value))
                    <tr>
                        <td class="label">{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                        <td class="value">
                            @if(is_numeric($value) && (Str::contains($key, 'penghasilan') || Str::contains($key, 'pendapatan') || Str::contains($key, 'gaji')))
                                Rp {{ number_format(floatval($value), 0, ',', '.') }}
                            @elseif(is_numeric($value))
                                {{ number_format($value, 0, ',', '.') }}
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    </tr>
                    @endif
                @endforeach

                @if($submission->verified_by_name)
                <tr>
                    <td class="label">Diverifikasi Oleh</td>
                    <td class="value">{{ $submission->verified_by_name }}</td>
                </tr>
                @endif

                @if($submission->verified_at)
                <tr>
                    <td class="label">Diverifikasi Pada</td>
                    <td class="value">{{ \Carbon\Carbon::parse($submission->verified_at)->translatedFormat('d F Y H:i') }} WITA</td>
                </tr>
                @endif

                @if($submission->revision_by_name)
                <tr>
                    <td class="label">Revisi Diminta Oleh</td>
                    <td class="value">{{ $submission->revision_by_name }}</td>
                </tr>
                @endif

                @if($submission->revision_at)
                <tr>
                    <td class="label">Revisi Diminta Pada</td>
                    <td class="value">{{ \Carbon\Carbon::parse($submission->revision_at)->translatedFormat('d F Y H:i') }} WITA</td>
                </tr>
                @endif

                @if(!empty($submission->revision_items))
                <tr>
                    <td class="label">Yang Perlu Diperbaiki</td>
                    <td class="value">
                        @foreach($submission->revision_items as $item)
                            <span class="revision-chip">{{ ucwords(str_replace('_', ' ', $item)) }}</span>
                        @endforeach
                    </td>
                </tr>
                @endif

                @php
                    $latestVerification = $submission->verifications()
                        ->whereIn('action_type', ['rejected', 'revision_requested'])
                        ->latest()
                        ->first();
                @endphp

                @if($latestVerification?->notes)
                <tr>
                    <td class="label">Catatan Admin</td>
                    <td class="value" style="color:#991b1b;">"{{ $latestVerification->notes }}"</td>
                </tr>
                @endif

                <tr>
                    <td class="label">Penyaluran Bantuan</td>
                    <td class="value">
                        {{ $submission->disbursement_method === 'village_cash' ? 'Tunai (Balai Desa)' : 'Transfer Bank (BPD Kalimantan Selatan)' }}
                    </td>
                </tr>

                @if($submission->disbursement_method === 'bpd_transfer' && $submission->bank_account_number)
                <tr>
                    <td class="label">Nomor Rekening</td>
                    <td class="value">{{ $submission->bank_account_number }}</td>
                </tr>
                @endif
            </table>
        </div>

        <div class="section-container">
            <div class="section-title">III. Wilayah Domisili / Lokasi</div>
            <table class="table">
                <tr>
                    <td class="label">Provinsi</td>
                    <td class="value">{{ $submission->regency?->province?->name ?? 'Kalimantan Selatan' }}</td>
                </tr>
                <tr>
                    <td class="label">Kabupaten / Kota</td>
                    <td class="value">{{ $submission->regency?->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Kecamatan</td>
                    <td class="value">{{ $submission->district?->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Desa / Kelurahan</td>
                    <td class="value">{{ $submission->village?->name ?? '—' }}</td>
                </tr>
            </table>
        </div>

        <div class="section-container">
            <div class="section-title">IV. Daftar Lampiran Berkas Digital</div>
            <ul class="attachment-list">
                @forelse($submission->evidences ?? [] as $evidence)
                    <li class="attachment-item">{{ strtoupper(str_replace('_', ' ', $evidence->image_type)) }}</li>
                @empty
                    <li class="attachment-item">TIDAK ADA LAMPIRAN</li>
                @endforelse
            </ul>
        </div>

        <div class="footer">
            <p>Dokumen ini diterbitkan secara digital oleh sistem SABANA pada {{ $date }} WITA</p>
            <p>Pastikan data yang Anda berikan benar. Pemalsuan dokumen negara dapat ditindak secara hukum.</p>
            <p>Nomor Registrasi: {{ $submission->registration_number }}</p>
        </div>
    </div>
</body>
</html>