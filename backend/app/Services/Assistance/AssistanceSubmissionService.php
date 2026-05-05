<?php

namespace App\Services\Assistance;

use App\Repositories\Contracts\AssistanceRepositoryInterface;
use App\Contracts\Storage\FileStorageInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Exception;

class AssistanceSubmissionService
{
    public function __construct(
        private AssistanceRepositoryInterface $repository,
        private FileStorageInterface $storage
    ) {}

    /**
     * Memproses pendaftaran bantuan dari warga
     */
    public function submit(object $citizen, array $payload, array $files)
    {
        // Array sementara untuk melacak public_id Cloudinary yang sukses diunggah
        $uploadedPublicIds = [];

        try {
            return DB::transaction(function () use ($citizen, $payload, $files, &$uploadedPublicIds) {
                
                // 1. Identifikasi Key Statis (Kolom Tabel Utama)
                $staticKeys = [
                    'program_id', 
                    'regency_id', 
                    'district_id', 
                    'village_id', 
                    'disbursement_method', 
                    'bank_account_number'
                ];

                // 2. Filter Data Dinamis untuk JSONB (Buang key statis & buang objek File)
                $dynamicData = collect($payload)
                    ->except($staticKeys)
                    ->filter(function ($value) {
                        // Pastikan hanya string/number yang masuk ke kolom JSONB submission_data
                        // Kita buang jika value adalah instance dari UploadedFile
                        return !($value instanceof UploadedFile);
                    })
                    ->toArray();

                // 3. Simpan Data Utama Pengajuan
                $submission = $this->repository->createSubmission([
                    'citizen_id'           => $citizen->id,
                    'program_id'           => $payload['program_id'],
                    'regency_id'           => $payload['regency_id'],
                    'district_id'          => $payload['district_id'],
                    'village_id'           => $payload['village_id'], 
                    'registration_number'  => 'SBN-' . strtoupper(str()->random(8)),
                    'status'               => 'pending',
                    'submission_data'      => $dynamicData, // Data dinamis masuk sini (JSONB)
                    'disbursement_method'  => $payload['disbursement_method'],
                    'bank_account_number'  => $payload['bank_account_number'] ?? null,
                    'last_submission_date' => now(), 
                ]);

                // 4. Proses Unggah Berkas ke Cloudinary
                foreach ($files as $key => $file) {
                    if ($file instanceof UploadedFile) {
                        // Jalankan fungsi upload dari provider (Cloudinary)
                        $upload = $this->storage->upload($file, 'submissions/' . $submission->registration_number);
                        
                        // Catat public_id agar bisa dihapus jika transaksi database gagal di baris berikutnya
                        $uploadedPublicIds[] = $upload['public_id'];

                        // Simpan log metadata foto ke tabel assistance_evidences
                        $this->repository->storeEvidence([
                            'submission_id'   => $submission->id,
                            'image_type'      => $key, 
                            'image_url'       => $upload['url'],
                            'cloud_public_id' => $upload['public_id'],
                        ]);
                    }
                }

                return $submission;
            });
            
        } catch (Exception $e) {
            // --- FAIL-SAFE MECHANISM (Pembersihan Cloud) ---
            // Jika Database Error (misal SQL Error), hapus gambar yang sudah terlanjur di cloud
            foreach ($uploadedPublicIds as $publicId) {
                try {
                    $this->storage->delete($publicId);
                } catch (Exception $deleteEx) {
                    Log::warning("Gagal membersihkan file yatim di Cloudinary: " . $publicId);
                }
            }

            // Catat Error detail ke log Laravel untuk admin
            Log::error('SUBMISSION_FAILED: ' . $e->getMessage(), [
                'citizen_id' => $citizen->id,
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            // Lemparkan error asli agar muncul di layar Vue untuk debugging
            throw new Exception("Gagal memproses pengajuan: " . $e->getMessage() . " (Baris: " . $e->getLine() . ")");
        }
    }

    /**
     * Mencari data pengajuan berdasarkan Kode Lacak (Registration Number)
     */
    public function getByRegistrationNumber(string $registrationNumber)
    {
        return $this->repository->findByRegistrationNumber($registrationNumber); 
    }

    public function cancelSubmission(string $registrationNumber, string $citizenId)
    {
        return DB::transaction(function () use ($registrationNumber, $citizenId) {
            $submission = $this->repository->findByRegistrationNumber($registrationNumber);

            // Ambil Public IDs untuk Cloudinary sebelum datanya ghaib di DB
            $publicIds = $submission->evidences->pluck('cloud_public_id')->filter()->toArray();

            // Eksekusi Hard Delete di Repository
            $this->repository->deleteByRegistrationNumber($registrationNumber, $citizenId);

            // Hapus file di Cloudinary SETELAH DB sukses
            foreach ($publicIds as $publicId) {
                $this->storage->delete($publicId);
            }

            return true;
        });
    }

    /**
     * Mengambil riwayat pengajuan khusus untuk warga tertentu
     */
    public function getCitizenHistory(string $citizenId)
    {
        return $this->repository->getHistoryByCitizenId($citizenId);
    }

    /**
     * Memperbarui pendaftaran bantuan (Mode Revisi)
     */
    public function update(string $id, array $payload, array $files)
    {
        return DB::transaction(function () use ($id, $payload, $files) {
            
            // 1. Cari data pendaftaran aslinya
            $submission = $this->repository->findById($id);

            // 2. Update Data Utama
            $staticKeys = ['program_id', 'regency_id', 'district_id', 'village_id', 'disbursement_method', 'bank_account_number'];
            $dynamicData = collect($payload)->except($staticKeys)->filter(fn($v) => !($v instanceof UploadedFile))->toArray();

            $submission->update([
                'regency_id'           => $payload['regency_id'],
                'district_id'          => $payload['district_id'],
                'village_id'           => $payload['village_id'], 
                'status'               => 'pending', // Kembalikan ke pending agar dicek ulang admin
                'submission_data'      => $dynamicData,
                'disbursement_method'  => $payload['disbursement_method'],
                'bank_account_number'  => $payload['bank_account_number'] ?? null,
                'last_submission_date' => now(), 
            ]);

            // 3. LOGIKA REPLACE FOTO CLOUDINARY
            foreach ($files as $key => $file) {
                if ($file instanceof UploadedFile) {
                    
                    // A. Cari bukti foto lama berdasarkan image_type (misal: 'foto_ktp')
                    $oldEvidence = $submission->evidences()->where('image_type', $key)->first();

                    // B. Jika foto lama ada, hapus dari Cloudinary untuk hemat storage
                    if ($oldEvidence && $oldEvidence->cloud_public_id) {
                        try {
                            $this->storage->delete($oldEvidence->cloud_public_id);
                        } catch (\Exception $e) {
                            Log::warning("Gagal menghapus foto lama di Cloudinary: " . $oldEvidence->cloud_public_id);
                        }
                    }

                    // C. Unggah foto baru ke Cloudinary
                    $upload = $this->storage->upload($file, 'submissions/' . $submission->registration_number);
                    
                    // D. Update atau Create record di tabel evidences
                    $submission->evidences()->updateOrCreate(
                        ['image_type' => $key],
                        [
                            'image_url'       => $upload['url'],
                            'cloud_public_id' => $upload['public_id'],
                        ]
                    );
                }
            }

            return $submission;
        });
    }

    /**
     * Mengambil detail pengajuan berdasarkan UUID
     */
    public function getById(string $id)
    {
        // Mengambil dari repository dan langsung meload relasi agar muncul di Detail
        return $this->repository->findById($id)->load(['program', 'evidences']);
    }
}