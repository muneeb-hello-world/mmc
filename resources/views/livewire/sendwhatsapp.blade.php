<?php

use Livewire\Volt\Component;
use Twilio\Rest\Client;

new class extends Component {
    public $toNumber = '+923084447764';
    public $date = '10 August';
    public $time = '7:45 PM';

    public function sendWhatsApp()
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $twilio = new Client($sid, $token);

        try {
            $message = $twilio->messages->create(
                "whatsapp:{$this->toNumber}", // To
                [
                    "from" => "whatsapp:+14155238886", // Twilio Sandbox WhatsApp number
                    "contentSid" => "HXb5b62575e6e4ff6129ad7c8efe1f983e", // your Twilio content template SID
                    "contentVariables" => json_encode([
                        "1" => $this->date,
                        "2" => $this->time
                    ]),
                ]
            );

            session()->flash('success', 'Message sent! SID: ' . $message->sid);
        } catch (\Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }
}; ?>

<div>
    <div class="p-4">
        @if (session('success'))
            <div class="p-2 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="p-2 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
        @endif

        <div class="space-y-2">
            <input type="text" wire:model="toNumber" placeholder="Recipient number e.g. +92308xxxxxxx"
                class="border p-2 rounded w-full">
            <input type="text" wire:model="date" placeholder="Appointment date" class="border p-2 rounded w-full">
            <input type="text" wire:model="time" placeholder="Appointment time" class="border p-2 rounded w-full">
            <button wire:click="sendWhatsApp" class="bg-blue-500 text-white px-4 py-2 rounded">Send WhatsApp</button>
        </div>
    </div>
</div>