<?php

declare(strict_types=1);

namespace App\Exceptions\Ai;

use Throwable;

final class AiUserMessage
{
    public static function from(Throwable $e, string $fallback): string
    {
        if (! app()->isLocal()) {
            return $e instanceof UserSafeAiException
                ? $e->getMessage()
                : $fallback;
        }

        $detail = method_exists($e, 'developerMessage') ? (string) $e->developerMessage() : '';
        $base = $e->getMessage();

        if ($detail !== '' && $detail !== $base) {
            return $base !== '' ? $base.' — '.$detail : $detail;
        }

        return $base !== '' ? $base : $fallback;
    }
}
