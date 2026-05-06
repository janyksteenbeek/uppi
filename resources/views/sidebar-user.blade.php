{{-- Sidebar footer: live status badge + compact user identity. --}}
<div class="pulse-side-foot">
    <div class="pulse-side-status">
        <livewire:global-status-badge/>
    </div>

    <div class="pulse-side-user">
        <x-filament-panels::user-menu/>
        <div class="pulse-side-user-meta">
            <span class="pulse-side-user-name">{{ auth()->user()->name }}</span>
            <span class="pulse-side-user-email">{{ auth()->user()->email }}</span>
        </div>
    </div>
</div>
