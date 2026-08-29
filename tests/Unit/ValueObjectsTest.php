<?php

namespace Tests\Unit;

use App\Domains\Shared\Enums\Platform;
use App\Domains\Shared\Enums\PublicationStatus;
use App\Domains\Shared\Enums\UserRole;
use App\Domains\Shared\ValueObjects\Email;
use App\Domains\Shared\ValueObjects\Url;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValueObjectsTest extends TestCase
{
    public function test_valid_email_value_object(): void
    {
        $email = new Email('  Admin@Marketly.AI  ');
        $this->assertEquals('admin@marketly.ai', $email->value());
        $this->assertEquals('admin@marketly.ai', (string) $email);
    }

    public function test_invalid_email_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Email('not-an-email');
    }

    public function test_valid_external_url(): void
    {
        $url = new Url('https://google.com/search?q=ai');
        $this->assertEquals('https://google.com/search?q=ai', $url->value());
        $this->assertEquals('google.com', $url->host());
        $this->assertEquals('https', $url->scheme());
    }

    public function test_ssrf_blocking_private_host_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Url('http://localhost/admin');
    }

    public function test_publication_status_state_machine(): void
    {
        $draft = PublicationStatus::DRAFT;
        $approved = PublicationStatus::APPROVED;
        $published = PublicationStatus::PUBLISHED;

        $this->assertTrue($draft->canTransitionTo($approved));
        $this->assertFalse($draft->canTransitionTo($published)); // Cannot publish directly from draft without approval
        $this->assertFalse($published->canTransitionTo($draft)); // Terminal state cannot transition back to draft
    }

    public function test_user_roles_and_identifiers(): void
    {
        $admin = UserRole::ADMIN;
        $viewer = UserRole::VIEWER;
        $owner = UserRole::OWNER;

        $this->assertEquals('admin', $admin->value);
        $this->assertEquals('viewer', $viewer->value);
        $this->assertEquals('owner', $owner->value);
        $this->assertEquals('Owner', $owner->label());
    }
}
