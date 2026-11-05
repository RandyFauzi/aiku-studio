# System audit — 2026-11-05

Method: Graphify queries/path/impact navigation followed by source review,
isolated feature regressions and read-only aggregate data checks. Scope covers
routes/session/roles, contributor identity, payroll/files/status, attendance,
reports/assignments, device accounts, shifts/calendar, Atlas/1618 ingestion,
scheduler and frontend entry points. Graph findings were verified in source;
no conclusion relies on an inferred graph edge alone.

## Confirmed defects addressed

| Area | Defect | Result |
| --- | --- | --- |
| Private files | Any logged-in contributor could download another worker's PDF/evidence if the URL was known | Ownership checked against own slip references; live PDF exports carry private owner metadata |
| Payslip export routes | Duplicate admin registrations overrode the contributor routes | One authenticated route per export, with owner checks |
| Payslip identity | Stale users.name acted as an alternate payroll identity | Only the linked contributor name grants access; ambiguous names fail closed |
| Account recovery | Matching a name could link an unrelated person | Email-only recovery with uniqueness/ownership checks; deleted identity is not silently replaced |
| Contributor data | Duplicate names and renaming payroll-linked names could cross-link or orphan history | Duplicate names rejected; historical payroll names cannot be changed through ordinary editing |
| Admin access | Admin could delete their own contributor or clear their own email | Both actions rejected, matching existing self-demotion protection |
| GPS age | Signed Carbon time differences accepted old GPS timestamps | Absolute age check rejects old/future timestamps outside the configured 30-minute window |
| WiFi origin | Client-supplied proxy headers could impersonate office IP | Framework request IP only; headers require explicit trusted-proxy configuration |
| WiFi range | An exact configured IP implicitly trusted the entire IPv4 /24 or IPv6 /64 | Exact match by default; IPv4 wildcard ranges require explicit administrator configuration |
| Geofence | Missing GPS bypassed a configured fence; accuracy could greatly expand it; zero-valued coordinates disabled it | GPS/time/accuracy required for configured fence; accuracy tolerance bounded; zero coordinates remain valid |
| Attendance metadata | QR without GPS recorded fake zero coordinates; disabled WiFi still said verified | Missing GPS stays null; verified reflects whether the WiFi check ran |
| Attendance settings | Merely opening settings registered the admin's current network as office | Registration requires explicit save |
| Recording assignment | Submitted contributor ID and name could disagree | Canonical name derived from the database; clearing assignment clears both values |
| Assisted assignment | Stale candidate lists overwrote another person's assignment | Only still-unassigned records are assigned atomically |
| Daily reports | Unknown per-phone keys were silently dropped from totals | Unknown phone keys produce validation errors |
| Shift identity | Saving an old schedule after a name change discarded its stable contributor ID; aliases could duplicate one person | Valid ID resolves the canonical name; duplicate identities and missing referenced IDs are rejected |
| Device accounts | Moving an active account could leave two active accounts on one phone | Target phone's other accounts deactivated; mutations serialized through a shared cache lock |
| Studio identities | Editing email renamed a shared studio account, caused unique-key failures and retained another account's participant_id | Register/reuse the target email without mutating the old external identity |
| Payment state | Slip create/edit/status could disagree with payroll_statuses or other versions of a slip | Writes synchronize all saved versions and canonical worker/period payment state in one transaction |
| 1618 ingestion | Invalid/missing dates could overwrite historical dates with today or null | Invalid rows skipped; calendar date and duration validated; UTC interpreted explicitly; record IDs normalized |
| Sync response | Missing source or failed 1618 operation could be reported as completed | Explicit failure when no source succeeded or started |
| Payroll input | Array-valued periods or incomplete date bounds could trigger 500 or silently use another period | Structured validation returns 422 |
| Hosting scheduler | Cron still invoked the legacy sibling installation after the active app moved | Replaced only the AIKu cron with PHP 8.3 running public_html/artisan schedule:run every minute; verified the new account cron listing |

## Verification and limits

Final verification: **85 tests / 481 assertions passed**. Graph refreshed to
913 nodes and 1,586 edges. Read-only integrity audit reported zero issues in all
11 checks on both local MySQL and production. Deployment checked all business
table hashes before/after and preserved them, including the primary admin.
Live browser checks passed on nine admin pages, five contributor pages and three
personal APIs. Contributor access to admin data, another person's slip and
another person's raw PDF returned 403. No business records were created by those
live browser checks. These results describe the deployed snapshot, not all
future inputs or database changes.
`php vendor/bin/phpunit --no-progress` and `npm run build` also passed during the
coordinated handover. Build warnings remain for the local `#` directory name and
a JavaScript bundle above 500 kB; they did not prevent the production build.
The active production layout currently forwards requests through
`public_html/public/`; the replaced scheduler UID is `TV9nyHgDON`. Other sites'
cron jobs were preserved. The scheduler configuration was read back; external
sync was not manually triggered as an audit test.

Regression suite: `tests/Feature/SystemAuditRegressionTest.php`. Full suite runs
against SQLite in-memory, not operational MySQL. PHP formatting is checked only
on changed files. No external synchronization, emails, payroll imports or fake
attendance/report submissions are performed on production as part of the audit.

`php artisan panel:audit-data` is a repeatable read-only check for duplicate
identity fields, missing owners, mismatched recording ID/name, conflicting active
phone accounts, payment state discrepancies and per-phone total inconsistencies.
It prints counts only, never names/emails/GPS/pay values. Nonzero counts require
source/history review, not automatic merging or assignment by guesswork.

Legacy payroll remains name-based and several references intentionally lack
foreign keys to preserve history after contributor deletion. This audit adds
guards rather than bulk rewriting financial history. Already malformed upstream
records are not retroactively re-dated. Full temporal phone-to-account history is
not stored, so report assistance remains a suggestion requiring admin review.

GPS/IP are browser/network signals, not proof of physical presence. If a reverse
proxy is introduced, configure only its verified addresses as trusted proxies;
do not trust arbitrary forwarded headers or all proxies. Existing wildcard
settings intentionally authorize the range selected by the admin.

This is a source/behavior audit, not an assertion that every possible defect or
external-service failure has been ruled out. Native MySQL concurrency, scheduler
network failures and live upstream changes need operational monitoring as well.
