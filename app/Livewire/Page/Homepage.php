<?php

namespace App\Livewire\Page;

use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class Homepage extends Component
{
    public $name = 'Erik Gratz';
    public $tagline = "I have a passion for computers. I create solutions to modern web problems.";
    public $intro = "Welcome. I'm Erik, thanks for visiting.";
    public $cta = "Schedule a Call";

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
        return view('livewire.page.homepage');
    }
}
