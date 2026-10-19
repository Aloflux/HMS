<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\Service;
use App\Models\ServicePrice;
use App\Models\Visit;

class PriceBook
{
    public function forService(Service $service, Visit $visit): int
    {
        if ($visit->insurance_scheme_id) {
            $amount = ServicePrice::query()
                ->where('service_id', $service->id)
                ->where('insurance_scheme_id', $visit->insurance_scheme_id)
                ->value('amount');

            if ($amount !== null) {
                return (int) $amount;
            }
        }

        return (int) $service->cash_price;
    }

    public function forItem(Item $item, Visit $visit): int
    {
        if ($visit->insurance_scheme_id) {
            $amount = ItemPrice::query()
                ->where('item_id', $item->id)
                ->where('insurance_scheme_id', $visit->insurance_scheme_id)
                ->value('amount');

            if ($amount !== null) {
                return (int) $amount;
            }
        }

        return (int) $item->cash_price;
    }
}
