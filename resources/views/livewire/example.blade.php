<?php

use Livewire\Volt\Component;

new class extends Component {
    public string $disgrace = 'you';
}; ?>

<div @class($disgrace)>
    {{$disgrace}}
</div>
