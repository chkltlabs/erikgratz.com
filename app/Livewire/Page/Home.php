<?php

namespace App\Livewire\Page;

use Illuminate\Support\Facades\Mail;
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
        $this->heroImage = asset('images/webp/suit-up.webp');
    }


    // contact form fields
    public $contact_name = '';
    public $contact_email = '';
    public $contact_message = '';

    protected $rules = [
        'contact_name' => 'required|string|min:2|max:100',
        'contact_email' => 'required|email',
        'contact_message' => 'required|string|min:10|max:2000',
    ];

    protected $messages = [
        'contact_name.required' => 'Please tell me your name.',
        'contact_email.required' => 'Please include your email so I can respond.',
        'contact_message.required' => 'Please include a short message.',
    ];

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

    public function submitContact()
    {
        $this->validate();

        // Example: send an email (uncomment and configure mail in .env)
        /*
        Mail::send('emails.contact', [
            'name' => $this->contact_name,
            'email' => $this->contact_email,
            'message' => $this->contact_message,
        ], function ($m) {
            $m->to('your@email.com')
              ->subject('New contact from website');
        });
        */

        // For now just reset the form and flash a message
        $this->reset(['contact_name','contact_email','contact_message']);
        session()->flash('success', 'Thanks — I received your message. I will respond shortly.');
    }

    public function render()
    {
        return view('livewire.page.home');
    }
}
