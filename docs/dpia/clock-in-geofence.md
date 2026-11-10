# DPIA: clock-in geofence check

| Item | Value |
| --- | --- |
| Status | **Approved by the owner on 10 November 2026** |
| Controller | Association of Protecting Exotic Species CIC (APES CIC) |
| Owner and approver | Bambie Murphy, Director |
| Feature | Optional geofence check at clock-in, intranet v0.4.0 Operations (#35, #36) |
| Template | ICO sample DPIA template, steps 1 to 7 |
| Drafted | 10 November 2026 |
| Review | Before the geofence is switched on, after any change to how it works, and once a year while it is on |

This assessment is kept in the repository so it can be reviewed with the code. The approved
copy is filed in SharePoint. The geofence stays off until the approval date is also set in the
Cloudron app's environment (see [Step 7](#step-7-sign-off-and-outcomes)).

## Step 1: Why a DPIA is needed

Members (staff, volunteers and students) clock in and out of shifts by tapping a button in the
intranet or its installed app. APES CIC is considering an optional check, at the moment of clock-in
only, that the member's device is near the site they are clocking in to.

Location data about identifiable people is personal data. The ICO lists location tracking and the
monitoring of employees among the kinds of processing that are likely to need a DPIA, and the
people concerned include volunteers and students, some of whom may be young. APES CIC has therefore
decided (AGENTS.md, decided features) that a DPIA is required before the check is switched on.

## Step 2: The processing

### Nature

1. The member taps **Clock in** on the intranet page or installed app.
2. Only if the geofence is switched on and the site has a geofence area, the browser asks the member
   for permission to read the device's position, once, at that moment.
3. The position is sent with the clock-in request over HTTPS.
4. The server works out whether the position is inside the site's radius, and stores only the
   result: `pass` or `fail`. The coordinates are discarded at once. They are never written to the
   database, logs, audit log or email.
5. Clock-in goes ahead either way. A `fail` result is shown to the member's manager next to the
   clock record, so they can ask about it.

There is no check at clock-out, during breaks or at any other time. There is no background
location, no continuous tracking, no route history and no map of where anyone has been. The
intranet has no service worker, so nothing runs when the page is closed.

### Scope

- **Data:** one pass or fail result per clock-in, linked to the clock record (member, time, shift
  and site). No coordinates, accuracy figures or device identifiers are kept.
- **Special category data:** none.
- **People:** APES CIC staff, volunteers and students who clock in to a site that has a geofence area.
- **Volume:** a few clock-ins per member per week.
- **Retention:** the result lives and is deleted with its clock record. Clock records and
  timesheets are kept for 2 years, as working time records, then deleted (confirmed by the owner
  on 10 November 2026).
- **Area:** the APES sites set up in the intranet's Admin panel.

### Context

- Members are in a working or volunteering relationship with APES CIC, so they may feel they cannot
  refuse. The check therefore never blocks a clock-in, and refusing location permission records a
  `fail` that a manager reads as "not checked", not as wrongdoing.
- Members would reasonably expect to record the time they start, but may not expect a location
  check. They are told on the clock-in page, before the browser asks, and in the privacy notice.
- Some members may be under 18. The check works the same for everyone and stores no more for them.
- Phone positions indoors can be wrong by tens of metres or more. The radius allows for this, and a
  `fail` is a prompt for a conversation, never an automatic sanction.

### Purposes

- To give managers some confidence that people clocked in at the site, for safe staffing around
  animals and for accurate working and volunteer hours.
- Benefit to members: fewer manual corrections to timesheets and fewer disputes about hours.

## Step 3: Consultation

- **Members:** before the check is switched on, managers explain it in a team update and give
  members the chance to raise concerns. The page text and privacy notice say what is and is not
  recorded.
- **Managers and supervisors:** asked how they would act on a `fail` result, and briefed that it is
  not evidence on its own.
- **IT Administrator:** confirms that coordinates are not logged by the web server or the app.
- **Data protection advice:** APES CIC does not have a statutory DPO. The Director decides, and may
  ask for outside advice before approving.

## Step 4: Necessity and proportionality

- **Lawful basis:** legitimate interests (UK GDPR Article 6(1)(f)), in safe staffing and accurate
  hours. A legitimate interests assessment is summarised here: the interest is real, the check is
  limited to one moment and one bit of information, and it does not override members' interests
  because it is optional per site, never blocks, keeps no coordinates and is explained up front.
  Consent is not used, because the working relationship makes it hard for consent to be freely given.
- **Necessity:** a manager's own knowledge, rota and sign-in sheet usually suffice. The check is
  therefore off by default, and switched on only for sites where those are not enough.
- **Minimisation:** pass or fail only; no coordinates stored; clock-in only.
- **Accuracy:** a generous default radius (250 metres) and a manager's review of any `fail`.
- **Transparency:** clock-in page notice, privacy notice and this DPIA.
- **Rights:** members can see their own clock records and results in the intranet, and can ask for
  access, correction or erasure through the Director.
- **Processors and transfers:** none new. The intranet runs on APES CIC's own Cloudron server in the
  UK or EU; no third-party location service is used (the browser's own location API only).

## Step 5: Risks

| Risk to people | Likelihood | Severity | Overall |
| --- | --- | --- | --- |
| Members feel watched, or the check is later widened into tracking | Possible | Significant | Medium |
| A wrong `fail` (poor signal) is treated as dishonesty | Possible | Significant | Medium |
| Coordinates leak through logs or error reports | Remote | Significant | Low |
| Results are used for a purpose other than staffing and hours | Remote | Significant | Low |
| A young volunteer's location is exposed | Remote | Severe | Low |

## Step 6: Measures to reduce risk

| Risk | Measure | Effect | Residual risk | Approved |
| --- | --- | --- | --- | --- |
| Feeling watched or scope creep | Clock-in only; no background or continuous location; code and tests enforce it; any change needs a new DPIA review | Reduced | Low | Owner |
| Wrong `fail` | Never blocks clock-in; 250 m default radius; managers briefed that `fail` is a prompt, not proof | Reduced | Low | Owner |
| Coordinates leak | Server discards coordinates before saving; not in audit log, email or logs; a test checks no coordinate is stored | Reduced | Low | Owner |
| Other purposes | Purpose limited in this DPIA and the privacy notice; results only shown to the member and their managers | Reduced | Low | Owner |
| Young volunteers | No more data than for adults; same safeguards | Accepted | Low | Owner |

## Step 7: Sign-off and outcomes

| Item | Name or date | Notes |
| --- | --- | --- |
| Measures approved by | Bambie Murphy, Director | 10 November 2026 |
| Residual risks approved by | Bambie Murphy, Director | All residual risks low; no ICO consultation needed |
| Decision | Approved | Including 2-year retention of clock records and timesheets |
| Approval date | 2026-11-10 | Set this in `OPERATIONS_GEOFENCE_DPIA_APPROVED_AT` to allow the geofence |
| Filed in SharePoint | Filed by Bambie Murphy | 10 November 2026 |
| Review date | 10 November 2027 | Or sooner on any change to how the check works |

### How the decision is recorded in the intranet

The geofence is on only when **both** of these are set in the Cloudron app's environment:

- `OPERATIONS_GEOFENCE_DPIA_APPROVED_AT`: the approval date above, for example `2026-12-01`.
- `OPERATIONS_GEOFENCE_ENABLED=true`.

Without the approval date the switch has no effect, and a test proves it. Each site also needs a
geofence area (latitude, longitude, radius) set by an admin in **Admin → Operations admin → Sites**.
Sites without an area are never checked.
