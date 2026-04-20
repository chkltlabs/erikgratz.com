<?php

namespace App\Livewire\Page;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.components.layouts.app', ['pageTitle' => self::TITLE])]
class Play extends Component
{
    public const TITLE = 'Playground';

    public function mount(): void
    {
        abort_unless(config('app.playground_enabled'), 404);
    }

    public function render()
    {
        return view('livewire.page.play');
    }
}
