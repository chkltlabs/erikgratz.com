<?php

use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Volt\Component;
use App\Livewire\Page;

new class extends Component {
    public mixed $title = null;
    public mixed $text = null;
    public mixed $imgUrl = null;
    public mixed $index = null;
    public mixed $link = null;
    public mixed $linkText = null;


    public function mount(
        $title,
        $text,
        $imgUrl,
        $index,
        $link,
        $linkText,
    ){
        $this->title = $title;
        $this->text = $text;
        $this->imgUrl = $imgUrl;
        $this->index = $index;
        $this->link = $link;
        $this->linkTex = $linkText;
    }
}
?>

<div class="bg-gray-800 rounded-lg p-4 md:p-6 mb-6 shadow-lg">
    <div class="flex flex-col md:flex-row md:items-center md:gap-6">
        <!-- Image on left for odd indices (desktop only) -->
        @if($imgUrl && $index % 2 !== 0)
            <div class="hidden md:block md:w-1/6 flex-shrink-0">
                <img class="w-full h-auto rounded-lg shadow-md" src="{{ $imgUrl }}" alt="{{ $title }}"/>
            </div>
        @endif

        <!-- Content section -->
        <div class="flex-1">
            <!-- Title -->
            <h3 class="text-xl md:text-2xl font-bold text-purple-400 mb-3">{{ $title }}</h3>

            <!-- Mobile image (shown on all mobile entries) -->
            @if($imgUrl)
                <div class="md:hidden mb-4">
                    <img class="w-1/2 h-auto rounded-lg shadow-md mx-auto" src="{{ $imgUrl }}" alt="{{ $title }}"/>
                </div>
            @endif

            <!-- Description text -->
            <p class="text-gray-300 text-sm md:text-base leading-relaxed mb-4">{{ $text }}</p>

            <!-- Link -->
            @if($link && $linkText)
                <a href="{{ $link }}"
                   class="inline-flex items-center text-purple-400 hover:text-purple-300 font-medium text-sm md:text-base transition-colors duration-200 group">
                    {{ $linkText }}
                    <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform duration-200"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
            @endif
        </div>

        <!-- Image on right for even indices (desktop only) -->
        @if($imgUrl && $index % 2 === 0)
            <div class="hidden md:block md:w-1/6 flex-shrink-0">
                <img class="w-full h-auto rounded-lg shadow-md" src="{{ $imgUrl }}" alt="{{ $title }}"/>
            </div>
        @endif
    </div>
</div>



