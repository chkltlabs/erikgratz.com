<?php

namespace App\Livewire\Page;

use App\Content\WorkHistory;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.components.layouts.app', ['pageTitle' => self::TITLE])]
class Experience extends Component
{
    public const TITLE = 'Experience';

    #[Computed]
    public function experience(): array
    {
        return app(WorkHistory::class)->roles();
    }

    public function render()
    {
        return view('livewire.page.experience');
    }
}
