<?php

namespace App\Domain\Entities;

class User
{
    private int $id;
    private string $name;
    private string $firstName;
    private string $email;
    private ?string $phone;
    private string $role;
    private string $password;

    public function __construct(
        string $name,
        string $firstName,
        string $email,
        ?string $phone,
        string $password
    ) {
        $this->name = $name;
        $this->firstName = $firstName;
        $this->email = $email;
        $this->phone = $phone;
        $this->password = $password;
        $this->setRole($email);
    }

    private function setRole(string $email): void
    {
        $this->role = (strpos($email, '@company.com') !== false) 
            ? 'Administrateur' 
            : 'Utilisateur standard';
    }

    // Getters
    public function getId(): int
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    // Setters with domain logic
    public function updateEmail(string $email): void
    {
        $this->email = $email;
        $this->setRole($email);
    }

    public function updatePassword(string $hashedPassword): void
    {
        $this->password = $hashedPassword;
    }
}