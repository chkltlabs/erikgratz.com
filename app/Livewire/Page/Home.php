<?php

namespace App\Livewire\Page;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.components.layouts.app', ['pageTitle' => self::TITLE])]
class Home extends Component
{
    public $name = 'Erik Gratz';

    public const TITLE = 'Home';
    public $siteTitle = 'Erik Gratz';
    public $heroHeadline = "I build small, useful tools for the web.";
    public $heroSub = "I have a passion for computers. I create solutions to modern web problems.";
    public $cta = "Schedule a Call";

    public $heroImage;

    public function mount(): void
    {
        $this->heroImage = asset('images/webp/github-avatar.webp');
    }

    #[Computed]
    public function jerbs()
    {
        return [
            [
                'company' => 'Pocketnest',
                'location' => 'Detroit, MI -> Fully Remote',
                'title' => 'Senior Software Engineer, Backend',
                'timeframe' => '2022-' . now()->year
            ],
            [
                'company' => 'Sonic Boom Wellness',
                'location' => 'San Diego, CA -> Fully Remote',
                'title' => 'Backend Developer',
                'timeframe' => '2020-2022',
            ],
            [
                'company' => 'Internet Things -> The Media Lab',
                'location' => 'San Diego, CA',
                'title' => 'PHP Developer -> Lead PHP Developer',
                'timeframe' => '2019-2020',
            ]
        ];
    }

    public function render()
    {
        return view('livewire.page.home');
    }
}
