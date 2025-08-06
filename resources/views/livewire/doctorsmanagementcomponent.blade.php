<?php

use Livewire\Volt\Component;
use App\Models\Doctor;
use App\Traits\ToastHelper;
use Carbon\Carbon;
use Flux\Flux;


new class extends Component {
    use ToastHelper;
    public $onpayroll = false;
    public $days = [];
    public $name;
    public $specialization;
    public $start_time;
    public $end_time;
    public $doctors=[];
    public $doctor_id;
public $isModelCreating = 1;


    public function mount()
    {
        $this->getDoc();
    }

public function CreateDoc()
{
    // dd($this->start_time, $this->end_time , $this->onpayroll);
    $this->validate([
        'name' => 'required|string|max:255',
        'specialization' => 'required|string|max:255',
        'onpayroll' => 'boolean',
        'days' => 'array',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i'
    ]);

    // ✅ Convert to MySQL format (H:i:s) after validation
    $start = Carbon::createFromFormat('H:i', $this->start_time)->format('H:i:s');
    $end = Carbon::createFromFormat('H:i', $this->end_time)->format('H:i:s');

    $doc = Doctor::create([
        'name' => $this->name,
        'specialization' => $this->specialization,
        'is_on_payroll' => $this->onpayroll,
        'days' => json_encode($this->days),
        'start_time' => $start,
        'end_time' => $end,
    ]);

    if ($doc) {
        // session()->flash('message', 'Doctor created successfully.');
        $this->showToast('success','Doctor created successfully.');

        $this->reset(['name', 'specialization', 'days', 'start_time', 'end_time']);
        $this->onpayroll = false; // Reset onpayroll to default
        Flux::modal('add-doctor')->close();
    } else {
        session()->flash('error', 'Failed to create doctor.');
    }

    $this->getDoc();
}

    public function getDoc()
    {
        // Logic to fetch doctors from the database
        $this->doctors = Doctor::all();
        // dd($this->doctors);
    }

    public function deleteDoctor($id)
    {
        $doctor = Doctor::find($id);
        if ($doctor) {
            $doctor->delete();
            // session()->flash('message', 'Doctor deleted successfully.');
        $this->showToast('danger','Doctor deleted successfully.');

            
            $this->getDoc();
        } else {
            // session()->flash('error', 'Doctor not found.');
        $this->showToast('danger','Doctor not found.');
        }
    }

    public function DocEdit($id){
    $doctor = Doctor::find($id);
    // dd($doctor);
     // dd($this->start_time, $this->end_time , $this->onpayroll);
    if($doctor){
        $this->doctor_id = $doctor->id;
        $this->isModelCreating = 0; // editing mode

        $this->name = $doctor->name;
        $this->specialization = $doctor->specialization;
        if($doctor->onpayroll){
            $onpayroll = true;

        }else{
            $onpayroll = false;
        }
        $this->onpayroll = $onpayroll;
        $this->days = json_decode($doctor->days, true);
        $this->start_time = Carbon::parse($doctor->start_time)->format('H:i');
        $this->end_time = Carbon::parse($doctor->end_time)->format('H:i');

        Flux::modal('add-doctor')->show();
    } else {
        session()->flash('error', 'Doctor not found.');
    }
}

    public function OpenAddDoc()
{
    $this->reset(['name', 'specialization', 'days', 'start_time', 'end_time']);

    $this->onpayroll = false;
    $this->days = [];

    // Flux::modal('add-doctor')->show();
}
public function UpdateDoc()
{
    // dd($this->start_time, $this->end_time , $this->onpayroll);
    $this->validate([
        'name' => 'required|string|max:255',
        'specialization' => 'required|string|max:255',
        'onpayroll' => 'boolean',
        'days' => 'array',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i'
    ]);

    $doctor = Doctor::find($this->doctor_id);

    if ($doctor) {
        $start = Carbon::createFromFormat('H:i', $this->start_time)->format('H:i:s');
        $end = Carbon::createFromFormat('H:i', $this->end_time)->format('H:i:s');

        $doctor->update([
            'name' => $this->name,
            'specialization' => $this->specialization,
            'is_on_payroll' => $this->onpayroll,
            'days' => json_encode($this->days),
            'start_time' => $start,
            'end_time' => $end,
        ]);

        // session()->flash('message', 'Doctor updated successfully.');
        $this->showToast('success','Doctor updated successfully.');
        Flux::modal('add-doctor')->close();

        $this->reset(['name', 'specialization', 'days', 'start_time', 'end_time', 'doctor_id']);
        $this->onpayroll = false; // Reset onpayroll to default

        $this->isModelCreating = 1;

        $this->getDoc();
    } else {
        // session()->flash('error', 'Failed to update doctor.');
        $this->showToast('danger','Failed to update doctor.');
    }
}



    
}; ?>

