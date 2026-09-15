<?php

namespace App\Services;

use App\Models\PricingRule;
use App\Models\Room;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PricingService
{
    public function calculate(Room $room, string $checkIn, string $checkOut): array
    {
        $arrival = CarbonImmutable::parse($checkIn);
        $departure = CarbonImmutable::parse($checkOut);
        $nights = (int) $arrival->diffInDays($departure);

        $rules = PricingRule::query()
            ->where('property_id', $room->property_id)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('room_id')->orWhere('room_id', $room->id))
            ->where('start_date', '<', $checkOut)
            ->where('end_date', '>=', $checkIn)
            ->orderByDesc('priority')
            ->get();

        $minimumStay = $this->minimumStay($rules, $arrival, $departure, $room);

        if ($nights < $minimumStay) {
            throw ValidationException::withMessages([
                'check_out_date' => ["Il soggiorno minimo per le date selezionate è di {$minimumStay} notti."],
            ]);
        }

        $subtotal = '0.00';
        $breakdown = [];

        for ($date = $arrival; $date->lt($departure); $date = $date->addDay()) {
            $rule = $this->ruleForDate($rules, $date, $room);
            $price = (string) ($rule?->price_per_night ?? $room->base_price);
            $subtotal = bcadd($subtotal, $price, 2);
            $breakdown[] = [
                'date' => $date->toDateString(),
                'price' => $price,
                'rule' => $rule?->name,
            ];
        }

        return [
            'nights' => $nights,
            'minimum_stay' => $minimumStay,
            'subtotal' => $subtotal,
            'breakdown' => $breakdown,
        ];
    }

    private function minimumStay(
        Collection $rules,
        CarbonImmutable $arrival,
        CarbonImmutable $departure,
        Room $room,
    ): int {
        $minimum = 1;

        for ($date = $arrival; $date->lt($departure); $date = $date->addDay()) {
            $rule = $this->ruleForDate($rules, $date, $room);
            $minimum = max($minimum, $rule?->minimum_stay ?? 1);
        }

        return $minimum;
    }

    private function ruleForDate(Collection $rules, CarbonImmutable $date, Room $room): ?PricingRule
    {
        return $rules->first(function (PricingRule $rule) use ($date, $room): bool {
            $roomMatches = $rule->room_id === null || $rule->room_id === $room->id;
            $dateMatches = $date->betweenIncluded($rule->start_date, $rule->end_date);
            $daysMatch = $rule->days_of_week === null
                || in_array($date->dayOfWeekIso, $rule->days_of_week, true);

            return $roomMatches && $dateMatches && $daysMatch;
        });
    }
}
