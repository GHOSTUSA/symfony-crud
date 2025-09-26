<?php

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\ValueObjects\Email;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class EmailTest extends TestCase
{
    public function test_valid_email_creation()
    {
        $emailStr = 'test@example.com';
        $email = new Email($emailStr);
        
        $this->assertEquals($emailStr, $email->getValue());
    }

    public function test_invalid_email_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        new Email('invalid-email');
    }

    public function test_empty_email_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        new Email('');
    }

    public function test_email_equality()
    {
        $email1 = new Email('test@example.com');
        $email2 = new Email('test@example.com');
        $email3 = new Email('different@example.com');

        $this->assertTrue($email1->equals($email2));
        $this->assertFalse($email1->equals($email3));
    }
}