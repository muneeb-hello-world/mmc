<?php
use Livewire\Volt\Component;
use App\Models\Expense;
use Carbon\Carbon;
use App\Traits\ShiftGeter;

new class extends Component {
    use ShiftGeter;

    public $selectedDate;
    public $selectedShift;
    public $total;
    public $expenses = [];
    public $amount, $given_by, $given_to, $purpose;

    public function mount()
    {
        $this->selectedDate = Carbon::today()->toDateString();
        $now= now();
        // dd($now);
        $shift = $this->detectCurrentShift($now);    
        // dd($x);
        $this->selectedDate=$shift['date'];
        $this->selectedShift=$shift['shift'];
        $this->getData();

    }

    public function updatedSelectedShift($v)
    {
        $this->getData();
    }
    public function updatedSelectedDate($v)
    {
        $this->getData();
    }

    public function getData()
    {
        $res = $this->getShift($this->selectedDate, $this->selectedShift);
        // dd($res);
        $this->expenses = Expense::whereBetween('created_at', [$res['start'], $res['end']])
            ->orderByDesc('created_at')
            ->get();

        $this->total= $this->expenses->sum('amount');

    }





    public function addExpense()
    {
        $this->validate([
            'amount' => 'required|numeric|min:1',
            'given_by' => 'required|string|max:255',
            'given_to' => 'required|string|max:255',
            'purpose' => 'required|string|max:255',
        ]);

        Expense::create([
            'amount' => $this->amount,
            'given_by' => $this->given_by,
            'given_to' => $this->given_to,
            'purpose' => $this->purpose,
        ]);

        // Reset form
        $this->amount = $this->given_by = $this->given_to = $this->purpose = '';

        // Refresh
        // $this->resetPage();
        session()->flash('success', 'Expense added successfully.');
    }
};
?>
<div class="max-w-4xl mx-auto p-6 space-y-6">

    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Daily Expenses</h2>

    <!-- Flash Message -->
    @if(session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded">
            {{ session('success') }}
        </div>
    @endif

    <!-- Add Expense Form -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow space-y-4">
        <h3 class="text-lg font-semibold text-gray-700 dark:text-white">Add Expense</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:input label="Amount" placeholder="Enter  Amount" type="number" wire:model="amount" />
            <flux:input type="text" placeholder="Name" label="Given By" wire:model="given_by" />
            <flux:input type="text" placeholder="Name" label="Given To" wire:model="given_to" />
            <flux:input type="text" placeholder="Purpose" label="Purpose" wire:model="purpose" />
        </div>

        <div class="flex justify-end">
            <button wire:click="addExpense"
                class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                Add Expense
            </button>
        </div>
    </div>

    <!-- Date Filter -->
    <div class=" flex gap-4 p-4  rounded-xl shadow-lg w-full ">
        <div class=" w-full">
            <flux:input type="date" label="Select Date" wire:model.live="selectedDate" />
        </div>
        <div class=" w-full">
            <flux:select label="Select Shift" wire:model.live="selectedShift">
                <flux:select.option value="m">Morning</flux:select.option>
                <flux:select.option value="e">Evening</flux:select.option>
                <flux:select.option value="n">Night</flux:select.option>
            </flux:select>
        </div>
    </div>

    <!-- Expense Summary -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-300 dark:border-blue-700 rounded-lg p-4">
        <div class="text-lg font-semibold text-blue-800 dark:text-blue-300">
            Total Spent on {{ $selectedDate }}:
            <span class="font-bold text-blue-600 dark:text-blue-200">Rs. {{ number_format($this->total, 2) }}</span>
        </div>
    </div>

    <!-- Expense Table -->

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Amount</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Given By</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Given To</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Purpose</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Time</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                @foreach($expenses as $expense)
                    <tr>
                        <td class="px-4 py-2">Rs. {{ number_format($expense->amount, 2) }}</td>
                        <td class="px-4 py-2">{{ $expense->given_by }}</td>
                        <td class="px-4 py-2">{{ $expense->given_to }}</td>
                        <td class="px-4 py-2">{{ $expense->purpose }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ $expense->created_at->format('h:i A') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="p-4">
        </div>
    </div>
    <div class="text-center py-8 text-gray-600 dark:text-gray-300">
        No expenses found for {{ $selectedDate }}.
    </div>
</div>