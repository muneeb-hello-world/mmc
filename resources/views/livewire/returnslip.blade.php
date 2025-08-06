<?php 
use Livewire\Volt\Component;
use App\Models\ServiceTransaction;
use App\Models\LabTestTransaction;
use App\Models\ReturnSlip;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public string $transactionId = '';
    public string $type = 'service'; // or 'lab'
    public string $reason = '';
    public $transaction = null;

    public function findTransaction()
    {
        $this->transaction = null;

        if ($this->type === 'service') {
            $this->transaction = ServiceTransaction::with('patient', 'doctor')
                ->where('id', $this->transactionId)
                ->where('is_returned', false)
                ->first();
        } else {
            $this->transaction = LabTestTransaction::with('patient', 'doctor')
                ->where('id', $this->transactionId)
                ->where('is_returned', false)
                ->first();
        }

        if (!$this->transaction) {
            session()->flash('error', 'Transaction not found or already returned.');
        }
    }

    public function markAsReturned()
    {
        if (!$this->transaction) {
            session()->flash('error', 'No transaction loaded.');
            return;
        }

        $this->transaction->is_returned = true;
        $this->transaction->save();

        ReturnSlip::create([
            'type' => $this->type,
            'transaction_id' => $this->transaction->id,
            'reason' => $this->reason,
            'refunded_by' => Auth::id(),
        ]);

        session()->flash('success', 'Transaction marked as returned.');
        $this->reset(['transactionId', 'reason', 'transaction']);
    }
};
?>
<div class="max-w-xl mx-auto mt-10 p-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg space-y-6">

    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Slip Return</h2>

    @if (session()->has('error'))
        <div class="text-red-600 dark:text-red-400">{{ session('error') }}</div>
    @endif

    @if (session()->has('success'))
        <div class="text-green-600 dark:text-green-400">{{ session('success') }}</div>
    @endif

    <div class="space-y-4">
        <flux:select label="Type" wire:model.live="type">
            <flux:select.option value="service">Service</flux:select.option>
            <flux:select.option value="lab">Lab</flux:select.option>
        </flux:select>

        <flux:input label="Transaction ID" wire:model.live="transactionId" />

        <button wire:click="findTransaction"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
            Find Transaction
        </button>
    </div>

    @if ($transaction)
        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded mt-4">
            <p><strong>Patient:</strong> {{ $transaction->patient->name ?? 'N/A' }}</p>
            <p><strong>Doctor:</strong> {{ $transaction->doctor->name ?? 'N/A' }}</p>
            <p><strong>Amount:</strong>
                Rs. {{ $type == 'service' ? number_format($transaction->price, 2) : number_format($transaction->amount, 2) }}
            </p>
        </div>

        <div class="mt-4 space-y-4">
            <flux:input label="Reason for Return" wire:model.live="reason" />

            <button wire:click="markAsReturned"
                class="px-6 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">
                Mark as Returned
            </button>
        </div>
    @endif
</div>
