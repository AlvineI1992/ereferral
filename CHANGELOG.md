# Changelog

## AEI - 2026-09-15
- Corrected API referral journey forwarding availability to require the token's write ability as well as endpoint permission and receiver ownership; added API retry, permission, EMR ownership, and immutable snapshot regression coverage.
- Added linked referral forwarding and a chronological referral journey for web/API, with separate transaction IDs, immutable prior-leg snapshots, current-receiver ownership, endpoint permissions, and duplicate-forward protection.
- Added web Incoming cancellation with a required reason, independent incoming cancel permission, source-ownership visibility, and refresh after cancellation; reuses the API cancellation service and preserves history.
- Added POST /api/cancel-referral with independent API permission, source-facility ownership, required reason, idempotent cancellation history, pending-list exclusion, and receive/admit protection. Received referrals cannot be cancelled.

## AEI - 2026-09-14
- Added administrator Database Maintenance with pending-migration preview, approved permission seeders, confirmation, exclusive execution locking, and recent execution results. No reset, rollback, or arbitrary command execution is exposed.
- Limited classification cells to five badges followed by an ellipsis; remaining classifications are available in the hover text to keep the table compact.
- Replaced broad API grants with individual endpoint permissions, displayed method/path in permission assignment, and migrated existing API grants to equivalent endpoint grants. Login stays public; individual EMR operations can now be granted or revoked independently.
- Audited documented API permission coverage and updated live OpenAPI access descriptions to show required API permissions and token abilities. Login remains public; documentation generation now rejects protected operations missing a permission guard.
- Added API-guard permission checks to protected API endpoints, seeded reference/referral/bed API permissions, and enabled API role assignment alongside existing web roles. Preserves Sanctum token abilities and the exact administrator exception; ordinary accounts require assigned API permissions.
- Enhanced Roles and Permissions with a shared compact directory, module/action classification, server-side search, guard and assignment filters, sorting, pagination, and reusable forms; fixed guard saving and permission update responses.
- Added EMR token generation as the fourth provider setup step, with copy/reveal controls, saved credential confirmation, and the existing user-edit permission requirement.
- Added a permission-protected provider setup wizard: add provider, create a linked user, and assign roles using existing validated endpoints and reusable forms. Saves each step independently and displays completion after role assignment.

## AEI - 2026-09-13
- Resolved stalled email encryption caused by an absent queue worker: verified the encrypted backup, processed the pending conversion, and confirmed encrypted storage, blind-index lookup, and administrator access.
- Fixed the provider profile first-load facilities error by restoring missing facility, region, and facility-type reference tables from the verified backup; consolidated the list into one cancellable request through a reusable facility API service handled nullable facility names, and used initials when no provider avatar exists.
- Granted the active `admin@referral.doh.gov.ph` account all application permissions and administrator pages, with unrestricted referral, facility, patient, bed, dashboard, and report access regardless of assigned roles or scope. Other accounts retain existing restrictions.
- Reset operational and account data in `referral_2022` after a verified backup, reseeded `admin@referral.doh.gov.ph`, and verified demographics, facilities, roles, permissions, and role-permission assignments remained unchanged.
- Made sidebar permission seeding explicitly use the web guard and clear cached permissions before and after seeding; preserves existing permissions and role assignments.
- Upgraded Laravel to 13.31.0 and raised the PHP requirement to 8.3; updated the Composer lockfile and compatible Scramble, TCPDF Laravel, Tinker, and Pest 4 dependencies.

