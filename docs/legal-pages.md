# Legal pages

Versioned legal content (privacy, cookies, terms, accessibility, hosting and security) lives in the database and is served by `LegalPageController`.

## Cookies

The cookie register is defined in `config/legal.php` and is always shown on `/cookies`, even when no published wording exists yet.

Adding a **non-essential** cookie (analytics, advertising, optional preferences, and similar) needs a consent decision and implementation work first. Today the intranet only sets **strictly necessary** cookies (session and CSRF protection). Do not add new cookies to production without updating the register and, when required, the consent approach.
