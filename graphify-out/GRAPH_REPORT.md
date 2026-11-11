# Graph Report - AIKU STUDIO  (2026-11-11)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 1491 nodes · 2887 edges · 136 communities (38 shown, 98 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 62 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `d10c4728`
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
- Community 97
- Community 98
- Community 99
- Community 100
- Community 101
- Community 102
- Community 103
- Community 104
- Community 105

## God Nodes (most connected - your core abstractions)
1. `SystemAuditRegressionTest` - 28 edges
2. `DailyWorkReportTest` - 24 edges
3. `AtlasSync` - 22 edges
4. `Robotics1618Sync` - 20 edges
5. `PayslipController` - 19 edges
6. `PayrollMigrationTest` - 19 edges
7. `AttendanceQrWifiTest` - 19 edges
8. `RecordStreakTest` - 19 edges
9. `DailyWorkReportController` - 18 edges
10. `AttendanceController` - 17 edges

## Surprising Connections (you probably didn't know these)
- `DeviceAccountTest` --references--> `User`  [EXTRACTED]
  tests/Feature/DeviceAccountTest.php → app/Models/User.php
- `{closure#2}()` --references_constant--> `DailyWorkReport`  [EXTRACTED]
  app/Services/RecordingMatching.php → app/Models/DailyWorkReport.php
- `{closure#2}()` --calls--> `ShiftDay`  [INFERRED]
  app/Http/Controllers/ShiftDayController.php → app/Models/ShiftDay.php
- `PayrollController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/PayrollController.php → app/Http/Controllers/Controller.php
- `PayslipController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/PayslipController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (136 total, 98 thin omitted)

