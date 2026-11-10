@props(['panel' => null])
<a href="{{ url('/') }}" class="sg-brand">
    <img src="{{ asset('brand/apes-mark-64.png') }}" width="40" height="40" alt="" class="sg-brand__mark">
    <span class="sg-brand__text">
        <span class="sg-brand__name">{{ config('app.name') }}</span>
        <span class="sg-brand__panel" data-brand-panel>{{ $panel ? $panel->label().' panel' : 'APES CIC' }}</span>
    </span>
</a>
