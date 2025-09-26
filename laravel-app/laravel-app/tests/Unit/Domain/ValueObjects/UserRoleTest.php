<?php

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\ValueObjects\UserRole;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class UserRoleTest extends TestCase
{
    public function test_valid_role_creation()
    {
        $role = new UserRole('user');
        $this->assertEquals('user', $role->getValue());

        $role = new UserRole('admin');
        $this->assertEquals('admin', $role->getValue());
    }

    public function test_invalid_role_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        new UserRole('invalid-role');
    }

    public function test_role_from_email_company()
    {
        $role = UserRole::fromEmail('user@company.com');
        $this->assertEquals('admin', $role->getValue());
    }

    public function test_role_from_email_other()
    {
        $role = UserRole::fromEmail('user@example.com');
        $this->assertEquals('user', $role->getValue());
    }

    public function test_role_equality()
    {
        $role1 = new UserRole('user');
        $role2 = new UserRole('user');
        $role3 = new UserRole('admin');

        $this->assertTrue($role1->equals($role2));
        $this->assertFalse($role1->equals($role3));
    }
}