<?php

namespace App\Livewire\Dashboard;

use App\Enums\ReceivableStatus;
use App\Enums\TrafficLight;
use App\Models\AccountReceivable;
use Illuminate\View\View;
use Livewire\Component;

class CollectionSummary extends Component
{
    public function render(): View
    {
        $open = AccountReceivable::query()->open();

        $summary = [
            'total' => (clone $open)->count(),
            'balance' => (clone $open)->sum('balance'),
            'verde' => (clone $open)->where('traffic_light', TrafficLight::Green)->count(),
            'amarillo' => (clone $open)->where('traffic_light', TrafficLight::Yellow)->count(),
            'rojo' => (clone $open)->where('traffic_light', TrafficLight::Red)->count(),
        ];

        return view('livewire.dashboard.collection-summary', ['summary' => $summary]);
    }
}
