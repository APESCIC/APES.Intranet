# Replacing the holding page

Until the Laravel app goes live, `https://intranet.apes.org.uk` shows the APES
"coming soon" holding page. The APES IT Administrator put it up in the LAMP
app's default web root, `/app/data/public`. DNS already points at the app, so
no DNS change is needed.

The switch happens **only after the owner approves it** on issue #13, and is
done by the owner, the APES IT Administrator, or an agent with the owner's
approval in the current chat. It was done on 5 November 2026 with v0.0.1. The
steps below stay as the record and for going back.

The commands use `$app` from [cloudron.md](cloudron.md) and run as written in
Windows PowerShell 5.1.

## Before the switch

- [ ] The owner has approved the switch on issue #13.
- [ ] First-time set-up steps 1 to 4 in [cloudron.md](cloudron.md) are done.
      The shared `.env`, `APP_KEY` and `run.sh` exist.
- [ ] At least one release has been uploaded and activated, so
      `/app/data/current` points at it.
- [ ] The `intranet.*` groups exist in Cloudron and at least one admin is in
      `intranet.admin` ([cloudron-sign-in.md](../cloudron-sign-in.md)).
- [ ] A Cloudron backup has been taken, and its id noted on issue #13.

The holding page keeps showing while a release is activated, because Apache
still serves `/app/data/public`.

## The switch

1. Leave the holding page files where they are, in `/app/data/public`. They are
   the fallback.
2. Install the Apache config that serves the Laravel app, then restart:

   ```powershell
   cloudron push --app $app .\deploy\cloudron\apache-app.conf /app/data/apache/app.conf
   cloudron restart --app $app
   ```

3. Check:
   - `https://intranet.apes.org.uk/healthz` returns 200 with `"status":"ok"`.
   - `https://intranet.apes.org.uk/` shows the APES sign-in page.
   - An `intranet.admin` member can sign in and open **Admin > Audit log**.
   - `https://intranet.apes.org.uk/no-such-page` shows the APES 404 page.
4. Record the result on issue #13.

## Going back to the holding page

If any check fails and cannot be fixed quickly:

```powershell
cloudron exec --app $app '--' rm /app/data/apache/app.conf
cloudron restart --app $app
```

Apache then serves `/app/data/public`, the holding page, again. Nothing in
`/app/data/current`, `/app/data/shared` or the database is lost.

## After a week live

Once the intranet has run well for a week, and with the owner's agreement, the
holding page files in `/app/data/public` can be removed.