## AEI - 2026-09-11
- Added independent `referral report list` and `diagnosis heatmap list` permissions to report pages, data/drill-down/export routes, validation requests, and sidebar links; migration preserves existing Incoming-based role and direct-user report grants while enabling separate assignment/revocation.
- Restricted regional (CHD) accounts to facilities inside their assigned region across directory pagination/search, facility options, direct details, and create/update/delete; accepts legacy padded/unpadded region codes and denies access when regional assignment is missing.
- Added Reports > Diagnosis Heat Map: an interactive Philippines map using final discharge diagnoses, date and exact-diagnosis filters, scoped receiving-facility counts, map intensity legend, and explicit unmapped-coordinate totals; uses OpenStreetMap without a Google API key.
- Added Google Maps location selection to facility registration/editing, optional saved latitude/longitude with paired range validation, current-location capture, and Google Maps search links; embedded map picking is enabled by GOOGLE_MAPS_API_KEY and GOOGLE_MAPS_MAP_ID.
- Fixed intermittent facility city/barangay selection by applying edit locations atomically, cancelling stale facility requests, and reading controlled demographic values directly instead of delayed state synchronization; blank codes remain blank.
- Standardized API exception responses as clean JSON even in debug mode: removed stack traces and internal paths, preserved HTTP status/headers and validation errors, and hid internal server-error details.
- Added saved EMR credential status on Users and an explicit `emr_id_token` generation response; enforced saved-provider facility ownership regardless of administrator roles and accepted surrounding copy/paste whitespace.
- Replaced EMR credential exception traces with clean 403 JSON responses, distinguishing missing headers and malformed tokens while retaining account, provider, and hash validation.
- Fixed the referral-list API documentation response from a placeholder string to the actual JSON array contract, including all returned fields, string referral IDs, nullable values, and credential/access and empty-list error examples.
- Made EMR token management discoverable in a dedicated Users table column, with explicit permission and provider-access requirements and generation guidance for inactive or unassigned accounts.
- Added one-time EMR credential generation, rotation, and revocation on Users for EMR-provider accounts; hashes are stored separately and validated against the authenticated account and current provider assignment.
- Changed the referral-list API to `/api/get-referral-list/{fhudcode}` with required `X-EMR-Token` and existing bearer authorization; removed numeric EMR IDs from the URL and documented the new contract.
- Completed compact dashboard KPI spacing by overriding shared card padding and gaps, stacking cards on narrow screens, and keeping metric descriptions readable without hover.
- Compacted the `/dashboard` header, operational focus, KPI cards, spacing, typography, and responsive layout while retaining the full scoped referral, capacity, activity, network, and action detail set.
- Limited the OpenAPI Schemas section to request models; response examples remain attached directly to their endpoint status responses.
- Published structured API request bodies as reusable named OpenAPI component schemas so applicable input contracts appear in the documentation's Schemas section.
- Registered Sanctum's `abilities` and `ability` middleware aliases and added coverage ensuring every API route middleware reference resolves correctly.
- Added schema-derived JSON response examples to every documented API endpoint and response status while preserving endpoint-specific referral examples.
- Added successful referral and facility-error response examples to the generated `/api/refer_patient` documentation.
- Added centralized API access auditing with user, endpoint, method, response status, duration, IP address, and user agent metadata while excluding request bodies, credentials, and bearer tokens.
- Made the `/api/refer_patient` ICD array optional while retaining format validation for ICD codes when supplied.
- Added complete and minimum operational JSON samples for `/api/refer_patient`, aligned its validation with fields required by the legacy service, and restored JSON request documentation.
- Restored the legacy `/api/refer_patient` request and response workflow while retaining Sanctum authentication and the existing facility EMR registration checks.
- Unlocked administrator-controlled CipherSweet activation with a verified encrypted backup, queued resumable user-email conversion, progress and failure reporting, and guarded deactivation.
- Added exact blind-index email lookup across login, user validation, user management, password recovery, and audit-trail display while preserving mixed plaintext/encrypted access during conversion.
- Normalized CipherSweet key/provider configuration and expanded the user email column for encrypted payload storage.

## AEI - 2026-09-10
- Fixed case-sensitive Inertia page resolution for user and role administration pages in production builds.
- Hid appointment navigation when the application has no registered appointment page, preventing Inertia prefetch 404 responses.
- Corrected role create and update redirects to use the registered `roles.index` route name.

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
