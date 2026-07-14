<?php

namespace Tests\Unit\Services;

use App\Services\PasswordSecurityService;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordSecurityServiceTest extends TestCase
{
    private PasswordSecurityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PasswordSecurityService::class);
    }

    /** @test */
    public function can_hash_password(): void
    {
        $password = 'MySecurePassword123!';
        $hash = $this->service->make($password);

        $this->assertNotEmpty($hash);
        $this->assertNotEquals($password, $hash);
        $this->assertTrue(Hash::check($password, $hash));
    }

    /** @test */
    public function can_verify_hashed_password(): void
    {
        $password = 'TestPassword456!';
        $hash = $this->service->make($password);

        $this->assertTrue($this->service->verify($password, $hash));
    }

    /** @test */
    public function rejects_incorrect_password(): void
    {
        $password = 'CorrectPassword123!';
        $hash = $this->service->make($password);

        $this->assertFalse($this->service->verify('WrongPassword', $hash));
    }

    /** @test */
    public function handles_null_hash(): void
    {
        $this->assertFalse($this->service->verify('anyPassword', null));
    }

    /** @test */
    public function handles_empty_hash(): void
    {
        $this->assertFalse($this->service->verify('anyPassword', ''));
    }

    /** @test */
    public function can_verify_legacy_md5_passwords(): void
    {
        $password = 'legacy_password';
        $legacyMd5 = md5($password);

        $this->assertTrue($this->service->verify($password, $legacyMd5));
    }

    /** @test */
    public function rejects_incorrect_legacy_md5_password(): void
    {
        $password = 'legacy_password';
        $legacyMd5 = md5($password);

        $this->assertFalse($this->service->verify('wrong_password', $legacyMd5));
    }

    /** @test */
    public function identifies_legacy_md5_hashes(): void
    {
        $legacyMd5 = md5('test');
        $this->assertTrue($this->service->isLegacyMd5($legacyMd5));
    }

    /** @test */
    public function rejects_non_md5_as_legacy(): void
    {
        $modernHash = Hash::make('test');
        $this->assertFalse($this->service->isLegacyMd5($modernHash));
    }

    /** @test */
    public function needs_rehash_for_legacy_md5(): void
    {
        $legacyMd5 = md5('test');
        $this->assertTrue($this->service->needsRehash($legacyMd5));
    }

    /** @test */
    public function needs_rehash_for_null(): void
    {
        $this->assertTrue($this->service->needsRehash(null));
    }

    /** @test */
    public function needs_rehash_for_empty(): void
    {
        $this->assertTrue($this->service->needsRehash(''));
    }

    /** @test */
    public function modern_hash_may_not_need_rehash(): void
    {
        $modernHash = Hash::make('test', ['rounds' => 4]);
        $this->assertFalse($this->service->needsRehash($modernHash));
    }

    /** @test */
    public function handles_invalid_hash_format(): void
    {
        $result = $this->service->verify('password', 'invalid_hash_format_xyz');
        $this->assertFalse($result);
    }

    /** @test */
    public function handles_special_characters_in_password(): void
    {
        $password = 'P@$$w0rd!#%&*()[]{}';
        $hash = $this->service->make($password);

        $this->assertTrue($this->service->verify($password, $hash));
    }

    /** @test */
    public function handles_unicode_characters_in_password(): void
    {
        $password = 'Contraseña™ñéü123';
        $hash = $this->service->make($password);

        $this->assertTrue($this->service->verify($password, $hash));
    }

    /** @test */
    public function is_case_insensitive_for_md5(): void
    {
        $password = 'test';
        $md5Lower = md5($password);
        $md5Upper = strtoupper($md5Lower);

        $this->assertTrue($this->service->verify($password, $md5Upper));
    }
}
