<?php

namespace Tests\Unit\Infrastructure\Storage;

use Tests\TestCase;
use App\Infrastructure\Storage\CloudinaryStorageProvider;
use App\Contracts\Storage\FileStorageInterface;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
#[Group('cloudinary')]

class CloudinaryStorageProviderTest extends TestCase
{
    private CloudinaryStorageProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        // Set URL default untuk unit test (tanpa koneksi asli)
        if (!env('CLOUDINARY_URL')) {
            putenv('CLOUDINARY_URL=cloudinary://test_key:test_secret@test_cloud');
        }

        $this->provider = new CloudinaryStorageProvider();
    }

    protected function tearDown(): void
    {
        // Bersihkan setelah test
        parent::tearDown();
    }

    // ========================================================================
    // INTERFACE COMPLIANCE
    // ========================================================================

    #[Group('critical')]
    public function test_implements_file_storage_interface(): void
    {
        $this->assertInstanceOf(
            FileStorageInterface::class,
            $this->provider,
            'CloudinaryStorageProvider MUST implement FileStorageInterface.'
        );
    }

    // ========================================================================
    // CONSTRUCTOR & INITIALIZATION
    // ========================================================================

    #[Group('critical')]
    public function test_throws_exception_when_cloudinary_url_is_empty(): void
    {
        // Simpan nilai asli
        $originalUrl = env('CLOUDINARY_URL');

        // Paksa overwrite di repository env Laravel
        putenv('CLOUDINARY_URL');
        $_ENV['CLOUDINARY_URL'] = null;
        $_SERVER['CLOUDINARY_URL'] = null;

        // Reset config cache
        app()->forgetInstance('config');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Koneksi Cloudinary gagal: URL tidak ditemukan di .env');

        try {
            new CloudinaryStorageProvider();
        } finally {
            // Kembalikan
            putenv('CLOUDINARY_URL=' . $originalUrl);
            $_ENV['CLOUDINARY_URL'] = $originalUrl;
            $_SERVER['CLOUDINARY_URL'] = $originalUrl;
        }
    }

    public function test_cloudinary_instance_is_initialized(): void
    {
        $reflection = new \ReflectionClass($this->provider);
        $property = $reflection->getProperty('cloudinary');
        $property->setAccessible(true);

        $instance = $property->getValue($this->provider);

        $this->assertNotNull($instance, 'Cloudinary instance cannot be null.');
        $this->assertInstanceOf(
            \Cloudinary\Cloudinary::class,
            $instance,
            'Must be instance of Cloudinary\Cloudinary.'
        );
    }

    // ========================================================================
    // UPLOAD METHOD
    // ========================================================================

    public function test_upload_returns_array_with_url_and_public_id(): void
    {
        // Skip jika tidak ada koneksi asli
        if ($this->isFakeUrl()) {
            $this->markTestSkipped('Integration test: requires valid CLOUDINARY_URL.');
        }

        $file = UploadedFile::fake()->image('test-upload.jpg', 200, 200);

        $result = $this->provider->upload($file, 'test_folder');

        $this->assertIsArray($result, 'Upload MUST return array.');
        $this->assertArrayHasKey('url', $result, 'Result MUST have "url" key.');
        $this->assertArrayHasKey('public_id', $result, 'Result MUST have "public_id" key.');
        $this->assertStringContainsString('https://', $result['url'], 'URL MUST use HTTPS.');

        // Cleanup
        if (isset($result['public_id'])) {
            $this->provider->delete($result['public_id']);
        }
    }

    public function test_upload_uses_environment_folder_prefix(): void
    {
        if ($this->isFakeUrl()) {
            $this->markTestSkipped('Integration test: requires valid CLOUDINARY_URL.');
        }

        $file = UploadedFile::fake()->image('test-env.jpg', 100, 100);

        $result = $this->provider->upload($file, 'env_check');

        $expectedPath = 'sabana_' . config('app.env') . '/env_check';
        $this->assertStringContainsString(
            $expectedPath,
            $result['url'],
            sprintf('URL must contain folder path: %s', $expectedPath)
        );

        if (isset($result['public_id'])) {
            $this->provider->delete($result['public_id']);
        }
    }

    // ========================================================================
    // DELETE METHOD
    // ========================================================================

    public function test_delete_returns_true_for_existing_file(): void
    {
        if ($this->isFakeUrl()) {
            $this->markTestSkipped('Integration test: requires valid CLOUDINARY_URL.');
        }

        // Upload dulu
        $file = UploadedFile::fake()->image('to-delete.jpg', 100, 100);
        $uploadResult = $this->provider->upload($file, 'delete_test');
        $this->assertArrayHasKey('public_id', $uploadResult);

        // Delete
        $result = $this->provider->delete($uploadResult['public_id']);
        $this->assertTrue($result, 'Delete MUST return true for existing file.');
    }

    public function test_delete_returns_false_for_nonexistent_id(): void
    {
        if ($this->isFakeUrl()) {
            $this->markTestSkipped('Integration test: requires valid CLOUDINARY_URL.');
        }

        $result = $this->provider->delete('nonexistent_public_id_12345');
        $this->assertFalse($result, 'Delete MUST return false for nonexistent ID.');
    }

    // ========================================================================
    // EDGE CASES
    // ========================================================================

    public function test_upload_handles_large_image(): void
    {
        if ($this->isFakeUrl()) {
            $this->markTestSkipped('Integration test: requires valid CLOUDINARY_URL.');
        }

        $file = UploadedFile::fake()->image('large.jpg', 2000, 2000)->size(5120);

        $result = $this->provider->upload($file, 'large_test');

        $this->assertArrayHasKey('url', $result, 'Large file upload MUST succeed.');

        if (isset($result['public_id'])) {
            $this->provider->delete($result['public_id']);
        }
    }

    public function test_upload_handles_png_file(): void
    {
        if ($this->isFakeUrl()) {
            $this->markTestSkipped('Integration test: requires valid CLOUDINARY_URL.');
        }

        $file = UploadedFile::fake()->image('transparent.png', 150, 150);

        $result = $this->provider->upload($file, 'png_test');

        $this->assertArrayHasKey('url', $result);
        $this->assertStringContainsString('https://', $result['url']);

        if (isset($result['public_id'])) {
            $this->provider->delete($result['public_id']);
        }
    }

    // ========================================================================
    // HELPER
    // ========================================================================

    /**
     * Cek apakah URL yang dipakai hanya untuk testing (bukan koneksi asli).
     */
    private function isFakeUrl(): bool
    {
        $url = env('CLOUDINARY_URL', '');
        return !$url || str_contains($url, 'test_key') || str_contains($url, 'test@test');
    }
}