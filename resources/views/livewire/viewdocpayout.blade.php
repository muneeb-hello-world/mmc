<?php 
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Doctor;
use App\Models\DoctorPayout;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    public $doctors;
    public $selectedDoctor = null;
    public $selectedDate;

    public function mount()
    {
        $this->doctors = Doctor::all();
        $this->selectedDate = Carbon::today()->toDateString(); // default today
    }

    public function updatingSelectedDoctor()
    {
        $this->resetPage();
    }

    public function updatingSelectedDate()
    {
        $this->resetPage();
    }

    public function getPayoutsProperty()
    {
        $query = DoctorPayout::with('doctor')
            ->whereDate('paid_at', $this->selectedDate)
            ->orderByDesc('paid_at');

        if ($this->selectedDoctor) {
            $query->where('doctor_id', $this->selectedDoctor);
        }

        return $query->paginate(10);
    }
};

?>
<div class="max-w-5xl mx-auto p-6 space-y-6">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Doctor Payouts on {{ $selectedDate }}</h2>

    <!-- Filters -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <flux:select label="Select Doctor" wire:model.live="selectedDoctor">
                <flux:select.option value="">All Doctors</flux:select.option>
                @foreach($doctors as $doc)
                    <flux:select.option value="{{ $doc->id }}">{{ $doc->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div>
            <flux:input type="date" label="Select Date" wire:model.live="selectedDate" />
        </div>
    </div>

    <!-- Results -->
    @php $paginatedPayouts = $this->payouts @endphp

    @if($paginatedPayouts->count())
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Doctor</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Amount</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Period</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Paid At</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Method</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                    @foreach($paginatedPayouts as $payout)
                        <tr>
                            <td class="px-4 py-2">{{ $payout->doctor->name }}</td>
                            <td class="px-4 py-2">Rs. {{ number_format($payout->amount, 2) }}</td>
                            <td class="px-4 py-2">{{ $payout->from_date }} - {{ $payout->to_date }}</td>
                            <td class="px-4 py-2">{{ \Carbon\Carbon::parse($payout->paid_at)->format('d M Y h:i A') }}</td>
                            <td class="px-4 py-2 capitalize">{{ $payout->method }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="p-4">
                {{ $paginatedPayouts->links('pagination::tailwind') }}
            </div>
        </div>
    @else
        <div class="text-center py-12 text-gray-600 dark:text-gray-300">
            No payouts found for this date.
        </div>
    @endif
</div>
