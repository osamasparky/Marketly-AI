<?php

namespace App\Domains\Billing\Domain\Contracts;

use App\Domains\Billing\Infrastructure\Persistence\Models\PlanModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;

interface PaymentGatewayInterface
{
    /**
     * Create customer profile at payment provider.
     */
    public function createCustomer(OrganizationModel $organization): string;

    /**
     * Create checkout session for plan upgrade.
     */
    public function createCheckoutSession(OrganizationModel $organization, PlanModel $plan, string $billingCycle = 'monthly'): array;

    /**
     * Cancel recurring subscription.
     */
    public function cancelSubscription(string $externalSubscriptionId): bool;
}
