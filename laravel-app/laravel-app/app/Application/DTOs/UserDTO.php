<?php

namespace App\Application\DTOs;

class UserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $firstName,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly ?string $password = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            $data['first_name'],
            $data['email'],
            $data['phone'] ?? null,
            $data['password'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'first_name' => $this->firstName,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => $this->password,
        ];
    }
}