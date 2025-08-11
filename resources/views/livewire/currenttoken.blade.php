<?php

use Livewire\Volt\Component;
use App\Models\CurrentToken;

new class extends Component {
    public $doctorId;
    public $currentToken;

    public function mount()
    {
        $this->currentToken = CurrentToken::where('doctor_id', $this->doctorId)->first();
    }

}; ?>

<div wire:poll.1000ms>
    @if($currentToken)
        <div class="bg-blue-100 p-4 rounded">
            <h2 class="text-xl font-bold">Now Serving</h2>
            <p class="text-4xl font-black text-blue-600">
                0{{ $currentToken->token_number }}
            </p>
            <p class="text-lg">{{ $currentToken->patient_name }}</p>
        </div>
    @else
        <p>No token is currently running.</p>
    @endif
</div>

