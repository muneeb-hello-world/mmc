<?php

use Livewire\Volt\Component;
use App\Models\ServiceTransaction;
use App\Models\LabTestTransaction;
use App\Models\DoctorPayout;
use App\Models\Expense;
use App\Models\ReturnSlip;
use App\Models\ShiftSummary;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $selectedShift;
    public $fromDate;
    public $toDate;

    public $totalServices = 0;
    public $totalLabs = 0;
    public $totalDoctorPayouts = 0;
    public $totalExpenses = 0;
    public $totalReturns = 0;
    public $finalCash = 0;

    public function mount()
    {
        $hour = now()->hour;

        if ($hour >= 22 || $hour < 8) {
            $this->selectedShift = 'night';
        } elseif ($hour >= 8 && $hour < 15) {
            $this->selectedShift = 'morning';
        } else {
            $this->selectedShift = 'evening';
        }

        $this->updatedSelectedShift(); // trigger data load
    }


    public function updatedSelectedShift()
    {
        $today = Carbon::now();

        if ($this->selectedShift === 'night') {
            $this->fromDate = $today->copy()->subDay()->setTime(22, 0, 0);
            $this->toDate = $today->copy()->setTime(8, 0, 0);
        } elseif ($this->selectedShift === 'morning') {
            $this->fromDate = $today->copy()->setTime(8, 0, 0);
            $this->toDate = $today->copy()->setTime(15, 0, 0);
        } elseif ($this->selectedShift === 'evening') {
            $this->fromDate = $today->copy()->setTime(15, 0, 0);
            $this->toDate = $today->copy()->setTime(22, 0, 0);
        } else {
            return;
        }

        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        $this->totalServices = ServiceTransaction::whereBetween('created_at', [$this->fromDate, $this->toDate])
            ->sum('price'); // ✅ Assuming 'price' is correct here

        $this->totalLabs = LabTestTransaction::whereBetween('created_at', [$this->fromDate, $this->toDate])
            ->sum('amount');

        $this->totalDoctorPayouts = DoctorPayout::whereBetween('created_at', [$this->fromDate, $this->toDate])
            ->sum('amount');

        $this->totalExpenses = Expense::whereBetween('created_at', [$this->fromDate, $this->toDate])
            ->sum('amount');

        // $this->totalReturns = ReturnSlip::whereBetween('created_at', [$this->fromDate, $this->toDate])
        //     ->sum('amount'); // ✅ You had this commented out, which broke final cash

        $this->finalCash = ($this->totalServices + $this->totalLabs)
            - ($this->totalDoctorPayouts + $this->totalExpenses + $this->totalReturns);
    }


    public function saveSummary()
    {
        if (!$this->selectedShift || !$this->fromDate || !$this->toDate) {
            return session()->flash('error', 'Please select a shift first.');
        }

        ShiftSummary::create([
            'from' => $this->fromDate,
            'to' => $this->toDate,
            'shift_name' => $this->selectedShift,
            'services' => $this->totalServices,
            'labs' => $this->totalLabs,
            'doctor_payouts' => $this->totalDoctorPayouts,
            'expenses' => $this->totalExpenses,
            'returns' => $this->totalReturns,
            'final_cash' => $this->finalCash,
            'created_by' => Auth::id(), // optional
        ]);

        session()->flash('success', 'Shift summary saved successfully.');
    }

    public function printSummary()
    {
        // You can integrate dompdf or open a print modal here.
        session()->flash('info', 'Print functionality is not implemented yet.');
    }
};
 ?>
<div class="">


<div class="max-w-4xl mx-auto space-y-8 p-6 bg-white dark:bg-gray-900 shadow-lg rounded-xl">
    <!-- Shift Selection Section -->
    <div>
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                    clip-rule="evenodd" />
            </svg>
            Shift Details
        </h2>

        <div class="grid md:grid-cols-3 gap-6">
            <div>
                <flux:select label="Select Doctor" wire:model.live="selectedDoctor" placeholder="✨ Choose Shift">
                    <option value="night">🌙 Night (10 PM – 8 AM)</option>
                    <option value="morning">🌅 Morning (8 AM – 3 PM)</option>
                    <option value="evening">🌇 Evening (3 PM – 10 PM)</option>
                </flux:select>
            </div>

            <div>
                <flux:input label="From" type="text" wire:model="fromDate" />
            </div>

            <div>
                <flux:input label="To" type="text" wire:model="toDate" />
            </div>
        </div>
    </div>

    <!-- Cash Summary Section -->
    <div>
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.51-1.31c-.562-.649-1.413-1.076-2.353-1.253V5z"
                    clip-rule="evenodd" />
            </svg>
            Cash Summary
        </h2>

        <div class="grid md:grid-cols-2 gap-4">
            <!-- Revenue -->
            <div class="space-y-3">
                <div
                    class="flex justify-between p-4 rounded-xl border-l-4 border-green-500 bg-green-50 dark:bg-green-900/10">
                    <span class="font-semibold text-gray-700 dark:text-gray-200">Total Services</span>
                    <span class="text-green-600 dark:text-green-400 font-bold">Rs.
                        {{ number_format($totalServices, 2) }}</span>
                </div>

                <div
                    class="flex justify-between p-4 rounded-xl border-l-4 border-blue-500 bg-blue-50 dark:bg-blue-900/10">
                    <span class="font-semibold text-gray-700 dark:text-gray-200">Total Labs</span>
                    <span class="text-blue-600 dark:text-blue-400 font-bold">Rs.
                        {{ number_format($totalLabs, 2) }}</span>
                </div>
            </div>

            <!-- Expenses -->
            <div class="space-y-3">
                <div class="flex justify-between p-4 rounded-xl border-l-4 border-red-500 bg-red-50 dark:bg-red-900/10">
                    <span class="font-semibold text-gray-700 dark:text-gray-200">Doctor Payouts</span>
                    <span class="text-red-600 dark:text-red-400 font-bold">- Rs.
                        {{ number_format($totalDoctorPayouts, 2) }}</span>
                </div>

                <div
                    class="flex justify-between p-4 rounded-xl border-l-4 border-orange-500 bg-orange-50 dark:bg-orange-900/10">
                    <span class="font-semibold text-gray-700 dark:text-gray-200">Expenses</span>
                    <span class="text-orange-600 dark:text-orange-400 font-bold">- Rs.
                        {{ number_format($totalExpenses, 2) }}</span>
                </div>

                <div
                    class="flex justify-between p-4 rounded-xl border-l-4 border-purple-500 bg-purple-50 dark:bg-purple-900/10">
                    <span class="font-semibold text-gray-700 dark:text-gray-200">Return Slips</span>
                    <span class="text-purple-600 dark:text-purple-400 font-bold">- Rs.
                        {{ number_format($totalReturns, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Divider -->
    <hr class="my-6 border-t-2 border-gray-200 dark:border-gray-700">

    <!-- Final Cash -->
    <div class="flex justify-between items-center bg-green-100 dark:bg-green-900/20 p-6 rounded-xl shadow-inner">
        <div>
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Final Cash to Submit</h3>
        </div>
        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
            Rs. {{ number_format($finalCash, 2) }}
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex gap-4 justify-end">
        <button wire:click="saveSummary"
            class="px-6 py-3 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">
            Save Summary
        </button>

        <button wire:click="printSummary"
            class="px-6 py-3 rounded-lg bg-gray-700 text-white font-semibold hover:bg-gray-800 transition">
            Print
        </button>
    </div>
</div>
<livewire:returnslip/>
</div>
