<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

class PasswordSecurityService
{
    public function make(string $password): string
    {
        return Hash::make($password);
    }

    public function verify(string $password, ?string $storedHash): bool
    {
        if (!$storedHash) {
            return false;
        }

        if ($this->isLegacyMd5($storedHash)) {
            return hash_equals(strtolower($storedHash), md5($password));
        }

        try {
            return Hash::check($password, $storedHash);
        } catch (\Throwable $exception) {
            return false;
        }
    }

    public function needsRehash(?string $storedHash): bool
    {
        if (!$storedHash || $this->isLegacyMd5($storedHash)) {
            return true;
        }

        try {
            return Hash::needsRehash($storedHash);
        } catch (\Throwable $exception) {
            return true;
        }
    }

    public function isLegacyMd5(?string $storedHash): bool
    {
        return is_string($storedHash) && preg_match('/^[a-f0-9]{32}$/i', $storedHash) === 1;
    }
}
