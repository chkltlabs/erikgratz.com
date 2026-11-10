<?php

namespace App\Livewire\Page;

use App\Content\WorkHistory;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.components.layouts.app', ['pageTitle' => self::TITLE])]
class Home extends Component
{
    public $name = 'Erik Gratz';

    public const TITLE = 'Home';

    public $siteTitle = 'Erik Gratz';

    public $heroSub = 'I have a passion for computers. I create solutions to modern web problems.';

    public $cta = 'Schedule a Call';

    public $heroImage;

    public function mount(): void
    {
        $this->heroImage = asset('images/webp/github-avatar.webp');
    }

    /**
     * @return list<array{company: string, location: string, title: string, timeframe: string, bullets: list<string>, technologies: list<string>}>
     */
    #[Computed]
    public function jerbs(): array
    {
        return app(WorkHistory::class)->roles();
    }

    public function render()
    {
        return view('livewire.page.home');
    }
}
