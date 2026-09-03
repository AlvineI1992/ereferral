# Changelog

## AEI - 2026-09-03
- Added filter-aware referral metrics, daily sent-versus-received trends, and top receiving-facility graphs to the facility report.
- Made incoming patient profiles tolerate missing demographic reference rows and corrected the profile street-address source.
- Widened the referral report patient drill-down and stabilized its table width so the Action column remains accessible.
- Added permission-controlled patient transaction deletion by LogID to the Referrals by Facility patient drill-down, with typed confirmation and refreshed report totals.

## AEI - 2026-09-02
- Added permission- and facility-scoped deletion for a specific Incoming Referral LogID, including typed confirmation and transactional cleanup of related referral records and attachments.
- Added access-scoped advanced Incoming Referral filters for date range, origin, destination, referral type, category, and reason with server-side pagination support and leaner list eager loading.
- Minimized all Incoming Referral dashboard cards with compact local padding, spacing, typography, badges, and responsive gaps.
- Linked patients in the referral report drill-down directly to their Incoming Referral profiles.
- Added provider filtering to the referral report, including summary totals, facility and RHU breakdowns, patient drill-downs, and CSV exports.
- Added access-controlled View Patients actions to the referral report, with filtered referred/received patient details loaded on demand for receiving-facility and RHU rows.
- Added a dedicated Reports classification to the application sidebar and moved Referral Report out of Transactions into that section.
- Added a facility referral report with date, referring-facility, and referral-facility filters, sent/received/pending totals, a dedicated per-RHU referral-sending breakdown, receiving-facility types, receipt rates, access-level scoping, and a filter-preserving CSV export.
- Added supporting attachment selection and upload to the e-referral create and edit form, with removable file previews and server-side file count, size, and type validation.

## AEI - 2026-09-01
- Added an admin-only CipherSweet Data Encryption module with an OFF-by-default switch, key/schema preflight checks, conversion status storage, explicit activation confirmation, and a safety lock that prevents premature database rewriting.

## AEI - 2026-08-31
- Compacted all Incoming Referral profile cards, navigation, spacing, and attachment rows for a denser responsive layout.
- Connected the Incoming Referral profile Attachments tab to received referral files with file metadata and authenticated downloads.
- Added secure optional JPEG, PNG, WebP, and PDF attachments to the `/api/refer_patient` multipart API, including private storage, metadata persistence, validation, and authenticated downloads.
- Added consistent row numbering to the incoming referral list across paginated results.
- Added database indexes for incoming referral facility, date, category, reason, province, municipality, and barangay filters to improve query performance.
- Updated the incoming referral form to load religions from the authenticated `/religions/list` endpoint.
- Hardened incoming referral pagination by normalizing invalid page and page-size values to positive integers.

## AEI - 2026-07-15
- Added incoming PH eReferral FHIR fetch endpoint at `/api/incoming/fhir/{LogID}` that returns the saved incoming referral and a regenerated FHIR Bundle.
- Added separate incoming PH eReferral FHIR endpoint at `/api/incoming/fhir` that stores received FHIR referrals into the incoming queue.
- Returned the referral `log_id` and full FHIR server result from `/api/refer_patient` when FHIR response modes are used.
- Added FHIR referral support for `/api/refer_patient` with `service_mode=current|fhir|both` and `response_format=current|fhir|both`.
- Configured the FHIR server base URL to default to `http://10.11.133.129:8080/`.
- Added a reusable FHIR referral adapter that can build FHIR transaction Bundles and normalize FHIR referral payloads back into the existing referral service format.
