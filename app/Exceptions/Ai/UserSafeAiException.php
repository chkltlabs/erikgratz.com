<?php

declare(strict_types=1);

namespace App\Exceptions\Ai;

use RuntimeException;

/** Validation / rate-limit — safe to render anywhere. */
class UserSafeAiException extends RuntimeException {}
