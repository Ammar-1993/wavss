<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../csrf.php';

class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        // Mock $_SESSION for CSRF functions
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $_SESSION = [];
        }
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testCsrfTokenGeneratesAndReturnsToken()
    {
        $token1 = csrf_token();
        $this->assertNotEmpty($token1);
        $this->assertEquals(64, strlen($token1)); // bin2hex(random_bytes(32)) is 64 chars

        $token2 = csrf_token();
        $this->assertEquals($token1, $token2, 'Subsequent calls should return the same token');
    }

    public function testCsrfVerifyWithValidToken()
    {
        $token = csrf_token();
        $this->assertTrue(csrf_verify($token));
    }

    public function testCsrfVerifyWithInvalidToken()
    {
        csrf_token(); // generate a token in session
        $this->assertFalse(csrf_verify('invalid_token'));
    }

    public function testCsrfVerifyWithEmptyToken()
    {
        csrf_token();
        $this->assertFalse(csrf_verify(''));
    }

    public function testCsrfVerifyWithoutSessionToken()
    {
        // Ensure $_SESSION['csrf_token'] is empty
        $_SESSION = [];
        $this->assertFalse(csrf_verify('some_token'));
    }
}
