<?php

declare(strict_types=1);

namespace App\Ai;

use App\Exceptions\Ai\AiConfigurationException;
use App\Exceptions\Ai\AiProviderException;
use App\Exceptions\Ai\UserSafeAiException;
use Illuminate\Http\Client\RequestException;
use Laravel\Ai\Exceptions\RateLimitedException;
use LogicException;
use Throwable;

final class ProviderFailure
{
    public static function translate(Throwable $e): UserSafeAiException|AiProviderException|AiConfigurationException
    {
        if ($e instanceof UserSafeAiException
            || $e instanceof AiProviderException
            || $e instanceof AiConfigurationException) {
            return $e;
        }

        report($e);

        if ($e instanceof RateLimitedException) {
            return new UserSafeAiException('Too many requests. Please wait a minute and try again.');
        }

        if ($e instanceof LogicException) {
            return new AiConfigurationException(
                developerMessage: $e->getMessage(),
            );
        }

        $status = $e instanceof RequestException ? $e->response?->status() : null;
        $detail = trim($e->getMessage());

        return new AiProviderException(
            developerMessage: $detail !== ''
                ? $detail
                : ($status !== null ? get_class($e).' HTTP '.$status : get_class($e)),
            previous: $e,
        );
    }
}
