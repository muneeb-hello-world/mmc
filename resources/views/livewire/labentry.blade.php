<?php

use Livewire\Volt\Component;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentLab;
use App\Models\DoctorLabShare;
use App\Models\LabTestTransaction;
use App\Traits\PrintsReceipt;



new class extends Component {
    use PrintsReceipt;

    public $patient = ['name' => '', 'contact' => '', 'age' => '', 'gender' => ''];
    public $labTests;
    public $originalTests = [];
    public $paymentMethod = "Cash";

    public $docs = [];
    public $discount = null;

    public $selectedTest;
    public $selectedTests = [];
    public $selectedDoctor;
    public $price;
    public $totalPrice = 0;


    public function mount()
    {
        $this->labTests = LabTest::all();
        $this->selectedTest = $this->labTests->first()->id ?? null; // Set default selected test
        $this->docs = DoctorLabShare::with('doctor')->get();
        // dd($this->docs);

    }

    public function UpdatedDiscount($value)
    {
        if ($value === null || $value === '') {
            // Reset prices to original when discount is cleared
            $this->selectedTests = $this->originalTests;
            $this->calculateTotalPrice();
            return;
        }

        if ($value < 0 || $value > 100) {
            $this->showToast('error', 'Discount must be between 0 and 100');
            $this->discount = null;
            $this->selectedTests = $this->originalTests;
            $this->calculateTotalPrice();
            return;
        }

        $this->selectedTests = array_map(function ($test, $originalTest) use ($value) {
            $cost = $originalTest['cost_price'];
            $originalProfit = $originalTest['profit'];

            $discountOnProfit = ($value / 100) * $originalProfit;
            $newProfit = $originalProfit - $discountOnProfit;

            $test['profit'] = $newProfit;
            $test['price'] = round($cost + $newProfit, -1);

            return $test;
        }, $this->selectedTests, $this->originalTests);

        $this->calculateTotalPrice();
    }


    public function addTest()
    {
        $SelectedTest = LabTest::find($this->selectedTest);

        // Prevent duplicate test
        $alreadyAdded = collect($this->selectedTests)
            ->pluck('test_id')
            ->contains($SelectedTest->id);

        if ($alreadyAdded) {
            $this->showToast('danger', 'This test is already added.');
            return;
        }

        $cost = $SelectedTest->price * $SelectedTest->cost_price_percentage / 100;
        $profit = ($SelectedTest->price - $cost);

        $this->selectedTests[] = [
            'test_id' => $SelectedTest->id,
            'cost_price' => $cost,
            'profit' => $profit,
            'days' => $SelectedTest->days_required,
            'test_name' => $SelectedTest->name,
            'original_price' => $SelectedTest->price,
            'price' => $SelectedTest->price, // will change on discount
        ];



        $this->originalTests = $this->selectedTests;
        $this->calculateTotalPrice();
    }



    public function deleteTest($index)
    {
        unset($this->selectedTests[$index]);
        unset($this->originalTests[$index]);

        $this->selectedTests = array_values($this->selectedTests);
        $this->originalTests = array_values($this->originalTests);

        $this->calculateTotalPrice();

    }

    public function calculateTotalPrice()
    {
        $this->totalPrice = array_sum(array_column($this->selectedTests, 'price'));
    }

    // STart
    public function createLabTestTransaction()
    {
        $this->validate([
            'patient.name' => 'required|string|max:255',
            'patient.contact' => 'required|string|max:255',
            'patient.age' => 'required|string|max:255',
            'selectedTests' => 'required|array|min:1',
            'paymentMethod' => 'required|string|in:Cash,Online',
        ]);

        DB::beginTransaction();

        try {
            $patient = $this->createPatient();
            $transactions = $this->createLabTransactions($patient);
            $this->createLabPayment($patient, $transactions);
            DB::commit();

            $this->showToast('success', 'Test Added Successfully');
            $this->print($patient, $transactions);
            $this->resetForm();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->showToast('error', 'Transaction Failed: ' . $e->getMessage());
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

    private function createLabTransactions($patient)
    {
        $transactions = [];

        foreach ($this->selectedTests as $test) {
            $labTest = LabTest::find($test['test_id']);
            $price = $test['price'];
            $costPrice = ($labTest->price * $labTest->cost_price_percentage / 100);
            $profit = $price - $costPrice;

            $doctorShare = 0;
            $hospitalShare = $profit;

            if ($this->selectedDoctor) {
                $share = $this->docs->where('doctor_id', $this->selectedDoctor)->first();
                $doctorSharePercent = $share->doctor_share_percent ?? 0;
                $hospitalSharePercent = $share->hospital_share_percent ?? 0;

                $doctorShare = ($doctorSharePercent / 100) * $profit;
                $hospitalShare = ($hospitalSharePercent / 100) * $profit;
            }

            $transaction = LabTestTransaction::create([
                'patient_id' => $patient->id,
                'lab_test_id' => $test['test_id'],
                'doctor_id' => $this->selectedDoctor,
                'amount' => $price,
                'doctor_share' => $doctorShare,
                'hospital_share' => $hospitalShare,
            ]);

            $transactions[] = $transaction;
        }

        return $transactions;
    }

    private function createLabPayment($patient, $transactions)
    {
        $payment = Payment::create([
            'patient_id' => $patient->id,
            'amount' => $this->totalPrice,
            'method' => $this->paymentMethod,
            'remarks' => 'Lab test payment',
        ]);

        foreach ($transactions as $txn) {
            PaymentLab::create([
                'payment_id' => $payment->id,
                'lab_test_transaction_id' => $txn->id,
                'amount' => $txn->amount,
            ]);
        }
    }

    private function print($patient, $transactions)
    {
        try {
            $tests = collect($this->selectedTests)->map(function ($test) {
                return [
                    'name' => $test['test_name'],
                    'original_price' => $test['original_price'],
                    'discounted_price' => $test['price'],
                ];
            });

            $totalOriginal = collect($this->selectedTests)->sum('original_price');
            $discountPercent = $this->discount;
            $finalTotal = $this->totalPrice;

            $this->printLabReceipt($patient, $tests, $totalOriginal, $discountPercent, $finalTotal);



        } catch (\Exception $e) {
            logger()->error('Receipt print failed: ' . $e->getMessage());
        }
    }


    // End
    public function resetForm()
    {
        $this->patient = [
            'name' => '',
            'contact' => '',
            'age' => '',
            'gender' => '',
        ];
        $this->selectedTests = [];
        $this->selectedTest = $this->labTests->first()->id ?? null; // Set default selected test
        $this->totalPrice = 0;
        $this->price = null;
        $this->paymentMethod = "Cash";
        $this->mount();

    }

    public function showToast($type, $message)
    {
        $this->dispatch('notify', [
            'type' => $type,
            'message' => $message,
        ]);
    }



}?>

<div>
    <div class=" p-4 m-4 rounded-lg border">
        <h2 class="text-lg font-semibold mb-4">Patient Information</h2>

        <div class=" grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Patient Info --}}


            <flux:input label="Patient Name" placeholder="Enter patient name" wire:model="patient.name" class="mb-4" />
            <flux:input label="Patient Contact" placeholder="Enter patient contact" wire:model="patient.contact"
                type="number" class="mb-4" />
            <flux:input label="Patient Age" placeholder="Enter patient age" wire:model="patient.age" class="mb-4"
                type="number" />
            <flux:select label="Patient Gender" placeholder="Select patient gender" wire:model="patient.gender"
                class="mb-4">
                <flux:select.option value="male">Male</flux:select.option>
                <flux:select.option value="female">Female</flux:select.option>
            </flux:select>
            @if ($docs)

                <flux:select label="Reffered By" placeholder="Select Doctor" wire:model="selectedDoctor" class="w-full">
                    <flux:select.option value="" selected>None</flux:select.option>
                    @foreach ($docs as $doc)
                        @if ($doc->doctor)
                            <flux:select.option value="{{ $doc->doctor->id }}">{{ $doc->doctor->name }}</flux:select.option>
                        @endif
                    @endforeach
                </flux:select>
            @endif


        </div>
    </div>
    <div class="p-4 m-4 border rounded-lg">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold mb-4 ">Test Information</h2>
        </div>

        <div class="flex p-3 rounded-lg m-4 items-end gap-2">
            <!-- Service Select (flex-grow) -->
            <div class="flex-grow">
                <flux:select label="Lab Test" placeholder="Select Lab Test" wire:model.live="selectedTest"
                    class="w-full">
                    @foreach ($labTests as $test)
                        <flux:select.option value="{{ $test->id }}">{{ $test->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>


            <!-- Add Button (smaller, aligned to bottom) -->
            <div class="">
                <flux:button wire:click="addTest" variant="primary" color="blue" class="px-6 py-2 text-sm">Add
                </flux:button>
            </div>
        </div>


        <div class="p-4 pt-2  m-4 rounded-lg border">
            <div class="grid grid-cols-4 p-2">
                <div class=" text-gray-400 text-sm text-center border-b p-1">Name</div>
                <div class=" text-gray-400 text-sm border-b p-1">Price</div>
                <div class=" text-gray-400 text-sm border-b p-1">Recieve On</div>
                <div class=" text-gray-400 text-sm border-b p-1"> Action</div>

            </div>
            <div class="grid grid-cols-4 p-2 ">
                @forelse ($selectedTests as $i => $s_test)


                    <div class=" font-bold  text-center border-b p-1">{{ $s_test['test_name'] }}</div>
                    <div class=" font-bold  border-b p-1">{{ $s_test['price'] }}</div>
                    <div class="font-bold border-b p-1">
                        {{ $s_test['days'] ? \Carbon\Carbon::today()->addDays((int) $s_test['days'])->format('d-M-Y') : 'N/A' }}
                    </div>


                    <div class=" font-bold  border-b p-1">
                        <flux:button wire:click="deleteTest({{ $i }})" size="sm" class="text-sm mb-3" variant="danger">
                            Remove</flux:button>
                    </div>
                @empty

                @endforelse

            </div>

            <div class="flex justify-end">
                <div class="">
                    <div class=" flex justify-center items-center gap-4">
                        <flux:input label="Discount percent" placeholder="Discount" type="number"
                            wire:model.live="discount" class="mb-4" />
                        <flux:select label="Payment Method" placeholder="Select payment method"
                            wire:model="paymentMethod" class="mb-4 w-48">
                            <flux:select.option value="Cash">Cash</flux:select.option>
                            <flux:select.option value="Online">Online</flux:select.option>
                        </flux:select>
                    </div>
                    <div class=" flex justify-between items-center gap-4">

                        <span>Total : {{ $totalPrice }}</span>
                        <flux:button wire:click='createLabTestTransaction' class="mt-4 mr-6" variant="primary"
                            color="green">Print</flux:button>
                    </div>

                </div>
            </div>

        </div>

    </div>

</div>