<?php

use Livewire\Volt\Component;
use App\Models\LabTest;
use App\Traits\ToastHelper;
use Carbon\Carbon;

new class extends Component {
    use ToastHelper;
    public $name;
    public $price;
    public $days_required;
    public $fromOutsideLab = false;
    public $tests=[];
    public $test_id;
    public $costPrice = 18;
public $isModelCreating = 1;


    public function mount()
    {
        $this->getTest();
    }

public function CreateTest()
{
    // dd($this->fromOutsideLab);
    $this->validate([
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0',
        'days_required' => 'required|integer|min:1',
    ]);

  


    $test = LabTest::create([
        'name' => $this->name,
        'price' => $this->price,
        'days_required' => $this->days_required,
        'fromOutsideLab' => $this->fromOutsideLab,
        'cost_price_percentage' => $this->costPrice ? $this->costPrice : null
    ]);
        

    if ($test) {
        // session()->flash('message', 'Doctor created successfully.');
        $this->showToast('success','Test created successfully.');

        $this->reset(['name', 'price', 'days_required', 'costPrice', 'fromOutsideLab']);
        Flux::modal('add-test')->close();
    } else {
        session()->flash('error', 'Failed to create test.');
    }

    $this->getTest();
}

    public function getTest()
    {
        // Logic to fetch tests from the database
        $this->tests = LabTest::all();
        // dd($this->tests);
    }

    public function deleteTest($id)
    {
        $test = LabTest::find($id);
        if ($test) {
            $test->delete();
            // session()->flash('message', 'Doctor deleted successfully.');
        $this->showToast('danger','Test deleted successfully.');


            $this->getTest();
        } else {
            // session()->flash('error', 'Doctor not found.');
        $this->showToast('danger','Test not found.');
        }
    }

    public function TestEdit($id){
    $test = LabTest::find($id);
    // dd($doctor);
     // dd($this->start_time, $this->end_time , $this->onpayroll);
    if($test){
        $this->test_id = $test->id;
        $this->isModelCreating = 0; // editing mode

        $this->name = $test->name;
        $this->price = $test->price;
        $this->days_required = $test->days_required;
        $this->costPrice = $test->cost_price;
        $this->fromOutsideLab = $test->fromOutsideLab;

        Flux::modal('add-test')->show();
    } else {
        session()->flash('error', 'Test not found.');
    }
}

    public function OpenAddTest()
{
    $this->reset(['name', 'price', 'days_required', 'costPrice', 'fromOutsideLab']);

    $this->isModelCreating = true;

    Flux::modal('add-test')->show();
}
public function UpdateTest()
{
    // dd($this->start_time, $this->end_time , $this->onpayroll);
    $this->validate([
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0',
        'days_required' => 'required|integer|min:1',
    ]);

    $test = LabTest::find($this->test_id);

    if ($test) {
        $test->update([
            'name' => $this->name,
            'price' => $this->price,
            'days_required' => $this->days_required,
        ]);

        $test->update([
            'name' => $this->name,
            'price' => $this->price,
            'days_required' => $this->days_required,
            'fromOutsideLab' => $this->fromOutsideLab,
            'cost_price_percentage' => $this->costPrice ? $this->costPrice : null
        ]);

        // session()->flash('message', 'Doctor updated successfully.');
        $this->showToast('success','Test updated successfully.');
        Flux::modal('add-test')->close();

        $this->reset(['name', 'price' , 'days_required' , 'costPrice', 'fromOutsideLab']);

        $this->isModelCreating = 1;

        $this->getTest();
    } else {
        // session()->flash('error', 'Failed to update doctor.');
        $this->showToast('danger','Failed to update Test.');
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

                                                <flux:input wire:model="costPrice" label="Cost Price Percentage" type="text" placeholder="100" />

                                                </div>

                                            </div>

                                            <div class="flex">
                                                <flux:spacer />

                                                <flux:button wire:click="{{ $isModelCreating ? 'CreateTest' : 'UpdateTest'}}" variant="primary">Save changes</flux:button>
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
                                                <flux:button wire:click="TestEdit({{ $test->id }})" class=" mr-2"  >
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