<?php

namespace App\Livewire\Page;

use Livewire\Attributes\Title;
use Livewire\Component;
#[Title('Home')]
class Home extends Component
{
    public function render()
    {
        return view('livewire.page.home');
    }
}
