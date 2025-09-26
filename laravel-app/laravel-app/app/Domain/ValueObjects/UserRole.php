<?php

namespace App\Domain\ValueObjects;

class UserRole
{
    private const ADMIN = 'Administrateur';
    private const USER = 'Utilisateur standard';
    private string $value;

    public function __construct(string $role)
    {
        if ($role !== self::ADMIN && $role !== self::USER) {
            throw new \InvalidArgumentException('Invalid user role');
        }
        $this->value = $role;
    }

    public static function fromEmail(string $email): self
    {
        $role = (strpos($email, '@company.com') !== false) 
            ? self::ADMIN 
            : self::USER;
        return new self($role);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isAdmin(): bool
    {
        return $this->value === self::ADMIN;
    }

    public function equals(UserRole $other): bool
    {
        return $this->value === $other->value;
    }
}