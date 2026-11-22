<?php

namespace App\Data;

readonly class SsoTokenPayload
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
        public SsoUserClaims $claims,
    ) {}
}
