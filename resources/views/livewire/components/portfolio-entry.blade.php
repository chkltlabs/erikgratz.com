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
<div class="flex flex-row items-center justify-between p-3 mb-4">
    @if($this->imgUrl && $index % 2 !== 0)
        <img class="hidden md:block items-end w-1/4" src="{{ $imgUrl }}"/>
    @endif
    <div class="flex-col m-2">
        <p class="text-3xl font-light text-gray-300">
            {{ $title }}
        </p>
        @if($imgUrl)
            <img class="md:hidden w-auto p-4" src="{{ $imgUrl }}" />
        @endif
        <p class="text-gray-300">{{ $text }}</p>
        @if($link && $linkText)
            <a href="{{ $link }}">{{ $linkText }}</a>
        @endif
    </div>
    @if($imgUrl && $index % 2 === 0)
        <img class="hidden md:block items-end w-1/4" src="{{ $imgUrl }}"/>
    @endif
</div>

