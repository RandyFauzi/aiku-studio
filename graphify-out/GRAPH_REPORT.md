# Graph Report - AIKU STUDIO  (2026-11-08)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 1412 nodes · 2732 edges · 141 communities (38 shown, 103 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 62 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `c8a99d90`
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
- Community 39
- Community 40
- Community 41
- Community 42
- Community 43
- Community 44
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
- Community 64
- Community 65
- Community 66
- Community 67
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
- Community 102
- Community 103
- Community 104
- Community 105
- Community 106
- Community 107
- Community 108
- Community 109
- Community 110
- Community 111

## God Nodes (most connected - your core abstractions)
1. `SystemAuditRegressionTest` - 28 edges
2. `DailyWorkReportTest` - 24 edges
3. `Robotics1618Sync` - 19 edges
4. `AtlasSync` - 19 edges
5. `PayslipController` - 19 edges
6. `AttendanceQrWifiTest` - 19 edges
7. `PayrollMigrationTest` - 19 edges
8. `RecordStreakTest` - 19 edges
9. `DailyWorkReportController` - 18 edges
10. `RecordingSync` - 17 edges

## Surprising Connections (you probably didn't know these)
- `DeviceAccountTest` --references--> `User`  [EXTRACTED]
  tests/Feature/DeviceAccountTest.php → app/Models/User.php
- `{closure#2}()` --calls--> `ShiftDay`  [INFERRED]
  app/Http/Controllers/ShiftDayController.php → app/Models/ShiftDay.php
- `{closure#2}()` --references_constant--> `DailyWorkReport`  [EXTRACTED]
  app/Services/RecordingMatching.php → app/Models/DailyWorkReport.php
- `Robotics1618SyncTest` --references--> `User`  [EXTRACTED]
  tests/Feature/Robotics1618SyncTest.php → app/Models/User.php
