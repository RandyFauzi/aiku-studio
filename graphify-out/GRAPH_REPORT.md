# Graph Report - AIKU STUDIO  (2026-11-07)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 1381 nodes · 2662 edges · 133 communities (37 shown, 96 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 63 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `30d0debe`
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
- Community 11
- Community 12
- Community 13
- Community 14
- Community 15
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
- Community 60
- Community 61
- Community 62
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
- Community 76
- Community 77
- Community 78
- Community 79
- Community 80
- Community 81
- Community 82
- Community 83
- Community 84
- Community 85
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
- Community 102
- Community 103

## God Nodes (most connected - your core abstractions)
1. `SystemAuditRegressionTest` - 28 edges
2. `DailyWorkReportTest` - 24 edges
3. `PayslipController` - 19 edges
4. `AttendanceQrWifiTest` - 19 edges
5. `RecordStreakTest` - 19 edges
6. `PayrollMigrationTest` - 19 edges
7. `DailyWorkReportController` - 18 edges
8. `Robotics1618Sync` - 17 edges
9. `User` - 17 edges
10. `AttendanceController` - 17 edges

## Surprising Connections (you probably didn't know these)
- `DeviceAccountTest` --references--> `User`  [EXTRACTED]
  tests/Feature/DeviceAccountTest.php → app/Models/User.php
- `Robotics1618SyncTest` --references--> `User`  [EXTRACTED]
  tests/Feature/Robotics1618SyncTest.php → app/Models/User.php
- `{closure#2}()` --references_constant--> `DailyWorkReport`  [EXTRACTED]
  app/Services/RecordingMatching.php → app/Models/DailyWorkReport.php
- `SyncController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/SyncController.php → app/Http/Controllers/Controller.php
- `RecordStreakController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/RecordStreakController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (133 total, 96 thin omitted)

### Community 0 - "Community 0"
Cohesion: 0.06
Nodes (12): AtlasTick, AuditDataIntegrity, CreatePanelAdmin, ImportLegacyPayroll, SyncRobotics1618, VerifyLegacyPayroll, SyncController, AtlasParser (+4 more)

### Community 1 - "Community 1"
Cohesion: 0.07
Nodes (48): aggregate(), clone(), dateRange(), members(), patternForDate(), prepareConfig(), problems(), sessionRows() (+40 more)

### Community 2 - "Community 2"
Cohesion: 0.05
Nodes (61): "attendances", attendances_contributor_id_index, attendances_user_id_index, attendances_user_id_work_date_unique, attendances_work_date_index, "cache", cache_expiration_index, "cache_locks" (+53 more)

