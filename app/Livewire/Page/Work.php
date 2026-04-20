<?php

namespace App\Livewire\Page;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.components.layouts.app', ['pageTitle' => self::TITLE])]
class Work extends Component
{
    public const TITLE = 'Work';

    #[Computed]
    public function portfolioItems() :array
    {
        return config('portfolio');
    }
    public function render()
    {
        return view('livewire.page.work');
    }
}
