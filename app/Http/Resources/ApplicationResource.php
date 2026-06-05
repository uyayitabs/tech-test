<?php

namespace App\Http\Resources;

use App\Enums\ApplicationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_name' => $this->customer->full_name,
            'address' => [
                'address_1' => $this->address_1,
                'address_2' => $this->address_2,
                'city' => $this->city,
                'state' => $this->state,
                'postcode' => $this->postcode,
            ],
            'plan_type' => $this->plan->type->value,
            'plan_name' => $this->plan->name,
            'status' => $this->status->value,
            'plan_monthly_cost' => '$'.number_format($this->plan->monthly_cost / 100, 2),
            'order_id' => $this->status === ApplicationStatus::Complete ? $this->order_id : null,
        ];
    }
}
