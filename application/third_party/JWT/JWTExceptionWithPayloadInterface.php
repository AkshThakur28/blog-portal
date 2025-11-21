<?php

namespace Firebase\JWT;

/**
 * Minimal interface used by our custom exception classes to carry the decoded payload.
 * This is provided because we're not installing firebase/php-jwt via Composer.
 */
interface JWTExceptionWithPayloadInterface
{
    public function setPayload(object $payload): void;

    public function getPayload(): object;
}
