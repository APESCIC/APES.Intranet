@props(['nameSuffix' => 'account'])
<section class="sg-user-menu__appearance" data-appearance-panel aria-label="Appearance">
    <p class="sg-user-menu__appearance-heading">Appearance</p>
    <fieldset class="sg-appearance-fieldset">
        <legend class="sr-only">Colour mode</legend>
        <div class="sg-appearance-options" role="radiogroup" aria-label="Colour mode">
            <label class="sg-appearance-option">
                <input type="radio" name="appearance-mode-{{ $nameSuffix }}" value="light" data-appearance-mode-option>
                <span>Light</span>
            </label>
            <label class="sg-appearance-option">
                <input type="radio" name="appearance-mode-{{ $nameSuffix }}" value="dark" data-appearance-mode-option>
                <span>Dark</span>
            </label>
            <label class="sg-appearance-option">
                <input type="radio" name="appearance-mode-{{ $nameSuffix }}" value="system" data-appearance-mode-option>
                <span>System</span>
            </label>
        </div>
    </fieldset>
    <fieldset class="sg-appearance-fieldset">
        <legend class="sr-only">Theme</legend>
        <div class="sg-appearance-options" role="radiogroup" aria-label="Theme">
            <label class="sg-appearance-option">
                <input type="radio" name="appearance-theme-{{ $nameSuffix }}" value="glass" data-appearance-theme>
                <span>Glass</span>
            </label>
            <label class="sg-appearance-option">
                <input type="radio" name="appearance-theme-{{ $nameSuffix }}" value="jungle" data-appearance-theme>
                <span>Jungle</span>
            </label>
        </div>
    </fieldset>
    <label class="sg-appearance-check">
        <input type="checkbox" data-appearance-reduced-transparency>
        <span>Reduced transparency</span>
    </label>
</section>