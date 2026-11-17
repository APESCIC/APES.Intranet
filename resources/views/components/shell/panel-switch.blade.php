@props(['panel', 'class' => null])
@if ($panel)
    <nav @class(['sg-switch', $class]) aria-label="Switch panel" data-panel-switch>
        <a href="{{ route('dashboard') }}" class="sg-switch__link" @if ($panel === \App\Core\Navigation\MenuMode::Staff) aria-current="true" @endif>Staff</a>
        <a href="{{ route('admin.overview') }}" class="sg-switch__link" @if ($panel === \App\Core\Navigation\MenuMode::Admin) aria-current="true" @endif>Admin</a>
    </nav>
@endif
