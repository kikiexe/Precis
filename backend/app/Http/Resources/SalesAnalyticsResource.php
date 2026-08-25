<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesAnalyticsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'period' => $this->resource['period'],
            'period_label' => $this->resource['period_label'],
            'total_revenue' => (int) $this->resource['total_revenue'],
            'total_orders' => (int) $this->resource['total_orders'],
            'average_order_value' => (int) $this->resource['average_order_value'],
            'growth_percent' => (float) $this->resource['growth_percent'],
            'growth_label' => $this->resource['growth_label'],
            'gross_sales' => (int) $this->resource['gross_sales'],
            'total_discount' => (int) $this->resource['total_discount'],
            'net_revenue' => (int) $this->resource['net_revenue'],
            'breakdown' => $this->resource['breakdown'],
            'top_products' => $this->resource['top_products'],
            'category_breakdown' => $this->resource['category_breakdown'],
            'payment_methods' => $this->resource['payment_methods'],
        ];
    }
}
