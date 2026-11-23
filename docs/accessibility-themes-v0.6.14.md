# Theme accessibility check — v0.6.14

Manual check on Laravel Herd (`https://apes-intranet.test`) in Cursor's
built-in browser, 23 November 2026. Covers Glass/Jungle × Light/Dark on staff
and admin surfaces (desktop and phone width). Full launch audit remains #74.

## Method

1. Preview gate (`scripts\preview.ps1`), Vite on 5174.
2. Developer sign-in as Staff + Admin; switch Appearance for each variant.
3. Keyboard: Tab through top bar, mega menus / phone menu, account menu,
   theme toggle, primary dashboard actions; confirm visible 3 px focus rings.
4. Contrast: measured from theme tokens (WCAG relative luminance) for body
   text (≥4.5:1), secondary text (≥4.5:1), and controls/borders (≥3:1).
5. Phone: viewport under 64 rem — Menu button, panel, Escape returns focus.

Ignore Herd's `#herd-ai-host` badge.

## Results

| Variant | Desktop staff | Desktop admin | Phone menu | Keyboard | Contrast body | Contrast secondary | Controls | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Glass light | Pass | Pass | Pass | Pass | Pass (~12:1 ink on soft card) | Pass (~6.7:1 muted `#2f5558`) | Pass (≥3:1 edges) | Softer cards from #416 |
| Glass dark | Pass | Pass | Pass | Pass | Pass (ink `#e6f2f4` on `#0c1c21`) | Pass (muted `#8fb4b8`) | Pass | Status pills keep hue meaning |
| Jungle light | Pass | Pass | Pass | Pass | Pass (ink `#12261c` on `#dceee4`) | Pass (muted `#2f5540`) | Pass | Leaves decorative only; hidden when transparency reduced |
| Jungle dark | Pass | Pass | Pass | Pass | Pass (ink `#e6f4ec` on `#0c1a14`) | Pass (muted `#8fb89a`) | Pass | Amber accent decoration only |

## Failures

None raised for this version. Residual WCAG 2.2 AA work stays on #74.

## Preview note

A stale `public/build` asset (from before themes) can keep light tokens while
`data-mode="dark"` is set. Local check used a fresh `npm run build` / Vite
assets so theme rules were present. CI always builds assets on deploy.

## Related

- Epic #411, task #424
- [Accessibility](accessibility.md) baseline
- Shell notes: [shell.md](shell.md)
