<?php

namespace App\Livewire\Page;

use App\Http\Requests\ContactStoreRequest;
use App\Models\Contact as ContactModel;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.components.layouts.app', ['pageTitle' => self::TITLE])]
class Contact extends Component
{
    public const TITLE = 'Contact';

    public string $contact = '';

    public string $name = '';

    public string $message = '';

    public string $phone = '';

    public string $email = '';

    public string $resumeUrl = '';

    public string $submitButtonText = '';

    public function mount(): void
    {
        $this->phone = (string) config('contact.phone');
        $this->email = (string) config('contact.email');
        $this->resumeUrl = (string) config('contact.resumeUrl');
        $this->submitButtonText = Collection::make([
            'Validate me...',
            'I\'m hungry, feed me words.',
            'I love you.',
            'You matter.',
        ])->random();
    }

    public function submit(): void
    {
        $validated = $this->validate(ContactStoreRequest::rulesArray());
        ContactModel::create($validated);
        $this->reset('contact', 'name', 'message');
        session()->flash('success', 'contact request sent!');
    }

    public function render()
    {
        return view('livewire.page.contact');
    }
}
