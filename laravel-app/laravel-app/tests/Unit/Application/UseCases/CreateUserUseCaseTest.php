<?php

namespace Tests\Unit\Application\UseCases;

use App\Application\DTOs\UserDTO;
use App\Application\UseCases\CreateUserUseCase;
use App\Domain\Entities\User;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\UserRole;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Hashing\Hasher;
use PHPUnit\Framework\TestCase;

class CreateUserUseCaseTest extends TestCase
{
    private $userRepository;
    private $container;
    private $hasher;
    private $useCase;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->container = $this->createMock(Container::class);
        $this->hasher = $this->createMock(Hasher::class);

        $this->useCase = new CreateUserUseCase(
            $this->userRepository,
            $this->container,
            $this->hasher
        );
    }

    public function test_execute_creates_user_successfully()
    {
        // Arrange
        $userDTO = new UserDTO(
            'John',
            'Doe',
            'john@example.com',
            '1234567890',
            'password123'
        );

        $expectedUser = new User(
            null,
            'John',
            'Doe',
            new Email('john@example.com'),
            '1234567890',
            'hashed_password',
            new UserRole('user')
        );

        $this->hasher->expects($this->once())
            ->method('make')
            ->with('password123')
            ->willReturn('hashed_password');

        $this->container->expects($this->once())
            ->method('make')
            ->with('UserEntityFactory')
            ->willReturn(function($id, $name, $firstName, $email, $phone, $password, $role) use ($expectedUser) {
                return $expectedUser;
            });

        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($expectedUser)
            ->willReturn($expectedUser);

        // Act
        $result = $this->useCase->execute($userDTO);

        // Assert
        $this->assertSame($expectedUser, $result);
    }
}