### Community 0 - "Community 0"
Cohesion: 0.05
Nodes (13): PayrollController, {closure#2}(), {closure#3}(), PayslipController, ContributorIdentity, {closure#2}(), PayrollCalculator, PayrollFiles (+5 more)

### Community 1 - "Community 1"
Cohesion: 0.06
Nodes (63): aggregate(), clone(), dateRange(), members(), patternForDate(), prepareConfig(), problems(), sessionRows() (+55 more)

### Community 2 - "Community 2"
Cohesion: 0.06
Nodes (11): {closure#2}(), {closure#3}(), {closure#4}(), DeviceAccountController, Attendance, PayrollStatus, RecordStreakDay, ShiftDay (+3 more)

### Community 3 - "Community 3"
Cohesion: 0.05
Nodes (62): "attendances", attendances_contributor_id_index, attendances_user_id_index, attendances_user_id_work_date_unique, attendances_work_date_index, "cache", cache_expiration_index, "cache_locks" (+54 more)

### Community 4 - "Community 4"
Cohesion: 0.06
Nodes (11): {closure#3}(), RecordingController, {closure#2}(), {closure#4}(), RecordingMatchingController, {closure#2}(), RecordingMatching, RecordingOwnerRecommendations (+3 more)

### Community 5 - "Community 5"
Cohesion: 0.08
Nodes (10): AtlasTick, SyncController, {closure#1}(), AtlasSync, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}() (+2 more)

### Community 6 - "Community 6"
Cohesion: 0.06
Nodes (35): assignCandidates(), calcCreateTotal(), calcEditTotal(), closeCreateModal(), closeEditModal(), confirmDeleteReport(), copyPhoneTasks(), deleteReport() (+27 more)

### Community 7 - "Community 7"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 8 - "Community 8"
Cohesion: 0.08
Nodes (25): checkIn(), checkInWithQr(), closeScanner(), confirmAttendance(), deleteAttendance(), fetchContributors(), handleQrScanned(), init() (+17 more)

### Community 9 - "Community 9"
Cohesion: 0.06
Nodes (26): connectedDays(), init(), openPopup(), refresh(), statusLabel(), weekDays(), credentials, legacyRequire (+18 more)

### Community 10 - "Community 10"
Cohesion: 0.08
Nodes (4): StudioAccount, AtlasParser, AtlasSyncTest, PayrollMigrationTest

### Community 11 - "Community 11"
Cohesion: 0.11
Nodes (5): AtlasSession, {closure#1}(), AtlasSessionTest, {closure#1}(), {closure#3}()

### Community 12 - "Community 12"
Cohesion: 0.17
Nodes (4): AuthController, {closure#1}(), DailyWorkReportController, DailyWorkReport

### Community 15 - "Community 15"
Cohesion: 0.11
Nodes (21): devDependencies, autoprefixer@^10.6.2, axios@^1.11.0, concurrently@^9.0.1, laravel-vite-plugin@^2.0.0, postcss@^8.5.32, tailwindcss@^3.4.19, vite@^7.0.7 (+13 more)

### Community 18 - "Community 18"
Cohesion: 0.14
Nodes (4): RecordStreakController, {closure#1}(), {closure#4}(), RecordStreak

### Community 19 - "Community 19"
Cohesion: 0.10
Nodes (5): DeviceAccount, DeviceAccountTest, ExampleTest, ShiftRosterTest, TestCase

### Community 21 - "Community 21"
Cohesion: 0.18
Nodes (6): {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), ContributorController, ContributorPerformanceService

### Community 23 - "Community 23"
Cohesion: 0.17
Nodes (12): addTask(), canAdd(), choices(), getOptions(), init(), isTaskSelected(), labels, lookup() (+4 more)

### Community 24 - "Community 24"
Cohesion: 0.18
Nodes (3): SettingController, {closure#1}(), ShiftPatterns

### Community 25 - "Community 25"
Cohesion: 0.17
Nodes (3): Payslip, User, PendingCarryOverTest

### Community 26 - "Community 26"
Cohesion: 0.19
Nodes (10): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#1}(), {closure#2}() (+2 more)

### Community 27 - "Community 27"
Cohesion: 0.17
Nodes (7): drawQr(), init(), load(), loadConfig(), openModal(), renderQrCode(), saveSettings()

### Community 30 - "Community 30"
Cohesion: 0.17
Nodes (4): Controller, {closure#2}(), ShiftDayController, ShiftConfiguration

### Community 31 - "Community 31"
Cohesion: 0.22
Nodes (3): fingerprints(), main(), run()

### Community 32 - "Community 32"
Cohesion: 0.18
Nodes (11): dependencies, alpinejs@^3.17.5, html5-qrcode@^2.3.8, qrcode@^1.5.4, sweetalert2@^11.26.25, alpinejs, html5-qrcode, qrcode (+3 more)

### Community 34 - "Community 34"
Cohesion: 0.19
Nodes (4): confirmOwner(), json(), load(), openFor()

### Community 35 - "Community 35"
Cohesion: 0.17
Nodes (3): {closure#1}(), {closure#1}(), up()

### Community 36 - "Community 36"
Cohesion: 0.17
Nodes (4): {closure#1}(), {closure#1}(), {closure#1}(), {closure#2}()

### Community 39 - "Community 39"
Cohesion: 0.24
Nodes (4): dateKey(), groups(), init(), load()

### Community 44 - "Community 44"
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

### Community 74 - "Community 74"
Cohesion: 0.50
Nodes (3): layouts.workspace-sidebar, portal.icon, portal.navigation

### Community 76 - "Community 76"
Cohesion: 0.50
Nodes (3): portal.streak, portal.icon, portal.layout

### Community 77 - "Community 77"
Cohesion: 0.50
Nodes (3): portal.icon, portal.layout, work-reports.form

## Knowledge Gaps
- **45 isolated node(s):** `layouts.sidebar`, `portal.icon`, `portal.layout`, `portal.layout`, `work-reports.task-picker` (+40 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 513 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **98 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `AttendanceQrWifiTest` connect `Community 28` to `Community 16`?**
  _High betweenness centrality (0.018) - this node is a cross-community bridge._
- **What connects `layouts.sidebar`, `portal.icon`, `portal.layout` to the rest of the system?**
  _45 weakly-connected nodes found - possible missing edges._
- **Should `Community 0` be split into smaller, more focused modules?**
  _Cohesion score 0.050837496326770495 - nodes in this community are weakly interconnected._
- **Why does `DailyWorkReportTest` connect `Community 20` to `Community 16`, `Community 36`?**
  _High betweenness centrality (0.016) - this node is a cross-community bridge._
- **Should `Community 1` be split into smaller, more focused modules?**
  _Cohesion score 0.05524537173082574 - nodes in this community are weakly interconnected._
- **Why does `RecordStreakTest` connect `Community 29` to `Community 16`, `Community 25`, `Community 2`?**
  _High betweenness centrality (0.015) - this node is a cross-community bridge._
- **Should `Community 2` be split into smaller, more focused modules?**
  _Cohesion score 0.05920745920745921 - nodes in this community are weakly interconnected._