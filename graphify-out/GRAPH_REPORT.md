# Graph Report - AIKU STUDIO  (2026-11-20)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 1705 nodes · 3433 edges · 171 communities (59 shown, 112 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 81 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `3a4b6405`
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
- Community 63
- Community 64
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
- Community 79
- Community 80
- Community 81
- Community 82
- Community 83
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
- Community 97
- Community 98
- Community 99
- Community 100
- Community 101
- Community 103
- Community 104
- Community 105
- Community 106
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
- Community 135
- Community 136
- Community 137
- Community 138
- Community 139
- Community 140
- Community 141

## God Nodes (most connected - your core abstractions)
1. `SystemAuditRegressionTest` - 28 edges
2. `PayrollCalculator` - 28 edges
3. `AttendanceQrWifiTest` - 25 edges
4. `DailyWorkReportTest` - 25 edges
5. `PayslipIntegrityTest` - 25 edges
6. `AttendanceController` - 21 edges
7. `DailyWorkReport` - 20 edges
8. `PayslipController` - 20 edges
9. `PayrollMigrationTest` - 19 edges
10. `RecordStreakTest` - 19 edges

## Surprising Connections (you probably didn't know these)
- `DeviceAccountController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/DeviceAccountController.php → app/Http/Controllers/Controller.php
- `RecordStreakController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/RecordStreakController.php → app/Http/Controllers/Controller.php
- `AttendanceController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/AttendanceController.php → app/Http/Controllers/Controller.php
- `{closure#7}()` --references_constant--> `PayslipWriter`  [EXTRACTED]
  app/Http/Controllers/PayslipController.php → app/Services/PayslipWriter.php
- `WorkerSlipArchive` --references--> `ContributorIdentity`  [EXTRACTED]
  app/Services/WorkerSlipArchive.php → app/Services/ContributorIdentity.php

## Import Cycles
- None detected.

## Communities (171 total, 112 thin omitted)

### Community 0 - "Community 0"
Cohesion: 0.06
Nodes (63): aggregate(), clone(), dateRange(), members(), patternForDate(), prepareConfig(), problems(), sessionRows() (+55 more)

### Community 1 - "Community 1"
Cohesion: 0.07
Nodes (5): Attendance, Contributor, PayrollStatus, ShiftSetting, StudioDevice

### Community 2 - "Community 2"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 3 - "Community 3"
Cohesion: 0.08
Nodes (5): {closure#1}(), DailyWorkReportController, DailyWorkReport, DeviceAccountTest, RecordingMatchingTest

### Community 4 - "Community 4"
Cohesion: 0.08
Nodes (26): checkIn(), checkInWithQr(), closeScanner(), confirmAttendance(), deleteAttendance(), fetchContributors(), handleQrScanned(), init() (+18 more)

### Community 5 - "Community 5"
Cohesion: 0.08
Nodes (10): {closure#3}(), {closure#5}(), {closure#2}(), {closure#4}(), RecordingMatchingController, {closure#2}(), RecordingMatching, RecordingOwnerRecommendations (+2 more)

### Community 6 - "Community 6"
Cohesion: 0.06
Nodes (26): connectedDays(), init(), openPopup(), refresh(), statusLabel(), weekDays(), credentials, legacyRequire (+18 more)

### Community 7 - "Community 7"
Cohesion: 0.08
Nodes (3): SyncController, StudioAccount, StudioRecording

### Community 8 - "Community 8"
Cohesion: 0.08
Nodes (6): AdminNoteTest, ContributorPayslipTest, RecordingQcTrackingTest, RecordingSourcesTest, Robotics1618SyncTest, ShiftRosterTest

### Community 9 - "Community 9"
Cohesion: 0.08
Nodes (10): availablePhones(), availableTasks(), filtered(), formatPhoneName(), getAssistForReport(), getAssistSummary(), getReportTasks(), labels (+2 more)

### Community 10 - "Community 10"
Cohesion: 0.23
Nodes (5): {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), DeviceAccountController

### Community 11 - "Community 11"
Cohesion: 0.13
Nodes (5): RecordStreakController, RecordStreakDay, {closure#1}(), {closure#4}(), RecordStreak

### Community 12 - "Community 12"
Cohesion: 0.11
Nodes (26): "cache", cache_expiration_index, "cache_locks", cache_locks_expiration_index, "job_batches", "jobs", jobs_queue_index, "migrations" (+18 more)

### Community 14 - "Community 14"
Cohesion: 0.14
Nodes (3): AttendanceController, {closure#6}(), ShiftSchedule

### Community 15 - "Community 15"
Cohesion: 0.16
Nodes (9): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#1}(), {closure#2}() (+1 more)

### Community 16 - "Community 16"
Cohesion: 0.13
Nodes (3): Payslip, ContributorIdentity, PendingCarryOverTest

### Community 17 - "Community 17"
Cohesion: 0.11
Nodes (21): devDependencies, autoprefixer@^10.6.2, axios@^1.11.0, concurrently@^9.0.1, laravel-vite-plugin@^2.0.0, postcss@^8.5.32, tailwindcss@^3.4.19, vite@^7.0.7 (+13 more)

### Community 18 - "Community 18"
Cohesion: 0.17
Nodes (6): AuthController, {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), ContributorController

### Community 24 - "Community 24"
Cohesion: 0.14
Nodes (13): dependencies, alpinejs@^3.17.5, html5-qrcode@^2.3.8, qrcode@^1.5.4, sweetalert2@^11.26.25, alpinejs, html5-qrcode, qrcode (+5 more)

### Community 25 - "Community 25"
Cohesion: 0.13
Nodes (4): {closure#2}(), ShiftDayController, ShiftDay, ShiftConfiguration

### Community 26 - "Community 26"
Cohesion: 0.16
Nodes (12): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#1}(), {closure#2}() (+4 more)

### Community 27 - "Community 27"
Cohesion: 0.12
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), KameraKitaPartnerIntegrationTest

### Community 28 - "Community 28"
Cohesion: 0.15
Nodes (7): drawQr(), init(), load(), loadConfig(), openModal(), renderQrCode(), saveSettings()

### Community 29 - "Community 29"
Cohesion: 0.14
Nodes (7): AtlasTick, CreatePanelAdmin, ImportLegacyPayroll, SyncDraftPayslips, SyncRobotics1618, VerifyLegacyPayroll, PayslipDraftSync

### Community 30 - "Community 30"
Cohesion: 0.25
Nodes (3): {closure#3}(), {closure#4}(), RecordingSync

### Community 32 - "Community 32"
Cohesion: 0.16
Nodes (3): SettingController, {closure#1}(), ShiftPatterns

### Community 33 - "Community 33"
Cohesion: 0.17
Nodes (12): addTask(), canAdd(), choices(), getOptions(), init(), isTaskSelected(), labels, lookup() (+4 more)

### Community 35 - "Community 35"
Cohesion: 0.21
Nodes (4): {closure#1}(), AtlasSync, {closure#1}(), {closure#2}()

### Community 37 - "Community 37"
Cohesion: 0.22
Nodes (3): fingerprints(), main(), run()

### Community 38 - "Community 38"
Cohesion: 0.17
Nodes (15): calendarNextMonth(), calendarPrevMonth(), changeDateOffset(), deleteReport(), fetchContributors(), init(), initCalendar(), isTodaySelected() (+7 more)

### Community 39 - "Community 39"
Cohesion: 0.14
Nodes (3): Controller, MyRecordingController, RecordingController

### Community 43 - "Community 43"
Cohesion: 0.31
Nodes (3): PayrollPeriod, {closure#1}(), WorkerSlipArchive

### Community 45 - "Community 45"
Cohesion: 0.15
Nodes (3): {closure#1}(), {closure#1}(), {closure#1}()

### Community 46 - "Community 46"
Cohesion: 0.19
Nodes (4): confirmOwner(), json(), load(), openFor()

### Community 49 - "Community 49"
Cohesion: 0.17
Nodes (3): {closure#1}(), up(), {closure#1}()

### Community 50 - "Community 50"
Cohesion: 0.24
Nodes (3): AtlasSessionTest, {closure#1}(), {closure#3}()

### Community 54 - "Community 54"
Cohesion: 0.22
Nodes (4): dateKey(), groups(), init(), load()

### Community 58 - "Community 58"
Cohesion: 0.22
Nodes (9): "admin_notes", admin_notes_is_completed_index, admin_notes_is_pinned_created_at_index, admin_notes_is_pinned_index, admin_notes_note_date_index, admin_notes_note_date_is_completed_index, "users", users_contributor_id_unique (+1 more)

### Community 59 - "Community 59"
Cohesion: 0.22
Nodes (9): copyPhoneTasks(), editReport(), openEditModal(), openNewReport(), playReportCoin(), reload(), submit(), suggestShiftLabel() (+1 more)

### Community 66 - "Community 66"
Cohesion: 0.29
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 67 - "Community 67"
Cohesion: 0.29
Nodes (7): "studio_recordings", studio_recordings_assigned_contributor_id_index, studio_recordings_assigned_worker_name_index, studio_recordings_manual_payment_status_index, studio_recordings_participant_email_index, studio_recordings_qc_changed_at_index, studio_recordings_recorded_at_index

### Community 68 - "Community 68"
Cohesion: 0.29
Nodes (7): assignCandidates(), calcEditTotal(), closeEditModal(), confirmDeleteReport(), reloadAssistOnly(), saveEditReport(), unassignRecords()

### Community 73 - "Community 73"
Cohesion: 0.47
Nodes (3): {closure#1}(), SyncContributorLogins, ContributorAccess

### Community 76 - "Community 76"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 77 - "Community 77"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 78 - "Community 78"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 79 - "Community 79"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 80 - "Community 80"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 81 - "Community 81"
Cohesion: 0.33
Nodes (6): "device_accounts", device_accounts_email_index, device_accounts_is_active_index, device_accounts_kamerakita_partner_id_index, device_accounts_phone_key_email_unique, device_accounts_phone_key_index

### Community 94 - "Community 94"
Cohesion: 0.40
Nodes (5): "attendances", attendances_contributor_id_index, attendances_user_id_index, attendances_user_id_work_date_unique, attendances_work_date_index

### Community 95 - "Community 95"
Cohesion: 0.40
Nodes (5): "daily_work_reports", daily_work_reports_contributor_id_index, daily_work_reports_user_id_index, daily_work_reports_user_id_work_date_index, daily_work_reports_work_date_index

### Community 97 - "Community 97"
Cohesion: 0.40
Nodes (5): getPhoneInfo(), getReportPhoneAccount(), getReportPhoneEmail(), toggleCreatePhone(), toggleEditPhone()

### Community 104 - "Community 104"
Cohesion: 0.50
Nodes (3): portal.navigation, layouts.workspace-sidebar, portal.icon

### Community 105 - "Community 105"
Cohesion: 0.50
Nodes (3): portal.streak, portal.icon, portal.layout

### Community 106 - "Community 106"
Cohesion: 0.50
Nodes (3): portal.icon, portal.layout, work-reports.form

### Community 109 - "Community 109"
Cohesion: 0.67
Nodes (3): "contributors", contributors_email_index, contributors_nama_index

### Community 110 - "Community 110"
Cohesion: 0.67
Nodes (3): "payslips", payslips_active_key_unique, payslips_employee_name_period_index

### Community 111 - "Community 111"
Cohesion: 0.67
Nodes (3): "sessions", sessions_last_activity_index, sessions_user_id_index

### Community 113 - "Community 113"
Cohesion: 0.67
Nodes (3): calcCreateTotal(), closeCreateModal(), saveCreateReport()

## Knowledge Gaps
- **45 isolated node(s):** `portal.navigation`, `layouts.workspace-sidebar`, `portal.icon`, `portal.streak`, `portal.icon` (+40 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 566 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **112 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `RecordingMatchingTest` connect `Community 3` to `Community 8`, `Community 7`?**
  _High betweenness centrality (0.014) - this node is a cross-community bridge._
- **What connects `portal.navigation`, `layouts.workspace-sidebar`, `portal.icon` to the rest of the system?**
  _45 weakly-connected nodes found - possible missing edges._
- **Should `Community 0` be split into smaller, more focused modules?**
  _Cohesion score 0.05524537173082574 - nodes in this community are weakly interconnected._
- **Why does `User` connect `Community 56` to `Community 1`, `Community 73`, `Community 16`, `Community 48`, `Community 29`, `Community 31`?**
  _High betweenness centrality (0.013) - this node is a cross-community bridge._
- **Should `Community 1` be split into smaller, more focused modules?**
  _Cohesion score 0.07397959183673469 - nodes in this community are weakly interconnected._
- **Why does `SystemAuditRegressionTest` connect `Community 13` to `Community 8`, `Community 23`?**
  _High betweenness centrality (0.011) - this node is a cross-community bridge._
- **Should `Community 2` be split into smaller, more focused modules?**
  _Cohesion score 0.041666666666666664 - nodes in this community are weakly interconnected._