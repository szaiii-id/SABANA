<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Storage;

use Tests\TestCase;
use App\Infrastructure\Storage\CloudinaryStorageProvider;
use App\Contracts\Storage\FileStorageInterface;
use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
#[Group('cloudinary')]
final class CloudinaryStorageProviderTest extends TestCase
{
    private CloudinaryStorageProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        if (!env('CLOUDINARY_URL')) {
            putenv('CLOUDINARY_URL=cloudinary://test_key:test_secret@test_cloud');
        }

        $this->provider = new CloudinaryStorageProvider(new Cloudinary(env('CLOUDINARY_URL')));
    }

    // ===== INTERFACE COMPLIANCE =====

    #[Group('critical')]
    public function test_implements_file_storage_interface(): void
    {
        $this->assertInstanceOf(
            FileStorageInterface::class,
            $this->provider,
            'CloudinaryStorageProvider MUST implement FileStorageInterface.'
        );
    }

    // ===== CONSTRUCTOR =====

    public function test_cloudinary_instance_is_initialized(): void
    {
        $reflection = new \ReflectionClass($this->provider);
        $property = $reflection->getProperty('cloudinary');
        $property->setAccessible(true);

        $instance = $property->getValue($this->provider);

        $this->assertNotNull($instance);
        $this->assertInstanceOf(Cloudinary::class, $instance);
    }

    // ===== CREATE FROM ENV =====

    public function test_create_from_env_with_valid_url(): void
    {
        putenv('CLOUDINARY_URL=cloudinary://key:secret@cloud');

        $provider = CloudinaryStorageProvider::createFromEnv();

        $this->assertInstanceOf(CloudinaryStorageProvider::class, $provider);
    }

    public function test_create_from_env_throws_exception_when_url_empty(): void
    {
        $originalUrl = env('CLOUDINARY_URL');
        
        putenv('CLOUDINARY_URL');
        $_ENV['CLOUDINARY_URL'] = null;
        $_SERVER['CLOUDINARY_URL'] = null;
        app()->forgetInstance('config');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Koneksi Cloudinary gagal: URL tidak ditemukan di .env');

        try {
            CloudinaryStorageProvider::createFromEnv();
        } finally {
            putenv('CLOUDINARY_URL=' . $originalUrl);
            $_ENV['CLOUDINARY_URL'] = $originalUrl;
            $_SERVER['CLOUDINARY_URL'] = $originalUrl;
        }
    }

    // ===== UPLOAD =====

    public function test_upload_returns_array_with_url_and_public_id(): void
    {
        if ($this->isFakeUrl()) {
            $this->markTestSkipped('Integration test: requires valid CLOUDINARY_URL.');
        }

        $file = UploadedFile::fake()->image('test-upload.jpg', 200, 200);

        $result = $this->provider->upload($file, 'test_folder');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('public_id', $result);
        $this->assertStringContainsString('https://', $result['url']);

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
        $this->assertStringContainsString($expectedPath, $result['url']);

        if (isset($result['public_id'])) {
            $this->provider->delete($result['public_id']);
        }
    }

    // ===== DELETE =====

    public function test_delete_returns_true_for_existing_file(): void
    {
        if ($this->isFakeUrl()) {
            $this->markTestSkipped('Integration test: requires valid CLOUDINARY_URL.');
        }

        $file = UploadedFile::fake()->image('to-delete.jpg', 100, 100);
        $uploadResult = $this->provider->upload($file, 'delete_test');

        $this->assertArrayHasKey('public_id', $uploadResult);

        $result = $this->provider->delete($uploadResult['public_id']);
        $this->assertTrue($result);
    }

    public function test_delete_returns_false_for_nonexistent_id(): void
    {
        if ($this->isFakeUrl()) {
            $this->markTestSkipped('Integration test: requires valid CLOUDINARY_URL.');
        }

        $result = $this->provider->delete('nonexistent_public_id_12345');
        $this->assertFalse($result);
    }

    // ===== EDGE CASES =====

    public function test_upload_handles_large_image(): void
    {
        if ($this->isFakeUrl()) {
            $this->markTestSkipped('Integration test: requires valid CLOUDINARY_URL.');
        }

        $file = UploadedFile::fake()->image('large.jpg', 2000, 2000)->size(5120);

        $result = $this->provider->upload($file, 'large_test');

        $this->assertArrayHasKey('url', $result);

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

    // ===== HELPER =====

    private function isFakeUrl(): bool
    {
        $url = env('CLOUDINARY_URL', '');
        return !$url || str_contains($url, 'test_key') || str_contains($url, 'test@test');
    }
}