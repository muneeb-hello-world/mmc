<?php

use Livewire\Volt\Component;
use Carbon\Carbon;
use App\Models\Doctor;
use App\Models\ServiceTransaction;
use App\Models\CurrentToken;



new class extends Component {

    public $doctors;
    public $selectedDocId;
    public $step = 1;
    public $token;


    public function mount()
    {
        $this->doctors = Doctor::whereHas('serviceTransactions', function ($query) {
            $query->whereDate('created_at', Carbon::today());
        })->get();


    }



    public function getFirst($id)
    {
        $this->selectedDocId = $id;

        $token = ServiceTransaction::with(['patient', 'service', 'doctor'])
            ->where('doctor_id', $this->selectedDocId)
            ->whereDate('created_at', Carbon::today())
            ->orderBy('token', 'asc')
            ->first();

        if ($token) {
            $this->token = $token;
            $this->e($token);
            $this->step = 2;
        }
        // dd($firstTransaction);
    }

    public function getNextToken()
    {
        if (!$this->token) {
            session()->flash('error', 'No current token selected.');
            return;
        }

        $next = ServiceTransaction::with(['patient', 'service', 'doctor'])
            ->where('doctor_id', $this->selectedDocId)
            ->whereDate('created_at', Carbon::today())
            ->where('token', '>', $this->token->token)
            ->orderBy('token', 'asc')
            ->first();

        if ($next) {
            $this->token = $next;
            $this->e($next);

        } else {
            session()->flash('error', 'No next token found.');
        }
    }

    public function getPreviousToken()
    {
        if (!$this->token) {
            session()->flash('error', 'No current token selected.');
            return;
        }

        $prev = ServiceTransaction::with(['patient', 'service', 'doctor'])
            ->where('doctor_id', $this->selectedDocId)
            ->whereDate('created_at', Carbon::today())
            ->where('token', '<', $this->token->token)
            ->orderBy('token', 'desc')
            ->first();

        if ($prev) {
            $this->token = $prev;
            $this->e($prev);
        } else {
            session()->flash('error', 'No previous token found.');
        }
    }

    public function e($e)
    {
        CurrentToken::updateOrCreate(
        ['doctor_id' => $e->doctor_id], // Match doctor
        [
            'token_number' => $e->token,
            'patient_name' => $e->patient->name
        ]
    );

        $this->dispatch('announce-token', [
            'name' => $e->patient->name,
            'token' => $e->token
        ]);
    }







};

?>

