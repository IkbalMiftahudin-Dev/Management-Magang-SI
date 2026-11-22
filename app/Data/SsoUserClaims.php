<?php

namespace App\Data;

use App\Enums\UserRole;
use Carbon\CarbonImmutable;

readonly class SsoUserClaims
{
    public function __construct(
        public string $sub,
        public string $name,
        public string $email,
        public ?string $nip,
        public UserRole $role,
        public string $iss,
        public string $aud,
        public int $iat,
        public int $exp,
    ) {}

    public function expiresAt(): CarbonImmutable
    {
        return CarbonImmutable::createFromTimestamp($this->exp);
    }
}
