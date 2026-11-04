@props(['menu', 'user', 'qaEnabled' => false])
<div class="sg-rail" data-workspace-rail>
    <div class="sg-rail__top">
        <x-shell.brand />
        <button type="button" class="sg-icon-btn" aria-label="Close menu" data-workspace-close hidden>
            <i data-lucide="x"></i>
        </button>
    </div>
    <x-shell.nav :menu="$menu" />
    <x-shell.user-dock :user="$user" :qa-enabled="$qaEnabled" />
</div>