<div class="">
    <livewire:currenttoken/>

    {{-- @foreach($doctors as $item)
    <div wire:click="getFirst({{ $item->id }})">{{ $item->name }}</div>
    @endforeach --}}

    <div class="bg-gray-50 dark:bg-gray-900 rounded-xl  h-full p-8">
        <div class=" w-full flex  items-center justify-center">

            <flux:navbar>
                <flux:navbar.item wire:click.prevent="$set('step', 1)">Doctors</flux:navbar.item>
                <span>-></span>
                <flux:navbar.item wire:click.prevent="$set('step', 2)">Token Nmber</flux:navbar.item>
            </flux:navbar>
        </div>
        @if ($step == 1)


            <div class="max-w-4xl mx-auto">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 text-center dark:text-white mb-2">Select Your Doctor</h1>
                    <p class="text-gray-600 dark:text-gray-300 text-center">Choose from our experienced medical
                        professionals</p>
                </div>

                <!-- Doctor Cards Grid -->
                @php
                    $colorSets = [
                        ['bg-blue-100 dark:bg-blue-900', 'text-blue-600 dark:text-blue-400'],
                        ['bg-green-100 dark:bg-green-900', 'text-green-600 dark:text-green-400'],
                        ['bg-purple-100 dark:bg-purple-900', 'text-purple-600 dark:text-purple-400'],
                        ['bg-orange-100 dark:bg-orange-900', 'text-orange-600 dark:text-orange-400'],
                        ['bg-red-100 dark:bg-red-900', 'text-red-600 dark:text-red-400'],
                        ['bg-indigo-100 dark:bg-indigo-900', 'text-indigo-600 dark:text-indigo-400'],
                    ];
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-2  gap-6">
                    @foreach ($doctors as $doc)
                        @php
                            $colors = $colorSets[$loop->index % count($colorSets)];
                        @endphp

                        <div wire:click="getFirst({{ $doc->id }})"
                            class="bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg 
                                                                               transition-shadow duration-300 cursor-pointer border 
                                                                               border-gray-200 dark:border-gray-700 
                                                                               hover:border-{{ str_replace('text-', '', explode(' ', $colors[1])[0]) }}-300 
                                                                               dark:hover:border-{{ str_replace('text-', '', explode(' ', $colors[1])[0]) }}-500">
                            <div class="p-6">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="w-16 h-16 {{ $colors[0] }} rounded-full flex items-center justify-center">
                                        <svg class="w-8 h-8 {{ $colors[1] }}" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 
                                                                                                 00-7 7h14a7 7 0 00-7-7z">
                                            </path>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $doc->name }}
                                        </h3>
                                        <p class="{{ $colors[1] }} text-sm font-medium">
                                            {{ $doc->specialization ?? 'Specialist' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>



            </div>
        @elseif($step == 2)



            <div class="bg-gray-100 dark:bg-gray-900  flex items-center justify-center p-4">
                <div class="max-w-2xl w-full">
                    <!-- Token Display Card -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 text-center border-2 border-gray-200 dark:border-gray-700">
                        <!-- Header -->
                        <div class="mb-8">
                            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-2">Now Calling</h1>
                            <div class="w-20 h-1 bg-blue-500 mx-auto rounded"></div>
                        </div>

                        <!-- Token Number -->
                        <div class="mb-6">
                            <div class="text-8xl font-black text-blue-600 dark:text-blue-400 mb-2" id="tokenNumber">
                                0{{ $token->token }}
                            </div>
                            <div class="text-lg text-gray-500 dark:text-gray-400 font-medium">Token Number</div>
                        </div>

                        <!-- Patient Name -->
                        <div class="mb-8">
                            <div class="text-3xl font-bold text-gray-800 dark:text-gray-200 mb-2" id="patientName">
                                {{ $token->patient->name }}
                            </div>
                            <div class="text-lg text-gray-500 dark:text-gray-400 font-medium">Patient Name</div>
                        </div>

                        <!-- Navigation Arrows -->
                        <div class="flex justify-center items-center gap-8">
                            <button id="prevBtn" wire:click="getPreviousToken"
                                class="bg-gray-500 hover:bg-gray-600 dark:bg-gray-600 dark:hover:bg-gray-700 text-white p-4 rounded-full shadow-lg transition-all duration-200 hover:scale-110 focus:outline-none focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>

                            <div class="text-sm text-gray-500 dark:text-gray-400 font-medium">
                                <span id="currentIndex">1</span> of <span id="totalTokens">5</span>
                            </div>

                            <button id="nextBtn" wire:click="getNextToken"
                                class="bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white p-4 rounded-full shadow-lg transition-all duration-200 hover:scale-110 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>


                </div>
                @script
                <script>
                     let voices = [];
                    function loadVoices() {
                        voices = speechSynthesis.getVoices();
                    }
                    if (typeof speechSynthesis !== "undefined") {
                        loadVoices();
                        speechSynthesis.onvoiceschanged = loadVoices;
                    }
                      $wire.on('announce-token', (event) => {
                        // console.log(event[0].name);
                        const { name, token } = event[0];
                        const announcement = `Token number ${token}, patient ${name}`;
                        const utterance = new SpeechSynthesisUtterance(announcement);
                        const femaleVoice = voices.find(v =>
                            v.name.includes("Zira") ||
                            v.name.includes("Heera") ||
                            v.name.includes("Google UK English Female")
                        ) || voices[0];
                        if (femaleVoice) utterance.voice = femaleVoice;
                        speechSynthesis.speak(utterance);
                    });
                </script>
                @endscript

                <script>
                   
                </script>
            </div>


        @endif
    </div>
</div>