<?php

use Flux\Flux;
use Livewire\Volt\Component;
use Carbon\Carbon;
use App\Models\ServiceTransaction;
use App\Models\Doctor;
use App\Models\DoctorServiceShare;
use App\Models\PaymentService;


new class extends Component {
    public $bookings;
    public $selectedDoc;
    public $doctors;
    public $price;
    public $selectedToken;
    public $selectedPatient;
    public $paymentMethod = "Cash";
    public $doctorShare = 0;
    public $hospitalShare = 0;
    public $patient = [
        'name' => '',
        'contact' => ''
    ];
    public $emergencyPrice = false;
    public $serviceId = 13;



    public function mount()
    {
        $this->doctors = Doctor::all();
        if ($this->doctors->isNotEmpty()) {
            $this->selectedDoc = $this->doctors[0]->id;
            $this->getAppointments();
        } else {
            $this->bookings = collect(); // prevent undefined var
        }
    }



    public function getAppointments()
    {
        $bookingsToday = ServiceTransaction::where('doctor_id', $this->selectedDoc)
            ->whereDate('created_at', Carbon::today())
            ->get()
            ->keyBy('token'); // Key by token for quick lookup

        $this->bookings = collect();
        $doctor = Doctor::find($this->selectedDoc);
        $startTime = Carbon::parse($doctor->start_time);


        // Generate 60 slots
        for ($i = 1; $i <= 50; $i++) {
            $expectedTime = $startTime->copy()->addMinutes(($i - 1) * 5)->format('h:i A');

            if ($bookingsToday->has($i)) {
                $this->bookings->push([
                    'token' => $i,
                    'doc' => $bookingsToday[$i]->doctor_id,
                    'price' => $bookingsToday[$i]->price,
                    'arrived' => $bookingsToday[$i]->arrived,
                    'expected_time' => $expectedTime,
                    'status' => 'booked',
                    'patient' => $bookingsToday[$i]->patient,
                ]);
            } else {
                $this->bookings->push([
                    'token' => $i,
                    'doc_id' => $this->selectedDoc,
                    'status' => $i % 5 == 0 ? 'reserved' : 'available',
                    'arrived' => 0,
                    'patient' => null,
                    'expected_time' => $expectedTime,
                ]);
            }
        }
    }



    public function getPrice()
    {
        $serviceId = $this->serviceId;
        $this->price = 0;
        $this->doctorShare = 0;
        $this->hospitalShare = 0;

        $service = \App\Models\Service::find($serviceId);
        if (!$service)
            return;

        if ($service->is_doctor_related && $this->selectedDoc) {
            $share = DoctorServiceShare::where('doctor_id', $this->selectedDoc)
                ->where('service_id', $serviceId)
                ->first();

            $this->price = $share->price ?? $service->default_price ?? 0;


            $doctorSharePercent = $share->doctor_share_percent ?? 0;
            $hospitalSharePercent = $share->hospital_share_percent ?? 0;

            $this->doctorShare = ($doctorSharePercent / 100) * $this->price;
            $this->hospitalShare = ($hospitalSharePercent / 100) * $this->price;
        } else {
            $this->price = $service->default_price;
            $this->doctorShare = 0;
            $this->hospitalShare = $this->price;
        }
    }



    public function openModel($token)
    {
        $this->resetForm();
        $i = $token - 1;

        if (!isset($this->bookings[$i])) {
            return;
        }

        $status = $this->bookings[$i]['status'];
        $this->selectedToken = $token;

        if ($status === "available") {
            Flux::modal('book')->show();
        } elseif ($status === 'reserved') {
            $this->emergencyPrice = 1;
            Flux::modal('reserved')->show();
        } elseif ($status === "booked") {
            $this->selectedPatient = $this->bookings[$i]['patient']->name ?? 'N/A';
            Flux::modal('arrived')->show();
        }
    }


    public function book()
    {
        $this->validate([
            'patient.name' => 'required|string|max:255',
            'patient.contact' => 'nullable|string|max:15'
        ]);

        $this->getPrice(); // Sets price and shares

        $patient = \App\Models\Patient::create([
            'name' => $this->patient['name'],
            'contact' => $this->patient['contact']
        ]);

        ServiceTransaction::create([
            'patient_id' => $patient->id,
            'service_id' => $this->serviceId,
            'doctor_id' => $this->selectedDoc,
            'price' => $this->price,
            'doctor_share' => $this->doctorShare,
            'hospital_share' => $this->hospitalShare,
            'booking' => true,
            'arrived' => false,
            'token' => $this->selectedToken
        ]);

        Flux::modal('book')->close();
        $this->resetForm();
        $this->getAppointments();
    }

    public function emergencyBook()
    {
        $this->validate([
            'patient.name' => 'required|string|max:255',
        ]);


        $this->getPrice();

        // Increase all values by 1.5 if emergency
        if ($this->emergencyPrice) {
            $this->price = round($this->price * 1.5);
            $this->doctorShare = round($this->doctorShare * 1.5);
            $this->hospitalShare = round($this->hospitalShare * 1.5);
        }

        $patient = \App\Models\Patient::create([
            'name' => $this->patient['name'],
            'contact' => $this->patient['contact']
        ]);

        ServiceTransaction::create([
            'patient_id' => $patient->id,
            'service_id' => $this->serviceId,
            'doctor_id' => $this->selectedDoc,
            'price' => $this->price,
            'doctor_share' => $this->doctorShare,
            'hospital_share' => $this->hospitalShare,
            'booking' => true,
            'arrived' => false,
            'token' => $this->selectedToken
        ]);

        Flux::modal('reserved')->close();
        $this->resetForm();
        $this->getAppointments();
    }

    public function arrive($token)
    {
        $service = ServiceTransaction::where('doctor_id', $this->selectedDoc)
            ->whereDate('created_at', Carbon::today())
            ->where('token', $token)
            ->firstOrFail();

        // ✅ Simply mark as arrived — no recalculations
        $service->update([
            'arrived' => true,
        ]);

        // Use already stored price
        $price = $service->price;

        // Create Payment
        $payment = \App\Models\Payment::create([
            'patient_id' => $service->patient_id,
            'amount' => $price,
            'method' => $this->paymentMethod,
            'status' => 'paid',
        ]);

        PaymentService::create([
            'payment_id' => $payment->id,
            'service_transaction_id' => $service->id,
            'amount' => $price,
        ]);

        // $this->print($service->id);
        $this->getAppointments();
    }


    public function resetForm()
    {
        $this->reset(['patient', 'selectedToken', 'selectedPatient', 'price', 'doctorShare', 'hospitalShare', 'emergencyPrice']);
    }







}; ?>

