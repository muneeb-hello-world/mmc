<?php

use Livewire\Volt\Component;
use App\Models\DoctorServiceShare;
use App\Traits\ToastHelper;

new class extends Component {
    use ToastHelper;

    public $doctor_service_share_id;
    public $shares;
    public $doctor_id;
    public $doctors;
    public $services;
    public $service_id;
    public $price;
    public $doctor_share_percent;
    public $hospital_share_percent;
    public $isModelCreating = true; // default is create mode

    public function mount()
    {
        $this->getShares();
        $this->doctors = \App\Models\Doctor::all();
        $this->services = \App\Models\Service::all();
        $this->setDefaultSelections();
    }

    private function setDefaultSelections()
    {
        $this->doctor_id = $this->doctors->first()->id ?? null;
        $this->service_id = $this->services->first()->id ?? null;
    }

    private function resetForm()
    {
        $this->reset([
            'doctor_service_share_id',
            'doctor_id',
            'service_id',
            'price',
            'doctor_share_percent',
            'hospital_share_percent'
        ]);
        $this->setDefaultSelections();
    }

    private function validateShares()
    {
        $this->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'service_id' => 'required|exists:services,id',
            'price' => 'required|numeric|min:0',
            'doctor_share_percent' => 'required|numeric|min:0|max:100',
            'hospital_share_percent' => 'required|numeric|min:0|max:100',
        ]);

        if (($this->doctor_share_percent + $this->hospital_share_percent) > 100) {
            $this->addError('doctor_share_percent', 'Total share percentages cannot exceed 100%.');
            return false;
        }
        return true;
    }

    public function createShare()
    {
        if (!$this->validateShares()) return;

        $share = DoctorServiceShare::create([
            'doctor_id' => $this->doctor_id,
            'service_id' => $this->service_id,
            'price' => $this->price,
            'doctor_share_percent' => $this->doctor_share_percent,
            'hospital_share_percent' => $this->hospital_share_percent,
        ]);

        if ($share) {
            $this->showToast('success', 'Doctor service share created successfully.');
            $this->resetForm();
            $this->getShares();
            Flux::modal('add-share')->close();
        } else {
            $this->showToast('error', 'Failed to create doctor service share.');
        }
    }

    public function getShares()
    {
        $this->shares = DoctorServiceShare::with(['doctor', 'service'])->get();
    }

    public function editShare($id)
    {
        $share = DoctorServiceShare::find($id);

        if (!$share) {
            $this->showToast('error', 'Doctor Service Share not found.');
            return;
        }

        $this->doctor_service_share_id = $share->id;
        $this->isModelCreating = false;

        $this->doctor_id = $share->doctor_id;
        $this->service_id = $share->service_id;
        $this->price = $share->price;
        $this->doctor_share_percent = $share->doctor_share_percent;
        $this->hospital_share_percent = $share->hospital_share_percent;

        Flux::modal('add-share')->show();
    }

    public function openAddShare()
    {
        $this->resetForm();
        $this->isModelCreating = true;
        Flux::modal('add-share')->show();
    }

    public function updateShare()
    {
        if (!$this->validateShares()) return;

        $share = DoctorServiceShare::find($this->doctor_service_share_id);

        if (!$share) {
            $this->showToast('error', 'Doctor service share not found.');
            return;
        }

        $share->update([
            'doctor_id' => $this->doctor_id,
            'service_id' => $this->service_id,
            'price' => $this->price,
            'doctor_share_percent' => $this->doctor_share_percent,
            'hospital_share_percent' => $this->hospital_share_percent,
        ]);

        $this->showToast('success', 'Doctor service share updated successfully.');
        $this->resetForm();
        $this->getShares();
        Flux::modal('add-share')->close();
    }

    public function deleteShare($id)
    {
        $share = DoctorServiceShare::find($id);

        if ($share) {
            $share->delete();
            $this->showToast('success', 'Doctor service share deleted successfully.');
            $this->getShares();
        } else {
            $this->showToast('error', 'Doctor service share not found.');
        }
    }
};
?>
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
                                    Doctor Service Share Management
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

                                   <flux:modal.trigger name="add-share" variant="primary" color="blue">
    <flux:button wire:click="openAddShare">Add Service Share</flux:button>
</flux:modal.trigger>


                                    <flux:modal name="add-share" class="md:w-96">
                                        <div class="space-y-6">
                                            <div>
                                                <flux:heading size="lg">Add New Service Share</flux:heading>
                                                <flux:text class="mt-2">Fill the details for the Service Share.
                                                </flux:text>
                                            </div>
                                            <div class="flex flex-col gap-4">
                                                <flux:select required label="Doctor Name" wire:model="doctor_id" placeholder="Choose Doctor">
                                                    @foreach ($doctors as $doc)
                                                        <flux:select.option value="{{ $doc->id }}">{{ $doc->name }}</flux:select.option>
                                                    @endforeach
                                                </flux:select>
                                                  <flux:select label="Service Name" wire:model="service_id" placeholder="Choose Service">
                                                    @foreach ($services as $service)
                                                        <flux:select.option value="{{ $service->id }}">{{ $service->name }}</flux:select.option>
                                                    @endforeach
                                                </flux:select>
                                            </div>
                                            <div class=" flex flex-col gap-4">
                                             <flux:input label="Price" type="number" wire:model="price" />
                                             <flux:input label="Doctor Share" type="number" wire:model="doctor_share_percent" />
                                             <flux:input label="Hospital Share" type="number" wire:model="hospital_share_percent" />
                                            </div>
                                          



                                            <div class="flex">
                                                <flux:spacer />

                                                <flux:button 
    wire:click="{{ $isModelCreating ? 'createShare' : 'updateShare' }}" 
    variant="primary">
    {{ $isModelCreating ? 'Create Share' : 'Update Share' }}
</flux:button>
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
                                                Service
                                            </span>
                                        </div>
                                    </th>

                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span
                                                class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Doctor
                                            </span>
                                        </div>
                                    </th>

                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span
                                                class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Price
                                            </span>
                                        </div>
                                    </th>

                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span
                                                class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Doctor Share Percentage
                                            </span>
                                        </div>
                                    </th>

                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span
                                                class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Hospital Share Percentage

                                            </span>
                                        </div>
                                    </th>



                                    <th scope="col" class="px-6 py-3 text-end"></th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                                @forelse ($shares as $share)
                                    <tr key="{{ $share->id }}">

                                        <td class="size-px pl-4 whitespace-nowrap">
                                            <div class="ps-6 lg:ps-3 xl:ps-0 pe-6 py-3">
                                                <div class="">
                                                    
                                                    <div class="">
                                                        <span
                                                            class="block text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ $share->service->name }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-3">
                                                <span
                                                    class="block text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ $share->doctor->name }}</span>
                                            </div>
                                        </td>
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-3">
                                                    {{ $share->price }}
                                            </div>
                                        </td>
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-3">
                                                <span class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
                                                    {{ $share->doctor_share_percent }}%
                                                </span>
                                            </div>                                  
                                            
                                        </td>
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-3">
                                                <span class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
                                                    {{ $share->hospital_share_percent }}%
                                                </span>
                                            </div>
                                        </td>
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-1.5">
                                                <flux:button wire:click="editShare({{ $share->id }})" class=" mr-2"  >
                                                    Edit
                                                </flux:button>
                                                <flux:button variant="danger" size="sm" wire:confirm="Are you sure you want to delete the {{ $share->doctor->name }} And {{ $share->service->name }}?" wire:click="deleteShare({{ $share->id }})" >
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