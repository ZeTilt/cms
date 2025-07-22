<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Gallery;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $user = new User();
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertNull($user->getUpdatedAt());
        $this->assertTrue($user->isActive());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
        $this->assertCount(0, $user->getGalleries());
    }

    public function testUserProperties(): void
    {
        $user = new User();
        
        $user->setEmail('test@example.com');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setPassword('hashed_password');
        $user->setRoles(['ROLE_ADMIN']);
        
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('test@example.com', $user->getUserIdentifier());
        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertEquals('John Doe', $user->getFullName());
        $this->assertEquals('hashed_password', $user->getPassword());
        $this->assertEquals(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    public function testUserActivation(): void
    {
        $user = new User();
        
        $this->assertTrue($user->isActive());
        
        $user->setActive(false);
        $this->assertFalse($user->isActive());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getUpdatedAt());
    }

    public function testGalleryManagement(): void
    {
        $user = new User();
        $gallery = $this->createMock(Gallery::class);
        
        // Test adding gallery
        $gallery->expects($this->once())
               ->method('setAuthor')
               ->with($user);
        
        $user->addGallery($gallery);
        $this->assertCount(1, $user->getGalleries());
        $this->assertTrue($user->getGalleries()->contains($gallery));
        
        // Test removing gallery
        $gallery->expects($this->once())
               ->method('getAuthor')
               ->willReturn($user);
        $gallery->expects($this->once())
               ->method('setAuthor')
               ->with(null);
        
        $user->removeGallery($gallery);
        $this->assertCount(0, $user->getGalleries());
    }

    public function testRemoveGalleryWithDifferentAuthor(): void
    {
        $user = new User();
        $otherUser = new User();
        $gallery = $this->createMock(Gallery::class);
        
        $user->addGallery($gallery);
        
        // Mock gallery having different author
        $gallery->expects($this->once())
               ->method('getAuthor')
               ->willReturn($otherUser);
        $gallery->expects($this->never())
               ->method('setAuthor');
        
        $user->removeGallery($gallery);
        $this->assertCount(0, $user->getGalleries());
    }

    public function testEraseCredentials(): void
    {
        $user = new User();
        // This method should exist but do nothing for now
        $user->eraseCredentials();
        $this->assertTrue(true); // Just ensure no exception is thrown
    }
}