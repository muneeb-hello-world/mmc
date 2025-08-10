<?php
use Livewire\Volt\Component;
use App\Models\ServiceTransaction;
use App\Models\LabTestTransaction;
use App\Models\Doctor;
use Carbon\Carbon;
use App\Traits\ShiftGeter;

new class extends Component {
    use ShiftGeter;

    public $selectedDate;
    public $start;
    public $end;
    public $startTime;
    public $endTime;
    public $selectedShift;
    public $selectedDoctor = ''; // Will store doctor_id
    public $transactions = [];
    public $labs;
    public $doctors = [];

    public function mount()
    {
        $shift = $this->detectCurrentShift();

        $this->selectedShift = $shift['shift'];
        $this->selectedDate = $shift['date'];
        $this->start = $shift['start'];
        $this->end = $shift['end'];
        $this->startTime = $shift['start']->toTimeString();
        $this->endTime = $shift['end']->toTimeString();

        // Fetch all doctors for dropdown
        $this->doctors = Doctor::orderBy('name')->get();

        $this->loadTransactions();
    }

    public function updatedSelectedDate()
    {
        $this->loadTransactions();
    }

    public function updatedSelectedShift()
    {
        $shift = $this->getShift($this->selectedDate, $this->selectedShift);

        $this->start = $shift['start'];
        $this->end = $shift['end'];
        $this->startTime = $shift['start']->toTimeString();
        $this->endTime = $shift['end']->toTimeString();

        $this->loadTransactions();
    }

    public function updatedStartTime()
    {
        $this->updateTimeRange();
        $this->loadTransactions();
    }

    public function updatedEndTime()
    {
        $this->updateTimeRange();
        $this->loadTransactions();
    }

    public function updatedSelectedDoctor()
    {
        $this->loadTransactions();
    }

    private function updateTimeRange()
    {
        $this->start = Carbon::parse($this->selectedDate . ' ' . $this->startTime)->startOfMinute();
        $this->end = Carbon::parse($this->selectedDate . ' ' . $this->endTime)->startOfMinute();
    }

    public function loadTransactions()
    {
        $serviceQuery = ServiceTransaction::with(['patient', 'doctor', 'service'])
            ->whereBetween('created_at', [$this->start, $this->end]);

        $labQuery = LabTestTransaction::with(['patient', 'doctor', 'labTest'])
            ->whereBetween('created_at', [$this->start, $this->end]);

        if ($this->selectedDoctor) {
            $serviceQuery->where('doctor_id', $this->selectedDoctor);
            $labQuery->where('doctor_id', $this->selectedDoctor);
        }

        $this->transactions = $serviceQuery->orderBy('created_at', 'desc')->get();
        $this->labs = $labQuery->orderBy('created_at', 'desc')->get();
    }

    public function renderStatusBadge($isReturned)
    {
        if ($isReturned) {
            return '<span class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium bg-red-100 text-red-800 rounded-full dark:bg-red-500/10 dark:text-red-500">
                <svg class="size-2.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                    fill="currentColor" viewBox="0 0 16 16">
                    <path
                        d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
                </svg>
                Danger
            </span>';
        }

        return '<span class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
            <svg class="size-2.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                fill="currentColor" viewBox="0 0 16 16">
                <path
                    d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
            </svg>
            Active
        </span>';
    }

    public function getServiceTotalProperty()
    {
        return $this->transactions->sum('price');
    }

    public function getLabTotalProperty()
    {
        return $this->labs->sum('amount');
    }
};
?>


<div class="max-w-6xl mx-auto py-8 px-6 space-y-6 bg-white dark:bg-gray-800 rounded-lg shadow">

    <div class="flex gap-4">

        <div class="w-full">
            <flux:select label="Filter by Doctor" wire:model.live="selectedDoctor">
                <flux:select.option value="">All Doctors</flux:select.option>
                @foreach($doctors as $doc)
                    <flux:select.option value="{{ $doc->id }}">{{ $doc->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full">
            <flux:input type="date" label="Select Date" wire:model.live="selectedDate" />
        </div>

        <div class="w-full">
            <flux:select label="Select Shift" wire:model.live="selectedShift">
                <flux:select.option value="m">Morning</flux:select.option>
                <flux:select.option value="e">Evening</flux:select.option>
                <flux:select.option value="n">Night</flux:select.option>
            </flux:select>
        </div>

        <div class="w-full">
            <flux:input type="time" label="Start Time" wire:model.live="startTime" />
        </div>

        <div class="w-full">
            <flux:input type="time" label="End Time" wire:model.live="endTime" />
        </div>
    </div>
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Service Transactions</h2>

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
                    <th class="px-4 py-2">Is Returned</th>
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
                        <td class="px-4 py-2">
                            @if ($tx->is_returned)
                                <div class="px-6 py-3">
                                    <span
                                        class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium bg-red-100 text-red-800 rounded-full dark:bg-red-500/10 dark:text-red-500">
                                        <svg class="size-2.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            fill="currentColor" viewBox="0 0 16 16">
                                            <path
                                                d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
                                        </svg>
                                        Danger
                                    </span>
                                </div>
                            @else
                                <div class="px-6 py-3">
                                    <span
                                        class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
                                        <svg class="size-2.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            fill="currentColor" viewBox="0 0 16 16">
                                            <path
                                                d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                        </svg>
                                        Active
                                    </span>
                                </div>
                            @endif
                        </td>
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
        <div class="mt-4 text-right font-semibold text-lg text-gray-900 dark:text-white">
            Total: Rs. {{ number_format($this->serviceTotal, 2) }}
        </div>
    </div>
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Labs Transactions</h2>

    <div class="overflow-x-auto">
        <table class="w-full mt-6 border dark:border-gray-700 rounded-lg">
            <thead class="bg-gray-100 dark:bg-gray-700 text-sm text-left text-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-2">Txn ID</th>
                    <th class="px-4 py-2">Patient</th>
                    <th class="px-4 py-2">Test</th>
                    <th class="px-4 py-2">Doctor</th>
                    <th class="px-4 py-2 text-right">Price</th>
                    <th class="px-4 py-2">Is Returned</th>
                    <th class="px-4 py-2">Created At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-600 text-sm">
                @forelse($labs as $tx)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-2">{{ $tx->id }}</td>
                        <td class="px-4 py-2">{{ $tx->patient->name ?? 'N/A' }}</td>
                        <td class="px-4 py-2">{{ $tx->labTest->name ?? 'N/A' }}</td>
                        <td class="px-4 py-2">{{ $tx->doctor->name ?? 'N/A' }}</td>
                        <td class="px-4 py-2 text-right">Rs. {{ number_format($tx->amount, 2) }}</td>
                        <td class="px-4 py-2">
                            @if ($tx->is_returned)
                                <div class="px-6 py-3">
                                    <span
                                        class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium bg-red-100 text-red-800 rounded-full dark:bg-red-500/10 dark:text-red-500">
                                        <svg class="size-2.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            fill="currentColor" viewBox="0 0 16 16">
                                            <path
                                                d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
                                        </svg>
                                        Danger
                                    </span>
                                </div>
                            @else
                                <div class="px-6 py-3">
                                    <span
                                        class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
                                        <svg class="size-2.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            fill="currentColor" viewBox="0 0 16 16">
                                            <path
                                                d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                        </svg>
                                        Active
                                    </span>
                                </div>
                            @endif
                        </td>
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
        <div class="mt-4 text-right font-semibold text-lg text-gray-900 dark:text-white">
            Total: Rs. {{ number_format($this->labTotal, 2) }}
        </div>
    </div>
</div>