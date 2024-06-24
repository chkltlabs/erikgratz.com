<?php

namespace App\Filament\Resources\ActivityResource\Widgets;

use Filament\Widgets\ChartWidget;

class ActivityChart extends ChartWidget
{
    protected static ?string $heading = 'Chart';

    protected function getData(): array
    {
        return [];
    }

    protected function getType(): string
    {
        return 'bubble';
    }
}
