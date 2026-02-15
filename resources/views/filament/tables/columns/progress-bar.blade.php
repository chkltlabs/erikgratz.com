@php
    $total = $getRecord()->total_spend;
    $actual = $getRecord()->actual_spend;
    $percentage = $total > 0 ? round(($actual / $total) * 100) : 0;

    // Burn Rate Calculation
    $daysInMonth = now()->daysInMonth;
    $currentDay = now()->day;
    $expectedPercentage = round(($currentDay / $daysInMonth) * 100);

    $isOverBurn = $percentage > $expectedPercentage;

    $color = $percentage >= 100 ? 'bg-danger-500' : ($isOverBurn ? 'bg-warning-500' : 'bg-success-500');
@endphp

<div class="space-y-1">
    <div class="flex justify-between items-center text-xs">
        <span class="text-gray-500">{{ $percentage }}% spent</span>
        <span class="text-gray-400">Target: {{ $expectedPercentage }}%</span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700 relative">
        <!-- Expected Progress Marker -->
        <div class="absolute top-0 bottom-0 border-r-2 border-gray-400 z-10" style="left: {{ $expectedPercentage }}%"></div>

        <!-- Actual Progress Bar -->
        <div class="{{ $color }} h-2 rounded-full transition-all" style="width: {{ min(100, $percentage) }}%"></div>
    </div>
    @if($isOverBurn && $percentage < 100)
        <div class="text-[10px] text-warning-600 font-medium">Over daily budget pace</div>
    @endif
</div>
