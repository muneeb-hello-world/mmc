<?php
use Livewire\Volt\Component;
use App\Models\ServiceTransaction;
use Carbon\Carbon;

new class extends Component {
    public $date;
    public $startTime;
    public $endTime;
    public $transactions = [];

    public function mount()
    {
        $now = Carbon::now();
        $this->date = $now->toDateString();
        $this->startTime = '00:00';
        $this->endTime = '23:59';
        $this->loadTransactions();
    }

    public function updatedDate()
    {
        $this->loadTransactions();
    }

    public function updatedStartTime()
    {
        $this->loadTransactions();
    }

    public function updatedEndTime()
    {
        $this->loadTransactions();
    }

    public function loadTransactions()
    {
        $start = Carbon::parse($this->date . ' ' . $this->startTime)->startOfMinute();
        $end = Carbon::parse($this->date . ' ' . $this->endTime)->endOfMinute();

        $this->transactions = ServiceTransaction::with(['patient', 'doctor', 'service'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'desc')
            ->get();
    }
};
?>

<div class="max-w-6xl mx-auto py-8 px-6 space-y-6 bg-white dark:bg-gray-800 rounded-lg shadow">

    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Service Transactions</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <flux:input type="date" label="Select Date" wire:model.live="date" />

        <flux:input type="time" label="Start Time" wire:model.live="startTime" />

        <flux:input type="time" label="End Time" wire:model.live="endTime" />
    </div>

    <div class="overflow-x-auto">
        <table class="w-full mt-6 border dark:border-gray-700 rounded-lg">
            <thead class="bg-gray-100 dark:bg-gray-700 text-sm text-left text-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-2">Txn ID</th>
                    <th class="px-4 py-2">Patient</th>
                    <th class="px-4 py-2">Service</th>
                    <th class="px-4 py-2">Doctor</th>
                    <th class="px-4 py-2">Token</th>
                    <th class="px-4 py-2 text-right">Price</th>
                    <th class="px-4 py-2">Created At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-600 text-sm">
                @forelse($transactions as $tx)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-2">{{ $tx->id }}</td>
                        <td class="px-4 py-2">{{ $tx->patient->name ?? 'N/A' }}</td>
                        <td class="px-4 py-2">{{ $tx->service->name ?? 'N/A' }}</td>
                        <td class="px-4 py-2">{{ $tx->doctor->name ?? 'N/A' }}</td>
                        <td class="px-4 py-2">{{ $tx->token ?? '-' }}</td>
                        <td class="px-4 py-2 text-right">Rs. {{ number_format($tx->price, 2) }}</td>
                        <td class="px-4 py-2">{{ $tx->created_at->format('d M Y h:i A') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-gray-500 dark:text-gray-400">
                            No transactions found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
