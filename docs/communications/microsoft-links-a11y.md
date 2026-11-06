# Microsoft 365 links — accessibility and mobile notes

## Automated checks

- Pest covers audience filtering, new-tab labelling on the dashboard tile, and
  allowlisted HTTPS URLs.
- Sidebar Microsoft 365 items reuse the shell external-link pattern with
  `target="_blank"`, `rel="noopener"` and visible `sr-only` “opens in a new tab”
  text (same as Teams).

## Manual viewport notes (Laragon / Cloudron)

Recorded for Agents on Linux cloud without Laragon: verify on Windows Laragon
at 390, 820 and 1440 px before ship.

| Width | Expected |
| --- | --- |
| 390 | Drawer opens; Microsoft 365 group reachable; external-link icon and SR text present |
| 820 | Rail + content; apps tile wraps without overflow |
| 1440 | Full rail; apps tile and menu stay keyboard reachable |

## PWA and mobile app behaviour

| Link | PWA | iOS | Android |
| --- | --- | --- | --- |
| Teams | Opens outside the PWA (system browser / Teams app if installed) | Prefers Teams app when installed; otherwise Safari | Prefers Teams app when installed; otherwise Chrome |
| Planner | Opens outside the PWA | Planner mobile app if installed; otherwise browser | Same |
| Outlook / OneDrive / SharePoint / Engage / Bookings / Loop | Opens outside the PWA | System browser / Microsoft apps if installed | Same |

No Teams tokens are stored in the intranet.
