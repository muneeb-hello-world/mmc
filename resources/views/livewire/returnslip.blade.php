<?php
use Livewire\Volt\Component;
use App\Models\ServiceTransaction;
use App\Models\LabTestTransaction;
use App\Models\ReturnSlip;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\ToastHelper;

new class extends Component {
    use ToastHelper;

    public $transactions = [];
    public $selected = [];
    public $typeFilter = 'all';
    public $date = '';
    public $patientId = '';
    public $bulkReason = '';
    public $selectAll = false;

    public function mount()
    {
        $this->date = now()->toDateString();
        $this->loadTransactions();
    }

    /**
     * Load transactions into plain arrays (Livewire-friendly)
     * If $patientId is set we load all non-returned transactions for that patient (ignores date)
     */
    public function loadTransactions()
    {
        if ($this->patientId) {
            $serviceCollection = ServiceTransaction::with('patient', 'doctor', 'service')
                ->where('patient_id', $this->patientId)
                ->where('is_returned', false)
                ->get();

            $labCollection = LabTestTransaction::with('patient', 'doctor', 'labTest')
                ->where('patient_id', $this->patientId)
                ->where('is_returned', false)
                ->get();
        } else {
            $serviceCollection = ServiceTransaction::with('patient', 'doctor', 'service')
                ->whereDate('created_at', $this->date)
                ->where('is_returned', false)
                ->get();

            $labCollection = LabTestTransaction::with('patient', 'doctor', 'labTest')
                ->whereDate('created_at', $this->date)
                ->where('is_returned', false)
                ->get();
        }

        if ($this->typeFilter === 'service') {
            $combined = $serviceCollection;
        } elseif ($this->typeFilter === 'lab') {
            $combined = $labCollection;
        } else {
            $combined = $serviceCollection->merge($labCollection);
        }

        // Map after merge
        $this->transactions = $combined->map(function ($t) {
            if ($t instanceof ServiceTransaction) {
                return [
                    'id' => $t->id,
                    'type' => 'service',
                    'token' => $t->token ?? '-',
                    'patient' => $t->patient->name ?? 'N/A',
                    'patient_id' => $t->patient_id ?? null,
                    'doctor' => $t->doctor->name ?? 'N/A',
                    'service' => $t->service->name ?? 'N/A',
                    'amount' => $t->price,
                    'created_at' => $t->created_at->format('Y-m-d H:i'),
                ];
            } else { // LabTestTransaction
                return [
                    'id' => $t->id,
                    'type' => 'lab',
                    'token' => $t->token ?? '-',
                    'patient' => $t->patient->name ?? 'N/A',
                    'patient_id' => $t->patient_id ?? null,
                    'doctor' => $t->doctor->name ?? 'N/A',
                    'service' => $t->labTest->name ?? 'N/A',
                    'amount' => $t->amount,
                    'created_at' => $t->created_at->format('Y-m-d H:i'),
                ];
            }
        })->sortByDesc('created_at')->values()->toArray();

        // Reset selections
        $this->selected = [];
        $this->selectAll = false;

    }

    // Keep reactive: when filters change, reload
    public function updatedTypeFilter()
    {
        // dd('hi');
        $this->loadTransactions();
    }
    public function updatedDate()
    {
        if (!$this->patientId)
            $this->loadTransactions();
    } // date ignored when patientId set
    public function updatedPatientId()
    {
        $this->loadTransactions();
    }

    /**
     * When header "select all" checkbox toggles, fill or clear $selected
     */
    public function updatedSelectAll($value)
    {
        if ($value) {
            $values = [];
            foreach ($this->transactions as $t) {
                $values[] = $t['type'] . '_' . $t['id'];
            }
            $this->selected = $values;
        } else {
            $this->selected = [];
        }
    }

    /**
     * When user manually checks/unchecks rows, keep selectAll in sync
     */
    public function updatedSelected()
    {
        // dd('hi');

        if (count($this->selected) === count($this->transactions) && count($this->transactions) > 0) {
            $this->selectAll = true;
        } else {
            $this->selectAll = false;
        }
    }

    /**
     * Mark selected rows as returned
     */
    public function markSelectedAsReturned()
    {
        if (empty($this->selected)) {
            $this->showToast('error', 'No records selected.');
            return;
        }

        DB::transaction(function () {
            foreach ($this->selected as $item) {
                // value format: "{type}_{id}" e.g. "service_123" or "lab_45"
                [$type, $id] = explode('_', $item, 2);

                if ($type === 'service') {
                    $transaction = ServiceTransaction::find($id);
                } else {
                    $transaction = LabTestTransaction::find($id);
                }

                if (!$transaction || $transaction->is_returned)
                    continue;

                $transaction->is_returned = true;
                $transaction->save();

                ReturnSlip::create([
                    'type' => $type,
                    'amount' => $type === 'service' ? $transaction->price : $transaction->amount,
                    'transaction_id' => $transaction->id,
                    'reason' => $this->bulkReason ?: 'Returned via selected action',
                    'refunded_by' => Auth::id(),
                ]);
            }
        });

        $this->showToast('success', 'Selected transactions returned.');
        $this->selected = [];
        $this->selectAll = false;
        $this->loadTransactions();
    }

    /**
     * Refund all non-returned transactions for a given patient id
     */
    public function markPatientAsReturned()
    {
        if (!$this->patientId) {
            $this->showToast('error', 'Please enter a patient id first.');
            return;
        }

        // get all non-returned service & lab transactions for this patient
        $serviceItems = ServiceTransaction::where('patient_id', $this->patientId)
            ->where('is_returned', false)
            ->get();

        $labItems = LabTestTransaction::where('patient_id', $this->patientId)
            ->where('is_returned', false)
            ->get();

        $total = $serviceItems->count() + $labItems->count();

        if ($total === 0) {
            $this->showToast('info', 'No non-returned transactions found for this patient.');
            return;
        }

        DB::transaction(function () use ($serviceItems, $labItems) {
            foreach ($serviceItems as $t) {
                $t->is_returned = true;
                $t->save();

                ReturnSlip::create([
                    'type' => 'service',
                    'amount' => $t->price,
                    'transaction_id' => $t->id,
                    'reason' => $this->bulkReason ?: 'Returned for patient id ' . $this->patientId,
                    'refunded_by' => Auth::id(),
                ]);
            }

            foreach ($labItems as $t) {
                $t->is_returned = true;
                $t->save();

                ReturnSlip::create([
                    'type' => 'lab',
                    'amount' => $t->amount,
                    'transaction_id' => $t->id,
                    'reason' => $this->bulkReason ?: 'Returned for patient id ' . $this->patientId,
                    'refunded_by' => Auth::id(),
                ]);
            }
        });

        $this->showToast('success', "Returned {$total} transactions for patient id {$this->patientId}.");
        $this->selected = [];
        $this->selectAll = false;
        $this->bulkReason = '';
        $this->loadTransactions();
    }
};
?>
<div class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div>
            <flux:select wire:model.live="typeFilter" label="Type Filter">
                <flux:select.option value="all">All</flux:select.option>
                <flux:select.option value="service">Service</flux:select.option>
                <flux:select.option value="lab">Lab</flux:select.option>
            </flux:select>
        </div>

        <div>
            <flux:input type="date" wire:model.live="date" label="Date" />
        </div>

        <div>
            <flux:input label="Patient ID (optional)" wire:model.live="patientId"
                placeholder="Enter patient id to view/refund all" />
        </div>
    </div>

    <div class="flex gap-4 items-center">
        <flux:input label="Reason for return (optional)" wire:model="bulkReason" />
        <div class="ml-auto space-x-2">
            @if($patientId)
                <button wire:click="markPatientAsReturned"
                    class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 transition">
                    Refund All for Patient
                </button>
            @endif

            @if(count($selected) > 0)
                <button wire:click="markSelectedAsReturned"
                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">
                    Return Selected ({{ count($selected) }})
                </button>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow-md">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                <tr>
                    <th class="p-3 w-12">
                        <flux:checkbox wire:model.live="selectAll" />
                    </th>
                    <th class="p-3">Token</th>
                    <th class="p-3">Patient</th>
                    <th class="p-3">Patient ID</th>
                    <th class="p-3">Doctor</th>
                    <th class="p-3">Service/Test</th>
                    <th class="p-3">Type</th>
                    <th class="p-3">Created</th>
                    <th class="p-3 text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $t)
                    <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="p-3">
                            <flux:checkbox value="{{ $t['type'] . '_' . $t['id'] }}" wire:model.live="selected" />

                        </td>
                        <td class="p-3">{{ $t['token'] }}</td>
                        <td class="p-3">{{ $t['patient'] }}</td>
                        <td class="p-3">{{ $t['patient_id'] }}</td>
                        <td class="p-3">{{ $t['doctor'] }}</td>
                        <td class="p-3">{{ $t['service'] }}</td>
                        <td class="p-3">{{ ucfirst($t['type']) }}</td>
                        <td class="p-3">{{ $t['created_at'] }}</td>
                        <td class="p-3 text-right">Rs. {{ number_format($t['amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="p-4 text-center text-gray-500 dark:text-gray-300">No transactions found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>