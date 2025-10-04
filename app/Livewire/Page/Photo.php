<?php

namespace App\Livewire\Page;

use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Component;

#[Layout('livewire.components.layouts.app', ['pageTitle' => self::TITLE])]
class Photo extends Component
{
    public const TITLE = 'Photo';
//    #[Title('Photography')]

    #[Computed]
    public function photos(): array
    {
        return \App\Models\Photo::all()->map(fn ($p) => $p->toArray())->toArray();
    }

    #[Computed]
    public function tags(): array
    {
        $photos = $this->photos();
        $tags = array_unique(array_merge(...array_column($photos, 'tags')));
        return array_merge(['all'], $tags);
    }

    public string $selectedTag = 'all';

    public function filterByTag($tag)
    {
        $this->selectedTag = $tag;
    }

    #[Computed]
    public function filteredPhotos(): array
    {
        if ($this->selectedTag === 'all') {
            return $this->photos();
        }

        return array_filter($this->photos(), function($photo) {
            return in_array($this->selectedTag, $photo['tags']);
        });
    }

    public function render()
    {
        return view('livewire.page.photo');
    }
}
