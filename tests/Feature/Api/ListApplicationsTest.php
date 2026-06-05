<?php

namespace Tests\Feature\Api;

use App\Enums\PlanType;
use App\Models\Application;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListApplicationsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_guest_cannot_list_applications(): void
    {
        $this->getJson('/api/applications')->assertUnauthorized();
    }

    public function test_returns_paginated_list_of_applications(): void
    {
        Application::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/applications')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'customer_name',
                        'address' => ['address_1', 'address_2', 'city', 'state', 'postcode'],
                        'plan_type',
                        'plan_name',
                        'status',
                        'plan_monthly_cost',
                        'order_id',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_applications_are_ordered_oldest_first(): void
    {
        $older = Application::factory()->create(['created_at' => now()->subDay()]);
        $newer = Application::factory()->create(['created_at' => now()]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/applications')
            ->assertOk()
            ->assertJsonPath('data.0.id', $older->id)
            ->assertJsonPath('data.1.id', $newer->id);
    }

    public function test_can_filter_by_plan_type(): void
    {
        $nbnPlan = Plan::factory()->create(['type' => PlanType::Nbn->value]);
        $mobilePlan = Plan::factory()->create(['type' => PlanType::Mobile->value]);

        $nbnApplication = Application::factory()->create(['plan_id' => $nbnPlan->id]);
        Application::factory()->create(['plan_id' => $mobilePlan->id]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/applications?type=nbn')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $nbnApplication->id);
    }

    public function test_rejects_invalid_plan_type(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/applications?type=invalid')
            ->assertUnprocessable();
    }

    public function test_plan_cost_shows_as_dollars(): void
    {
        $plan = Plan::factory()->create(['monthly_cost' => 9900]);
        Application::factory()->create(['plan_id' => $plan->id]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/applications')
            ->assertOk()
            ->assertJsonPath('data.0.plan_monthly_cost', '$99.00');
    }
}
