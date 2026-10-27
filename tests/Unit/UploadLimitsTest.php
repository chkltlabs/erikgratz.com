<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Filament\Resources\Photos\Schemas\PhotoForm;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UploadLimitsTest extends TestCase
{
    private const FOUR_AND_A_HALF_MEGABYTES = 4_718_592;

    #[Test]
    public function a_four_and_a_half_megabyte_photo_fits_every_upload_limit(): void
    {
        $formMax = PhotoForm::MAX_UPLOAD_KILOBYTES * 1024;
        $uploadMax = $this->iniBytes($this->read('public/.user.ini'), 'upload_max_filesize');
        $postMax = $this->iniBytes($this->read('public/.user.ini'), 'post_max_size');
        $nginxMax = $this->nginxBodyBytes($this->read('deploy/nginx/site-snippet.conf'));

        $this->assertGreaterThanOrEqual(self::FOUR_AND_A_HALF_MEGABYTES, $formMax);
        $this->assertGreaterThanOrEqual($formMax, $uploadMax);
        $this->assertGreaterThanOrEqual($uploadMax, $postMax);
        $this->assertGreaterThanOrEqual($postMax, $nginxMax);
        $this->assertStringContainsString(
            '->maxSize(self::MAX_UPLOAD_KILOBYTES)',
            $this->read('app/Filament/Resources/Photos/Schemas/PhotoForm.php'),
        );
    }

    private function read(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 2).'/'.$path);
        $this->assertNotFalse($contents);

        return $contents;
    }

    private function iniBytes(string $contents, string $key): int
    {
        $this->assertSame(1, preg_match('/^'.preg_quote($key, '/').'\s*=\s*(\d+)\s*([KMG])?/m', $contents, $matches));

        return $this->toBytes((int) $matches[1], $matches[2] ?? '');
    }

    private function nginxBodyBytes(string $contents): int
    {
        $this->assertSame(1, preg_match('/client_max_body_size\s+(\d+)([kmg])?;/i', $contents, $matches));

        return $this->toBytes((int) $matches[1], $matches[2] ?? '');
    }

    private function toBytes(int $value, string $unit): int
    {
        return match (strtoupper($unit)) {
            'K' => $value * 1024,
            'M' => $value * 1024 * 1024,
            'G' => $value * 1024 * 1024 * 1024,
            default => $value,
        };
    }
}
