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

    /** @var list<array{href?: string, text?: string}> */
    public array $extraLinks = [];

    public function mount(
        $title,
        $text,
        $imgUrl,
        $index,
        $link,
        $linkText,
        array $extraLinks = [],
    ){
        $this->title = $title;
        $this->text = $text;
        $this->imgUrl = $imgUrl;
        $this->index = $index;
        $this->link = $link;
        $this->linkText = $linkText;
        $this->extraLinks = $extraLinks;
    }

    /**
     * @return list<array{href: string, text: string}>
     */
    #[Computed]
    public function entryLinks(): array
    {
        $links = [];

        if (is_string($this->link) && $this->link !== '' && is_string($this->linkText) && $this->linkText !== '') {
            $links[] = ['href' => $this->link, 'text' => $this->linkText];
        }

        foreach ($this->extraLinks as $extraLink) {
            if (! is_array($extraLink)) {
                continue;
            }

            $href = $extraLink['href'] ?? null;
            $text = $extraLink['text'] ?? null;

            if (! is_string($href) || $href === '' || ! is_string($text) || $text === '') {
                continue;
            }

            $links[] = ['href' => $href, 'text' => $text];
        }

        return $links;
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

            @if($this->entryLinks !== [])
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                    @foreach($this->entryLinks as $entryLink)
                        <a href="{{ $entryLink['href'] }}"
                           class="inline-flex items-center text-purple-400 hover:text-purple-300 font-medium text-sm md:text-base transition-colors duration-200 group">
                            {{ $entryLink['text'] }}
                            <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform duration-200"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    @endforeach
                </div>
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



