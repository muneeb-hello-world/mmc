<?php

use Livewire\Volt\Component;
use App\Models\Doctor;
use App\Models\CaseModel;
use App\Models\Patient;
use App\Traits\ToastHelper;
use Carbon\Carbon;
use Barryvdh\Debugbar\Facades\Debugbar;


new class extends Component {
    use ToastHelper;


    public $paymentAmount = 0;
    public $totalAmount = 0; // This can default to 0 or the same as final_price
    public $paymentMethod= 'Cash';


    public $patient = [
        'name' => '',
        'contact' => '',
        'age' => '',
        'gender' => ''
    ];
    public $doctor_id;
    public $doctors;
    public $title;
    public $final_price;
    public $room_type;
    public $scheduled_date;
    public $room = 'ward'; // Default room type
    public $notes;
    public $status;
    public $remainingBalance;
    public $cases;
    public $startDate;
    public $endDate;

    public function mount()
    {
        $this->doctors = Doctor::all();
        $this->doctor_id = $this->doctors->first()->id ?? null; // Set default doctor if available
        $this->endDate = Carbon::now()->toDateString();
        $this->startDate = Carbon::now()->subDays(3)->toDateString();

        $this->loadCases();

    }

    public function createCase()
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
            'remainingBalance'=> 'required|numeric'
        ]);



        // Create Patient
        $patient = Patient::create([
            'name' => $this->patient['name'],
            'contact' => $this->patient['contact'],
            'age' => $this->patient['age'],
            'gender' => $this->patient['gender'],
        ]);



        // Create Case
        $case = CaseModel::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor_id,
            'title' => $this->title,
            'status' => $this->status, // Default status
            'final_price' => $this->final_price,
            'scheduled_date' => $this->scheduled_date,
            'notes' => $this->notes,
            'room_type' => $this->room,
            'balance'=>$this->remainingBalance
        ]);


        // Save Payment
        $payment = \App\Models\Payment::create([
            'patient_id' => $patient->id,
            'amount' => $this->paymentAmount,
            'method' => $this->paymentMethod, // or dynamic if needed
            'remarks' => 'Advance payment for case',
        ]);



        // Link payment to the case
        \App\Models\PaymentCase::create([
            'payment_id' => $payment->id,
            'case_model_id' => $case->id,
            'amount' => $this->paymentAmount,
        ]);


        $this->showToast('success', 'Case and payment created successfully!');
        $this->loadCases();


        // Reset fields
        $this->reset(['doctor_id', 'title', 'final_price', 'scheduled_date', 'notes', 'paymentAmount', 'status', 'remainingBalance']);
        $this->paymentMethod="Cash";
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
        $start = \Carbon\Carbon::parse($this->startDate);
        $end = \Carbon\Carbon::parse($this->endDate);

        $this->endDate = $start->toDateString();                    // Move window back
        $this->startDate = $start->copy()->subDays(3)->toDateString();

        Debugbar::info('previous');
        $this->loadCases();
    }

    public function nextCases()
    {
        $start = \Carbon\Carbon::parse($this->startDate);
        $end = \Carbon\Carbon::parse($this->endDate);

        $this->startDate = $end->toDateString();                    // Move window forward
        $this->endDate = $end->copy()->addDays(3)->toDateString();

        Debugbar::info('next');
        $this->loadCases();
    }


    public function loadCases()
{
    $from = \Carbon\Carbon::parse($this->startDate)->startOfDay();
    $to   = \Carbon\Carbon::parse($this->endDate)->endOfDay();

    $this->cases = CaseModel::whereBetween('created_at', [$from, $to])->get();
    Debugbar::info('loaded');
}


}; ?>

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

                <flux:button label="Submit Payment" variant="primary" color="lime" wire:click="createCase"
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
    <livewire:casesview /> --}}
</div>