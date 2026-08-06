<?php

declare(strict_types=1);

namespace App\Exception;

final class DuelLimitException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $retryAfterSeconds,
    ) {
        parent::__construct($message);
    }
}
