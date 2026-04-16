<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('Profile') }}</h2>
    </x-slot>

    <div class="app-page app-page--narrow space-y-6">
        <div class="app-card">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="app-card">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="app-card border-red-100">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
