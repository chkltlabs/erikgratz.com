<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\Ai\AiConfigurationException;
use App\Exceptions\Ai\UserSafeAiException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class AiExceptionsTest extends TestCase
{
    #[Test]
    public function configuration_exception_keeps_a_public_message_and_developer_detail(): void
    {
        $previous = new RuntimeException('missing key');
        $exception = new AiConfigurationException(
            'The AI assistant is not configured yet.',
            'GEMINI_API_KEY is empty',
            $previous,
        );

        $this->assertSame('The AI assistant is not configured yet.', $exception->getMessage());
        $this->assertSame('GEMINI_API_KEY is empty', $exception->developerMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }

    #[Test]
    public function user_safe_exception_is_renderable_as_its_message(): void
    {
        $exception = new UserSafeAiException('Slow down and try again.');

        $this->assertSame('Slow down and try again.', $exception->getMessage());
    }
}
