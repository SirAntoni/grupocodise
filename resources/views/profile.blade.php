<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            Mi perfil
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            {{-- La eliminación de cuenta se deshabilita: los usuarios se
                 gestionan desde Administración y tienen registros asociados. --}}
        </div>
    </div>
</x-app-layout>
