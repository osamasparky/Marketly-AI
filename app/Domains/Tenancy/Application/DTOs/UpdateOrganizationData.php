<?php

namespace App\Domains\Tenancy\Application\DTOs;

use InvalidArgumentException;

readonly class UpdateOrganizationData
{
    public function __construct(
        public ?string $name = null,
        public ?string $type = null,
        public ?string $defaultLocale = null,
        public ?string $timezone = null
    ) {
        if ($this->name !== null && (strlen(trim($this->name)) < 2 || strlen(trim($this->name)) > 100)) {
            throw new InvalidArgumentException('Organization name must be between 2 and 100 characters.');
        }

        if ($this->type !== null && !in_array($this->type, ['business', 'agency'], true)) {
            throw new InvalidArgumentException('Invalid organization type.');
        }

        if ($this->defaultLocale !== null && !in_array($this->defaultLocale, ['en', 'ar'], true)) {
            throw new InvalidArgumentException('Invalid default locale.');
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name !== null ? trim($this->name) : null,
            'type' => $this->type,
            'default_locale' => $this->defaultLocale,
            'timezone' => $this->timezone,
        ], fn ($val) => $val !== null);
    }
}
