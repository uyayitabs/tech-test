<?php

namespace Tests\Feature\Console;

use App\Enums\ApplicationStatus;
use App\Enums\PlanType;
use App\Jobs\ProcessNbnApplication;
use App\Models\Application;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessNbnApplicationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_job_for_each_pending_nbn_application(): void
    {
        Queue::fake();
        $nbnPlan = Plan::factory()->create(['type' => PlanType::Nbn->value]);
        Application::factory()->count(2)->create([
            'plan_id' => $nbnPlan->id,
            'status' => ApplicationStatus::Order,
        ]);

        $this->artisan('applications:process-nbn');

        Queue::assertPushed(ProcessNbnApplication::class, 2);
    }

    public function test_ignores_non_nbn_applications(): void
    {
        Queue::fake();
        $mobilePlan = Plan::factory()->create(['type' => PlanType::Mobile->value]);
        Application::factory()->create([
            'plan_id' => $mobilePlan->id,
            'status' => ApplicationStatus::Order,
        ]);

        $this->artisan('applications:process-nbn');

        Queue::assertNothingPushed();
    }

    public function test_ignores_non_order_status_applications(): void
    {
        Queue::fake();
        $nbnPlan = Plan::factory()->create(['type' => PlanType::Nbn->value]);
        Application::factory()->create([
            'plan_id' => $nbnPlan->id,
            'status' => ApplicationStatus::Complete,
        ]);

        $this->artisan('applications:process-nbn');

        Queue::assertNothingPushed();
    }
}
