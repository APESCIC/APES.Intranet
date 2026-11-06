# SharePoint and intranet split (v0.3.x)

Agreed working split for APES Communications. SharePoint remains the document
and collaboration home. The intranet is the member portal.

## Written split

| Lives on the intranet | Stays in SharePoint |
| --- | --- |
| News / updates feed | Policy and handbook *files* |
| Digital handbook chapters (TipTap) | Collaboration libraries and draft working docs |
| Policy *index* (title, owner, review date, link) | Full policy PDFs and Word originals |
| Events and RSVPs | Meeting recordings and large media |
| Microsoft 365 app launcher links | OneDrive personal files |

The APES SharePoint Developer leads every SharePoint change. Intranet agents
do not edit SharePoint sites, pages or permissions.

## Migration list (owners)

| Item | From | To | Owner | Target |
| --- | --- | --- | --- | --- |
| Staff news posts | SharePoint news web parts (if any) | Intranet Updates | Communications / Bambie | v0.3.0 |
| Handbook narrative pages | SharePoint wiki / pages | Intranet Handbook | HR | v0.3.0 |
| Policy discovery | Folder browsing | Intranet Policies index + SharePoint file | Legal / HR | v0.3.0 |
| Document libraries | SharePoint | SharePoint (unchanged) | SharePoint Developer | n/a |

## Redirect list (SharePoint Developer)

| Old SharePoint URL / page | Redirect or home change | Notes |
| --- | --- | --- |
| SharePoint home “News” web part | Point members to `https://intranet.apes.org.uk/updates` | Developer-owned |
| Legacy handbook pages (when listed) | Redirect to matching `/handbook/{slug}` | Confirm slugs before go-live |
| Policy landing page | Link to `/policies` then SharePoint file | Keep file URL stable |

## Sign-off

- Bambie (Director): plan confirmed for v0.3.x Communications.
- APES SharePoint Developer: written split and redirect list to be confirmed
  before SharePoint home changes are applied. Until that confirmation lands,
  this document is the intranet-side coordination deliverable for #97.
