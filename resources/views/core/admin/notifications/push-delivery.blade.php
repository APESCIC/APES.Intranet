<x-layouts.app title="Push delivery" subtitle="Counts only. Push payloads and full subscription endpoints are never shown here.">
    <section class="sg-card mb-4 max-w-3xl p-5" aria-labelledby="push-keys-heading">
        <h2 id="push-keys-heading" class="font-serif text-lg font-semibold">Web Push keys</h2>
        <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm">
            <dt class="text-sg-muted">VAPID keys configured</dt>
            <dd data-vapid-configured="{{ $vapidConfigured ? 'yes' : 'no' }}">
                {{ $vapidConfigured ? 'Yes' : 'No' }}
            </dd>
        </dl>
        <p class="mt-3 text-sm text-sg-muted">
            Keys live in the APES Vault and Cloudron shared env only. Treat them like
            <code>APP_KEY</code>: do not rotate them casually, or every device must subscribe again.
        </p>
    </section>

    <section class="sg-card mb-4 max-w-3xl p-5" aria-labelledby="push-subs-heading">
        <h2 id="push-subs-heading" class="font-serif text-lg font-semibold">Subscriptions</h2>
        <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm">
            <dt class="text-sg-muted">Stored subscriptions</dt>
            <dd data-subscription-count>{{ $subscriptionCount }}</dd>
            <dt class="text-sg-muted">People with at least one device</dt>
            <dd data-subscriber-count>{{ $subscriberCount }}</dd>
        </dl>
        <p class="mt-3 text-sm text-sg-muted">
            Expired endpoints are dropped automatically when the push service returns gone (410/404).
            Endpoints themselves are not listed on this page.
        </p>
    </section>

    <section class="sg-card mb-4 max-w-3xl p-5" aria-labelledby="push-optin-heading">
        <h2 id="push-optin-heading" class="font-serif text-lg font-semibold">Push opt-in by category</h2>
        <p class="mt-2 text-sm text-sg-muted">How many people have browser push switched on for each category.</p>
        <table class="mt-3 w-full text-left text-sm">
            <caption class="sr-only">Push opt-in counts by category</caption>
            <thead>
                <tr>
                    <th scope="col" class="py-2 pr-3">Category</th>
                    <th scope="col" class="py-2">People opted in</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($optInByCategory as $row)
                    <tr class="border-t border-sg-info-mist">
                        <th scope="row" class="py-2 pr-3 font-normal">{{ $row['label'] }}</th>
                        <td class="py-2" data-opt-in="{{ $row['label'] }}">{{ $row['push'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="sg-card mb-4 max-w-3xl p-5" aria-labelledby="inapp-heading">
        <h2 id="inapp-heading" class="font-serif text-lg font-semibold">In-app bell</h2>
        <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm">
            <dt class="text-sg-muted">Stored in-app items</dt>
            <dd data-inapp-total>{{ $inAppTotal }}</dd>
            <dt class="text-sg-muted">Unread</dt>
            <dd data-inapp-unread>{{ $inAppUnread }}</dd>
        </dl>
        <p class="mt-3 text-sm text-sg-muted">
            Titles and bodies on this admin page are never shown. Open a person's own inbox to see their items.
        </p>
    </section>
</x-layouts.app>
