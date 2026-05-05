<?php
namespace App\Infrastructure\Storage;

use App\Contracts\Storage\FileStorageInterface;
use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;

class CloudinaryStorageProvider implements FileStorageInterface {
    private Cloudinary $cloudinary;

    public function __construct() {
        // Ambil langsung dari ENV, jangan lewat config() dulu buat ngetes
        $url = env('CLOUDINARY_URL');
        
        if (!$url) {
            throw new \Exception("Koneksi Cloudinary gagal: URL tidak ditemukan di .env");
        }

        $this->cloudinary = new Cloudinary($url);
    }

    public function upload(UploadedFile $file, string $folder): array 
    {
        $environment = config('app.env'); 

        $rootFolder = 'sabana_' . $environment;

        $response = $this->cloudinary->uploadApi()->upload($file->getRealPath(), [
            'folder' => $rootFolder . '/' . $folder, 

        ]);

        return [
            'url' => $response['secure_url'],
            'public_id' => $response['public_id']
        ];
    }

    public function delete(string $publicId): bool {
        $response = $this->cloudinary->uploadApi()->destroy($publicId);
        return $response['result'] === 'ok';
    }
}