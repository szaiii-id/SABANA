<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\FileStorageInterface;
use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use RuntimeException;

final class CloudinaryStorageProvider implements FileStorageInterface 
{
    public function __construct(
        private readonly Cloudinary $cloudinary,
    ) {}

    /**
     * @throws RuntimeException
     */
    public static function createFromEnv(): self
    {
        $url = env('CLOUDINARY_URL');
        
        if (!$url) {
            throw new RuntimeException('Koneksi Cloudinary gagal: URL tidak ditemukan di .env');
        }

        return new self(new Cloudinary($url));
    }

    public function upload(UploadedFile $file, string $folder): array 
    {
        $environment = config('app.env'); 
        $rootFolder = 'sabana_' . $environment;

        $response = $this->cloudinary->uploadApi()->upload($file->getRealPath(), [
            'folder' => $rootFolder . '/' . $folder,
            'secure' => true, 
            'fetch_format' => 'auto',
            'quality' => 'auto',
        ]);

        return [
            'url' => $response['secure_url'],
            'public_id' => $response['public_id'],
        ];
    }

    public function delete(string $publicId): bool 
    {
        $response = $this->cloudinary->uploadApi()->destroy($publicId);
        
        return $response['result'] === 'ok';
    }
}