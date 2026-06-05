<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListApplicationsRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListApplicationsController extends Controller
{
    public function __invoke(ListApplicationsRequest $request): AnonymousResourceCollection
    {
        $planType = $request->validated('type');

        $applications = Application::with(['customer', 'plan'])
            ->when($planType, fn ($planTypeQuery) => $planTypeQuery->whereHas('plan', fn ($planQuery) => $planQuery->where('type', $planType)))
            ->oldest()
            ->paginate();

        return ApplicationResource::collection($applications);
    }
}
