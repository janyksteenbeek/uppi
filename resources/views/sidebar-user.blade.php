<div class="pt-12 flex flex-col justify-between h-full">
    <div class="border border-gray-200 rounded-lg p-2 shadow-sm bg-white">
        <livewire:global-status-badge/>
    </div>
    <div class=" flex rounded-lg border-gray-200  items-center gap-4">
        <x-filament-panels::user-menu/>
        <div class="flex flex-col -gap-0.5" onclick="document.querySelector('[aria-label=\'User menu\']')">
            <div class="block truncate text-sm/5 font-medium text-zinc-950">
                {{ auth()->user()->name }}
            </div>
            <div class="block truncate text-sm/5 text-gray-500">
                {{ auth()->user()->email }}
            </div>
        </div>
    </div>
</div>
