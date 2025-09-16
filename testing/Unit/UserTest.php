<?php

declare(strict_types=1);

namespace Tests\Unit;

use CMS\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testValidateUserDataWithValidData()
    {
        $data = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        // Mock the isUsernameAvailable and isEmailAvailable methods to return true
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['isUsernameAvailable', 'isEmailAvailable'])
            ->getMock();

        $userMock->method('isUsernameAvailable')->willReturn(true);
        $userMock->method('isEmailAvailable')->willReturn(true);

        $errors = $userMock::validateUserData($data);

        $this->assertEmpty($errors);
    }

    public function testValidateUserDataWithInvalidData()
    {
        $data = [
            'username' => 'u',
            'email' => 'invalid-email',
            'password' => 'short',
        ];

        // Mock the isUsernameAvailable and isEmailAvailable methods to return true
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['isUsernameAvailable', 'isEmailAvailable'])
            ->getMock();

        $userMock->method('isUsernameAvailable')->willReturn(true);
        $userMock->method('isEmailAvailable')->willReturn(true);

        $errors = $userMock::validateUserData($data);

        $this->assertArrayHasKey('username', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('password', $errors);
    }
}
