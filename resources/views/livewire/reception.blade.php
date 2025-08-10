<?php

use Livewire\Volt\Component;
use Barryvdh\Debugbar\Facades\Debugbar;
use App\Models\Service;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\DoctorServiceShare;
use App\Models\ServiceTransaction;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use App\Traits\PrintsReceipt;
use App\Traits\ToastHelper;
use App\Traits\ShiftGeter;


new class extends Component {

    use PrintsReceipt;
    use ToastHelper;

    use ShiftGeter;

    public $patient = ['name' => '', 'contact' => '', 'age' => '', 'gender' => ''];
    public $services;
    public $docs = [];
    public $paymentMethod = 'Cash'; // Default payment method

    public $selectedService;
    public $selectedServices = [];
    public $selectedDoctor;
    public $token;
    public $price;
    public $totalPrice = 0;

    public $changedPrice;

    public function mount()
    {
        $this->services = Service::query()
            ->orderByRaw("
            CASE
                WHEN name = 'Consultation' THEN 1
                WHEN name = 'Drip' THEN 2
                WHEN name = 'BSR' THEN 3
                WHEN name = 'Bandage' THEN 4
                ELSE 5
            END
        ")
            ->orderBy('name') // alphabetical for the rest
            ->get();

        $this->selectedService = $this->services[0]->id;
        $this->updatedSelectedService($this->selectedService);
    }



    public function updatedSelectedService($value)
    {
        $this->selectedDoctor = '';
        if ($value) {
            $service = Service::with('doctorShares')->find($value);

            if ($service->is_doctor_related) {
                $this->docs = $service->doctorShares
                    ->sortBy(function ($share) {
                        // Priority rank
                        $rank = $share->doctor->id == 12 ? 0 : 1;
                        return [$rank, strtolower($share->doctor->name)];
                    })
                    ->values();


                $this->selectedDoctor = $this->docs[0]->doctor->id ?? null;

            } else {
                $this->docs = [];
            }

        } else {
            $this->docs = [];
        }
    }

    public function addService()
    {
        $this->getPrice();

        // Prevent duplication
        foreach ($this->selectedServices as $s) {
            if (
                $s['service_id'] == $this->selectedService &&
                ($s['doctor_id'] == ($this->selectedDoctor ?? null))
            ) {
                $this->showToast('warning', 'This service has already been added.');
                return;
            }
        }

        $SelectedServiceName = Service::find($this->selectedService)->name;

        if ($this->selectedDoctor) {
            $token = $this->getToken($this->selectedService, $this->selectedDoctor);
            $SelectedDoctorName = Doctor::find($this->selectedDoctor)->name;

            $this->selectedServices[] = [
                'service_id' => $this->selectedService,
                'service_name' => $SelectedServiceName,
                'doctor_id' => $this->selectedDoctor,
                'doctor_name' => $SelectedDoctorName,
                'price' => $this->price,
                'token' => $token,
            ];
        } else {
            $this->selectedServices[] = [
                'service_id' => $this->selectedService,
                'service_name' => $SelectedServiceName,
                'doctor_id' => null,
                'doctor_name' => null,
                'price' => $this->price,
            ];
        }

        $this->calculateTotalPrice();
    }


    public function getToken($service_id, $doctor_id)
    {
        // Special rule for service_id = 13 and doctor_id = 12
        if ($service_id == 13 && $doctor_id == 12) {
            $shift = $this->detectCurrentShift();

            $maxToken = ServiceTransaction::where('doctor_id', $doctor_id)
                ->where('service_id', $service_id)
                ->whereBetween('created_at', [$shift['start'], $shift['end']])
                ->max('token');

            return ($maxToken ?? 0) + 1;
        }

        // Default: reset daily
        $maxToken = ServiceTransaction::where('doctor_id', $doctor_id)
            ->where('service_id', $service_id)
            ->whereDate('created_at', Carbon::today())
            ->max('token');

        return ($maxToken ?? 0) + 1;
    }



    public function getPrice()
    {
        if ($this->selectedService) {
            $service = Service::find($this->selectedService);
            if ($service->is_doctor_related && $this->selectedDoctor) {
                // dd($this->selectedDoctor, $this->selectedService);
                $price = DoctorServiceShare::where('doctor_id', $this->selectedDoctor)
                    ->where('service_id', $this->selectedService)
                    ->first();
                // dd($this->selectedDoctor, $this->selectedService, $price);
                $this->price = $price->price ?? 0; // Get the price or default to 0
            } else {
                $this->price = $service->default_price; // Default price if service not found
            }
        }
    }

    public function editPrice($index)
    {
        $this->selectedServices[$index]['price'] = $this->changedPrice;
        $this->changedPrice = null; // Reset the changed price after editing
        Flux::modal("edit-price{$index}")->close();
        $this->calculateTotalPrice();
    }
    public function deleteService($index)
    {
        unset($this->selectedServices[$index]);
        $this->selectedServices = array_values($this->selectedServices); // Re-index the array
        $this->calculateTotalPrice();
    }

    public function calculateTotalPrice()
    {
        $this->totalPrice = array_sum(array_column($this->selectedServices, 'price'));
    }





    public function createServiceTransaction()
    {
        $this->validate([
            'patient.name' => 'required|string|max:255',
            'selectedServices' => 'required|array|min:1'
        ]);

        DB::beginTransaction();

        try {
            $patient = $this->createPatient();
            $transactions = $this->createServiceTransactions($patient);
            $this->createPayment($patient, $transactions);

            DB::commit();
            $this->showToast('success', 'Patient and Payment Created Successfully');
            // $this->print();
            $this->print($patient, $transactions);

            $this->resetForm();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->showToast('error', 'Transaction Failed: ' . $e->getMessage());
            return;
        }
    }

    private function createPatient()
    {
        $patient = Patient::create([
            'name' => $this->patient['name'],
            'contact' => $this->patient['contact'] ?? null,
            'age' => is_numeric($this->patient['age']) ? (int) $this->patient['age'] : null,
            'gender' => $this->patient['gender'] ?? null,
        ]);

        if (!$patient) {
            throw new \Exception('Failed to create patient');
        }

        return $patient;
    }

    private function createServiceTransactions($patient)
    {
        $transactions = [];

        foreach ($this->selectedServices as $service) {
            $share = DoctorServiceShare::where('doctor_id', $service['doctor_id'])
                ->where('service_id', $service['service_id'])
                ->first();

            $doctorSharePercent = $share->doctor_share_percent ?? 0;
            $hospitalSharePercent = $share->hospital_share_percent ?? 0;

            $price = $service['price'];
            $doctorShare = ($doctorSharePercent / 100) * $price;
            $hospitalShare = ($hospitalSharePercent / 100) * $price;

            $transaction = ServiceTransaction::create([
                'patient_id' => $patient->id,
                'service_id' => $service['service_id'],
                'doctor_id' => $service['doctor_id'],
                'price' => $price,
                'doctor_share' => $doctorShare,
                'hospital_share' => $hospitalShare,
                'booking' => false,
                'arrived' => true,
                'token' => $service['token'] ?? null
            ]);

            $transactions[] = $transaction;
        }

        return $transactions;
    }

    private function createPayment($patient, $transactions)
    {
        $payment = \App\Models\Payment::create([
            'patient_id' => $patient->id,
            'amount' => $this->totalPrice,
            'method' => $this->paymentMethod,
            'remarks' => 'Service Payment',
        ]);

        foreach ($transactions as $transaction) {
            \App\Models\PaymentService::create([
                'payment_id' => $payment->id,
                'service_transaction_id' => $transaction->id,
                'amount' => $transaction->price,
            ]);
        }
    }


    public function resetForm()
    {
        $this->patient = [
            'name' => '',
            'contact' => '',
            'age' => '',
            'gender' => '',
        ];
        $this->selectedServices = [];
        $this->totalPrice = 0;
        $this->token = null;
        $this->docs = [];
        $this->selectedDoctor = null;
        $this->price = null;
        $this->changedPrice = null;
        $this->selectedService = $this->services[0]->id;

        $this->mount();
    }

    public function showToast($type, $message)
    {
        $this->dispatch('notify', [
            'type' => $type,
            'message' => $message,
        ]);
    }


    private function print($patient, $transactions)
    {
        try {
            // Extract services for receipt
            $services = collect($transactions)->map(function ($tx) {
                return [
                    'name' => $tx->service->name,
                    'charged_price' => $tx->price,
                ];
            });

            // Get token from the consultation service (if any)
            $token = collect($transactions)
                ->first(fn($tx) => !is_null($tx->token))
                    ?->token;

            $this->printReceipt($patient, $services, $token, 0);

        } catch (\Exception $e) {
            logger()->error('Receipt print failed: ' . $e->getMessage());
        }
    }



}?>

<div>
    <div class=" p-4 m-4 rounded-lg border">
        <h2 class="text-lg font-semibold mb-4">Patient Information</h2>

        <div class=" grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Patient Info --}}


            <flux:input label="Patient Name" placeholder="Enter patient name" wire:model="patient.name" class="mb-4" />
            <flux:input label="Patient Contact" placeholder="Enter patient contact" wire:model="patient.contact"
                class="mb-4" />
            <flux:input label="Patient Age" placeholder="Enter patient age" wire:model="patient.age" class="mb-4" />
            <flux:select label="Patient Gender" placeholder="Select patient gender" wire:model="patient.gender"
                class="mb-4">
                <flux:select.option value="male">Male</flux:select.option>
                <flux:select.option value="female">Female</flux:select.option>
            </flux:select>

        </div>
    </div>
    <div class="p-4 pt-0 m-4 border rounded-lg">
        <div class="flex justify-between items-center">
            @if ($token)
                <h2 class="text-lg font-semibold mr-4">Token : {{ $token }}</h2>
            @endif
        </div>

        <div class="flex p-3 rounded-lg m-4 items-end gap-2">
            <!-- Service Select (flex-grow) -->
            <div class="flex-grow">
                <flux:select label="Service" placeholder="Select Service" wire:model.live="selectedService"
                    class="w-full">
                    @foreach ($services as $service)
                        <flux:select.option value="{{ $service->id }}">{{ $service->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            @if ($docs)

                <!-- Doctor Select (optional, doesn't grow) -->
                <div class="w-48 "> <!-- Fixed width, adjust as needed -->
                    <flux:select label="Doctor" placeholder="Select Doctor" wire:model.live="selectedDoctor" class="w-full">
                        @foreach ($docs as $doc)
                            <flux:select.option value="{{ $doc->doctor->id }}">{{ $doc->doctor->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            @endif

            <!-- Add Button (smaller, aligned to bottom) -->
            <div class="">
                <flux:button wire:click="addService" variant="primary" color="blue" class="px-6 py-2 text-sm">Add
                </flux:button>
            </div>
        </div>


        <div class="p-4 pt-2  m-4 rounded-lg border">
            <div class="grid grid-cols-5 p-2">
                <div class=" text-gray-400 text-sm text-center border-b p-1">Name</div>
                <div class=" text-gray-400 text-sm border-b p-1">Reffered By</div>
                <div class=" text-gray-400 text-sm border-b p-1">Price</div>
                <div class=" text-gray-400 text-sm border-b p-1">Token</div>
                <div class=" text-gray-400 text-sm border-b p-1"> Action</div>

            </div>
            <div class="grid grid-cols-5 p-2 ">
                @forelse ($selectedServices as $i => $s_service)


                    <div class=" font-bold  text-center border-b p-1">{{ $s_service['service_name'] }}</div>
                    <div class=" font-bold  border-b p-1">
                        {{ $s_service['doctor_name'] ? $s_service['doctor_name'] : 'N/A' }}
                    </div>
                    <div class=" font-bold  border-b p-1">{{ $s_service['price'] }}</div>
                    <div class=" font-bold  border-b p-1">{{ $s_service['token'] ?? '' }}</div>
                    <div class=" font-bold  border-b p-1">
                        {{-- <flux:button wire:click="editPrice({{ $i }})" size="sm" class=" mb-3 text-sm">Edit Price
                        </flux:button> --}}
                        <flux:modal.trigger name="edit-price{{ $i }}">
                            <flux:button>Edit Price</flux:button>
                        </flux:modal.trigger>

                        <flux:modal name="edit-price{{ $i }}" class="md:w-96">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Update Price</flux:heading>
                                    <flux:text class="mt-2">Make changes to the service price.</flux:text>
                                </div>

                                <flux:input autofocus wire:keydown.enter="editPrice({{ $i }})" wire:model="changedPrice"
                                    label="Price" placeholder="Service price" />
                                <div class="flex">
                                    <flux:spacer />

                                    <flux:button wire:click="editPrice({{ $i }})" variant="primary">Save changes
                                    </flux:button>
                                </div>
                            </div>
                        </flux:modal>
                        <flux:button wire:click="deleteService({{ $i }})" size="sm" class=" mb-3" variant="danger"
                            class="  text-sm">Remove</flux:button>
                    </div>
                @empty

                @endforelse

            </div>

            <div class="flex justify-end">
                <div class="flex items-center gap-4">
                    <span>Total : {{ $totalPrice }}</span>

                    <flux:select label="Payment Method" placeholder="Select payment method" wire:model="paymentMethod"
                        class="mb-4 w-48">
                        <flux:select.option value="Cash">Cash</flux:select.option>
                        <flux:select.option value="Online">Online</flux:select.option>
                    </flux:select>
                    <flux:button wire:click='createServiceTransaction' class="mt-4 mr-6" variant="primary"
                        color="green">Print</flux:button>
                </div>
            </div>

        </div>

    </div>

</div>