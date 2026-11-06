# Graph Report - AIKU STUDIO  (2026-11-06)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 1267 nodes · 2400 edges · 122 communities (32 shown, 90 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 50 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `a1720ddd`
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
- Community 45
- Community 46
- Community 47
- Community 48
- Community 49
- Community 50
- Community 51
- Community 53
- Community 54
- Community 55
- Community 56
- Community 57
- Community 59
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

## God Nodes (most connected - your core abstractions)
1. `SystemAuditRegressionTest` - 28 edges
2. `PayrollCalculator` - 25 edges
3. `DailyWorkReportTest` - 19 edges
4. `PayrollMigrationTest` - 19 edges
5. `User` - 19 edges
6. `RecordStreakTest` - 19 edges
7. `PayslipController` - 18 edges
8. `Robotics1618Sync` - 18 edges
9. `AttendanceQrWifiTest` - 17 edges
10. `RecordingMatchingTest` - 16 edges

## Surprising Connections (you probably didn't know these)
- `Robotics1618SyncTest` --references--> `User`  [EXTRACTED]
  tests/Feature/Robotics1618SyncTest.php → app/Models/User.php
- `DeviceAccountTest` --references--> `User`  [EXTRACTED]
  tests/Feature/DeviceAccountTest.php → app/Models/User.php
- `{closure#2}()` --calls--> `ShiftDay`  [INFERRED]
  app/Http/Controllers/ShiftDayController.php → app/Models/ShiftDay.php
- `{closure#2}()` --references_constant--> `DailyWorkReport`  [EXTRACTED]
  app/Services/RecordingMatching.php → app/Models/DailyWorkReport.php
