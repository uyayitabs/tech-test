<?php

namespace Tests\Feature\Jobs;

use App\Enums\ApplicationStatus;
use App\Enums\PlanType;
use App\Jobs\ProcessNbnApplication;
use App\Models\Application;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use JsonException;
use Tests\TestCase;

class ProcessNbnApplicationTest extends TestCase
{
    use RefreshDatabase;

    private Plan $nbnPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nbnPlan = Plan::factory()->create([
            'type' => PlanType::Nbn->value,
        ]);
    }

    /**
     * @throws JsonException
     */
    public function test_it_marks_the_application_as_complete_when_nbn_order_is_successful(): void
    {
        $this->fakeNbnResponse('nbn-successful-response.json');

        $application = $this->createNbnApplication();

        $this->processApplication($application);

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Complete->value,
            'order_id' => 'ORD000000000000',
        ]);
    }

    /**
     * @throws JsonException
     */
    public function test_it_marks_the_application_as_order_failed_when_nbn_returns_a_failed_response(): void
    {
        $this->fakeNbnResponse('nbn-fail-response.json');

        $application = $this->createNbnApplication();

        $this->processApplication($application);

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::OrderFailed->value,
            'order_id' => null,
        ]);
    }

    public function test_it_marks_the_application_as_order_failed_when_nbn_request_fails(): void
    {
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $application = $this->createNbnApplication();

        $this->processApplication($application);

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::OrderFailed->value,
            'order_id' => null,
        ]);
    }

    public function test_it_does_not_retry_the_job(): void
    {
        $application = Application::factory()->create();

        $job = new ProcessNbnApplication($application);

        $this->assertSame(1, $job->tries);
    }

    private function createNbnApplication(array $overrides = []): Application
    {
        return Application::factory()->create([
            'plan_id' => $this->nbnPlan->id,
            'status' => ApplicationStatus::Order,
            ...$overrides,
        ]);
    }

    private function processApplication(Application $application): void
    {
        (new ProcessNbnApplication($application))->handle();
    }

    /**
     * @throws JsonException
     */
    private function fakeNbnResponse(string $stubFile, int $status = 200): void
    {
        Http::fake([
            '*' => Http::response(
                $this->getStubJson($stubFile),
                $status
            ),
        ]);
    }

    /**
     * @throws JsonException
     */
    private function getStubJson(string $file): array
    {
        return json_decode(
            file_get_contents(base_path("tests/stubs/{$file}")),
            true,
            flags: JSON_THROW_ON_ERROR
        );
    }
}