- `SyncController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/SyncController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (141 total, 103 thin omitted)

### Community 0 - "Community 0"
Cohesion: 0.05
Nodes (17): AtlasTick, AuditDataIntegrity, CreatePanelAdmin, ImportLegacyPayroll, {closure#1}(), SyncContributorLogins, SyncRobotics1618, VerifyLegacyPayroll (+9 more)

### Community 1 - "Community 1"
Cohesion: 0.07
Nodes (48): aggregate(), clone(), dateRange(), members(), patternForDate(), prepareConfig(), problems(), sessionRows() (+40 more)

### Community 2 - "Community 2"
Cohesion: 0.06
Nodes (11): {closure#3}(), {closure#5}(), {closure#2}(), {closure#4}(), RecordingMatchingController, {closure#2}(), RecordingMatching, RecordingOwnerRecommendations (+3 more)

### Community 3 - "Community 3"
Cohesion: 0.05
Nodes (61): "attendances", attendances_contributor_id_index, attendances_user_id_index, attendances_user_id_work_date_unique, attendances_work_date_index, "cache", cache_expiration_index, "cache_locks" (+53 more)

### Community 4 - "Community 4"
Cohesion: 0.06
Nodes (35): assignCandidates(), calcCreateTotal(), calcEditTotal(), closeCreateModal(), closeEditModal(), confirmDeleteReport(), copyPhoneTasks(), deleteReport() (+27 more)

### Community 5 - "Community 5"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 6 - "Community 6"
Cohesion: 0.08
Nodes (25): checkIn(), checkInWithQr(), closeScanner(), confirmAttendance(), deleteAttendance(), fetchContributors(), handleQrScanned(), init() (+17 more)

### Community 7 - "Community 7"
Cohesion: 0.06
Nodes (26): connectedDays(), init(), openPopup(), refresh(), statusLabel(), weekDays(), credentials, legacyRequire (+18 more)

### Community 10 - "Community 10"
Cohesion: 0.13
Nodes (5): RecordStreakController, {closure#1}(), {closure#1}(), {closure#4}(), RecordStreak

### Community 13 - "Community 13"
Cohesion: 0.21
Nodes (6): {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), ContributorController, ContributorPerformanceService

### Community 15 - "Community 15"
Cohesion: 0.13
Nodes (18): devDependencies, autoprefixer@^10.6.2, axios@^1.11.0, concurrently@^9.0.1, laravel-vite-plugin@^2.0.0, postcss@^8.5.32, tailwindcss@^3.4.19, vite@^7.0.7 (+10 more)

### Community 16 - "Community 16"
Cohesion: 0.16
Nodes (15): addTask(), canAdd(), choices(), getOptions(), init(), initialLabel(), isTaskSelected(), labelFor() (+7 more)

### Community 17 - "Community 17"
Cohesion: 0.13
Nodes (3): Payslip, StudioAccount, StudioRecording

### Community 18 - "Community 18"
Cohesion: 0.12
Nodes (6): Attendance, Contributor, PayrollStatus, RecordStreakDay, ShiftDay, ShiftSetting

### Community 19 - "Community 19"
Cohesion: 0.20
Nodes (3): {closure#1}(), DailyWorkReportController, DailyWorkReport

### Community 21 - "Community 21"
Cohesion: 0.16
Nodes (12): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#1}(), {closure#2}() (+4 more)

### Community 23 - "Community 23"
Cohesion: 0.17
Nodes (7): drawQr(), init(), load(), loadConfig(), openModal(), renderQrCode(), saveSettings()

### Community 26 - "Community 26"
Cohesion: 0.27
Nodes (4): {closure#2}(), {closure#3}(), {closure#4}(), DeviceAccountController

### Community 27 - "Community 27"
Cohesion: 0.17
Nodes (3): SettingController, {closure#1}(), ShiftPatterns

### Community 29 - "Community 29"
Cohesion: 0.22
Nodes (3): fingerprints(), main(), run()

### Community 30 - "Community 30"
Cohesion: 0.18
Nodes (11): dependencies, alpinejs@^3.17.5, html5-qrcode@^2.3.8, qrcode@^1.5.4, sweetalert2@^11.26.25, alpinejs, html5-qrcode, qrcode (+3 more)

### Community 32 - "Community 32"
Cohesion: 0.21
Nodes (3): {closure#2}(), ShiftDayController, ShiftConfiguration

### Community 33 - "Community 33"
Cohesion: 0.19
Nodes (4): confirmOwner(), json(), load(), openFor()

### Community 34 - "Community 34"
Cohesion: 0.32
Nodes (3): PayrollController, PayrollPeriod, {closure#1}()

### Community 35 - "Community 35"
Cohesion: 0.18
Nodes (3): {closure#2}(), {closure#3}(), PayrollPaymentStatus

### Community 37 - "Community 37"
Cohesion: 0.17
Nodes (3): {closure#1}(), {closure#1}(), up()

### Community 42 - "Community 42"
Cohesion: 0.24
Nodes (3): ExampleTest, ShiftRosterTest, TestCase

### Community 43 - "Community 43"
Cohesion: 0.24
Nodes (4): dateKey(), groups(), init(), load()

### Community 46 - "Community 46"
Cohesion: 0.22
Nodes (4): AuthController, Controller, MyRecordingController, RecordingController

### Community 58 - "Community 58"
Cohesion: 0.29
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 64 - "Community 64"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 65 - "Community 65"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 66 - "Community 66"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 67 - "Community 67"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 80 - "Community 80"
Cohesion: 0.50
Nodes (3): layouts.workspace-sidebar, portal.icon, portal.navigation

### Community 82 - "Community 82"
Cohesion: 0.50
Nodes (3): portal.streak, portal.icon, portal.layout

### Community 83 - "Community 83"
Cohesion: 0.50
Nodes (3): portal.icon, portal.layout, work-reports.form

### Community 87 - "Community 87"
Cohesion: 0.67
Nodes (3): scripts, build, dev

## Knowledge Gaps
- **44 isolated node(s):** `layouts.sidebar`, `layouts.sidebar`, `layouts.sidebar`, `layouts.sidebar`, `layouts.sidebar` (+39 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 479 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **103 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `Community 40` to `Community 0`, `Community 36`, `Community 8`, `Community 74`, `Community 42`, `Community 49`, `Community 17`, `Community 56`?**
  _High betweenness centrality (0.025) - this node is a cross-community bridge._
- **What connects `layouts.sidebar`, `layouts.sidebar`, `layouts.sidebar` to the rest of the system?**
  _44 weakly-connected nodes found - possible missing edges._
- **Should `Community 0` be split into smaller, more focused modules?**
  _Cohesion score 0.05289193302891933 - nodes in this community are weakly interconnected._
- **Why does `PayrollMigrationTest` connect `Community 25` to `Community 8`, `Community 17`, `Community 42`?**
  _High betweenness centrality (0.024) - this node is a cross-community bridge._
- **Should `Community 1` be split into smaller, more focused modules?**
  _Cohesion score 0.07373271889400922 - nodes in this community are weakly interconnected._
- **Why does `AtlasSync` connect `Community 0` to `Community 17`, `Community 20`?**
  _High betweenness centrality (0.015) - this node is a cross-community bridge._
- **Should `Community 2` be split into smaller, more focused modules?**
  _Cohesion score 0.06187202538339503 - nodes in this community are weakly interconnected._