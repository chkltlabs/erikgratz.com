<?php

namespace App\Livewire;

use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new class extends Component
{
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
}; ?>

<div
    x-data="{
        typingSpeed: 100,
        deletingSpeed: 60,
        pauseBeforeDelete: 4000,
        pauseBeforeNext: 1500,

        currentSentenceJson: '',

        async typeThings() {
            let textOptions = $wire.sentences
            let i = 0
            await this.sleep(this.pauseBeforeDelete)
            while (i < textOptions.length) {
                await this.deleteSentence(this.deletingSpeed)
                await this.sleep(this.pauseBeforeNext)
                await this.typeSentence(textOptions[i], this.typingSpeed)
                await this.sleep(this.pauseBeforeDelete)
                i++
                if (i === textOptions.length) {
                    i = 0
                }
            }
        },

        async typeSentence(sentence, delay = 100) {
            const letters = sentence.split('')
            let i = this.currentSentenceJson.length;
            while (i < letters.length) {
                await this.sleep(delay)
                this.currentSentenceJson += letters[i]
                i++
            }
        },
        async deleteSentence(delay = 100) {
            while (this.currentSentenceJson.length > 0) {
                this.currentSentenceJson = this.currentSentenceJson.slice(0, -1)
                await this.sleep(delay)
            }
        },

        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }
    }"
    x-init="typeThings"
    class="font-normal text-gray-300 text-3xl md:text-6xl leading-none mb-8"
>
    <span class="font-mono" x-text="currentSentenceJson"></span>
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

