<?php

namespace App\Domains\Brand\Domain\Repositories;

use App\Domains\Brand\Application\DTOs\SaveBrandVoiceData;

interface BrandVoiceRepositoryInterface
{
    public function findByOrganizationId(int $organizationId): ?object;

    public function saveForOrganization(int $organizationId, int $brandProfileId, SaveBrandVoiceData $data): object;
}
