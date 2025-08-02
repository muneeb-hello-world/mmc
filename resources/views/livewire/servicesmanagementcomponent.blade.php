<?php

use Livewire\Volt\Component;
use App\Models\Service;
use App\Traits\ToastHelper;



new class extends Component {
  use ToastHelper;
  public $services = [];
  public $name;
  public $price;
  public $isDocRelated = 0;
  public $isModelCreating = 1;
  public $service_id;
  public $hasToken;



  public function mount()
  {
    $this->GetServices();
  }
  public function GetServices()
  {
    $this->services = Service::all();
  }

  public function CreateService()
  {
    $this->validate([
      'name' => 'required',
      'price' => 'required | numeric',
      'isDocRelated' => 'required | boolean',
      'hasToken' => 'required | boolean'
    ]);

    $service = Service::create([
      'name' => $this->name,
      'default_price' => $this->price,
      'is_doctor_related' => $this->isDocRelated,
      'has_token' => $this->hasToken
    ]);

    if ($service) {
      // session()->flash('message', 'Service created successfully.');
      $this->showToast('success', 'Service created successfully.');
      $this->reset(['name', 'price', 'isDocRelated', 'hasToken']);
      Flux::modal('add-service')->close();
      $this->GetServices();
    } else {
      // session()->flash('error', 'Failed to create Service.');
      $this->showToast('danger', 'Failed to create Service.');
    }
  }

  public function EditService($id)
  {
    $service = Service::find($id);
    if ($service) {
      $this->service_id = $service->id;
      $this->isModelCreating = 0; // Indicate that we are editing an existing service
      $this->name = $service->name;
      $this->price = $service->default_price;
      $this->isDocRelated = $service->is_doctor_related;
      $this->hasToken = $service->has_token;

      Flux::modal('add-service')->show();
    } else {
      // session()->flash('error', 'Service not found.');
      $this->showToast('danger', 'Service not found.');
    }
  }

  public function OpenAddDoc()
  {
    $this->reset(['name', 'price', 'isDocRelated', 'has_token']);

    $this->isModelCreating = 1; // Indicate that we are creating a new service

    // Flux::modal('add-doctor')->show();
  }

  public function UpdateService()
  {
    // dd('hee');

    $this->validate([
      'name' => 'required',
      'price' => 'required | numeric',
      'isDocRelated' => 'required | boolean',
      'hasToken' => 'required | boolean',
    ]);

    $service = Service::find($this->service_id);

    if ($service) {
      $service->update([
        'name' => $this->name,
        'default_price' => $this->price,
        'is_doctor_related' => $this->isDocRelated,
        'has_token' => $this->hasToken
      ]);

      // session()->flash('message', 'Service updated successfully.');
      $this->showToast('success', 'Service updated successfully.');
      Flux::modal('add-service')->close();
      $this->GetServices();
    } else {
      $this->showToast('danger', 'Failed to update Service.');
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
                  Services
                </h2>
                <p class="text-sm text-gray-600 dark:text-neutral-400">
                  Add users, edit and more.
                </p>
              </div>

              <div>
                <div class="inline-flex gap-x-2">


                  <flux:modal.trigger name="add-service" variant="primary" color="blue">
                    <flux:button>Add Service</flux:button>
                  </flux:modal.trigger>

                  <flux:modal name="add-service" class="md:w-96">
                    <div class="space-y-6">
                      <div>
                        <flux:heading size="lg">Add New Service</flux:heading>
                        <flux:text class="mt-2">Fill the details for the Service.
                        </flux:text>
                      </div>
                      {{-- <div class="flex gap-4"> --}}

                        <flux:input wire:model="name" label="Name" placeholder="Your name" />
                        <flux:input wire:model="price" label="Default Price" type="number" placeholder="300" />
                        {{--
                      </div> --}}
                      <div class=" ">
                        <flux:field class="mx-2" variant="inline">
                          <flux:label>Is Doc Related</flux:label>
                          <flux:checkbox wire:model="isDocRelated" />
                          <flux:error name="isDocRelated" />
                        </flux:field>
                        <flux:field class="mx-2" variant="inline">
                          <flux:label>Has Token</flux:label>
                          <flux:checkbox wire:model="hasToken" />
                          <flux:error name="hasToken" />
                        </flux:field>

                      </div>





                      <div class="flex">
                        <flux:spacer />

                        <flux:button wire:click="{{ $isModelCreating ? 'CreateService' : 'UpdateService' }}"
                          variant="primary">Save changes</flux:button>
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

                  <th scope="col" class="ps-6 lg:ps-3 xl:ps-0 pe-6 py-3 text-start">
                    <div class="  text-center  -m-1">
                      <span class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                        Name
                      </span>
                    </div>
                  </th>

                  <th scope="col" class="px-6 py-3 text-start">
                    <div class="flex items-center gap-x-2">
                      <span class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                        Price
                      </span>
                    </div>
                  </th>

                  <th scope="col" class="px-6 py-3 text-start">
                    <div class="flex items-center gap-x-2">
                      <span class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                        Has Token
                      </span>
                    </div>
                  </th>
                   <th scope="col" class="px-6 py-3 text-start">
                    <div class="flex items-center gap-x-2">
                      <span class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                        Is Doctor Related
                      </span>
                    </div>
                  </th>


                  <th scope="col" class="px-6 py-3 text-start">
                    <div class="flex items-center gap-x-2">
                      <span class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                        Created
                      </span>
                    </div>
                  </th>

                  <th scope="col" class="px-6 py-3 text-end"></th>
                </tr>


              </thead>

              <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                @forelse ($services as $service)

              <tr>

                <td class="size-px whitespace-nowrap">
                <div class="ps-6 lg:ps-3 xl:ps-0 pe-6 py-3">
                  <div class=" text-center">

                  <div class="">
                    <span
                    class="block text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ $service->name }}
                    </span>
                  </div>
                  </div>
                </div>
                </td>
                <td class=" size-px whitespace-nowrap">
                <div class="px-6 py-3">
                  <span
                  class="block text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ $service->default_price }}</span>
                </div>
                </td>
                <td class="size-px whitespace-nowrap">
                @if ($service->is_doctor_related)

                  <div class="px-6 py-3">
                    <span
                    class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
                    <svg class="size-2.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                    fill="currentColor" viewBox="0 0 16 16">
                    <path
                    d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                    </svg>
                    Yes
                    </span>
                  </div>
                @else
                <div class="px-6 py-3">
                  <span
                  class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full dark:bg-yellow-500/10 dark:text-yellow-500">
                  <svg class="size-2.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                  fill="currentColor" viewBox="0 0 16 16">
                  <path
                  d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
                  </svg>
                  Not Related
                  </span>
                </div>
                @endif

                </td>
                  <td class="size-px whitespace-nowrap">
                @if ($service->has_token)

                  <div class="px-6 py-3">
                    <span
                    class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
                    <svg class="size-2.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                    fill="currentColor" viewBox="0 0 16 16">
                    <path
                    d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                    </svg>
                    Yes
                    </span>
                  </div>
                @else
                <div class="px-6 py-3">
                  <span
                  class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full dark:bg-yellow-500/10 dark:text-yellow-500">
                  <svg class="size-2.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                  fill="currentColor" viewBox="0 0 16 16">
                  <path
                  d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
                  </svg>
                  No Token
                  </span>
                </div>
                @endif

                </td>

                <td class="size-px whitespace-nowrap">
                <div class="px-6 py-3">
                  <span
                  class="text-sm text-gray-500 dark:text-neutral-500">{{ \Carbon\Carbon::parse($service->created_at)->format('d M, H:i') }}
                  </span>
                </div>
                </td>
                <td class="size-px whitespace-nowrap">
                <div class="px-6 py-1.5">
                  <a wire:click="EditService({{ $service->id }})"
                  class="inline-flex items-center gap-x-1 text-sm text-blue-600 decoration-2 hover:underline focus:outline-hidden focus:underline font-medium dark:text-blue-500"
                  href="#">
                  Edit
                  </a>
                </div>
                </td>
              </tr>

        @empty
          <td colspan="4">
            <div class="px-6 py-3 text-center   text-lg font-bold">No Service Entered Yet!</div>
          </td>

        @endforelse


              </tbody>
            </table>
            <!-- End Table -->

            <!-- Footer -->
            <div
              class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 dark:border-neutral-700">
              <div>
                <p class="text-sm text-gray-600 dark:text-neutral-400">
                  <span class="font-semibold text-gray-800 dark:text-neutral-200">12</span> results
                </p>
              </div>

              <div>
                <div class="inline-flex gap-x-2">
                </div>
              </div>
            </div>
            <!-- End Footer -->
          </div>
        </div>
      </div>
    </div>
    <!-- End Card -->
  </div>
  <!-- End Table Section -->
</div>