<?php
namespace App\Contracts\Storage;
use Illuminate\Http\UploadedFile;

interface FileStorageInterface {
    public function upload(UploadedFile $file, string $folder): array;
    public function delete(string $publicId): bool;
}