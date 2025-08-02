<?php

use Livewire\Volt\Component;
use App\Models\CaseModel;
use App\Models\Doctor;
use App\Models\Payment;
use App\Models\PaymentCase;
use App\Traits\ToastHelper;



new class extends Component {
    use ToastHelper;

    public $case;
    public $totalPaid;

    public $editing = 0;

    public $patient = [
        'name' => '',
        'contact' => '',
        'age' => '',
        'gender' => ''
    ];
    public $doctor_id, $title, $final_price, $scheduled_date, $room, $notes;
    public $paymentAmount, $paymentMethod, $remainingBalance, $status;
    public $balance;

    public $doctor;
    public $payments;


    public function mount($id)
    {
        $this->case = CaseModel::with('paymentCases')->find($id);
        // dd($this->payments);
        // dd($this->case->paymentCases[0]->payment);
        $this->getPayments();


        $this->patient = [
            'name' => $this->case->patient->name ?? '',
            'contact' => $this->case->patient->contact ?? '',
            'age' => $this->case->patient->age ?? '',
            'gender' => $this->case->patient->gender ?? ''
        ];


        $this->doctor_id = $this->case->doctor_id;
        $this->balance = $this->case->balance;
        $this->title = $this->case->title;
        $this->final_price = $this->case->final_price;
        $date = \Carbon\Carbon::parse($this->case->scheduled_date);
        $this->scheduled_date = $date?->format('Y-m-d');
        $this->room = $this->case->room_type;
        $this->notes = $this->case->notes;
        $totalPaid = $this->case->paymentCases->sum('amount');
        $this->remainingBalance = $this->case->final_price - $totalPaid;
        $this->balance = $this->remainingBalance;

        $this->status = $this->case->status;

        $this->paymentMethod = "Cash";
        $this->paymentAmount = $this->balance;

        $this->doctor = Doctor::find($this->case->doctor_id);
        $this->refreshCaseAndRemaining();

    }

    public function rules()
    {
        // Ensure paymentAmount is required, positive, and not more than remaining balance
        return [
            'paymentAmount' => ['required', 'numeric', 'min:1', 'lte:remainingBalance'],
            'paymentMethod' => ['required', 'in:Cash,Online'],
        ];
    }

    protected function refreshCaseAndRemaining()
    {
        $this->case = CaseModel::with('paymentCases')->find($this->case->id);
        $totalPaid = $this->case->paymentCases->sum('amount');
        $this->totalPaid = $totalPaid;
        $this->remainingBalance = max(0, $this->case->final_price - $totalPaid);
        $this->balance = $this->remainingBalance;
    }



    public function getPayments()
    {
        $this->payments = Payment::where('patient_id', $this->case->patient->id)->get();

    }

    public function pay()
    {
        $this->refreshCaseAndRemaining();

        $this->validate();

        $payment = \App\Models\Payment::create([
            'patient_id' => $this->case->patient->id,
            'amount' => $this->paymentAmount,
            'method' => $this->paymentMethod,
            'remarks' => 'Advance payment for case',
        ]);

        $p = \App\Models\PaymentCase::create([
            'payment_id' => $payment->id,
            'case_model_id' => $this->case->id,
            'amount' => $this->paymentAmount,
        ]);

        if ($p) {
            $this->showToast('success', 'Payment Added Successfully ');
            // Refresh case with related payments
            $this->case = CaseModel::with('paymentCases.payment')->find($this->case->id);

            // Re-fetch payments
            $this->getPayments();

            // Recalculate total paid amount for this case
            $totalPaid = $this->case->paymentCases->sum('amount');

            // Update the balance and remaining
            $this->balance = $this->case->final_price - $totalPaid;
            $this->remainingBalance = $this->balance;

            // Optionally update status
            if ($this->remainingBalance > 0) {
                $this->status = 'pending';
            } elseif ($this->remainingBalance == 0) {
                $this->status = 'completed';
            }

            // Optionally persist new balance and status
            $this->case->balance = $this->balance;
            $this->case->status = $this->status;
            $this->case->save();
            $this->showToast('success', 'Case Status Updated Successfully ');


            $this->modal('edit-profile')->close();
            $this->mount($this->case->id);
        }
    }

}; ?>

