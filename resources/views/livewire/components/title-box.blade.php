<?php

namespace App\Livewire;

use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new class extends Component
{
    public string $currentSentence = '';
    public int $currentIndex = 0;
    public array $sentences = [
        'Senior Software Engineer',
        'Backend Developer',
        'Self-Educated Programmer',
        'Microservice Architect',
        'Test-Coverage Obsessive',
        'Laravel Disciple',
        'LAMP Artisan',
        'Coffee Obsessive',
        'World Traveler',
        'Rescue Diver',
        'Gym Rat',
        'Distance Hiker'

    ];
    public bool $isDeleting = false;
    public int $charIndex = 0;

    public function placeholder()
    {
        return <<<'HTML'
        <div class="animate-pulse">
            <div class="h-4 bg-gray-300 rounded w-48"></div>
        </div>
        HTML;
    }
}; ?>

<div
    x-data="{
        typingSpeed: 100,
        deletingSpeed: 60,
        pauseBeforeDelete: 4000,
        pauseBeforeNext: 700,

        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        async typeNextChar() {
{{--            console.log('make')--}}
            const currentSentence = $wire.sentences[$wire.currentIndex];
            while ($wire.charIndex < currentSentence.length) {
                await this.sleep(this.typingSpeed);
                $wire.$set('currentSentence', currentSentence.substring(0, $wire.charIndex + 1));
                $wire.$set('charIndex', $wire.charIndex + 1);
            }

            await this.sleep(this.pauseBeforeDelete);
            await this.deleteChars();
        },

        async deleteChars() {
{{--            console.log('delete')--}}
            while ($wire.charIndex > 0) {
                await this.sleep(this.deletingSpeed);
                $wire.$set('charIndex', $wire.charIndex - 1);
                $wire.$set('currentSentence', $wire.sentences[$wire.currentIndex].substring(0, $wire.charIndex));
            }

            $wire.$set('currentIndex', ($wire.currentIndex + 1) % $wire.sentences.length);
            await this.sleep(this.pauseBeforeNext);
            await this.typeNextChar();
        }
    }"
    x-init="typeNextChar()"
    class="font-normal text-gray-300 text-3xl md:text-6xl leading-none mb-8"
>
    <span>{{ $currentSentence }}</span>
    <span class="input-cursor"></span>
</div>

<style>
    @keyframes blink {
        0% { opacity: 1; }
        45% { opacity: 1; }
        55% { opacity: 0; }
        100% { opacity: 0; }
    }

    .input-cursor {
        display: inline-block;
        width: 2px;
        height: 42px;
        background-color: white;
        margin-left: 4px;
        animation: blink .6s linear infinite alternate;
    }

    @media (min-width: 768px) {
        .input-cursor {
            height: 74px;
        }
    }
</style>

