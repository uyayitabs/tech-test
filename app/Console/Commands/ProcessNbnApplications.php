<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStatus;
use App\Enums\PlanType;
use App\Jobs\ProcessNbnApplication;
use App\Models\Application;
use Illuminate\Console\Command;

class ProcessNbnApplications extends Command
{
    protected $signature = 'applications:process-nbn';

    protected $description = 'Dispatch queued jobs to process pending NBN applications';

    public function handle(): void
    {
        // Note: Duplicate-dispatch risk
        Application::with('plan')
            ->whereHas('plan', fn ($planQuery) => $planQuery->where('type', PlanType::Nbn))
            ->where('status', ApplicationStatus::Order)
            ->get()
            ->each(fn ($application) => ProcessNbnApplication::dispatch($application));
    }
}
