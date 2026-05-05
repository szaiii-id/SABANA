<?php

namespace Database\Seeders;

use App\Models\AssistanceProgram;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AssistanceProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            [
                'name' => 'Bantuan Perbaikan Rumah (Bedah Rumah)',
                'slug' => 'bedah-rumah',
                'description' => 'Bantuan stimulan untuk perbaikan kualitas rumah tidak layak huni bagi masyarakat berpenghasilan rendah.',
                'is_active' => true,
                'criteria' => [
                    'badge' => 'Rehabilitasi Fisik',
                    'iconSvg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-4.5 4.5 4.5M6.75 21V9" /></svg>',
                    'inputs' => [
                        ['key' => 'luas_bangunan', 'label' => 'Luas Bangunan (m2)', 'type' => 'number'],
                        ['key' => 'status_tanah', 'label' => 'Status Kepemilikan Tanah', 'type' => 'text'],
                        ['key' => 'jumlah_penghuni', 'label' => 'Jumlah Orang Tinggal di Rumah', 'type' => 'number'],
                    ],
                    'files' => [
                        ['key' => 'foto_rumah_rusak', 'label' => 'Foto Bagian Rumah yang Rusak'],
                        ['key' => 'foto_surat_tanah', 'label' => 'Foto Sertifikat / Surat Tanah'],
                        ['key' => 'foto_denah_lokasi', 'label' => 'Foto Denah Lokasi Rumah'],
                    ]
                ]
            ],
            [
                'name' => 'Beasiswa SABANA Pintar',
                'slug' => 'beasiswa-pintar',
                'description' => 'Pemberian bantuan biaya pendidikan untuk mahasiswa berprestasi dari keluarga kurang mampu.',
                'is_active' => true,
                'criteria' => [
                    'badge' => 'Pendidikan Tinggi',
                    'iconSvg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.174L10.72 14.13a1.5 1.5 0 001.56 0l6.46-3.956m-13.75 0a1.5 1.5 0 01-.461-2.07l3.75-6.25a1.5 1.5 0 012.07-.461l10 6.25a1.5 1.5 0 01.461 2.07l-3.75 6.25a1.5 1.5 0 01-2.07.461l-10-6.25z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 14v7m-3-3h6" /></svg>',
                    'inputs' => [
                        ['key' => 'nama_kampus', 'label' => 'Nama Perguruan Tinggi', 'type' => 'text'],
                        ['key' => 'ipk_terakhir', 'label' => 'IPK Terakhir (Contoh: 3.50)', 'type' => 'text'],
                        ['key' => 'semester_saat_ini', 'label' => 'Semester Saat Ini', 'type' => 'number'],
                    ],
                    'files' => [
                        ['key' => 'khs_terakhir', 'label' => 'Foto/Scan KHS Terakhir'],
                        ['key' => 'surat_aktif_kuliah', 'label' => 'Surat Keterangan Aktif Kuliah'],
                        ['key' => 'sertifikat_prestasi', 'label' => 'Foto Sertifikat Prestasi (Opsional)'],
                    ]
                ]
            ],
            [
                'name' => 'Insentif Pelaku Keagamaan (Marbot/Guru Ngaji)',
                'slug' => 'insentif-keagamaan',
                'description' => 'Program apresiasi berupa bantuan tunai bagi guru ngaji dan pengelola tempat ibadah di wilayah Tabalong.',
                'is_active' => true,
                'criteria' => [
                    'badge' => 'Kesejahteraan Sosial',
                    'iconSvg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" /></svg>',
                    'inputs' => [
                        ['key' => 'nama_tempat_ibadah', 'label' => 'Nama Masjid / Musholla / TPA', 'type' => 'text'],
                        ['key' => 'lama_mengabdi', 'label' => 'Lama Mengabdi (Tahun)', 'type' => 'number'],
                        ['key' => 'jabatan_saat_ini', 'label' => 'Jabatan (Contoh: Marbot / Guru Ngaji)', 'type' => 'text'],
                    ],
                    'files' => [
                        ['key' => 'surat_rekomendasi_desa', 'label' => 'Surat Rekomendasi Kepala Desa'],
                        ['key' => 'foto_kegiatan', 'label' => 'Foto Dokumentasi Saat Bertugas'],
                        ['key' => 'sertifikat_pelatihan', 'label' => 'Sertifikat Kompetensi (Jika Ada)'],
                    ]
                ]
            ],
        ];

        foreach ($programs as $program) {
            AssistanceProgram::updateOrCreate(
                ['slug' => $program['slug']],
                $program
            );
        }
    }
}