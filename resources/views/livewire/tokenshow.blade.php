<?php

use Livewire\Volt\Component;
use App\Models\Doctor;
use App\Models\ServiceTransaction;

new class extends Component {
    public $doctors = [];
    public $selectedDoctor = null;
    public $currentToken = null;

    public function mount()
    {
        $this->doctors = Doctor::orderBy('name')->get();
    }

    public function selectDoctor($doctorId)
    {
        $this->selectedDoctor = $doctorId;
        $this->fetchFirstToken();
    }

    public function fetchFirstToken()
    {
        $this->currentToken = ServiceTransaction::with('patient')
            ->where('service_id', 13)
            ->where('doctor_id', $this->selectedDoctor)
            ->where('booking', true)
            ->where('arrived', false)
            ->orderBy('created_at')
            ->first();

        // Trigger announcement in browser
        if ($this->currentToken) {
            $this->dispatchBrowserEvent('announce-token', [
                'name' => $this->currentToken->patient->name,
                'token' => $this->currentToken->token
            ]);
        }
    }

    public function nextToken()
    {
        if (!$this->currentToken) {
            $this->fetchFirstToken();
            return;
        }

        $this->currentToken = ServiceTransaction::with('patient')
            ->where('service_id', 13)
            ->where('doctor_id', $this->selectedDoctor)
            ->where('booking', true)
            ->where('arrived', false)
            ->where('id', '>', $this->currentToken->id)
            ->orderBy('id')
            ->first();

        if ($this->currentToken) {
            $this->dispatchBrowserEvent('announce-token', [
                'name' => $this->currentToken->patient->name,
                'token' => $this->currentToken->token
            ]);
        }
    }

};


?>
<div>
    <div class="p-4 space-y-4">
        {{-- Doctor list --}}
        <div class="flex flex-wrap gap-2">
            @foreach($doctors as $doc)
                <button wire:click="selectDoctor({{ $doc->id }})" class="px-4 py-2 rounded-lg border 
                           {{ $selectedDoctor == $doc->id ? 'bg-blue-500 text-white' : 'bg-gray-100' }}">
                    {{ $doc->name }}
                </button>
            @endforeach
        </div>

        {{-- Token display --}}
        <div class="mt-4">
            @if($currentToken)
                <div class="p-4 border rounded-lg bg-green-100 text-lg">
                    <p><strong>Patient:</strong> {{ $currentToken->patient->name }}</p>
                    <p><strong>Token:</strong> {{ $currentToken->token }}</p>
                </div>

                <button wire:click="nextToken" class="mt-3 px-4 py-2 bg-blue-500 text-white rounded-lg">
                    Next Token
                </button>
            @elseif($selectedDoctor)
                <p class="text-gray-500">No pending token found for this doctor.</p>
            @endif
        </div>
    </div>

    {{-- Speech announcement --}}
    <script>
        window.addEventListener('announce-token', event => {
            const { name, token } = event.detail;
            const message = `Token number ${token}, patient ${name}`;
            const utterance = new SpeechSynthesisUtterance(message);
            utterance.lang = 'en-US';
            speechSynthesis.speak(utterance);
        });
    </script>


</div>