- `PayrollController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/PayrollController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (122 total, 90 thin omitted)

### Community 0 - "Community 0"
Cohesion: 0.06
Nodes (10): PayrollController, {closure#1}(), {closure#2}(), PayslipController, Payslip, {closure#2}(), PayrollCalculator, PayrollFiles (+2 more)

### Community 1 - "Community 1"
Cohesion: 0.07
Nodes (48): aggregate(), clone(), dateRange(), members(), patternForDate(), prepareConfig(), problems(), sessionRows() (+40 more)

### Community 2 - "Community 2"
Cohesion: 0.05
Nodes (61): "attendances", attendances_contributor_id_index, attendances_user_id_index, attendances_user_id_work_date_unique, attendances_work_date_index, "cache", cache_expiration_index, "cache_locks" (+53 more)

### Community 3 - "Community 3"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 4 - "Community 4"
Cohesion: 0.07
Nodes (30): assignCandidates(), calcCreateTotal(), calcEditTotal(), closeCreateModal(), closeEditModal(), confirmDeleteReport(), copyPhoneTasks(), dateLabel() (+22 more)

### Community 5 - "Community 5"
Cohesion: 0.07
Nodes (23): checkIn(), checkInWithQr(), closeScanner(), confirmAttendance(), handleQrScanned(), init(), load(), loadReportStatus() (+15 more)

### Community 6 - "Community 6"
Cohesion: 0.06
Nodes (26): connectedDays(), init(), openPopup(), refresh(), statusLabel(), weekDays(), credentials, legacyRequire (+18 more)

### Community 7 - "Community 7"
Cohesion: 0.10
Nodes (6): SyncController, AtlasParser, AtlasSync, {closure#1}(), {closure#2}(), Robotics1618Sync

### Community 8 - "Community 8"
Cohesion: 0.10
Nodes (4): RecordStreakController, {closure#1}(), RecordStreak, RecordStreakTest

### Community 9 - "Community 9"
Cohesion: 0.09
Nodes (9): AtlasTick, AuditDataIntegrity, CreatePanelAdmin, ImportLegacyPayroll, {closure#1}(), SyncContributorLogins, SyncRobotics1618, VerifyLegacyPayroll (+1 more)

### Community 10 - "Community 10"
Cohesion: 0.14
Nodes (5): {closure#2}(), {closure#4}(), RecordingMatchingController, RecordingMatching, RecordingMatchingTest

### Community 12 - "Community 12"
Cohesion: 0.11
Nodes (21): devDependencies, autoprefixer@^10.6.2, axios@^1.11.0, concurrently@^9.0.1, laravel-vite-plugin@^2.0.0, postcss@^8.5.32, tailwindcss@^3.4.19, vite@^7.0.7 (+13 more)

### Community 14 - "Community 14"
Cohesion: 0.17
Nodes (5): {closure#2}(), {closure#3}(), {closure#4}(), DeviceAccountController, DeviceAccount

### Community 16 - "Community 16"
Cohesion: 0.13
Nodes (6): PayrollStatus, RecordStreakDay, ShiftDay, ShiftSetting, StudioRecording, PayrollPaymentStatus

### Community 17 - "Community 17"
Cohesion: 0.17
Nodes (14): addTask(), canAdd(), choices(), getOptions(), init(), initialLabel(), labelFor(), labels (+6 more)

### Community 18 - "Community 18"
Cohesion: 0.13
Nodes (5): StudioAccount, ExampleTest, Robotics1618SyncTest, ShiftRosterTest, TestCase

### Community 22 - "Community 22"
Cohesion: 0.17
Nodes (7): drawQr(), init(), load(), loadConfig(), openModal(), renderQrCode(), saveSettings()

### Community 25 - "Community 25"
Cohesion: 0.18
Nodes (3): SettingController, {closure#1}(), ShiftPatterns

### Community 26 - "Community 26"
Cohesion: 0.14
Nodes (3): Attendance, {closure#1}(), {closure#4}()

### Community 27 - "Community 27"
Cohesion: 0.20
Nodes (9): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}() (+1 more)

### Community 28 - "Community 28"
Cohesion: 0.22
Nodes (3): fingerprints(), main(), run()

### Community 30 - "Community 30"
Cohesion: 0.20
Nodes (3): {closure#2}(), ShiftDayController, ShiftConfiguration

### Community 32 - "Community 32"
Cohesion: 0.19
Nodes (4): confirmOwner(), json(), load(), openFor()

### Community 39 - "Community 39"
Cohesion: 0.27
Nodes (9): dependencies, alpinejs@^3.17.5, html5-qrcode@^2.3.8, qrcode@^1.5.4, sweetalert2@^11.26.25, alpinejs, html5-qrcode, qrcode (+1 more)

### Community 40 - "Community 40"
Cohesion: 0.31
Nodes (4): {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}()

### Community 42 - "Community 42"
Cohesion: 0.22
Nodes (5): portal.icon, portal.layout, portal.streak, portal.layout, work-reports.form

### Community 45 - "Community 45"
Cohesion: 0.25
Nodes (3): AuthController, Controller, RecordingController

### Community 51 - "Community 51"
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

### Community 71 - "Community 71"
Cohesion: 0.50
Nodes (3): layouts.workspace-sidebar, portal.icon, portal.navigation

## Knowledge Gaps
- **34 isolated node(s):** `portal.streak`, `portal.layout`, `work-reports.form`, `panel.partials.recording-matching`, `layouts.workspace-sidebar` (+29 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 437 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **90 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `RecordStreakTest` connect `Community 8` to `Community 20`, `Community 38`, `Community 31`?**
  _High betweenness centrality (0.021) - this node is a cross-community bridge._
- **What connects `portal.streak`, `portal.layout`, `work-reports.form` to the rest of the system?**
  _34 weakly-connected nodes found - possible missing edges._
- **Should `Community 0` be split into smaller, more focused modules?**
  _Cohesion score 0.06241519674355495 - nodes in this community are weakly interconnected._
- **Why does `SystemAuditRegressionTest` connect `Community 11` to `Community 20`, `Community 38`?**
  _High betweenness centrality (0.017) - this node is a cross-community bridge._
- **Should `Community 1` be split into smaller, more focused modules?**
  _Cohesion score 0.07373271889400922 - nodes in this community are weakly interconnected._
- **Why does `Robotics1618Sync` connect `Community 7` to `Community 9`, `Community 18`, `Community 21`, `Community 38`?**
  _High betweenness centrality (0.017) - this node is a cross-community bridge._
- **Should `Community 2` be split into smaller, more focused modules?**
  _Cohesion score 0.0523532522474881 - nodes in this community are weakly interconnected._