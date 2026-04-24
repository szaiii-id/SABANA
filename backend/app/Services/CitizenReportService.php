<?php 

namespace App\Services;

use App\Services\FonnteService;
use App\Services\AspirasiService;
use Exception;
use Illuminate\Support\Facades\Log;

class CitizenReportService
{
    public function __construct(
        private FonnteService $whatsapp,
        private AspirasiService $emailService
    ) {}

    public function sendViaWhatsapp(object $citizen, string $pesan): void
    {
        try {
            $adminNumber = config('services.fonnte.admin_number');
            $cleanPhone = $this->formatPhoneForLink($citizen->whatsapp_number);
            
            $encodedReply = urlencode("Halo Bapak/Ibu {$citizen->full_name}, laporan Anda mengenai: '" . substr($pesan, 0, 30) . "...' sudah kami terima.");
            $waLink = "https://wa.me/{$cleanPhone}?text={$encodedReply}";

            // Laporan ke Admin
            $adminMsg = "*[LAPORAN WARGA - WA]*\n\n"
                      . "Nama: {$citizen->full_name}\n"
                      . "NIK: {$citizen->nik}\n\n"
                      . "*PESAN:*\n_{$pesan}_\n\n"
                      . "--- \n"
                      . "*BALAS KE WARGA:* \n" . $waLink;

            $this->whatsapp->sendMessage($adminNumber, $adminMsg);

            // Auto-reply ke Warga
            $citizenMsg = "Halo Warga Banua \n\n Atas Nama : *{$citizen->full_name}*,\n\nLaporan Anda telah diterima oleh sistem SABANA. Admin akan segera menindaklanjuti. Terima kasih.";
            $this->whatsapp->sendMessage($citizen->whatsapp_number, $citizenMsg);
        } catch (Exception $e) {
            Log::error('Error WA Report: ' . $e->getMessage());
            throw $e;
        }
    }

    public function sendViaEmail(object $citizen, string $subject, string $message, string $manualEmail): void
    {
        try {
            $payload = [
                'nama'   => $citizen->full_name,
                'email'  => $manualEmail, // Email manual dari form Vue
                'subjek' => $subject,
                'pesan'  => $message
            ];

            $this->emailService->prosesDanKirimAspirasi($payload);

            $this->emailService->kirimBalasanOtomatis($payload, $manualEmail, $citizen->full_name);

        } catch (Exception $e) {
            \Log::error('Error Email Report: ' . $e->getMessage());
            throw $e;
        }
    }

    private function formatPhoneForLink(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        return str_starts_with($clean, '0') ? '62' . substr($clean, 1) : $clean;
    }
}