<?php

use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Volt\Component;
use App\Livewire\Page;

new class extends Component {
    public string $company;
    public string $location;
    public string $title;
    public string $timeframe;
    public array $bullets;
    public array $technologies;


    public function mount(
        $company,
        $location,
        $title,
        $timeframe,
        $bullets,
        $technologies,
    ){
        $this->company = $company;
        $this->location = $location;
        $this->title = $title;
        $this->timeframe = $timeframe;
        $this->bullets = $bullets;
        $this->technologies = $technologies;
    }
}
?>
{{--<div class="flex flex-row items-center justify-between p-3 mb-4">--}}
{{--    <div class="flex-col m-2">--}}
{{--        <p class="text-3xl font-light text-gray-100">--}}
{{--            {{ $company }}--}}
{{--        </p>--}}
{{--        <p class="text-gray-300">--}}
{{--            {{ $location }}--}}
{{--        </p>--}}
{{--    </div>--}}
{{--</div>--}}
<div class="bg-gray-800 rounded-lg p-4 md:p-6 mb-6 shadow-lg">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4">
        <div class="mb-2 md:mb-0">
            <h3 class="text-xl md:text-2xl font-bold text-purple-400">{{ $title }}</h3>
            <p class="text-lg md:text-xl text-gray-300 font-semibold">{{ $company }}</p>
        </div>
        <div class="text-sm md:text-base text-gray-400 md:text-right">
            <p class="font-medium">{{ $timeframe }}</p>
            <p>{{ $location }}</p>
        </div>
    </div>

    <!-- Bullets Section -->
    @if(!empty($bullets))
        <div class="mb-4">
            <ul class="space-y-2">
                @foreach($bullets as $bullet)
                    <li class="flex items-start">
                        <span class="text-purple-400 mr-2 mt-1 flex-shrink-0">•</span>
                        <span class="text-gray-300 text-sm md:text-base leading-relaxed">{{ $bullet }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Technologies Section -->
    @if(!empty($technologies))
        <div class="border-t border-gray-700 pt-4">
            <h4 class="text-sm font-semibold text-gray-400 mb-2 uppercase tracking-wide">Technologies</h4>
            <div class="flex flex-wrap gap-2">
                @foreach($technologies as $tech)
                    <span class="bg-purple-900 bg-opacity-30 text-purple-300 px-2 py-1 rounded-md text-xs md:text-sm font-medium border border-purple-700">
                        {{ $tech }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</div>