<div>
    <!-- Table Section -->
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
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
                                    Doctors
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-neutral-400">
                                    Add users, edit and more.
                                </p>
                            </div>

                            <div>
                                <div class="inline-flex gap-x-2">
                                    <a class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-50 dark:bg-transparent dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800"
                                        href="#">
                                        View all
                                    </a>

                                    <flux:modal.trigger name="add-doctor" variant="primary" color="blue">
                                        <flux:button wire:click="OpenAddDoc">Add doctor</flux:button>
                                    </flux:modal.trigger>

                                    <flux:modal name="add-doctor" class="md:w-96">
                                        <div class="space-y-6">
                                            <div>
                                                <flux:heading size="lg">Add New Doctor</flux:heading>
                                                <flux:text class="mt-2">Fill the details for the Doctor.
                                                </flux:text>
                                            </div>
                                            <div class="flex gap-4">

                                                <flux:input wire:model="name" label="Name" placeholder="Your name" />
                                                <flux:input wire:model="specialization" label="Edu / Specialization"
                                                    type="text" placeholder="M.B.B.S" />
                                            </div>
                                               
                                                <div class=" ">
                                                    <flux:field class="mx-2" variant="inline">
                                                    <flux:label>On Pay Roll</flux:label>
                                                    <flux:checkbox wire:model="onpayroll" />
                                                    <flux:error name="onpayroll" />
                                                    </flux:field>

                                                </div>
                                            <div class="px-2">
                                                <flux:checkbox.group wire:model="days" label="Days">
                                                    <flux:checkbox label="Monday" value="monday" />
                                                    <flux:checkbox label="Tuesday" value="tuesday" />
                                                    <flux:checkbox label="Wednesday" value="wednesday" />
                                                    <flux:checkbox label="Thursday" value="thursday" />
                                                    <flux:checkbox label="Friday" value="friday" />
                                                    <flux:checkbox label="Saturday" value="saturday" />
                                                    <flux:checkbox label="Sunday" value="sunday" />
                                                </flux:checkbox.group>
                                            </div>
                                            <div class="flex gap-4">

                                                <flux:input wire:model="start_time" type="time" label="Start Time"
                                                    placeholder="09:00 AM" />
                                                <flux:input wire:model="end_time" type="time" label="End Time" 
                                                    placeholder="05:00 PM" />
                                            </div>



                                            <div class="flex">
                                                <flux:spacer />

                                                <flux:button wire:click="{{ $isModelCreating ? 'CreateDoc' : 'UpdateDoc'}}" variant="primary">Save changes</flux:button>
                                            </div>
                                        </div>
                                    </flux:modal>
                                </div>
                            </div>
                        </div>
                        <!-- End Header -->

                        <!-- Table -->
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                            <thead class="bg-gray-50 dark:bg-neutral-800">
                                <tr>

                                    <th scope="col" class="ps-6  lg:ps-3 xl:ps-0 pe-6 py-3 ">
                                        <div class="pl-6  flex items-center gap-x-2">
                                            <span
                                                class="text-xs text-center font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Name
                                            </span>
                                        </div>
                                    </th>

                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span
                                                class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Education / Specialization
                                            </span>
                                        </div>
                                    </th>

                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span
                                                class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                On Payroll
                                            </span>
                                        </div>
                                    </th>

                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span
                                                class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Days
                                            </span>
                                        </div>
                                    </th>

                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span
                                                class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Start Time - End Time
                                            </span>
                                        </div>
                                    </th>



                                    <th scope="col" class="px-6 py-3 text-end"></th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                                @forelse ($doctors as $doctor)
                                    <tr key="{{ $doctor->id }}">

                                        <td class="size-px pl-4 whitespace-nowrap">
                                            <div class="ps-6 lg:ps-3 xl:ps-0 pe-6 py-3">
                                                <div class="flex items-center gap-x-3">
                                                    
                                                    <div class="relative inline-flex items-center justify-center w-10 h-10 overflow-hidden bg-gray-100 rounded-full dark:bg-gray-600">
                                                        @php
                                                            $initials = collect(explode(' ', $doctor->name))
                                                                        ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                                                                        ->join('');
                                                        @endphp

                                                      

                                                        <span class="font-medium text-gray-600 dark:text-gray-300"> {{ $initials }}</span>
                                                    </div>

                                                    <div class="grow">
                                                        <span
                                                            class="block text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ $doctor->name }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-3">
                                                <span
                                                    class="block text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ $doctor->specialization }}</span>
                                            </div>
                                        </td>
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-3">
                                                <span
                                                    class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium  {{ $doctor->is_on_payroll ? 'bg-amber-100 text-amber-800 rounded-full dark:bg-amber-500/10 dark:text-amber-500' : 'bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500' }} ">
                                                    <svg class="size-2.5" xmlns="http://www.w3.org/2000/svg" width="16"
                                                        height="16" fill="currentColor" viewBox="0 0 16 16">
                                                        <path
                                                            d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                                    </svg>
                                                    {{ $doctor->is_on_payroll ? 'Hired' : 'Freelance' }}
                                                    {{-- {{ $doctor->onpayroll ? 'Yes' : 'No' }} --}}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="size-px whitespace-nowrap">
                                            {{-- <div class=" capitalize">
                                                {{ implode(', ', json_decode($doctor->days)) }}
                                            </div> --}}
                                            
                                            @php
                                             $days = json_decode($doctor->days);
                                            @endphp
                                                                                    
                                            <div class="grid grid-cols-2 gap-1 my-2">
                                                @foreach ($days as $day)
                                                    <span class="bg-gray-100 text-black text-xs px-2 py-0.5 rounded capitalize">
                                                        {{ substr($day, 0, 3) }}
                                                    </span>
                                                @endforeach
                                            </div>

                                        </td>
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-3">
                                                <span class=" pl-1 text-sm  ">
                                                    {{ $doctor->start_time }} -- {{ $doctor->end_time }}</span>
                                            </div>
                                        </td>
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-1.5">
                                                <flux:button wire:click="DocEdit({{ $doctor->id }})" class=" mr-2"  >
                                                    Edit
                                                </flux:button>
                                                <flux:button variant="danger" size="sm" wire:confirm="Are you sure you want to delete the {{ $doctor->name }}?" wire:click="deleteDoctor({{ $doctor->id }})" >
                                                    Delete
                                                </flux:button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                <tr>
                                     <td class="size-px whitespace-nowrap" colspan="6">
                                            <div class="px-6 py-3">
                                                <span
                                                    class="block text-lg text-red-500 font-semibold text-center  dark:text-neutral-200">Nothing Here!</span>
                                            </div>
                                        </td>
                                </tr>
                                @endforelse
                                


                            </tbody>
                        </table>
                        <!-- End Table -->

                     
                    </div>
                </div>
            </div>
        </div>
        <!-- End Card -->
    </div>
    <!-- End Table Section -->
</div>