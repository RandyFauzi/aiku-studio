# Graph Report - AIKU STUDIO  (2026-11-15)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 1627 nodes · 3237 edges · 164 communities (53 shown, 111 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 77 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `fabedaea`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Community 0
- Community 1
- Community 2
- Community 3
- Community 4
- Community 5
- Community 6
- Community 7
- Community 8
- Community 9
- Community 10
- Community 11
- Community 12
- Community 13
- Community 14
- Community 15
- Community 16
- Community 17
- Community 18
- Community 19
- Community 20
- Community 21
- Community 22
- Community 23
- Community 24
- Community 25
- Community 26
- Community 27
- Community 28
- Community 29
- Community 30
- Community 31
- Community 32
- Community 33
- Community 34
- Community 35
- Community 36
- Community 37
- Community 38
- Community 39
- Community 40
- Community 41
- Community 42
- Community 43
- Community 44
- Community 45
- Community 46
- Community 47
- Community 48
- Community 49
- Community 50
- Community 51
- Community 52
- Community 53
- Community 54
- Community 55
- Community 56
- Community 57
- Community 58
- Community 59
- Community 60
- Community 61
- Community 62
- Community 63
- Community 65
- Community 66
- Community 67
- Community 68
- Community 69
- Community 70
- Community 71
- Community 72
- Community 73
- Community 74
- Community 75
- Community 76
- Community 77
- Community 78
- Community 80
- Community 81
- Community 82
- Community 83
- Community 84
- Community 85
- Community 86
- Community 87
- Community 88
- Community 89
- Community 90
- Community 91
- Community 92
- Community 93
- Community 94
- Community 95
- Community 96
- Community 98
- Community 99
- Community 100
- Community 101
- Community 103
- Community 104
- Community 105
- Community 106
- Community 107
- Community 108
- Community 109
- Community 110
- Community 111
- Community 112
- Community 113
- Community 114
- Community 115
- Community 116
- Community 117
- Community 118
- Community 119
- Community 120
- Community 121
- Community 122
- Community 123
- Community 124
- Community 125
- Community 126
- Community 127
- Community 128
- Community 129
- Community 130
- Community 131
- Community 132
- Community 133
- Community 134

## God Nodes (most connected - your core abstractions)
1. `SystemAuditRegressionTest` - 28 edges
2. `AttendanceQrWifiTest` - 25 edges
3. `DailyWorkReportTest` - 24 edges
4. `AttendanceController` - 22 edges
5. `PayslipIntegrityTest` - 21 edges
6. `PayslipController` - 21 edges
7. `PayrollMigrationTest` - 19 edges
8. `RecordStreakTest` - 19 edges
9. `DailyWorkReportController` - 17 edges
10. `RecordingSync` - 17 edges

## Surprising Connections (you probably didn't know these)
- `{closure#2}()` --references_constant--> `DailyWorkReport`  [EXTRACTED]
  app/Services/RecordingMatching.php → app/Models/DailyWorkReport.php
- `DeviceAccountTest` --references--> `User`  [EXTRACTED]
  tests/Feature/DeviceAccountTest.php → app/Models/User.php
- `{closure#7}()` --references_constant--> `PayslipWriter`  [EXTRACTED]
  app/Http/Controllers/PayslipController.php → app/Services/PayslipWriter.php
- `AuthController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/AuthController.php → app/Http/Controllers/Controller.php
- `ContributorController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/ContributorController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (164 total, 111 thin omitted)

### Community 0 - "Community 0"
Cohesion: 0.06
Nodes (63): aggregate(), clone(), dateRange(), members(), patternForDate(), prepareConfig(), problems(), sessionRows() (+55 more)

### Community 1 - "Community 1"
Cohesion: 0.05
Nodes (44): assignCandidates(), calcCreateTotal(), calcEditTotal(), calendarNextMonth(), calendarPrevMonth(), changeDateOffset(), closeCreateModal(), closeEditModal() (+36 more)

