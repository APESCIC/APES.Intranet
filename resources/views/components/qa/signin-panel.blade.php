@props(['profiles'])
<section id="developer-signin" {{ $attributes->class('sg-qa-panel') }} aria-labelledby="developer-signin-heading" data-qa-signin-panel>
    <div class="sg-qa-panel__header">
        <h2 id="developer-signin-heading" class="sg-qa-panel__title">
            <i data-lucide="flask-conical"></i>
            Developer sign-in
        </h2>
        <span class="sg-qa-tag">Local only</span>
    </div>
    <p class="sg-qa-panel__intro">
        Sign in as a fake <code>@example.test</code> account in one approved group, or a common stack of groups.
    </p>
    <x-qa.profile-picker :profiles="$profiles" id-prefix="developer-signin" />
</section>
