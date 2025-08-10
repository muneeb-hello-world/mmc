<?php

use Livewire\Volt\Component;
use App\Models\LabTest;
use App\Traits\ToastHelper;

new class extends Component {
    use ToastHelper;

    public $name;
    public $price;
    public $days_required;
    public $fromOutsideLab = false;
    public $tests = [];
    public $test_id;
    public $costPrice;
    public $isModelCreating = true; // true for create, false for edit

    public function mount()
    {
        $this->getTest();
    }

    public function updatedFromOutsideLab()
    {
        // Auto-set cost price percentage when lab type changes
        $this->costPrice = $this->fromOutsideLab ? 60 : 18;
    }

    public function createTest()
    {
        $this->validateFields();

        LabTest::create([
            'name' => $this->name,
            'price' => $this->price,
            'days_required' => $this->days_required,
            'fromOutsideLab' => $this->fromOutsideLab,
            'cost_price_percentage' => $this->costPrice
        ]);

        $this->showToast('success', 'Test created successfully.');
        $this->resetForm();
        Flux::modal('add-test')->close();
        $this->getTest();
    }

    public function updateTest()
    {
        $this->validateFields();

        $test = LabTest::find($this->test_id);

        if (!$test) {
            return $this->showToast('danger', 'Test not found.');
        }

        $test->update([
            'name' => $this->name,
            'price' => $this->price,
            'days_required' => $this->days_required,
            'fromOutsideLab' => $this->fromOutsideLab,
            'cost_price_percentage' => $this->costPrice
        ]);

        $this->showToast('success', 'Test updated successfully.');
        $this->resetForm();
        Flux::modal('add-test')->close();
        $this->getTest();
    }

    public function deleteTest($id)
    {
        $test = LabTest::find($id);

        if (!$test) {
            return $this->showToast('danger', 'Test not found.');
        }

        $test->delete();
        $this->showToast('danger', 'Test deleted successfully.');
        $this->getTest();
    }

    public function testEdit($id)
    {
        $test = LabTest::find($id);

        if (!$test) {
            return $this->showToast('danger', 'Test not found.');
        }

        $this->test_id = $test->id;
        $this->isModelCreating = false;

        $this->name = $test->name;
        $this->price = $test->price;
        $this->days_required = $test->days_required;
        $this->fromOutsideLab = $test->fromOutsideLab;
        $this->costPrice = $test->cost_price_percentage;

        Flux::modal('add-test')->show();
    }

    public function openAddTest()
    {
        $this->resetForm();
        $this->isModelCreating = true;
        Flux::modal('add-test')->show();
    }

    private function getTest()
    {
        $this->tests = LabTest::all();
    }

    private function validateFields()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'days_required' => 'required|integer|min:1',
        ]);

        // Ensure cost price is correct before saving
        $this->costPrice = $this->fromOutsideLab ? 40 : 18;
    }

    private function resetForm()
    {
        $this->reset(['name', 'price', 'days_required', 'fromOutsideLab', 'costPrice', 'test_id']);
        $this->isModelCreating = true;
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
                                    Lab Tests
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-neutral-400">
                                    Add tests, edit and more.
                                </p>
                            </div>

                            <div>
                                <div class="inline-flex gap-x-2">
                                    <a class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-50 dark:bg-transparent dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800"
                                        href="#">
                                        View all
                                    </a>

                                    <flux:modal.trigger name="add-test" variant="primary" color="blue">
                                        <flux:button wire:click="OpenAddTest">Add Test</flux:button>
                                    </flux:modal.trigger>

                                    <flux:modal name="add-test" class="md:w-96">
                                        <div class="space-y-6">
                                            <div>
                                                <flux:heading size="lg">Add New Test</flux:heading>
                                                <flux:text class="mt-2">Fill the details for the Test.
                                                </flux:text>
                                            </div>
                                            <div class="flex flex-col gap-4">

                                                <flux:input wire:model="name" label="Name" placeholder="CBC" />
                                                <flux:input wire:model="price" label="Price" type="number" placeholder="200" />
                                                <flux:input wire:model="days_required" label="Days Required" type="text" placeholder="3" />
                                               
                                               <flux:field variant="inline">
                                                    <flux:checkbox wire:model="fromOutsideLab" />

                                                    <flux:label>Test Is Outsourced</flux:label>

                                                    <flux:error name="fromOutsideLab" />
                                                </flux:field>

                                                <flux:input disabled readonly wire:model="costPrice" label="Cost Price Percentage" type="text" placeholder="100" />

                                                </div>

                                            </div>

                                            <div class="flex">
                                                <flux:spacer />

                                                <flux:button wire:click="{{ $isModelCreating ? 'createTest' : 'updateTest'}}" variant="primary">Save changes</flux:button>
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
                                                Price
                                            </span>
                                        </div>
                                    </th>

                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span
                                                class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Days Required
                                            </span>
                                        </div>
                                    </th>

                                     <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span
                                                class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Lab
                                            </span>
                                        </div>
                                    </th>

                                   <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span
                                                class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Cost Price
                                            </span>
                                        </div>
                                    </th>

                                 



                                    <th scope="col" class="px-6 py-3 text-end"></th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                                @forelse ($tests as $test)
                                    <tr key="{{ $test->id }}">

                                        <td class="size-px pl-4 whitespace-nowrap">
                                            <div class="ps-6 lg:ps-3 xl:ps-0 pe-6 py-3">
                                                <div class="flex items-center gap-x-3">
                                                    

                                                        <span
                                                            class=" capitalize block text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ $test->name }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-3">
                                                <span
                                                    class="block text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ $test->price }}</span>
                                            </div>
                                        </td>
                                        
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-3">
                                                <span
                                                    class="block text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ $test->days_required }} days
                                                </span>
                                            </div>

                                        </td>
                                          <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-3">
                                                <span
                                                    class="block text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ $test->fromOutsideLab ? 'Outside Lab' : 'MMC Lab' }}
                                                </span>
                                            </div>

                                        </td>
                                          <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-3">
                                                <span
                                                    class="block text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ $test->cost_price_percentage }}% 
                                                </span>
                                            </div>

                                        </td>
                                       
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-1.5">
                                                <flux:button wire:click="testEdit({{ $test->id }})" class=" mr-2"  >
                                                    Edit
                                                </flux:button>
                                                <flux:button variant="danger" size="sm" wire:confirm="Are you sure you want to delete the {{ $test->name }}?" wire:click="deleteTest({{ $test->id }})" >
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