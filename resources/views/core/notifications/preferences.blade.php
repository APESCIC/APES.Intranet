<x-layouts.app title="Notification preferences" subtitle="Choose how the intranet reaches you. Browser push starts off until you opt in.">
    @if (session('status'))
        <p class="mb-4 max-w-3xl rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="mb-4 max-w-3xl rounded-sg-field bg-sg-error-mist p-3 text-sg-error" role="alert">{{ session('error') }}</p>
    @endif

    <div class="mb-4 max-w-3xl rounded-sg-field bg-sg-warn-mist p-3 text-sg-warn" role="note">
        <strong>Shared devices.</strong> If you use a shared desk or phone, remove this device before you leave.
        Browser notifications can still arrive while nobody is signed in until the device is removed.
    </div>

    <section class="sg-card mb-6 max-w-3xl space-y-4 p-6" aria-labelledby="push-enable-heading">
        <h2 id="push-enable-heading" class="font-serif text-xl font-semibold">Browser notifications</h2>
        <p>
            Lock-screen wording stays generic. It never includes a ticket subject, a reply, a name, or any student
            or safeguarding detail. The browser will ask for permission only after you press the button below.
        </p>
        @unless ($pushConfigured)
            <p class="text-sg-muted" role="status">Web Push keys are not configured on this server yet, so browser notifications cannot be turned on here.</p>
        @endunless
        <div class="flex flex-wrap gap-3">
            <button type="button" class="sg-btn sg-btn-primary" id="enable-push" @disabled(! $pushConfigured) data-push-enable>
                Turn on browser notifications
            </button>
            <button type="button" class="sg-btn sg-btn-secondary" id="disable-push" data-push-disable>
                Turn off on this device
            </button>
        </div>
        <p id="push-status" class="text-sm text-sg-muted" role="status" aria-live="polite"></p>

        <details class="rounded-sg-field border border-sg-info-mist p-3">
            <summary class="cursor-pointer font-semibold">iPhone and iPad</summary>
            <p class="mt-2 text-sm">
                Safari on iPhone and iPad only supports Web Push after you add the intranet to your Home Screen
                (Share, then Add to Home Screen). Open it from the Home Screen icon, then use the button above.
                A normal Safari tab cannot subscribe.
            </p>
        </details>
    </section>

    <form method="post" action="{{ route('notifications.preferences.update') }}" class="sg-card mb-6 max-w-3xl space-y-6 p-6">
        @csrf
        @method('PUT')

        <h2 class="font-serif text-xl font-semibold">Categories</h2>
        <p class="text-sm text-sg-muted">Each category can use browser push, email and the in-app bell independently. Push starts off.</p>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Notification channels by category</caption>
                <thead>
                    <tr>
                        <th scope="col" class="py-2 pr-3">Category</th>
                        @foreach ($channels as $channel)
                            <th scope="col" class="px-2 py-2 text-center">{{ $channel->label() }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr class="border-t border-sg-info-mist">
                            <th scope="row" class="py-3 pr-3 align-top">
                                <span class="font-semibold">{{ $category->label() }}</span>
                                <span class="mt-1 block font-normal text-sg-muted">{{ $category->description() }}</span>
                            </th>
                            @foreach ($channels as $channel)
                                @php
                                    $id = 'channel-'.$category->value.'-'.$channel->value;
                                    $checked = $preferences->allows($category, $channel);
                                @endphp
                                <td class="px-2 py-3 text-center align-top">
                                    <label class="inline-flex flex-col items-center gap-1">
                                        <span class="sr-only">{{ $channel->label() }} for {{ $category->label() }}</span>
                                        <input
                                            type="checkbox"
                                            id="{{ $id }}"
                                            name="channels[{{ $category->value }}][{{ $channel->value }}]"
                                            value="1"
                                            class="h-5 w-5"
                                            @checked($checked)
                                        >
                                    </label>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <fieldset class="space-y-3">
            <legend class="font-serif text-xl font-semibold">Quiet hours</legend>
            <p class="text-sm text-sg-muted">Times are Europe/London. Account and security alerts can still come through.</p>
            <div class="flex flex-wrap gap-4">
                <div>
                    <label class="mb-1 block text-sm font-semibold" for="quiet_hours_start">Starts</label>
                    <input
                        type="time"
                        id="quiet_hours_start"
                        name="quiet_hours_start"
                        value="{{ old('quiet_hours_start', optional($preferences->quiet_hours_start)->format('H:i')) }}"
                        class="rounded-sg-field border border-sg-info-mist bg-white px-3 py-2"
                    >
                    @error('quiet_hours_start')<p class="mt-1 text-sm text-sg-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold" for="quiet_hours_end">Ends</label>
                    <input
                        type="time"
                        id="quiet_hours_end"
                        name="quiet_hours_end"
                        value="{{ old('quiet_hours_end', optional($preferences->quiet_hours_end)->format('H:i')) }}"
                        class="rounded-sg-field border border-sg-info-mist bg-white px-3 py-2"
                    >
                    @error('quiet_hours_end')<p class="mt-1 text-sm text-sg-danger">{{ $message }}</p>@enderror
                </div>
            </div>
        </fieldset>

        <button type="submit" class="sg-btn sg-btn-primary">Save preferences</button>
    </form>

    <section class="sg-card mb-6 max-w-3xl space-y-4 p-6" aria-labelledby="devices-heading">
        <h2 id="devices-heading" class="font-serif text-xl font-semibold">Devices</h2>
        @if ($subscriptions->isEmpty())
            <p class="text-sg-muted">No browser devices are subscribed yet.</p>
        @else
            <ul class="space-y-3">
                @foreach ($subscriptions as $subscription)
                    <li class="flex flex-wrap items-center justify-between gap-3 border-t border-sg-info-mist pt-3">
                        <div>
                            <p class="font-semibold">Browser device</p>
                            <p class="text-sm text-sg-muted">Added {{ $subscription->created_at?->timezone('Europe/London')->format('j M Y, H:i') }}</p>
                        </div>
                        <form method="post" action="{{ route('push-subscriptions.destroy') }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="endpoint" value="{{ $subscription->endpoint }}">
                            <button type="submit" class="sg-btn sg-btn-secondary sg-btn-sm">Remove device</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif

        <form method="post" action="{{ route('notifications.preferences.test') }}" class="pt-2">
            @csrf
            <button type="submit" class="sg-btn sg-btn-secondary" @disabled($subscriptions->isEmpty() || ! $pushConfigured)>
                Send a test notification
            </button>
        </form>
    </section>
</x-layouts.app>
