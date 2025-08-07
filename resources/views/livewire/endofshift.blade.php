<?php

use Livewire\Volt\Component;
use App\Models\PaymentService;
use App\Models\PaymentLab;
use App\Models\DoctorPayout;
use App\Models\Expense;
use App\Models\ReturnSlip;
use App\Models\ShiftSummary;
use App\Traits\ShiftGeter;
use App\Traits\PrintsReceipt;
use App\Traits\ToastHelper;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use ToastHelper;
    use ShiftGeter;
    public $selectedShift;
    public $selectedDate;
    public $start;
    public $end;
    public $amountLess;
    public $cashRecieved = 0;

    public $totalServicesCash = 0;
    public $totalServices = 0;
    public $totalServicesOnline = 0;
    public $totalLabsCash = 0;
    public $totalLabsOnline = 0;
    public $totalLabs = 0;
    public $totalDoctorPayouts = 0;
    public $totalExpenses = 0;
    public $totalReturns = 0;
    public $finalCash = 0;

    public function mount()
    {
        $shift = $this->detectCurrentShift();
        $this->selectedDate = $shift['date'];
        $this->selectedShift = $shift['shift'];
        $this->start = $shift['start'];
        $this->end = $shift['end'];
        $this->getData();
        $this->updatedCashRecieved($this->cashRecieved);
    }


    public function getData()
    {
        $cashservice = PaymentService::with(['payment', 'serviceTransaction'])
            ->whereBetween('created_at', [$this->start, $this->end])
            ->get();
        $this->totalServicesCash = $cashservice
            ->filter(function ($item) {
                return $item->payment && $item->payment->method === 'Cash'
                    && $item->serviceTransaction && $item->serviceTransaction->arrived;
            })
            ->sum('amount');

        $onlineservice = PaymentService::with(['payment', 'serviceTransaction'])
            ->whereBetween('created_at', [$this->start, $this->end])
            ->get();
        $this->totalServicesOnline = $onlineservice
            ->filter(function ($item) {
                return $item->payment && $item->payment->method === 'Online'
                    && $item->serviceTransaction && $item->serviceTransaction->arrived;
            })
            ->sum('amount');
        $cashlab = PaymentLab::with(['payment'])
            ->whereBetween('created_at', [$this->start, $this->end])
            ->get();
        // dd($cashlab);
        $this->totalLabsCash = $cashlab
            ->filter(function ($item) {
                return $item->payment && $item->payment->method === 'Cash';
            })
            ->sum('amount');
        // dd($this->totalLabsCash);

        $cashonline = PaymentLab::with(['payment'])
            ->whereBetween('created_at', [$this->start, $this->end])
            ->get();
        // dd($cashlab);
        $this->totalLabsOnline = $cashonline
            ->filter(function ($item) {
                return $item->payment && $item->payment->method === 'Online';
            })
            ->sum('amount');
        $this->totalDoctorPayouts = DoctorPayout::whereBetween('created_at', [$this->start, $this->end])
            ->sum('amount');
        $this->totalExpenses = Expense::whereBetween('created_at', [$this->start, $this->end])
            ->sum('amount');
        // dd($this->totalExpenses);
        $this->totalReturns = ReturnSlip::whereBetween('created_at', [$this->start, $this->end])
            ->sum('amount');
        // dd($this->totalDoctorPayouts);
        $this->totalServices = $this->totalServicesCash + $this->totalServicesOnline;
        $this->totalLabs = $this->totalLabsCash + $this->totalLabsOnline;
        $this->finalCash = ($this->totalServicesCash + $this->totalLabsCash)
            - ($this->totalDoctorPayouts + $this->totalExpenses + $this->totalReturns);
    }


    public function updatedCashRecieved($v)
    {
        if ($v == "") {
            $v = 0;
        }
        $this->amountLess = $this->finalCash - $v;

    }

    public function updatedSelectedShift($v)
    {
        $shift = $this->getShift($this->selectedDate, $this->selectedShift);
        // dd($shift);
        $this->start = $shift['start'];
        $this->end = $shift['end'];
        $this->getData();
    }

    public function updatedSelectedDate()
    {
        // dd($this->selectedShift);
        $shift = $this->getShift($this->selectedDate, $this->selectedShift);
        $this->start = $shift['start'];
        $this->end = $shift['end'];
        $this->getData();
    }



    public function Summary()
    {
        if (!$this->selectedShift || !$this->start || !$this->end) {
            return $this->showToast('danger', 'Please select a shift first.');
        }

        // ❗ Check for duplicate summary entry
        $exists = ShiftSummary::where('shift_name', $this->selectedShift)
            ->where('date', $this->selectedDate)
            ->where('from', $this->start)
            ->where('to', $this->end)
            ->exists();

        if ($exists) {
            return $this->showToast('danger', 'Summary for this shift has already been saved.');
        }

        // ✅ Proceed to save summary
        ShiftSummary::create([
            'from' => $this->start,
            'to' => $this->end,
            'shift_name' => $this->selectedShift,
            'services' => $this->totalServices,
            'services_cash' => $this->totalServicesCash,
            'services_online' => $this->totalServicesOnline,
            'labs' => $this->totalLabs,
            'labs_cash' => $this->totalLabsCash,
            'labs_online' => $this->totalLabsOnline,
            'date' => $this->selectedDate,
            'doctor_payouts' => $this->totalDoctorPayouts,
            'expenses' => $this->totalExpenses,
            'returns' => $this->totalReturns,
            'final_cash' => $this->finalCash,
            'created_by' => Auth::id(),
        ]);

        $this->showToast('success', 'Shift summary saved successfully.');
    }



    public function printSummary()
    {
        $this->printShiftSummary([
            'date' => $this->selectedDate,
            'shift_label' => $this->getShiftLabel($this->selectedShift),
            'services_cash' => $this->totalServicesCash,
            'services_online' => $this->totalServicesOnline,
            'services' => $this->totalServices,
            'labs_cash' => $this->totalLabsCash,
            'labs_online' => $this->totalLabsOnline,
            'labs' => $this->totalLabs,
            'doctor_payouts' => $this->totalDoctorPayouts,
            'expenses' => $this->totalExpenses,
            'returns' => $this->totalReturns,
            'final_cash' => $this->finalCash,
            'cash_received' => $this->cashRecieved,
            'amount_less' => $this->amountLess,
            'handler' => auth()->user()->name ?? 'Receptionist',
        ]);
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

            <div class="flex gap-5">
                <div class=" w-full">
                    <flux:input label="Date" type="date" wire:model.live="selectedDate" />
                </div>
                <div class=" w-full">
                    <flux:select label="Select Doctor" wire:model.live="selectedShift" placeholder="✨ Choose Shift">
                        <option value="n">🌙 Night (10 PM – 8 AM)</option>
                        <option value="m">🌅 Morning (8 AM – 3 PM)</option>
                        <option value="e">🌇 Evening (3 PM – 10 PM)</option>
                    </flux:select>
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
                Summary
            </h2>

            <div class="grid md:grid-cols-2 gap-4">
                <!-- Revenue -->
                <div class="space-y-3">
                    <div class=" p-4 rounded-xl border-l-4 border-green-500 bg-green-50 dark:bg-green-900/10">
                        <div class="flex mb-4 justify-between w-full">
                            <span class="font-semibold text-gray-700 dark:text-gray-200">Cash Services</span>
                            <span class="text-green-600 dark:text-green-400 text-sm">Rs.
                                {{ number_format($totalServicesCash, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm  rounded ">

                            <span class="font-semibold text-gray-700 dark:text-gray-200">Online</span>
                            <span class="text-green-600 dark:text-green-400 font-bold">Rs.
                                {{ number_format($totalServicesOnline, 2) }}</span>
                            <span class="font-semibold text-gray-700 dark:text-gray-200">Total</span>
                            <span class="text-green-600 dark:text-green-400 font-bold">Rs.
                                {{ number_format($totalServices, 2) }}</span>
                        </div>
                    </div>


                    <div class=" rounded-xl border-l-4 border-blue-500 bg-blue-50 dark:bg-blue-900/10">
                        <div class="flex justify-between p-4 pb-2">
                            <span class="font-semibold text-gray-700 dark:text-gray-200">Cash Labs</span>
                            <span class="text-blue-600 dark:text-blue-400 font-bold">Rs.
                                {{ number_format($totalLabsCash, 2) }}</span>
                        </div>
                        <div class=" flex justify-between text-sm p-4 pt-2">
                            <div class="flex justify-between w-full">

                                <span class="font-semibold text-gray-700 dark:text-gray-200">Online</span>
                                <span class="text-blue-600 dark:text-blue-400 font-bold">Rs.
                                    {{ number_format($totalLabsOnline, 2) }}</span>
                                <span class="font-semibold text-gray-700 dark:text-gray-200">Total Labs</span>
                                <span class="text-blue-600 dark:text-blue-400 font-bold">Rs.
                                    {{ number_format($totalLabs, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expenses -->
                <div class="space-y-3">
                    <div
                        class="flex justify-between p-4 rounded-xl border-l-4 border-red-500 bg-red-50 dark:bg-red-900/10">
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

        <div
            class="grid border  grid-cols-2 grid-rows-3 gap-4  bg-green-100 dark:bg-green-900/20 p-6 rounded-xl shadow-inner ">
            <div class=" ">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Final Cash to Submit</h3>
            </div>
            <div class="  ">
                <div class="text-2xl mb-2 pl-2 font-bold text-green-600 dark:text-green-400">
                    Rs. {{ number_format($finalCash, 2) }}
                </div>
            </div>
            <div class=""> <span>Cash Recieved</span>
            </div>
            <flux:input wire:model.live="cashRecieved" type="number" placeholder="Cash Recieved" />
            <div class=""> <span>Amount Less</span>
            </div>
            <div class=""> <span>{{ $amountLess }}</span>
            </div>
        </div>



        <!-- Action Buttons -->
        <div class="flex gap-4 justify-end">

            <div class="">

            </div>
            <div class=" flex items-end">
                <flux:button wire:click="Summary"> Save</flux:button>
            </div>

        </div>
    </div>
    <livewire:returnslip />
</div>