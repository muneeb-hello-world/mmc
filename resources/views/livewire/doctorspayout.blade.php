<?php
use Livewire\Volt\Component;
use App\Models\Doctor;
use App\Models\ServiceTransaction;
use App\Models\LabTestTransaction;
use App\Models\DoctorPayout;
use Carbon\Carbon;

new class extends Component {
    public $doctors;
    public $selectedDoctor;
    public $fromDate;
    public $fromDateShow;
    public $toDate;
    public $toDateShow;

    public $unpaidServices = [];
    public $unpaidLabs = [];
    public $totalAmount = 0;

    public function mount()
    {
        $this->doctors = Doctor::all();
        // dd($this->doctors);


        if ($this->doctors->isNotEmpty()) {
            $this->selectedDoctor = 5;
            // dd($this->selectedDoctor);

            $this->updatedSelectedDoctor(); // initialize
        }
    }

    public function updatedSelectedDoctor()
    {
        $doctor = Doctor::find($this->selectedDoctor);
        if (!$doctor)
            return;
        $today = Carbon::today();

        $this->toDate = $today->toDateString();

        if ($doctor->payout_frequency == 1) {
            $this->fromDate = $today->toDateString();
        } else {
            $this->fromDate = $today->copy()->subDays($doctor->payout_frequency)->toDateString();
        }


        // dd([
        //     'payout' => $doctor->payout_frequency,
        //     'from' => $this->fromDate,
        //     'to' => $this->toDate
        // ]);

        // dd($doctor->payout_frequency);


        // 🔧 Adjust to include whole day time range
        $this->fromDate = Carbon::parse($this->fromDate)->startOfDay();
        $this->fromDateShow = Carbon::parse($this->fromDate)->startOfDay()->toDateString();
        $this->toDate = Carbon::parse($this->toDate)->endOfDay();
        $this->toDateShow = Carbon::parse($this->toDate)->endOfDay()->toDateString();

        // $this->fromDate = Carbon::parse($this->fromDate)->startOfDay();
        // $this->toDate = Carbon::parse($this->toDate)->endOfDay();

        $this->loadUnpaidTransactions();
    }


    public function loadUnpaidTransactions()
    {
        $this->unpaidServices = ServiceTransaction::with('patient')
            ->where('doctor_id', $this->selectedDoctor)
            ->whereBetween('created_at', [$this->fromDate, $this->toDate])
            ->whereNull('doctor_payout_id')
            ->where('arrived', true)
            ->get();
        // dd($this->unpaidServices);

        $this->unpaidLabs = LabTestTransaction::with('patient')
            ->where('doctor_id', $this->selectedDoctor)
            ->whereBetween('created_at', [$this->fromDate, $this->toDate])
            ->whereNull('doctor_payout_id')
            ->get();

        $this->totalAmount = $this->unpaidServices->sum('doctor_share') +
            $this->unpaidLabs->sum('doctor_share');
    }

    public function payDoctor()
    {
        if ($this->totalAmount <= 0) {
            session()->flash('error', 'No payout due.');
            return;
        }

        $payout = DoctorPayout::create([
            'doctor_id' => $this->selectedDoctor,
            'amount' => $this->totalAmount,
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'paid_at' => now(),
            'method' => 'cash', // or dynamic later
            'notes' => 'Auto-generated payout',
        ]);

        // Attach transactions to payout
        foreach ($this->unpaidServices as $tx) {
            $tx->doctor_payout_id = $payout->id;
            $tx->save();
        }

        foreach ($this->unpaidLabs as $tx) {
            $tx->doctor_payout_id = $payout->id;
            $tx->save();
        }
        // dd($this->fromDate , $this->toDate);

        // Refresh
        $this->loadUnpaidTransactions();
        session()->flash('success', 'Payout completed successfully.');
    }
};

