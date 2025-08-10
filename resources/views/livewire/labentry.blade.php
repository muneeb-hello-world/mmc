<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentLab;
use App\Models\DoctorLabShare;
use App\Models\LabTestTransaction;
use App\Traits\PrintsReceipt;

new class extends Component {
    use PrintsReceipt;

    // Patient & Doctor
    public $patient = ['name' => '', 'contact' => '', 'age' => '', 'gender' => ''];
    public $selectedDoctor = null;
    public $docs = [];

    // Lab Tests
    public $labTests;
    public $selectedTest;
    public $selectedTests = [];
    public $originalTests = [];

    // Pricing & Payment
    public $discount = null;
    public $totalPrice = 0;
    public $paymentMethod = "Cash";

    public function mount()
    {
        $this->labTests = LabTest::all();
        $this->selectedTest = $this->labTests->first()->id ?? null;
        $this->docs = DoctorLabShare::with('doctor')->get();
    }

    /* ------------------------
     | Discount Handling
     ------------------------ */
    public function updatedDiscount($value)
    {
        if (!count($this->selectedTests)) {
            return;
        }

        if ($value === null || $value === '') {
            $this->selectedTests = $this->originalTests;
            return $this->calculateTotalPrice();
        }

        if ($value < 0 || $value > 100) {
            $this->showToast('error', 'Discount must be between 0 and 100');
            $this->discount = null;
            $this->selectedTests = $this->originalTests;
            return $this->calculateTotalPrice();
        }

        $this->applyDiscount($value);
    }

    private function applyDiscount($percent)
    {
        $this->selectedTests = array_map(function ($test, $originalTest) use ($percent) {
            $cost = $originalTest['cost_price'];
            $originalProfit = $originalTest['profit'];

            $discountOnProfit = ($percent / 100) * $originalProfit;
            $newProfit = $originalProfit - $discountOnProfit;

            $test['profit'] = $newProfit;
            $test['price'] = round($cost + $newProfit, -1);

            return $test;
        }, $this->selectedTests, $this->originalTests);

        $this->calculateTotalPrice();
    }

    /* ------------------------
     | Test Selection
     ------------------------ */
    public function addTest()
    {
        $selectedTestModel = LabTest::find($this->selectedTest);
        if (!$selectedTestModel) {
            return $this->showToast('error', 'Invalid test selected.');
        }

        if (collect($this->selectedTests)->pluck('test_id')->contains($selectedTestModel->id)) {
            return $this->showToast('danger', 'This test is already added.');
        }

        $cost = $selectedTestModel->price * $selectedTestModel->cost_price_percentage / 100;
        $profit = $selectedTestModel->price - $cost;

        $testData = [
            'test_id'        => $selectedTestModel->id,
            'cost_price'     => $cost,
            'profit'         => $profit,
            'days'           => $selectedTestModel->days_required,
            'test_name'      => $selectedTestModel->name,
            'original_price' => $selectedTestModel->price,
            'price'          => $selectedTestModel->price,
        ];

        $this->selectedTests[] = $testData;
        $this->originalTests[] = $testData;

        $this->calculateTotalPrice();
    }

    public function deleteTest($index)
    {
        unset($this->selectedTests[$index], $this->originalTests[$index]);
        $this->selectedTests = array_values($this->selectedTests);
        $this->originalTests = array_values($this->originalTests);

        $this->calculateTotalPrice();
    }

    private function calculateTotalPrice()
    {
        $this->totalPrice = array_sum(array_column($this->selectedTests, 'price'));
    }

    /* ------------------------
     | Main Transaction Flow
     ------------------------ */
    public function createLabTestTransaction()
    {
        $this->validate([
            'patient.name'     => 'required|string|max:255',
            'patient.contact'  => 'required|string|max:255',
            'patient.age'      => 'required|string|max:255',
            'selectedTests'    => 'required|array|min:1',
            'paymentMethod'    => 'required|string|in:Cash,Online',
        ]);

        DB::beginTransaction();

        try {
            $patient      = $this->createPatient();
            $transactions = $this->createLabTransactions($patient);
            $this->createLabPayment($patient, $transactions);

            DB::commit();

            $this->showToast('success', 'Test Added Successfully');
            $this->printReceipt($patient);
            $this->resetForm();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->showToast('error', 'Transaction Failed: ' . $e->getMessage());
        }
    }

    private function createPatient()
    {
        $patient = Patient::create([
            'name'   => $this->patient['name'],
            'contact'=> $this->patient['contact'] ?? null,
            'age'    => is_numeric($this->patient['age']) ? (int) $this->patient['age'] : null,
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
            $profit = $test['price'] - $test['cost_price'];

            $doctorShare = 0;
            $hospitalShare = $profit;

            if ($this->selectedDoctor) {
                $share = $this->docs->where('doctor_id', $this->selectedDoctor)->first();
                $doctorSharePercent = $share->doctor_share_percent ?? 0;
                $hospitalSharePercent = $share->hospital_share_percent ?? 0;

                $doctorShare = ($doctorSharePercent / 100) * $profit;
                $hospitalShare = ($hospitalSharePercent / 100) * $profit;
            }

            $transactionData = [
                'patient_id'     => $patient->id,
                'lab_test_id'    => $test['test_id'],
                'amount'         => $test['price'],
                'doctor_share'   => $doctorShare,
                'hospital_share' => $hospitalShare,
            ];

            if ($this->selectedDoctor) {
                $transactionData['doctor_id'] = $this->selectedDoctor;
            }

            $transactions[] = LabTestTransaction::create($transactionData);
        }

        return $transactions;
    }

    private function createLabPayment($patient, $transactions)
    {
        $payment = Payment::create([
            'patient_id' => $patient->id,
            'amount'     => $this->totalPrice,
            'method'     => $this->paymentMethod,
            'remarks'    => 'Lab test payment',
        ]);

        foreach ($transactions as $txn) {
            PaymentLab::create([
                'payment_id'               => $payment->id,
                'lab_test_transaction_id'  => $txn->id,
                'amount'                   => $txn->amount,
            ]);
        }
    }

    /* ------------------------
     | Printing
     ------------------------ */
    private function printReceipt($patient)
    {
        try {
            $tests = collect($this->selectedTests)->map(fn($test) => [
                'name'             => $test['test_name'],
                'original_price'   => $test['original_price'],
                'discounted_price' => $test['price'],
            ]);

            $totalOriginal  = collect($this->selectedTests)->sum('original_price');
            $discountPercent= $this->discount;
            $finalTotal     = $this->totalPrice;

            $this->printLabReceipt($patient, $tests, $totalOriginal, $discountPercent, $finalTotal);
        } catch (\Exception $e) {
            logger()->error('Receipt print failed: ' . $e->getMessage());
        }
    }

    /* ------------------------
     | Utilities
     ------------------------ */
    public function resetForm()
    {
        $this->patient = ['name' => '', 'contact' => '', 'age' => '', 'gender' => ''];
        $this->selectedTests = [];
        $this->originalTests = [];
        $this->selectedTest = $this->labTests->first()->id ?? null;
        $this->discount = null;
        $this->totalPrice = 0;
        $this->paymentMethod = "Cash";
        $this->selectedDoctor = null;
    }

    public function showToast($type, $message)
    {
        $this->dispatch('notify', [
            'type'    => $type,
            'message' => $message,
        ]);
    }
};
?>

<div>
    <div class=" p-4 m-4 rounded-lg border">
        <h2 class="text-lg font-semibold mb-4">Patient Information</h2>

        <div class=" grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Patient Info --}}


            <flux:input label="Patient Name" placeholder="Enter patient name" wire:model="patient.name" class="mb-4" />
            <flux:input label="Patient Contact" type="number" placeholder="Enter patient contact" wire:model="patient.contact"
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