<div>
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
                                Doctor Appointments
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-neutral-400">
                                Add users, edit and more.
                            </p>
                        </div>

                        <div>
                            <div class="flex gap-x-2">
                                <flux:label>Doctor: </flux:label>
                                <flux:select size="sm" wire:model="selectedDoc">
                                    @foreach ($doctors as $doc)
                                        <flux:select.option value="{{ $doc->id }}">{{$doc->name}}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:button size="sm" wire:click='getAppointments'>Get</flux:button>
                            </div>
                        </div>
                    </div>
                    <div class=" p-2 flex items-center justify-between mx-3">
                        <h3 class=" dark:text-black text-white">
                            .
                        </h3>
                        <div class="flex flex-wrap gap-6">
                            <div class="flex items-center">
                                <div class="w-6 h-6 seat-available rounded-lg mr-2"></div>
                                <span class="text-white">Available</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-6 h-6 seat-booked rounded-lg mr-2"></div>
                                <span class="text-white">Reserved</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-6 h-6 seat-selected rounded-lg mr-2"></div>
                                <span class="text-white">Booked</span>
                            </div>
                        </div>
                    </div>
                    <!-- End Header -->



                <div class="grid grid-cols-5 grid-rows-1 gap-4 p-4 border border-gray-200 dark:border-neutral-700">
    @foreach ($bookings as $booking)
        <div class="">
            <div wire:click="openModel({{ $booking['token'] }})" class="seat-item 
                    @if ($booking['arrived']) seat-arrived
                    @elseif ($booking['status'] === 'booked') seat-booked
                    @elseif ($booking['status'] === 'reserved') seat-reserved
                    @else seat-available
                    @endif
                    cursor-pointer rounded-xl p-3 min-h-[120px] flex flex-col justify-between">
                
                <!-- Header with Token and Chair Icon -->
                <div class="flex items-center justify-between mb-2">
                    <div class="text-white font-bold text-lg leading-tight">
                        {{ $booking['token'] }}
                    </div>
                    @if ($booking['status'] != 'booked')
                        <i class="fas fa-chair text-white/70 text-lg"></i>
                    @endif
                </div>

                <!-- Time - Secondary Info -->
                <div class="text-white/80 text-xs font-medium mb-2">
                    {{ $booking['expected_time'] }}
                </div>

                @if ($booking['status'] === 'booked')
                    <!-- Patient Info - Main Content -->
                    <div class="flex-1 space-y-1">
                        <div class="text-white font-semibold text-sm leading-tight">
                            {{ $booking['patient']?->name ?? 'N/A' }}
                        </div>
                        
                        <!-- Price -->
                        <div class="text-white/90 text-xs font-medium">
                            Rs {{ number_format($booking['price']) }}
                        </div>
                    </div>

                    <!-- Status Badge -->
                    @if ($booking['arrived'])
                        <div class="mt-2 pt-2 border-t border-white/20">
                            <div class="inline-block bg-green-500/20 text-green-300 text-xs font-semibold px-2 py-1 rounded-full">
                                ✓ Arrived
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endforeach
</div>s

                </div>
            </div>
        </div>
    </div>


    <flux:modal name="arrived" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Token Number: {{$selectedToken}}</flux:heading>
                <flux:heading size="lg">Patient Name: {{ $selectedPatient}}</flux:heading>
                {{-- <flux:text class="mt-2">Enter details to book Appointment.</flux:text> --}}
            </div>


            <div class="flex justify-center items-center">
                @php
                    $index = $selectedToken - 1;
                    $arrived = $bookings[$index]['arrived'] ?? false;
                @endphp

                @if (!$arrived)
                 <flux:select wire:model.live="emergencyPrice">
                <flux:select.option value="1">Emergency Price</flux:select.option>
                <flux:select.option value="0">Normal Price</flux:select.option>
            </flux:select>
                    <flux:button variant="primary" wire:click="arrive({{ $selectedToken }})">Arrived ?</flux:button>
                @else
                    <span class="text-green-500 text-lg font-semibold">Patient Arrived</span>
                @endif
            </div>
        </div>
    </flux:modal>


    <flux:modal name="book" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Token Number: {{$selectedToken}}</flux:heading>
                <flux:text class="mt-2">Enter details to book Appointment.</flux:text>
            </div>

            <flux:input wire:model="patient.name" label="Name" placeholder="Patient name" />

            <flux:input wire:model="patient.contact" label="Contact #" type="number" />

            

            <div class="flex">
                <flux:spacer />

                <flux:button wire:click="book" variant="primary">Book </flux:button>
            </div>
        </div>
    </flux:modal>


    <flux:modal name="reserved" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:text class="mt-2 text-xl text-red-400 text-center">Emergency Only Seat.</flux:text>
                <flux:heading size="lg" class=" text-center">Token Number: {{$selectedToken}}</flux:heading>
            </div>

            <flux:input wire:model="patient.name" label="Name" placeholder="Patient name" />

            {{--
            <flux:input label="Contact #" type="number" /> --}}
            <flux:select wire:model.live="emergencyPrice">
                <flux:select.option value="1">Emergency Price</flux:select.option>
                <flux:select.option value="0">Normal Price</flux:select.option>
            </flux:select>

             

            <div class="flex">
                <flux:spacer />

                <flux:button wire:click="emergencyBook" variant="primary">Print</flux:button>
            </div>
        </div>
    </flux:modal>

</div>