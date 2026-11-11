---
name: local-setup
description: Set up, repair or check the Laragon local copy of APES Intranet - run laragon-setup.ps1, load the demo data, start Vite and open the preview. Use for a first set-up, when the local site is broken, or when the user mentions Laragon, the local site, the demo data or apes-intranet.test.
---

# Local set-up (Laragon)

Follows `.cursor/rules/laragon.mdc`. The full guide, with fixes for common problems, is `docs/local-development.md`.

## Steps

1. **Check Laragon.** Laragon (Full) 8 or newer in `C:\laragon`, with PHP 8.4 (NTS), MySQL 8.4 and Node 22. **Stop and ask** before installing anything, naming the tool, why, and the exact command. Starting Laragon or clicking Reload needs Bambie's click.
2. **Set up or repair.** `powershell -NoProfile -ExecutionPolicy Bypass -File scripts\laragon-setup.ps1 -NoDemo`. It only touches this project and is safe to run twice.
3. **Demo data.** If the database is empty, `php artisan demo:seed`. **Stop and ask** before `php artisan demo:reset`, because it empties the local database.
4. **Preview.** Run the live preview gate (`scripts\preview.ps1`), open <http://apes-intranet.test> in Cursor's browser and take a screenshot.
5. **Sign in** at <http://apes-intranet.test/qa> as a fake account for the role you need.
6. If it still won't load after two attempts, **stop**, report what you saw, and ask how to proceed.