### Community 2 - "Community 2"
Cohesion: 0.07
Nodes (10): {closure#3}(), {closure#5}(), MyRecordingController, {closure#2}(), {closure#4}(), RecordingMatching, RecordingOwnerRecommendations, ReportTasks (+2 more)

### Community 3 - "Community 3"
Cohesion: 0.05
Nodes (8): DeviceAccount, User, UserFactory, DatabaseSeeder, DeviceAccountTest, ExampleTest, ShiftRosterTest, TestCase

### Community 4 - "Community 4"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 5 - "Community 5"
Cohesion: 0.08
Nodes (26): checkIn(), checkInWithQr(), closeScanner(), confirmAttendance(), deleteAttendance(), fetchContributors(), handleQrScanned(), init() (+18 more)

### Community 6 - "Community 6"
Cohesion: 0.10
Nodes (3): PendingCarryOverTest, RecordingQcTrackingTest, Robotics1618SyncTest

### Community 7 - "Community 7"
Cohesion: 0.06
Nodes (26): connectedDays(), init(), openPopup(), refresh(), statusLabel(), weekDays(), credentials, legacyRequire (+18 more)

### Community 9 - "Community 9"
Cohesion: 0.11
Nodes (26): "cache", cache_expiration_index, "cache_locks", cache_locks_expiration_index, "daily_work_reports", daily_work_reports_contributor_id_index, daily_work_reports_user_id_index, daily_work_reports_user_id_work_date_index (+18 more)

### Community 10 - "Community 10"
Cohesion: 0.12
Nodes (4): PayrollStatus, Payslip, PayrollPaymentStatus, PayslipPdf

### Community 12 - "Community 12"
Cohesion: 0.17
Nodes (8): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#2}(), PayslipWriter

### Community 13 - "Community 13"
Cohesion: 0.11
Nodes (21): devDependencies, autoprefixer@^10.6.2, axios@^1.11.0, concurrently@^9.0.1, laravel-vite-plugin@^2.0.0, postcss@^8.5.32, tailwindcss@^3.4.19, vite@^7.0.7 (+13 more)

### Community 14 - "Community 14"
Cohesion: 0.17
Nodes (6): AuthController, {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), ContributorController

### Community 19 - "Community 19"
Cohesion: 0.14
Nodes (13): dependencies, alpinejs@^3.17.5, html5-qrcode@^2.3.8, qrcode@^1.5.4, sweetalert2@^11.26.25, alpinejs, html5-qrcode, qrcode (+5 more)

### Community 21 - "Community 21"
Cohesion: 0.12
Nodes (4): Controller, RecordingController, RecordingMatchingController, {closure#2}()

### Community 22 - "Community 22"
Cohesion: 0.20
Nodes (3): {closure#1}(), DailyWorkReportController, DailyWorkReport

### Community 23 - "Community 23"
Cohesion: 0.13
Nodes (4): {closure#2}(), ShiftDayController, ShiftDay, ShiftConfiguration

### Community 25 - "Community 25"
Cohesion: 0.16
Nodes (12): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#1}(), {closure#2}() (+4 more)

### Community 26 - "Community 26"
Cohesion: 0.15
Nodes (7): drawQr(), init(), load(), loadConfig(), openModal(), renderQrCode(), saveSettings()

### Community 29 - "Community 29"
Cohesion: 0.16
Nodes (3): SettingController, {closure#1}(), ShiftPatterns

### Community 30 - "Community 30"
Cohesion: 0.17
Nodes (12): addTask(), canAdd(), choices(), getOptions(), init(), isTaskSelected(), labels, lookup() (+4 more)

### Community 32 - "Community 32"
Cohesion: 0.12
Nodes (5): {closure#1}(), {closure#2}(), up(), {closure#1}(), {closure#2}()

### Community 34 - "Community 34"
Cohesion: 0.28
Nodes (3): {closure#3}(), {closure#4}(), RecordingSync

### Community 36 - "Community 36"
Cohesion: 0.18
Nodes (4): RecordStreakDay, {closure#1}(), {closure#4}(), RecordStreak

### Community 37 - "Community 37"
Cohesion: 0.21
Nodes (4): {closure#1}(), AtlasSync, {closure#1}(), {closure#2}()

### Community 39 - "Community 39"
Cohesion: 0.22
Nodes (3): fingerprints(), main(), run()

### Community 40 - "Community 40"
Cohesion: 0.24
Nodes (4): SyncDraftPayslips, {closure#2}(), PayrollCalculator, PayslipDraftSync

### Community 41 - "Community 41"
Cohesion: 0.27
Nodes (4): {closure#2}(), {closure#3}(), {closure#4}(), DeviceAccountController

### Community 42 - "Community 42"
Cohesion: 0.21
Nodes (5): AtlasTick, CreatePanelAdmin, ImportLegacyPayroll, SyncRobotics1618, VerifyLegacyPayroll

### Community 45 - "Community 45"
Cohesion: 0.19
Nodes (4): confirmOwner(), json(), load(), openFor()

### Community 46 - "Community 46"
Cohesion: 0.17
Nodes (3): {closure#1}(), {closure#2}(), {closure#1}()

### Community 47 - "Community 47"
Cohesion: 0.24
Nodes (3): AtlasSessionTest, {closure#1}(), {closure#3}()

### Community 50 - "Community 50"
Cohesion: 0.31
Nodes (3): ContributorIdentity, {closure#1}(), WorkerSlipArchive

### Community 51 - "Community 51"
Cohesion: 0.22
Nodes (4): dateKey(), groups(), init(), load()

### Community 54 - "Community 54"
Cohesion: 0.22
Nodes (9): "admin_notes", admin_notes_is_completed_index, admin_notes_is_pinned_created_at_index, admin_notes_is_pinned_index, admin_notes_note_date_index, admin_notes_note_date_is_completed_index, "users", users_contributor_id_unique (+1 more)

### Community 62 - "Community 62"
Cohesion: 0.29
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 63 - "Community 63"
Cohesion: 0.29
Nodes (7): "studio_recordings", studio_recordings_assigned_contributor_id_index, studio_recordings_assigned_worker_name_index, studio_recordings_manual_payment_status_index, studio_recordings_participant_email_index, studio_recordings_qc_changed_at_index, studio_recordings_recorded_at_index

### Community 69 - "Community 69"
Cohesion: 0.47
Nodes (3): {closure#1}(), SyncContributorLogins, ContributorAccess

### Community 72 - "Community 72"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 73 - "Community 73"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 74 - "Community 74"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 75 - "Community 75"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 76 - "Community 76"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 84 - "Community 84"
Cohesion: 0.40
Nodes (5): "attendances", attendances_contributor_id_index, attendances_user_id_index, attendances_user_id_work_date_unique, attendances_work_date_index

### Community 85 - "Community 85"
Cohesion: 0.40
Nodes (5): "device_accounts", device_accounts_email_index, device_accounts_is_active_index, device_accounts_phone_key_email_unique, device_accounts_phone_key_index

### Community 96 - "Community 96"
Cohesion: 0.50
Nodes (4): "recording_owner_confirmations", recording_owner_confirmations_contributor_id_index, recording_owner_confirmations_record_id_index, recording_owner_confirmations_report_id_index

### Community 99 - "Community 99"
Cohesion: 0.50
Nodes (3): portal.navigation, layouts.workspace-sidebar, portal.icon

### Community 100 - "Community 100"
Cohesion: 0.50
Nodes (3): portal.streak, portal.icon, portal.layout

### Community 101 - "Community 101"
Cohesion: 0.50
Nodes (3): portal.icon, portal.layout, work-reports.form

### Community 104 - "Community 104"
Cohesion: 0.67
Nodes (3): "contributors", contributors_email_index, contributors_nama_index

### Community 105 - "Community 105"
Cohesion: 0.67
Nodes (3): "payslips", payslips_active_key_unique, payslips_employee_name_period_index

### Community 106 - "Community 106"
Cohesion: 0.67
Nodes (3): "record_streak_days", record_streak_days_contributor_id_index, record_streak_days_contributor_id_work_date_unique

### Community 107 - "Community 107"
Cohesion: 0.67
Nodes (3): "sessions", sessions_last_activity_index, sessions_user_id_index

## Knowledge Gaps
- **45 isolated node(s):** `portal.streak`, `portal.icon`, `portal.layout`, `portal.icon`, `portal.layout` (+40 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 542 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **111 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `SystemAuditRegressionTest` connect `Community 11` to `Community 24`, `Community 6`?**
  _High betweenness centrality (0.013) - this node is a cross-community bridge._
- **What connects `portal.streak`, `portal.icon`, `portal.layout` to the rest of the system?**
  _45 weakly-connected nodes found - possible missing edges._
- **Should `Community 0` be split into smaller, more focused modules?**
  _Cohesion score 0.05524537173082574 - nodes in this community are weakly interconnected._
- **Why does `AttendanceQrWifiTest` connect `Community 16` to `Community 6`?**
  _High betweenness centrality (0.012) - this node is a cross-community bridge._
- **Should `Community 1` be split into smaller, more focused modules?**
  _Cohesion score 0.05478750640040963 - nodes in this community are weakly interconnected._
- **Why does `AtlasSync` connect `Community 37` to `Community 34`, `Community 6`, `Community 17`, `Community 24`, `Community 60`?**
  _High betweenness centrality (0.010) - this node is a cross-community bridge._
- **Should `Community 2` be split into smaller, more focused modules?**
  _Cohesion score 0.07256894049346879 - nodes in this community are weakly interconnected._