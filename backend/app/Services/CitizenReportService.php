<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Jobs\SendWhatsAppJob;
use App\Models\Citizen;
use Exception;
use Illuminate\Support\Facades\Log;

final class CitizenReportService
{
    public function sendViaWhatsapp(Citizen $citizen, string $pesan): void
    {
        $adminNumber = config('services.fonnte.admin_number');
        $cleanPhone = $this->formatPhoneForLink($citizen->whatsapp_number);

        $encodedReply = urlencode("Halo Bapak/Ibu {$citizen->full_name}, laporan Anda mengenai: '" . substr($pesan, 0, 30) . "...' sudah kami terima.");
        $waLink = "https://wa.me/{$cleanPhone}?text={$encodedReply}";

        $adminMsg = "*[LAPORAN WARGA - WA]*\n\n"
                  . "Nama: {$citizen->full_name}\n"
                  . "NIK: {$citizen->nik}\n\n"
                  . "*PESAN:*\n_{$pesan}_\n\n"
                  . "--- \n"
                  . "*BALAS KE WARGA:* \n" . $waLink;

        $citizenMsg = "Halo Warga Banua \n\n Atas Nama : *{$citizen->full_name}*,\n\nLaporan Anda telah diterima oleh sistem SABANA. Admin akan segera menindaklanjuti. Terima kasih.";

        SendWhatsAppJob::dispatch($adminNumber, $adminMsg)->afterCommit();
        SendWhatsAppJob::dispatch($citizen->whatsapp_number, $citizenMsg)->afterCommit();
    }

    public function sendViaEmail(Citizen $citizen, string $subject, string $message, string $manualEmail): void
    {
        $payload = [
            'nama'   => $citizen->full_name,
            'email'  => $manualEmail,
            'subjek' => $subject,
            'pesan'  => $message,
        ];

        SendEmailJob::dispatch($payload, $citizen->full_name, $manualEmail)->afterCommit();
    }

    private function formatPhoneForLink(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        return str_starts_with($clean, '0') ? '62' . substr($clean, 1) : $clean;
    }
}