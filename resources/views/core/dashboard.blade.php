<x-layouts.app title="Dashboard" :subtitle="'Welcome, '.auth()->user()->name.'.'">
    <div class="grid gap-4 sm:grid-cols-2">
        <section class="sg-card p-5" aria-labelledby="dashboard-teams">
            <h2 id="dashboard-teams" class="font-serif text-xl font-semibold">Chat</h2>
            <p class="mt-2 text-sg-muted">Chat with your team in Microsoft Teams.</p>
            <a href="{{ \App\Core\Navigation\Menu::TEAMS_URL }}" class="sg-btn sg-btn-secondary sg-btn-sm mt-4" target="_blank" rel="noopener">
                <i data-lucide="messages-square"></i>
                Open Teams<span class="sr-only"> (opens in a new tab)</span>
            </a>
        </section>
        <section class="sg-card p-5" aria-labelledby="dashboard-coming">
            <h2 id="dashboard-coming" class="font-serif text-xl font-semibold">Coming soon</h2>
            <p class="mt-2 text-sg-muted">
                Updates, rotas, the help desk, HR and agreements arrive over the next releases. The people directory and organisation chart are ready now.
            </p>
        </section>
    </div>
</x-layouts.app>
