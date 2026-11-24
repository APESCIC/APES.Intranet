# Legal pages

Editable legal content for the intranet: Privacy notice, Cookies, Terms of use, Accessibility statement, and Hosting and security. Pages live in Core under Admin › Apps and content › Legal. See also the [glossary](glossary.md) and [decisions](decisions.md) (22 November 2026).

## Public paths

| Page | Path | Who can see it |
| --- | --- | --- |
| Privacy notice | `/privacy` | Everyone (including signed-out) |
| Cookies | `/cookies` | Everyone |
| Terms of use | `/terms` | Everyone |
| Accessibility statement | `/accessibility` | Everyone |
| Hosting and security | `/hosting-and-security` | Signed-in only |
| Earlier versions | `/legal/{slug}/versions` | Same as the page |

Published body HTML is sanitised with `HtmlSanitizer` and served with heading anchors for “On this page”.

## How to edit and publish

1. Sign in with a role that has `legal.pages.manage` (Admin or Legal).
2. Open **Admin › Apps and content › Legal › Legal pages**.
3. Open a page. The editor opens **one draft** at a time (Write and Preview).
4. Fill change summary, optional in-effect date, visibility, and (for Terms) whether this is a **material change**.
5. **Save draft**, then **Publish now** or **Schedule** from a future date.
6. Use **History** to compare versions or **restore** an old version into a new draft.

A scheduled version stays hidden until its in-effect date. Published versions are immutable; restore copies wording into a draft.

Review due defaults to **12 months** after publication and shows on the list.

## Footer links

**Legal › Footer links** chooses labels, targets, visibility and order for the grouped site footer (Legal, Trust and help, About). Privacy and Cookies are **locked** for Everyone and cannot be removed or hidden. Module-off pages hide their footer link automatically. The version pill behaviour is unchanged.

## Terms acceptance

Signed-in people must accept the current Terms of use at first sign-in and again after a **material** Terms change. Privacy and Cookies updates show a one-time “updated” note only (not a consent request). There is no cookie banner while only strictly necessary cookies are used.

Emergency off-switch (name only in `.env.example`; value in Vault / Cloudron): `LEGAL_ACCEPTANCE_PROMPT`. Set to `false` to disable the prompt.

**Acceptance** (same Legal menu) lists who has accepted the current Terms and exports an audited CSV.

## Cookies

The cookie register is defined in `config/legal.php` and is always shown on `/cookies`, even when no published wording exists yet.

Adding a **non-essential** cookie (analytics, advertising, optional preferences, and similar) needs a consent decision and implementation work first. Today the intranet only sets **strictly necessary** cookies (session and CSRF protection). Do not add new cookies to production without updating the register and, when required, the consent approach.

## Accessibility and theme check (v0.6.18)

Manual Herd check (24 November 2026, #456) on `https://apes-intranet.test`:

- Cookies and Hosting and security pages, light and dark, Glass and Jungle, plus **390 px** width
- Grouped footer (Legal / Trust and help) present; skip link and group headings present
- Cookie register uses horizontal scroll inside the table wrapper on narrow widths (page itself does not scroll sideways)
- No serious or critical issues found; Herd badge ignored during checks

Screenshots: [docs/legal/screenshots/](legal/screenshots/).
