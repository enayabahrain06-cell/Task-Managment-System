<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Setting;
use Illuminate\Support\Collection;

trait OrdersTeamMembers
{
    /**
     * Apply the admin-defined About Page display order, appending any members
     * not yet in the saved order (new hires) at the end in their default sort.
     */
    protected function applyTeamOrder(Collection $members): Collection
    {
        $order = json_decode(Setting::get('about_page_team_order', '[]'), true) ?: [];

        if (! $order) {
            return $members;
        }

        $ordered = collect($order)
            ->map(fn ($id) => $members->firstWhere('id', $id))
            ->filter()
            ->values();

        $remaining = $members->reject(fn ($m) => in_array($m->id, $order))->values();

        return $ordered->merge($remaining)->values();
    }
}
