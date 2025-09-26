<?php

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\User;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\UserRole;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;
    private Email $email;
    private UserRole $role;

    protected function setUp(): void
    {
        $this->email = new Email('test@example.com');
        $this->role = new UserRole('user');
        $this->user = new User(
            null,
            'John',
            'Doe',
            $this->email,
            '1234567890',
            'hashed_password',
            $this->role
        );
    }

    public function test_user_creation()
    {
        $this->assertEquals('John', $this->user->getName());
        $this->assertEquals('Doe', $this->user->getFirstName());
        $this->assertEquals($this->email, $this->user->getEmail());
        $this->assertEquals('1234567890', $this->user->getPhone());
        $this->assertEquals($this->role, $this->user->getRole());
    }

    public function test_email_update_changes_role_for_company_domain()
    {
        $companyEmail = new Email('john@company.com');
        $this->user->updateEmail($companyEmail);

        $this->assertEquals($companyEmail, $this->user->getEmail());
        $this->assertEquals('admin', $this->user->getRole()->getValue());
    }

    public function test_password_update()
    {
        $newPassword = 'new_hashed_password';
        $this->user->updatePassword($newPassword);

        $this->assertEquals($newPassword, $this->user->getPassword());
    }
}