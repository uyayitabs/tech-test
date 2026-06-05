<?php

namespace App\Jobs;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProcessNbnApplication implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public Application $application) {}

    public function handle(): void
    {
        try {
            $response = Http::post(config('services.nbn.endpoint'), [
                'address_1' => $this->application->address_1,
                'address_2' => $this->application->address_2,
                'city' => $this->application->city,
                'state' => $this->application->state,
                'postcode' => $this->application->postcode,
                'plan_name' => $this->application->plan->name,
            ])->throw()->json();

            if ((Str::lower($response['status']) ?? null) === 'successful' && ! blank($response['id'])) {
                $this->application->update([
                    'order_id' => $response['id'],
                    'status' => ApplicationStatus::Complete,
                ]);

                return;
            }
        } catch (Throwable $e) {
            Log::error("NBN order failed for application_id={$this->application->id}: {$e->getTraceAsString()}");
        }

        $this->application->update(['status' => ApplicationStatus::OrderFailed]);
    }
}
