# Changelog

## AEI - 2026-09-09
- Changed the development session default to file storage and cleared stale configuration so web requests no longer query an obsolete local SQLite sessions table.
- Added a centralized legacy MySQL/MariaDB schema grammar so runtime `Schema::hasColumn()` and column discovery no longer query the unavailable `generation_expression` metadata field.
- Fixed pending migrations across legacy MySQL/MariaDB and SQLite tests when servers lack modern schema metadata, contain historical zero-date timestamps, enforce the 767-byte index limit, or already contain deployed hierarchy tables; affected migrations now use driver-aware metadata queries, scoped SQL-mode compatibility, compatible attachment path indexes, and safe recovery from partially-created or pre-existing tables.
- Limited restrictive CSP, HSTS, and browser security policies to production responses; active Vite development mode now bypasses them even when a local environment is mislabeled as production.
- Fixed the production Content Security Policy to permit the configured Bunny Fonts stylesheet and font files.
- Completed application security hardening with production debug disabled, strict HTTP security headers, restricted CORS, and removal of the public diagnostic route.
- Updated Composer and NPM dependency locks to patched releases; both security audits now report zero known vulnerabilities.
- Protected role, permission, facility-type, religion, demographic, user, referral, and bed-management routes with explicit permissions or scoped Sanctum token abilities.
- Added reusable facility-aware referral authorization for clinical data, referral lists, FHIR payloads, status history, and private attachment downloads.
- Hardened API authentication with active-account enforcement, login throttling, duplicate-token revocation, limited token abilities, and eight-hour token expiration.
- Removed stale duplicate classes, corrected PSR-4 and Composer classmap warnings, and repaired SQLite-compatible religion seeding so the full PHP test suite can run cleanly.
- Added a referral-network recommendation workflow diagram to the interactive API documentation.
- Added an authenticated referral-network recommendation API that returns the next facility and full RHU-to-district-to-provincial-to-apex escalation path.
- Organized API documentation by workflow, documented actual role/permission behavior, and added usable Sanctum authorization for Try It without changing deployed endpoints.
- Added the resolved user access label to both application header layouts.
- Displayed the signed-in user's resolved region, hospital, provider, general-access, or administrator label in the sidebar profile.
- Hid every sidebar module and administrator submenu item when the signed-in user lacks its corresponding access permission.
- Enhanced user role assignment with server pagination, guard/search/sort filters, safer requests, and reliable post-assignment refresh.
- Added advanced server-side user-table filters for role, status, access type/scope, creation dates, sorting, and page size.
- Enhanced role permission assignment with module, action, guard, search, and page-size filters; corrected assignment endpoints and protected role-access routes.
- Added an administrator-only Audit Trail menu and paginated activity page with event, date, and search filters.
- Improved Incoming Referrals table loading by separating dashboard analytics from the paginated list request.
- Collapsed the Incoming dashboard by default and loaded its analytics only when expanded.
- Avoided repeated filter-option requests and cancelled stale table searches.

## AEI - 2026-09-04
- Added dedicated create, list, edit, and delete permissions across the Facility Hierarchy module, including permission-aware navigation and database rollout compatibility.
- Corrected province, city/municipality, and barangay cascading filters so they apply consistently to every hierarchy level, including Apex Hospital.
- Restored facility-type labels for legacy numeric codes so Apex Hospital selection no longer filters every regional facility out of the table.
- Fixed Regions I-IX showing no facilities by normalizing legacy numeric region codes to the canonical zero-padded region code returned by the selector.
- Fixed the shared dialog overlay/content ref forwarding required by Radix UI, removing the `Primitive.div.SlotClone` React warning when opening the facility picker.
- Fixed facilities intermittently appearing empty in larger regions by replacing the expensive multi-table join with a region-scoped facility query and batched reference-label lookups.
- Added transactional multiple-facility hierarchy assignment with facility-type and selection-status filters in the facility picker.
- Fixed the empty healthcare-facility picker by loading active facilities on demand for the selected region instead of fetching the entire national directory at once.
- Corrected healthcare-facility selection by removing the unrelated mandatory apex step, making geographic filters progressively optional, and limiting parent choices to valid same-area hierarchy nodes.
- Added Facility Hierarchy Management with apex-to-lower-level parent mapping, referral networks, region/province coverage, guarded hierarchy rules, filters, and permission-controlled maintenance.
- Prevented unmapped facilities with a null hierarchy from crashing the hierarchy parent selector.


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
