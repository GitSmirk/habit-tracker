<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCreation()
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        
        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertSame('John Doe', (string) $user);
        $this->assertNull($user->getId());
    }

    public function testUserRoles()
    {
        $user = new User();
        $user->setEmail('admin@example.com');
        
        // By default, a user should have ROLE_USER
        $this->assertContains('ROLE_USER', $user->getRoles());
        
        // Test adding roles
        $user->setRoles(['ROLE_ADMIN']);
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
        $this->assertContains('ROLE_USER', $user->getRoles()); // ROLE_USER should always be present
    }

    public function testPasswordHashing()
    {
        $user = new User();
        $plainPassword = 'securepassword123';
        $user->setPassword($plainPassword);
        
        $this->assertNotSame($plainPassword, $user->getPassword());
        $this->assertTrue(password_verify($plainPassword, $user->getPassword()));
    }
}
