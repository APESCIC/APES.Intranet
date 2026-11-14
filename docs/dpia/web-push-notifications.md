# DPIA: Web Push notifications

| Item | Value |
| --- | --- |
| Status | **Draft — not complete.** Do not treat as approved. Owner sign-off is still required. |
| Controller | Association of Protecting Exotic Species CIC (APES CIC) |
| Owner and approver | Bambie Murphy, Director |
| Feature | Opt-in browser Web Push and in-app notification bell, intranet v0.5.7 (#294–#299) |
| Template | ICO sample DPIA template, steps 1 to 7 |
| Drafted | 14 November 2026 |
| Review | Before go-live of production push, after any change to categories or payload content, and once a year while push is live |

This draft is kept in the repository so it can be reviewed with the code
([where documents live](../decisions.md#where-documents-live)). Completing and approving it is a
separate owner decision; shipping the code does not mark this DPIA complete.

## Step 1: Why a DPIA is needed

Members (staff, volunteers and students) may opt in to browser notifications about tickets, account
and security alerts, announcements, and rota or operations notices. Push can arrive on a locked
device screen. Recipients include volunteers and students. The processing therefore needs a DPIA
even though push is off by default and wording is deliberately generic.

## Step 2: The processing

### Nature

1. The person opens **Notification preferences**, reads the explanation, and presses **Turn on
   browser notifications** (a user gesture). The browser may then ask for permission.
2. On iPhone and iPad, Safari only supports Web Push after the intranet is added to the Home Screen
   and opened from that icon.
3. A push-only service worker at `/sw.js` receives push events and always calls `showNotification()`.
   It does not cache pages or personal data for offline use.
4. Subscription endpoints are stored against the signed-in user. Expired endpoints (410/404) are
   dropped.
5. When an existing email event fires (ticket, access review, addon down, event reminder, operations
   notice), a queued `IntranetAlert` may also send push and/or an in-app bell item, if that category
   is on and (for push) a subscription exists.
6. Lock-screen title and body are generic (for example “You have a new ticket reply”). A ticket
   reference may appear in the in-app bell only. Subjects, reply bodies, names, student details and
   safeguarding content are never put in the push payload.
7. Quiet hours (Europe/London) can suppress push for non-security categories. Account and security
   may still notify. Email behaviour stays as today unless the person changes their own preferences.

### Scope

- **Data:** push subscription endpoint and keys; per-category channel preferences; quiet hours;
  generic alert title/body; optional ticket reference for in-app items; VAPID public key exposed to
  signed-in browsers only.
- **Not processed in push:** ticket subjects, message bodies, student identifiers beyond ordinary
  account linkage, health or safeguarding narrative.
- **Keys:** VAPID private key lives only in the APES Vault and Cloudron shared env (private ops).
  Never in this repository. Treat like `APP_KEY`; do not rotate casually.

### Context and purposes

To let members who opt in learn promptly about intranet events they already receive by email, while
keeping lock-screen content non-sensitive and giving an in-app fallback (especially on iOS before
Home Screen install).

## Step 3–6: Risks and measures (summary for draft)

| Risk | Measure |
| --- | --- |
| Lock-screen leaks ticket or student content | Generic wording only; feature tests including student tickets |
| Shared device keeps receiving alerts | Preferences warning; remove device; document that push can arrive while signed out |
| Key rotation breaks every subscription | Vault + Cloudron only; documented “do not rotate casually” |
| Admin snooping on payloads | Admin **Push delivery** page shows counts only — no endpoints, no titles, no bodies |
| Service worker as tracking vector | Push-only SW; no offline cache of personal pages |

## Step 7: Sign-off and outcomes

| Item | Value |
| --- | --- |
| Owner decision | *Pending* |
| Approval date | *Not set* |
| Outcome | Draft only. Complete this DPIA before treating production push as DPIA-approved. |

Related decisions: [decisions.md](../decisions.md#features). Deploy notes for keys and the queue
worker: [deploy/cloudron.md](../deploy/cloudron.md#web-push-vapid-keys).