### Community 3 - "Community 3"
Cohesion: 0.07
Nodes (9): {closure#3}(), {closure#5}(), RecordingMatchingController, {closure#2}(), RecordingMatching, RecordingOwnerRecommendations, ReportTasks, TaskLanguage (+1 more)

### Community 4 - "Community 4"
Cohesion: 0.06
Nodes (35): assignCandidates(), calcCreateTotal(), calcEditTotal(), closeCreateModal(), closeEditModal(), confirmDeleteReport(), copyPhoneTasks(), deleteReport() (+27 more)

### Community 5 - "Community 5"
Cohesion: 0.05
Nodes (8): SettingController, {closure#2}(), EnsureActiveAccount, EnsureAdministrator, ShiftDay, ShiftConfiguration, {closure#1}(), ShiftPatterns

### Community 6 - "Community 6"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 7 - "Community 7"
Cohesion: 0.08
Nodes (25): checkIn(), checkInWithQr(), closeScanner(), confirmAttendance(), deleteAttendance(), fetchContributors(), handleQrScanned(), init() (+17 more)

### Community 8 - "Community 8"
Cohesion: 0.06
Nodes (26): connectedDays(), init(), openPopup(), refresh(), statusLabel(), weekDays(), credentials, legacyRequire (+18 more)

### Community 9 - "Community 9"
Cohesion: 0.07
Nodes (5): DeviceAccountTest, ExampleTest, PayrollMigrationTest, ShiftRosterTest, TestCase

### Community 11 - "Community 11"
Cohesion: 0.13
Nodes (6): RecordStreakController, {closure#1}(), RecordStreakDay, {closure#1}(), {closure#4}(), RecordStreak

### Community 12 - "Community 12"
Cohesion: 0.13
Nodes (5): Attendance, DeviceAccount, ShiftSetting, StudioAccount, StudioRecording

### Community 14 - "Community 14"
Cohesion: 0.17
Nodes (6): {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), ContributorController, ContributorPerformanceService

### Community 17 - "Community 17"
Cohesion: 0.16
Nodes (5): {closure#1}(), DailyWorkReportController, {closure#2}(), {closure#4}(), DailyWorkReport

### Community 19 - "Community 19"
Cohesion: 0.15
Nodes (3): User, ContributorIdentity, PendingCarryOverTest

### Community 20 - "Community 20"
Cohesion: 0.13
Nodes (18): devDependencies, autoprefixer@^10.6.2, axios@^1.11.0, concurrently@^9.0.1, laravel-vite-plugin@^2.0.0, postcss@^8.5.32, tailwindcss@^3.4.19, vite@^7.0.7 (+10 more)

### Community 21 - "Community 21"
Cohesion: 0.16
Nodes (15): addTask(), canAdd(), choices(), getOptions(), init(), initialLabel(), isTaskSelected(), labelFor() (+7 more)

### Community 24 - "Community 24"
Cohesion: 0.16
Nodes (12): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#1}(), {closure#2}() (+4 more)

### Community 25 - "Community 25"
Cohesion: 0.24
Nodes (4): {closure#2}(), {closure#3}(), {closure#4}(), DeviceAccountController

### Community 26 - "Community 26"
Cohesion: 0.17
Nodes (7): drawQr(), init(), load(), loadConfig(), openModal(), renderQrCode(), saveSettings()

### Community 29 - "Community 29"
Cohesion: 0.23
Nodes (4): PayrollController, PayrollPeriod, {closure#1}(), WorkerSlipArchive

### Community 30 - "Community 30"
Cohesion: 0.22
Nodes (3): fingerprints(), main(), run()

### Community 31 - "Community 31"
Cohesion: 0.18
Nodes (11): dependencies, alpinejs@^3.17.5, html5-qrcode@^2.3.8, qrcode@^1.5.4, sweetalert2@^11.26.25, alpinejs, html5-qrcode, qrcode (+3 more)

### Community 32 - "Community 32"
Cohesion: 0.17
Nodes (4): {closure#2}(), {closure#3}(), PayrollStatus, PayrollPaymentStatus

### Community 33 - "Community 33"
Cohesion: 0.19
Nodes (4): confirmOwner(), json(), load(), openFor()

### Community 34 - "Community 34"
Cohesion: 0.17
Nodes (5): AuthController, Controller, MyRecordingController, RecordingController, ShiftDayController

### Community 40 - "Community 40"
Cohesion: 0.24
Nodes (4): dateKey(), groups(), init(), load()

### Community 45 - "Community 45"
Cohesion: 0.36
Nodes (4): {closure#1}(), SyncContributorLogins, Contributor, ContributorAccess

### Community 49 - "Community 49"
Cohesion: 0.29
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 54 - "Community 54"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 55 - "Community 55"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 56 - "Community 56"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 57 - "Community 57"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 71 - "Community 71"
Cohesion: 0.50
Nodes (3): layouts.workspace-sidebar, portal.icon, portal.navigation

### Community 73 - "Community 73"
Cohesion: 0.50
Nodes (3): portal.streak, portal.icon, portal.layout

### Community 74 - "Community 74"
Cohesion: 0.50
Nodes (3): portal.icon, portal.layout, work-reports.form

### Community 77 - "Community 77"
Cohesion: 0.67
Nodes (3): scripts, build, dev

## Knowledge Gaps
- **44 isolated node(s):** `portal.layout`, `shifts.calendar`, `work-reports.task-picker`, `work-reports.task-picker`, `layouts.workspace-sidebar` (+39 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 468 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **96 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `RecordStreakTest` connect `Community 28` to `Community 19`, `Community 23`?**
  _High betweenness centrality (0.025) - this node is a cross-community bridge._
- **What connects `portal.layout`, `shifts.calendar`, `work-reports.task-picker` to the rest of the system?**
  _44 weakly-connected nodes found - possible missing edges._
- **Should `Community 0` be split into smaller, more focused modules?**
  _Cohesion score 0.059395801331285206 - nodes in this community are weakly interconnected._
- **Why does `DailyWorkReportTest` connect `Community 18` to `Community 12`, `Community 23`?**
  _High betweenness centrality (0.019) - this node is a cross-community bridge._
- **Should `Community 1` be split into smaller, more focused modules?**
  _Cohesion score 0.07373271889400922 - nodes in this community are weakly interconnected._
- **Why does `AttendanceQrWifiTest` connect `Community 27` to `Community 23`?**
  _High betweenness centrality (0.017) - this node is a cross-community bridge._
- **Should `Community 2` be split into smaller, more focused modules?**
  _Cohesion score 0.0523532522474881 - nodes in this community are weakly interconnected._