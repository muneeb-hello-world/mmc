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
<div class="min-h-screen py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header Card -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 mb-8 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-700 dark:to-purple-700 px-6 py-8">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                    <h1 class="text-3xl font-bold text-white">Case Information</h1>
                    @if ($status == 'completed')
                    <div class="flex items-center gap-2 bg-green-500/20 px-4 py-2 rounded-full border border-green-400/30">
                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        <span class="capitalize text-lg font-semibold text-green-100">{{ $status }}</span>
                    </div>
                    @else
                    <div class="flex items-center gap-2 bg-yellow-500/20 px-4 py-2 rounded-full border border-yellow-400/30">
                        <div class="w-2 h-2 bg-yellow-400 rounded-full animate-pulse"></div>
                        <span class="capitalize text-lg font-semibold text-yellow-100">{{ $status }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <!-- Left Column - Patient & Case Info -->
            <div class="xl:col-span-2 space-y-8">
                <!-- Patient Information Card -->
                <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Patient Information</h2>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <flux:input disabled label="Patient Name" placeholder="Enter patient name" wire:model="patient.name"
                                class="transition-all duration-200 hover:shadow-md focus-within:shadow-lg" />
                            <flux:input disabled label="Patient Contact" placeholder="Enter patient contact"
                                wire:model="patient.contact" type="tel" class="transition-all duration-200 hover:shadow-md focus-within:shadow-lg" />
                            <flux:input disabled label="Patient Age" placeholder="Enter patient age" wire:model="patient.age"
                                class="transition-all duration-200 hover:shadow-md focus-within:shadow-lg" type="number" />
                            <flux:select disabled label="Patient Gender" placeholder="Select patient gender"
                                wire:model="patient.gender" class="transition-all duration-200 hover:shadow-md focus-within:shadow-lg">
                                <flux:select.option value="male">Male</flux:select.option>
                                <flux:select.option value="female">Female</flux:select.option>
                            </flux:select>
                        </div>
                    </div>
                </div>

                <!-- Case Information Card -->
                <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/50 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Case Information</h2>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <flux:select disabled readonly label="Doctor" placeholder="Select doctor" wire:model="doctor_id"
                                class="transition-all duration-200 hover:shadow-md focus-within:shadow-lg">
                                <flux:select.option value="{{ $doctor->id }}">{{ $doctor->name }}</flux:select.option>
                            </flux:select>
                            <flux:input disabled label="Operation" placeholder="Enter operation name" wire:model="title"
                                class="transition-all duration-200 hover:shadow-md focus-within:shadow-lg" />
                            
                            <flux:input disabled label="Operation Date" placeholder="Enter operate date" wire:model="scheduled_date"
                                type="date" class="transition-all duration-200 hover:shadow-md focus-within:shadow-lg" />
                            <flux:select disabled label="Room" wire:model="room" class="transition-all duration-200 hover:shadow-md focus-within:shadow-lg">
                                <flux:select.option selected value="ward">Ward</flux:select.option>
                                <flux:select.option value="room_1">Room 1</flux:select.option>
                                <flux:select.option value="room_2">Room 2</flux:select.option>
                                <flux:select.option value="room_3">Room 3</flux:select.option>
                            </flux:select>
                            <flux:input disabled label="Final Package" placeholder="Enter final package" type="number"
                                wire:model="final_price" class="transition-all duration-200 hover:shadow-md focus-within:shadow-lg" />
                            <flux:input disabled label="Balance" placeholder="Enter balance" wire:model="balance"
                                class="transition-all duration-200 hover:shadow-md focus-within:shadow-lg" />
                            <flux:input readonly label="Total Paid" placeholder="Enter final package" type="number"
                                wire:model="totalPaid" class="transition-all duration-200 hover:shadow-md focus-within:shadow-lg" />
                            <flux:input readonly label="Status"  
                                wire:model="status" class="capitalize transition-all duration-200 hover:shadow-md focus-within:shadow-lg" />

                            <div class="col-span-full">
                                <flux:textarea disabled wire:model="notes" label="Operation Notes"
                                    placeholder="Enter operation notes..." 
                                    class="transition-all duration-200 hover:shadow-md focus-within:shadow-lg min-h-[120px]" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Financial Summary -->
            <div class="xl:col-span-1">
                <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden sticky top-6">
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/50 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Financial Summary</h3>
                        </div>
                    </div>
                    
                    <div class="p-6 space-y-4">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                            <div class="text-sm font-medium text-blue-600 dark:text-blue-400">Final Package</div>
                            <div class="text-2xl font-bold text-blue-900 dark:text-blue-300">₨ {{ number_format($final_price ?? 0) }}</div>
                        </div>
                        
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                            <div class="text-sm font-medium text-green-600 dark:text-green-400">Total Paid</div>
                            <div class="text-2xl font-bold text-green-900 dark:text-green-300">₨ {{ number_format($totalPaid ?? 0) }}</div>
                        </div>
                        
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 border border-orange-200 dark:border-orange-800">
                            <div class="text-sm font-medium text-orange-600 dark:text-orange-400">Remaining Balance</div>
                            <div class="text-2xl font-bold text-orange-900 dark:text-orange-300">₨ {{ number_format($balance ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments Section -->
        <div class="mt-8">
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Payment History</h2>
                        </div>
                        
                        <flux:modal.trigger name="edit-profile">
                            <flux:button class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold px-6 py-3 rounded-lg shadow-md hover:shadow-lg transform hover:scale-105 transition-all duration-200 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Payment
                            </flux:button>
                        </flux:modal.trigger>

                        <flux:modal name="edit-profile" class="md:w-96">
                            <div class="space-y-6 p-6">
                                <div class="text-center">
                                    <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                    </div>
                                    <flux:heading size="lg" class="text-gray-900 dark:text-white">Add Payment</flux:heading>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Record a new payment for this case</p>
                                </div>

                                <flux:input label="Payment Amount" placeholder="{{ $remainingBalance ?? 0 }}" min="1" max="{{ $remainingBalance ?? 0 }}"
                                    wire:model='paymentAmount' type="number" 
                                    class="transition-all duration-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />

                                <flux:select label="Payment Method" placeholder="Select payment method"
                                    wire:model="paymentMethod" class="transition-all duration-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <flux:select.option selected value="Cash">💵 Cash</flux:select.option>
                                    <flux:select.option value="Online">💳 Online</flux:select.option>
                                </flux:select>

                                <div class="flex gap-3 pt-4">
                                    <flux:modal.trigger name="edit-profile">
                                        <flux:button variant="ghost" class="flex-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300">
                                            Cancel
                                        </flux:button>
                                    </flux:modal.trigger>

                                    <flux:button :disabled="!(
                is_numeric($paymentAmount ?? null)
                && $paymentAmount > 0
                && $paymentAmount <= ($remainingBalance ?? 0)
                && in_array($paymentMethod, ['Cash','Online'], true)
            )" wire:click="pay" variant="primary" class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                        Save Payment
                                    </flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </div>
                </div>

                <div class="overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100 dark:bg-gray-700/70">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                        Patient Name
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                        Remarks
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                        Amount
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                        Method
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                @foreach ($payments as $pay)
                                <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center flex-shrink-0">
                                                <span class="text-xs font-semibold text-blue-600 dark:text-blue-400">
                                                    {{ strtoupper(substr($pay->patient->name, 0, 1)) }}
                                                </span>
                                            </div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $pay->patient->name}}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $pay->remarks ?: 'No remarks' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            ₨ {{ number_format($pay->amount) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium capitalize
                                            {{ $pay->method === 'Cash' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' }}">
                                            @if($pay->method === 'Cash')
                                                💵
                                            @else
                                                💳
                                            @endif
                                            {{ $pay->method }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                                
                                @if(count($payments) === 0)
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                                <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">No payments yet</h3>
                                                <p class="text-gray-500 dark:text-gray-400">Start by adding the first payment for this case.</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>