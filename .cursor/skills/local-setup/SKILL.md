---
name: local-setup
description: Set up, repair or check the Laravel Herd local copy of APES Intranet - run herd-setup.ps1, load the demo data, start Vite and open the preview. Use for a first set-up, when the local site is broken, or when the user mentions Herd, the local site, the demo data or apes-intranet.test.
---

# Local set-up (Herd)

Follows `.cursor/rules/herd.mdc`. The live preview gate is `.cursor/skills/live-preview-gate/SKILL.md`. Full guide: `docs/local-development.md`.

## Steps

1. **Check Herd.** Laravel Herd Pro for Windows, running, with PHP 8.4 (`ldap` turned on), a MySQL 8.4 service on port 3306 and Node 22 through Herd's nvm. `herd status`, `herd services:list` and `herd node:list` show them. **Stop and ask** before installing anything, naming the tool, why, and the exact command. Turning on Node.js in Herd's Settings needs Bambie's click.
2. **Set up or repair.** `powershell -NoProfile -ExecutionPolicy Bypass -File scripts\herd-setup.ps1 -NoDemo`. It only touches this project and is safe to run twice.
3. **Demo data.** If the database is empty, `php artisan demo:seed`. **Stop and ask** before `php artisan demo:reset`, because it empties the local database.
4. **Preview.** Follow the `live-preview-gate` skill. Do not use Herd's badge "dev" stack.
5. **Sign in** with the amber Developer sign-in panel on <https://apes-intranet.test/login> as a fake account for the role you need. Switch role later with the amber QA pill at the bottom of the menu (`/qa` is the fallback).
6. If it still won't load after two attempts, **stop**, report what you saw (the Herd MCP `site` `error` action shows the last exception), and ask how to proceed.