<div>
    <div class=" p-4 m-4 rounded-lg border">
        <div class="flex justify-betweenx  items-center gap-6 ">

            <h2 class="text-2xl font-semibold mb-6 text-center">Case Information </h2>
            @if ($status == 'completed')
            <span class=" capitalize text-xl mb-6 font-bold text-green-400">{{ $status }}</span>
            @else
            <span class=" capitalize text-xl mb-6 font-bold">{{ $status }}</span>
            @endif
        </div>
        <div class=" p-6 m-4 rounded-lg border">
            <h2 class="text-lg font-semibold mb-4">Patient Information</h2>

            <div class=" grid grid-cols-1 md:grid-cols-2 gap-4 px-2">

                {{-- Patient Info --}}


                <flux:input disabled label="Patient Name" placeholder="Enter patient name" wire:model="patient.name"
                    class="mb-4" />
                <flux:input disabled label="Patient Contact" placeholder="Enter patient contact"
                    wire:model="patient.contact" type="tel" class="mb-4" />
                <flux:input disabled label="Patient Age" placeholder="Enter patient age" wire:model="patient.age"
                    class="mb-4" type="number" />
                <flux:select disabled label="Patient Gender" placeholder="Select patient gender"
                    wire:model="patient.gender" class="mb-4">
                    <flux:select.option value="male">Male</flux:select.option>
                    <flux:select.option value="female">Female</flux:select.option>
                </flux:select>

            </div>
        </div>
        <div class=" p-6 m-4 rounded-lg border">
            <h2 class="text-lg font-semibold mb-4">Case Information</h2>

            <div class=" grid grid-cols-1 md:grid-cols-2 gap-4 px-2">

                {{-- Patient Info --}}

                <flux:select disabled readonly label="Doctor" placeholder="Select doctor" wire:model="doctor_id"
                    class="mb-4">
                    <flux:select.option value="{{ $doctor->id }}">{{ $doctor->name }}</flux:select.option>
                </flux:select>
                <flux:input disabled label="Operation" placeholder="Enter operation name" wire:model="title"
                    class="mb-4" />
                
                <flux:input disabled label="Operate Date" placeholder="Enter operate date" wire:model="scheduled_date"
                    type="date" class="mb-4" />
                <flux:select disabled label="Room" wire:model="room" class="mb-4">
                    <flux:select.option selected value="ward">Ward</flux:select.option>
                    <flux:select.option value="room_1">Room 1</flux:select.option>
                    <flux:select.option value="room_2">Room 2</flux:select.option>
                    <flux:select.option value="room_3">Room 3</flux:select.option>
                </flux:select>
                <flux:input disabled label="Final Package" placeholder="Enter final package" type="number"
                    wire:model="final_price" class="mb-4" />
                <flux:input disabled label="Balance" placeholder="Enter operate date" wire:model="balance"
                    class="mb-4" />
                    <flux:input readonly label="Total Paid" placeholder="Enter final package" type="number"
                    wire:model="totalPaid" class="mb-4" />
                    <flux:input readonly label="Status"  
                    wire:model="status" class="mb-4 capitalize" />

                <div class="col-span-2">


                    <flux:textarea disabled wire:model="notes" label="Operation notes"
                        placeholder="Enter operation notes..." />
                </div>


            </div>
        </div>
        <div class=" p-6 m-4 rounded-lg border">
            <div class=" flex items-center justify-between">

                <h2 class="text-lg font-semibold mb-4">Payments Information</h2>
                <div class=" pb-2">
                    <flux:modal.trigger name="edit-profile">
                        <flux:button>Add Payment</flux:button>
                    </flux:modal.trigger>

                    <flux:modal name="edit-profile" class="md:w-96">
                        <div class="space-y-6">
                            <div>
                                <flux:heading size="lg">Add Case Payment</flux:heading>
                            </div>

                            <flux:input label="Amount" placeholder="{{ $remainingBalance ?? 0 }}" min="1" max="{{ $remainingBalance ?? 0 }}"
                                wire:model='paymentAmount' type="number" />

                            <flux:select label="Payment Method" placeholder="Select payment method"
                                wire:model="paymentMethod" class="mb-4 w-48">
                                <flux:select.option selected value="Cash">Cash</flux:select.option>
                                <flux:select.option value="Online">Online</flux:select.option>
                            </flux:select>
                            <div class="flex">
                                <flux:spacer />

                                <flux:button :disabled="!(
        is_numeric($paymentAmount ?? null)
        && $paymentAmount > 0
        && $paymentAmount <= ($remainingBalance ?? 0)
        && in_array($paymentMethod, ['Cash','Online'], true)
    )" wire:click="pay" variant="primary">Save changes</flux:button>
                            </div>
                        </div>
                    </flux:modal>
                </div>
            </div>



            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                Name
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Remarks
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Payment
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Method
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $pay)

                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                                <th scope="row"
                                    class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $pay->patient->name}}
                                </th>
                                <td class="px-6 py-4">
                                    {{ $pay->remarks }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $pay->amount }}
                                </td>
                                <td class="px-6 py-4 capitalize">
                                    {{ $pay->method }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Table Section -->

        <!-- End Table Section -->
    </div>
</div>