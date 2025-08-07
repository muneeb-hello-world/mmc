<?php

use Livewire\Volt\Component;
use App\Models\Doctor;
use App\Models\CaseModel;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentCase;
use App\Traits\ToastHelper;
use App\Traits\PrintsReceipt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\Debugbar\Facades\Debugbar;

new class extends Component {
    use ToastHelper;
    use PrintsReceipt;

    public $paymentAmount = 0;
    public $totalAmount = 0;
    public $paymentMethod = 'Cash';

    public $patient = ['name' => '', 'contact' => '', 'age' => '', 'gender' => ''];
    public $doctor_id;
    public $doctors;
    public $title;
    public $final_price;
    public $room_type;
    public $scheduled_date;
    public $room = 'ward';
    public $notes;
    public $status;
    public $remainingBalance;

    public $cases;
    public $startDate;
    public $endDate;

    public function mount()
    {
        $this->doctors = Doctor::all();
        $this->doctor_id = $this->doctors->first()->id ?? null;
        $this->endDate = Carbon::now()->toDateString();
        $this->startDate = Carbon::now()->subDays(3)->toDateString();

        $this->loadCases();
    }

    public function createCase()
    {
        DB::beginTransaction();

        try {
            $this->validateFields();

            $patient = $this->createPatient();

            $case = $this->createCaseRecord($patient);

            $payment = $this->createPaymentRecord($patient);

            $this->linkPaymentToCase($payment, $case);

            DB::commit();

            $this->printReceipt($case);

            $this->showToast('success', 'Case and payment created successfully!');
            $this->resetForm();
            $this->loadCases();
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->showToast('danger', 'Failed: ' . $th->getMessage());
        }
    }

    private function validateFields()
    {
        $this->validate([
            'patient.name' => 'required|string|max:255',
            'patient.contact' => 'required|string|max:255',
            'patient.age' => 'required|integer|min:0',
            'patient.gender' => 'required|string|in:male,female',
            'doctor_id' => 'required|exists:doctors,id',
            'title' => 'required|string|max:255',
            'final_price' => 'required|numeric|min:0',
            'scheduled_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'paymentAmount' => 'required|numeric|min:0',
            'remainingBalance' => 'required|numeric'
        ]);
    }

    private function createPatient()
    {
        return Patient::create($this->patient);
    }

    private function createCaseRecord($patient)
    {
        return CaseModel::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor_id,
            'title' => $this->title,
            'status' => $this->status,
            'final_price' => $this->final_price,
            'scheduled_date' => $this->scheduled_date,
            'notes' => $this->notes,
            'room_type' => $this->room,
            'balance' => $this->remainingBalance
        ]);
    }

    private function createPaymentRecord($patient)
    {
        return Payment::create([
            'patient_id' => $patient->id,
            'amount' => $this->paymentAmount,
            'method' => $this->paymentMethod,
            'remarks' => 'Advance payment for case',
        ]);
    }

    private function linkPaymentToCase($payment, $case)
    {
        PaymentCase::create([
            'payment_id' => $payment->id,
            'case_model_id' => $case->id,
            'amount' => $this->paymentAmount,
        ]);
    }

    private function printReceipt($case)
    {
        $case->load(['patient', 'doctor', 'paymentCases']); // Ensure relations are loaded
        $this->printCasePaymentReceipt($case, $case->paymentCases);
    }

    private function resetForm()
    {
        $this->reset([
            'doctor_id', 'title', 'final_price', 'scheduled_date', 'notes',
            'paymentAmount', 'status', 'remainingBalance'
        ]);
        $this->paymentMethod = "Cash";
        $this->patient = ['name' => '', 'contact' => '', 'age' => '', 'gender' => ''];
    }

    public function updatedPaymentAmount()
    {
        $this->remainingBalance = ((float) $this->final_price ?? 0) - ((float) $this->paymentAmount ?? 0);

        if ($this->remainingBalance > 0) {
            $this->status = 'pending';
        } elseif ($this->remainingBalance == 0) {
            $this->status = 'completed';
        } else {
            $this->status = 'cancelled';
        }
    }

    public function previousCases()
    {
        $start = Carbon::parse($this->startDate);
        $this->endDate = $start->toDateString();
        $this->startDate = $start->copy()->subDays(3)->toDateString();
        $this->loadCases();
    }

    public function nextCases()
    {
        $end = Carbon::parse($this->endDate);
        $this->startDate = $end->toDateString();
        $this->endDate = $end->copy()->addDays(3)->toDateString();
        $this->loadCases();
    }

    public function loadCases()
    {
        $from = Carbon::parse($this->startDate)->startOfDay();
        $to = Carbon::parse($this->endDate)->endOfDay();

        $this->cases = CaseModel::whereBetween('created_at', [$from, $to])->get();
    }
};
?>
<div>
    <div class=" p-4 m-4 rounded-lg border">
        <h2 class="text-2xl font-semibold mb-6 text-center">Case Information</h2>
        <div class=" p-6 m-4 rounded-lg border">
            <h2 class="text-lg font-semibold mb-4">Patient Information</h2>

            <div class=" grid grid-cols-1 md:grid-cols-2 gap-4 px-2">

                {{-- Patient Info --}}


                <flux:input label="Patient Name" placeholder="Enter patient name" wire:model="patient.name"
                    class="mb-4" />
                <flux:input label="Patient Contact" placeholder="Enter patient contact" wire:model="patient.contact"
                    type="tel" class="mb-4" />
                <flux:input label="Patient Age" placeholder="Enter patient age" wire:model="patient.age" class="mb-4"
                    type="number" />
                <flux:select label="Patient Gender" placeholder="Select patient gender" wire:model="patient.gender"
                    class="mb-4">
                    <flux:select.option value="male">Male</flux:select.option>
                    <flux:select.option value="female">Female</flux:select.option>
                </flux:select>

            </div>
        </div>
        <div class=" p-6 m-4 rounded-lg border">
            <h2 class="text-lg font-semibold mb-4">Case Information</h2>

            <div class=" grid grid-cols-1 md:grid-cols-2 gap-4 px-2">

                {{-- Patient Info --}}

                <flux:select label="Doctor" placeholder="Select doctor" wire:model="doctor_id" class="mb-4">
                    @foreach ($doctors as $doctor)
                        <flux:select.option value="{{ $doctor->id }}">{{ $doctor->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input label="Operation" placeholder="Enter operation name" wire:model="title" class="mb-4" />
                <flux:input label="Final Package" placeholder="Enter final package" type="number"
                    wire:model="final_price" class="mb-4" />
                <flux:input label="Operate Date" placeholder="Enter operate date" wire:model="scheduled_date"
                    type="date" class="mb-4" />
                <flux:select label="Room" wire:model="room" class="mb-4">
                    <flux:select.option selected value="ward">Ward</flux:select.option>
                    <flux:select.option value="room_1">Room 1</flux:select.option>
                    <flux:select.option value="room_2">Room 2</flux:select.option>
                    <flux:select.option value="room_3">Room 3</flux:select.option>
                </flux:select>

                <div class="col-span-2">


                    <flux:textarea wire:model="notes" label="Operation notes" placeholder="Enter operation notes..." />
                </div>


            </div>
        </div>
        <div class=" p-6 m-4 rounded-lg border">
            <h2 class="text-lg font-semibold mb-4">Payments Information</h2>

            <div class="flex items-center px-2  w-full p-3">
                {{-- Payment Info --}}
                <div class="grow mr-4">
                    <flux:input label="Advance Payment" placeholder="Enter payment amount"
                        wire:model.live="paymentAmount" type="number" class="mb-0 w-full" />
                </div>
            </div>
            <div class=" p-4 m-2 flex justify-between items-center gap-4">
                <flux:select label="Payment Method" placeholder="Select payment method" wire:model="paymentMethod"
                    class="mb-4 w-48">
                    <flux:select.option selected value="Cash">Cash</flux:select.option>
                    <flux:select.option value="Online">Online</flux:select.option>
                </flux:select>
                <div class="">
                    <h2>Remaining Balance = {{ $remainingBalance ?? 'N/A'}}</h2>
                    <h2 class="text-yellow-500">{{ $status }}</h2>
                </div>

            </div>
            <div class="pl-6 pr-8">

                <flux:button label="Submit Payment" wire:loading.attr="disabled"  variant="primary" color="lime" wire:click="createCase"
                    class="w-full font-bold">
                    Save
                </flux:button>
            </div>


        </div>
        <!-- Table Section -->

        <!-- End Table Section -->
    </div>
    <div class=" px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        <!-- Card -->
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <div
                        class="bg-white border border-gray-200 rounded-xl shadow-2xs overflow-hidden dark:bg-neutral-800 dark:border-neutral-700">
                        <!-- Header -->
                        <div
                            class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200 dark:border-neutral-700">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-neutral-200">
                                    Cases
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-neutral-400">
                                    Add payment, edit and more.
                                </p>
                            </div>

                            <div class="">
                                <div class="flex gap-2 items-end">
                                     <flux:button wire:click="previousCases" size="sm">
                                        <div class="flex gap-2">

                                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24"
                                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m15 18-6-6 6-6" />
                                            </svg>
                                            Prev
                                            
                                        </div>
                                    </flux:button>
                                    <flux:input disabled type="date" wire:model="startDate" label="Start Date"
                                        readonly />
                                    <flux:input disabled type="date" wire:model="endDate" label="End Date" readonly />
                                    <flux:button wire:click="nextCases" size="sm">
                                        <div class="flex gap-2">

                                            Next
                                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24"
                                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m9 18 6-6-6-6" />
                                            </svg>
                                        </div>
                                    </flux:button>
                                </div>
                                 
                            </div>
                        </div>
                        <!-- End Header -->

                        <!-- Table -->
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                            <thead class="bg-gray-50 dark:bg-neutral-800">
                                <tr>


                                    <th scope="col" class="ps-6 lg:ps-3 xl:ps-0 pe-6 py-3 text-start">
                                        <div class=" ml-2 flex justify-center items-center gap-x-2">
                                            <span
                                                class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Patient Name
                                            </span>
                                        </div>
                                    </th>

                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span
                                                class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Opt
                                            </span>
                                        </div>
                                    </th>

                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span
                                                class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Doctor
                                            </span>
                                        </div>
                                    </th>

                                     <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span
                                                class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Status
                                            </span>
                                        </div>
                                    </th>


                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span
                                                class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Package
                                            </span>
                                        </div>
                                    </th>

                                     <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span
                                                class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Balance
                                            </span>
                                        </div>
                                    </th>
                                     <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span
                                                class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Opt_Date
                                            </span>
                                        </div>
                                    </th>



                                    <th scope="col" class="px-6 py-3 text-end"></th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                                @foreach ($cases as $case)
                                    {{-- #original: array:11 [▼
                                    "id" => 1
                                    "patient_id" => 12
                                    "doctor_id" => 4
                                    "title" => "svd"
                                    "final_price" => "12000.00"
                                    "room_type" => "ward"
                                    "status" => "pending"
                                    "scheduled_date" => "2025-08-01"
                                    "notes" => "hello"
                                    "created_at" => "2025-07-28 22:32:40"
                                    "updated_at" => "2025-07-28 22:32:40"
                                    ] --}}

                                    <tr>

                                        <td class="size-px whitespace-nowrap">
                                            <div class="ps-6 lg:ps-3 xl:ps-0 pe-6 py-3 ml-3 text-center">
                                                <div class="">
                                                    <span
                                                        class="block text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ $case->patient->name }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-3">
                                                <span
                                                    class="block text-sm font-semibold text-gray-800 uppercase dark:text-neutral-200">{{$case->title}}</span>

                                            </div>
                                        </td>
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-3">
                                               
                                                <span
                                                    class="block text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ $case->doctor->name }}</span>

                                            </div>
                                        </td>
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-3">
                                                @if ($case->balance==0)
                                                    
                                                
                                                <span
                                                    class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
                                                    <svg class="size-2.5" xmlns="http://www.w3.org/2000/svg" width="16"
                                                        height="16" fill="currentColor" viewBox="0 0 16 16">
                                                        <path
                                                            d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                                    </svg>
                                                    Active
                                                </span>
                                                @else
                                                 <span
                                                    class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium bg-amber-100 text-amber-800 rounded-full dark:bg-amber-500/10 dark:text-amber-500">
                                                    <svg class="size-2.5" xmlns="http://www.w3.org/2000/svg" width="16"
                                                        height="16" fill="currentColor" viewBox="0 0 16 16">
                                                        <path
                                                            d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                                    </svg>
                                                    Pending
                                                </span>
                                                @endif

                                            </div>
                                        </td>
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-3">
                                                <span
                                                    class="block text-sm font-semibold text-gray-800 uppercase dark:text-neutral-200">{{$case->final_price}}</span>

                                            </div>
                                        </td>
                                         <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-3">
                                                <span
                                                    class="block text-sm font-semibold text-gray-800 uppercase dark:text-neutral-200">{{$case->balance}}</span>

                                            </div>
                                        </td>
                                        <td class="size-px whitespace-nowrap">

                                            <div class="px-6 py-3">
                                                @php
                                                    $dt = $case->scheduled_date instanceof \Carbon\Carbon
                                                        ? $case->scheduled_date->copy()
                                                        : \Carbon\Carbon::parse($case->scheduled_date);

                                                    // Ensure it uses your app timezone (optional)
                                                    $dt->timezone(config('app.timezone'));
                                                @endphp



                                                <span
                                                    class=" capitalize block text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ $dt->isToday() ? 'today' : ($dt->isTomorrow() ? 'tomorrow' : $dt->format('j F Y')) }}</span>

                                            </div>
                                        </td>

                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-1.5">
                                                <a href="{{ route('caseview',$case->id) }}">
                                                    <flux:button  size="sm" variant="primary" color="blue">Details</flux:button>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach


                            </tbody>
                        </table>
                        <!-- End Table -->

                        <!-- Footer -->
                        <div
                            class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 dark:border-neutral-700">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-neutral-400">
                                    <span class="font-semibold text-gray-800 dark:text-neutral-200">{{ count($cases) }}</span>
                                    results
                                </p>
                            </div>

                            <div>
                               
                            </div>
                        </div>
                        <!-- End Footer -->
                    </div>
                </div>
            </div>
        </div>
        <!-- End Card -->
    </div>
    {{-- 
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Main Card Container -->
        <div class="bg-white dark:bg-gray-800 shadow-2xl rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Enhanced Header -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-700 dark:to-purple-700 px-6 py-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-white">Medical Cases</h1>
                            <p class="text-blue-100 dark:text-purple-100">Manage patient cases, payments and operations</p>
                        </div>
                    </div>

                    <!-- Navigation Controls -->
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="flex flex-col sm:flex-row gap-3 items-center">
                            <flux:button wire:click="previousCases" size="sm" 
                                class="bg-white/20 hover:bg-white/30 text-white border-white/30 hover:border-white/50 transition-all duration-200 flex items-center gap-2 px-4 py-2 rounded-lg">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18-6-6 6-6" />
                                </svg>
                                Previous
                            </flux:button>
                            
                            <div class="flex gap-2">
                                <flux:input disabled type="date" wire:model="startDate" label="Start Date" readonly 
                                    class="bg-white/20 border-white/30 text-white placeholder-white/70 text-sm" />
                                <flux:input disabled type="date" wire:model="endDate" label="End Date" readonly 
                                    class="bg-white/20 border-white/30 text-white placeholder-white/70 text-sm" />
                            </div>
                            
                            <flux:button wire:click="nextCases" size="sm"
                                class="bg-white/20 hover:bg-white/30 text-white border-white/30 hover:border-white/50 transition-all duration-200 flex items-center gap-2 px-4 py-2 rounded-lg">
                                Next
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6" />
                                </svg>
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Container -->
            <div class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <!-- Enhanced Table Header -->
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                            Patient Name
                                        </span>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-4 text-left">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                            Operation
                                        </span>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-4 text-left">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                            Doctor
                                        </span>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-4 text-left">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                            Status
                                        </span>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-4 text-left">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-indigo-500 rounded-full"></div>
                                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                            Package
                                        </span>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-4 text-left">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                            Balance
                                        </span>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-4 text-left">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-teal-500 rounded-full"></div>
                                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                            Operation Date
                                        </span>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-4 text-center">
                                    <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                        Actions
                                    </span>
                                </th>
                            </tr>
                        </thead>

                        <!-- Enhanced Table Body -->
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                            @foreach ($cases as $case)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-all duration-200 group">
                                <!-- Patient Name -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center flex-shrink-0 shadow-md">
                                            <span class="text-sm font-bold text-white">
                                                {{ strtoupper(substr($case->patient->name, 0, 1)) }}
                                            </span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                                {{ $case->patient->name }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Case #{{ $case->id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Operation -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white uppercase">
                                                {{ $case->title }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Doctor -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $case->doctor->name }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($case->balance == 0)
                                    <span class="inline-flex items-center gap-2 px-3 py-2 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800/50 shadow-sm">
                                        <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                                        Completed
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-2 px-3 py-2 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800/50 shadow-sm">
                                        <div class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></div>
                                        Pending Payment
                                    </span>
                                    @endif
                                </td>

                                <!-- Package -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                            ₨ {{ number_format($case->final_price) }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Balance -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 {{ $case->balance == 0 ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }} rounded-lg flex items-center justify-center">
                                            @if($case->balance == 0)
                                            <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            @else
                                            <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                            </svg>
                                            @endif
                                        </div>
                                        <span class="text-sm font-semibold {{ $case->balance == 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            ₨ {{ number_format($case->balance) }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Operation Date -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $dt = $case->scheduled_date instanceof \Carbon\Carbon
                                            ? $case->scheduled_date->copy()
                                            : \Carbon\Carbon::parse($case->scheduled_date);
                                        $dt->timezone(config('app.timezone'));
                                    @endphp
                                    
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 bg-teal-100 dark:bg-teal-900/30 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white capitalize">
                                                {{ $dt->isToday() ? 'Today' : ($dt->isTomorrow() ? 'Tomorrow' : $dt->format('j M Y')) }}
                                            </div>
                                            @if($dt->isToday())
                                            <div class="text-xs text-green-600 dark:text-green-400 font-medium">• Active</div>
                                            @elseif($dt->isTomorrow())
                                            <div class="text-xs text-blue-600 dark:text-blue-400 font-medium">• Upcoming</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <a href="{{ route('caseview', $case->id) }}" class="group inline-block">
                                        <flux:button size="sm" variant="primary" color="blue" 
                                            class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-medium px-4 py-2 rounded-lg shadow-md hover:shadow-lg transform hover:scale-105 transition-all duration-200 flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            View Details
                                        </flux:button>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                            
                            <!-- Empty State -->
                            @if(count($cases) === 0)
                            <tr>
                                <td colspan="8" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center gap-4">
                                        <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                            <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">No cases found</h3>
                                            <p class="text-gray-500 dark:text-gray-400 mt-1">There are no cases for the selected date range.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Enhanced Footer -->
            <div class="bg-gray-50 dark:bg-gray-700/30 px-6 py-4 border-t border-gray-200 dark:border-gray-600">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 00-2-2z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ count($cases) }} {{ count($cases) === 1 ? 'case' : 'cases' }} found
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Showing results for selected period
                            </p>
                        </div>
                    </div>

                    <!-- Summary Statistics -->
                    <div class="flex gap-4">
                        @php
                            $completedCases = collect($cases)->where('balance', 0)->count();
                            $pendingCases = collect($cases)->where('balance', '>', 0)->count();
                        @endphp
                        
                        <div class="flex items-center gap-2 bg-green-100 dark:bg-green-900/30 px-3 py-2 rounded-lg">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <span class="text-xs font-medium text-green-800 dark:text-green-400">
                                {{ $completedCases }} Completed
                            </span>
                        </div>
                        
                        <div class="flex items-center gap-2 bg-amber-100 dark:bg-amber-900/30 px-3 py-2 rounded-lg">
                            <div class="w-2 h-2 bg-amber-500 rounded-full"></div>
                            <span class="text-xs font-medium text-amber-800 dark:text-amber-400">
                                {{ $pendingCases }} Pending
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> --}}
    {{--
    <livewire:casesview /> --}}
</div>