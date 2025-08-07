<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Reception')" class="grid">
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                    <flux:navlist.item icon="bolt" :href="route('reception')" :current="request()->routeIs('reception')" wire:navigate>{{ __('Reception') }}</flux:navlist.item>
                    <flux:navlist.item icon="briefcase" :href="route('case')" :current="request()->routeIs('case')" wire:navigate>{{ __('Case') }}</flux:navlist.item>
                    <flux:navlist.item icon="clipboard-document-list" :href="route('labentry')" :current="request()->routeIs('labentry')" wire:navigate>{{ __('Lab Entry') }}</flux:navlist.item>
                    <flux:navlist.item icon="user" :href="route('bookings')" :current="request()->routeIs('bookings')" wire:navigate>{{ __('Appointment Booking') }}</flux:navlist.item>
                    <flux:navlist.item icon="banknotes" :href="route('payout')" :current="request()->routeIs('payout')" wire:navigate>{{ __('Doctor Payout') }}</flux:navlist.item>
                    <flux:navlist.item icon="calculator" :href="route('expence')" :current="request()->routeIs('expence')" wire:navigate>{{ __('Expences') }}</flux:navlist.item>
                    <flux:navlist.item icon="numbered-list" :href="route('showtrans')" :current="request()->routeIs('showtrans')" wire:navigate>{{ __('Reports') }}</flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>
            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Admin')" class="grid">
                    <flux:navlist.item icon="banknotes" :href="route('end')" :current="request()->routeIs('end')" wire:navigate>{{ __('End Of Shift') }}</flux:navlist.item>
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                    <flux:navlist.item icon="user" :href="route('admin.doctors')" :current="request()->routeIs('admin.doctors')" wire:navigate>{{ __('Doctors') }}</flux:navlist.item>
                    <flux:navlist.item icon="chart-bar" :href="route('admin.services')" :current="request()->routeIs('admin.services')" wire:navigate>{{ __('Services') }}</flux:navlist.item>
                    <flux:navlist.item icon="presentation-chart-line" :href="route('admin.serviceshare')" :current="request()->routeIs('admin.serviceshare')" wire:navigate>{{ __('Service Share') }}</flux:navlist.item>
                    <flux:navlist.item icon="clipboard-document-list" :href="route('admin.labtests')" :current="request()->routeIs('admin.labtests')" wire:navigate>{{ __('Lab Tests') }}</flux:navlist.item>
                      <flux:navlist.item icon="presentation-chart-bar" :href="route('admin.labtestsshare')" :current="request()->routeIs('admin.labtestsshare')" wire:navigate>{{ __('Lab Tests Share') }}</flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            {{-- <flux:navlist variant="outline">
                <flux:navlist.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                {{ __('Repository') }}
                </flux:navlist.item>

                <flux:navlist.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                {{ __('Documentation') }}
                </flux:navlist.item>
            </flux:navlist> --}}

            <!-- Desktop User Menu -->
            <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
