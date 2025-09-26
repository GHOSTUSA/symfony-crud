<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\UserRole;

class User
{
    private ?int $id = null;
    private string $name;
    private string $firstName;
    private Email $email;
    private ?string $phone;
    private UserRole $role;
    private ?string $password;

    public function __construct(
        ?int $id,
        string $name,
        string $firstName,
        Email $email,
        ?string $phone,
        ?string $password,
        ?UserRole $role = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->firstName = $firstName;
        $this->email = $email;
        $this->phone = $phone;
        $this->password = $password;
        $this->role = $role ?? UserRole::fromEmail($email->getValue());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function updateEmail(Email $email): void
    {
        $this->email = $email;
        $this->role = UserRole::fromEmail($email->getValue());
    }

    public function updatePassword(?string $hashedPassword): void
    {
        $this->password = $hashedPassword;
    }

    public function updateName(string $name): void
    {
        $this->name = $name;
    }

    public function updateFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function updatePhone(?string $phone): void
    {
        $this->phone = $phone;
    }
}