?>
<div>

    <!-- Additional CSS for enhanced styling -->
    <style>
        .form-select,
        .form-input {
            @apply block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500;
        }

        .hover\:bg-gray-50:hover {
            background-color: #f9fafb;
        }

        .dark .hover\:bg-gray-50:hover {
            background-color: #374151;
        }

        /* Loading animation */
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .fa-spin {
            animation: spin 1s linear infinite;
        }
    </style>
    <div class="max-w-6xl mx-auto p-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg transition-colors duration-200">
        <div class="space-y-6">
            <!-- Header -->
            <div class="border-b border-gray-200 dark:border-gray-600 pb-4">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Doctor Payment Processing</h2>
                <p class="text-gray-600 dark:text-gray-300">Manage and process doctor payouts</p>
            </div>

            <!-- Doctor Selection Card -->
            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg transition-colors duration-200">
                <flux:select label="Select Doctor" wire:model.live="selectedDoctor">
                    @foreach($doctors as $doc)
                        <flux:select.option value="{{ $doc->id }}">
                            {{ $doc->name }}
                            <span
                                class="text-sm text-gray-500 dark:text-gray-400">({{ $doc->payout_frequency == 1 ? 'Daily ' : $doc->payout_frequency }}
                                Payout)</span>
                        </flux:select.option>
                    @endforeach

                </flux:select>


            </div>

            <!-- Payment Period Card -->
            @if($selectedDoctor)
                <div
                    class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800 transition-colors duration-200">
                    <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200 mb-3">Payment Period</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <flux:input label="From Date" readonly wire:model="fromDateShow" />
                        </div>
                        <div>
                            <flux:input label="To Date" readonly wire:model="toDateShow" />
                        </div>
                    </div>
                    <div class="mt-2 text-sm text-blue-600 dark:text-blue-300">
                        <i class="fas fa-info-circle"></i> Dates auto-calculated based on doctor's payout frequency
                    </div>
                </div>
            @endif

            <!-- Unpaid Transactions -->
            @if($selectedDoctor && (count($unpaidServices) > 0 || count($unpaidLabs) > 0))
                <div
                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden transition-colors duration-200">
                    <div class="bg-gray-50 dark:bg-gray-700 px-6 py-3 border-b border-gray-200 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Unpaid Transactions</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ count($unpaidServices) + count($unpaidLabs) }} transactions pending payment
                        </p>
                    </div>

                    <!-- Summary Cards -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                            <div
                                class="bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 transition-colors duration-200">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ count($unpaidServices) }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-300">Services</div>
                            </div>
                            <div
                                class="bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 transition-colors duration-200">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ count($unpaidLabs) }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-300">Lab Tests</div>
                            </div>
                            <div
                                class="bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 transition-colors duration-200">
                                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">Rs.
                                    {{ number_format($totalAmount, 2) }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-300">Total Share</div>
                            </div>
                        </div>
                    </div>

                    <!-- Transactions Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Patient
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Amount
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Doctor Share
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Date
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                                @foreach($unpaidServices as $tx)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200">
                                                <i class="fas fa-stethoscope mr-1"></i>
                                                Service
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ $tx->patient->name ?? 'N/A' }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">
                                            Rs. {{ number_format($tx->price, 2) }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600 dark:text-green-400 text-right">
                                            Rs. {{ number_format($tx->doctor_share, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $tx->created_at->format('M d, Y') }}
                                        </td>
                                    </tr>
                                @endforeach

                                @foreach($unpaidLabs as $tx)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200">
                                                <i class="fas fa-flask mr-1"></i>
                                                Lab
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ $tx->patient->name ?? 'N/A' }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">
                                            Rs. {{ number_format($tx->amount, 2) }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600 dark:text-green-400 text-right">
                                            Rs. {{ number_format($tx->doctor_share, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $tx->created_at->format('M d, Y') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Payment Actions -->
                <div
                    class="bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 p-6 rounded-lg border border-gray-200 dark:border-gray-600 transition-colors duration-200">
                    <div
                        class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
                        <div>
                            <div class="text-2xl font-bold text-gray-800 dark:text-white">
                                Total Doctor Share: Rs. {{ number_format($totalAmount, 2) }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                Payment for {{ count($unpaidServices) + count($unpaidLabs) }} transactions
                            </div>
                        </div>
                        <div class="flex space-x-3">
                            <button type="button"
                                class="bg-gray-500 dark:bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-600 dark:hover:bg-gray-500 transition duration-200 flex items-center">
                                <i class="fas fa-download mr-2"></i>
                                Export Details
                            </button>
                            <button wire:click="payDoctor" wire:loading.attr="disabled" wire:target="payDoctor"
                                class="bg-green-600 dark:bg-green-700 text-white px-8 py-2 rounded-lg hover:bg-green-700 dark:hover:bg-green-600 transition duration-200 flex items-center disabled:opacity-50">
                                <span wire:loading.remove wire:target="payDoctor">
                                    <i class="fas fa-credit-card mr-2"></i>
                                    Pay Now
                                </span>
                                <span wire:loading wire:target="payDoctor">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>
                                    Processing...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            @elseif($selectedDoctor)
                <!-- No Transactions -->
                <div
                    class="text-center py-12 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 transition-colors duration-200">
                    <i class="fas fa-check-circle text-6xl text-green-500 dark:text-green-400 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-2">All Caught Up!</h3>
                    <p class="text-gray-600 dark:text-gray-300">No pending transactions for this doctor in the current
                        period.</p>
                </div>
            @else
                <!-- No Doctor Selected -->
                <div
                    class="text-center py-12 bg-gray-50 dark:bg-gray-700 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 transition-colors duration-200">
                    <i class="fas fa-user-md text-6xl text-gray-400 dark:text-gray-500 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-2">Select a Doctor</h3>
                    <p class="text-gray-600 dark:text-gray-300">Choose a doctor from the dropdown above to view their
                        pending payments.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Additional CSS for enhanced styling -->
    <style>
        .form-select,
        .form-input {
            @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500;
        }

        .hover\:bg-gray-50:hover {
            background-color: #f9fafb;
        }

        /* Loading animation */
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .fa-spin {
            animation: spin 1s linear infinite;
        }
    </style>
    <livewire:viewdocpayout/>

</div>