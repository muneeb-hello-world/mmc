<?php

use Livewire\Volt\Component;
use App\Models\Patient;

use App\Traits\PrintsReceipt;

new class extends Component {
    use PrintsReceipt;
    
    public function runn(){
     $patient = Patient::find(14);
        $services = [
            // ['name' => 'MO', 'charged_price' => 150],
            ['name' => 'Dr. Sadia Sohail', 'charged_price' => 500],
            // ['name' => 'Dr. Tariq', 'charged_price' => 750],
            ['name' => 'Bandage', 'charged_price' => 50],
        ];
        $token = 125;

        $this->printReceipt($patient, $services, $token,0);
    }
}; ?>

<div>
    <flux:button wire:click="runn">Run</flux:button>
</div>