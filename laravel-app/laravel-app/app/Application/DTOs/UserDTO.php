<?php

namespace App\Application\DTOs;

class UserDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $firstName = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $password = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'] ?? null,
            $data['first_name'] ?? null,
            $data['email'] ?? null,
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