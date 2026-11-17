@props(['menu'])
<nav class="sg-nav" aria-label="Main menu" data-workspace-nav>
    @foreach ($menu as $group)
        <div>
            <p id="nav-heading-{{ $group->key }}" class="sg-nav-heading">{{ $group->heading }}</p>
            <x-shell.nav-items :group="$group" id-prefix="nav" />
        </div>
    @endforeach
</nav>
