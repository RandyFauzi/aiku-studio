# Graph Report - AIKU STUDIO  (2026-11-06)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 1189 nodes · 2244 edges · 119 communities (27 shown, 92 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 47 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `4df1651d`
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
- Community 24
- Community 25
- Community 26
- Community 27
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
- Community 57
- Community 58
- Community 59
- Community 60
- Community 61
- Community 62
- Community 63
- Community 64
- Community 66
- Community 67
- Community 68
- Community 69
- Community 70
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
- Community 84
- Community 85
- Community 86
- Community 87
- Community 88
- Community 89
- Community 90

## God Nodes (most connected - your core abstractions)
1. `SystemAuditRegressionTest` - 28 edges
2. `PayrollCalculator` - 25 edges
3. `User` - 19 edges
4. `DailyWorkReportTest` - 19 edges
5. `PayrollMigrationTest` - 19 edges
6. `RecordStreakTest` - 19 edges
7. `Robotics1618Sync` - 18 edges
8. `PayslipController` - 18 edges
9. `AttendanceQrWifiTest` - 17 edges
10. `AttendanceController` - 16 edges

## Surprising Connections (you probably didn't know these)
- `DeviceAccountTest` --references--> `User`  [EXTRACTED]
  tests/Feature/DeviceAccountTest.php → app/Models/User.php
- `Robotics1618SyncTest` --references--> `User`  [EXTRACTED]
  tests/Feature/Robotics1618SyncTest.php → app/Models/User.php
- `SyncController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/SyncController.php → app/Http/Controllers/Controller.php
- `RecordStreakController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/RecordStreakController.php → app/Http/Controllers/Controller.php
- `ContributorController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/ContributorController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (119 total, 92 thin omitted)

### Community 0 - "Community 0"
Cohesion: 0.05
Nodes (12): AtlasTick, AuditDataIntegrity, CreatePanelAdmin, ImportLegacyPayroll, SyncRobotics1618, VerifyLegacyPayroll, SyncController, AtlasParser (+4 more)

### Community 1 - "Community 1"
Cohesion: 0.07
Nodes (48): aggregate(), clone(), dateRange(), members(), patternForDate(), prepareConfig(), problems(), sessionRows() (+40 more)

### Community 2 - "Community 2"
Cohesion: 0.06
Nodes (58): "attendances", attendances_contributor_id_index, attendances_user_id_index, attendances_user_id_work_date_unique, attendances_work_date_index, "cache", cache_expiration_index, "cache_locks" (+50 more)

### Community 3 - "Community 3"
Cohesion: 0.06
Nodes (36): dependencies, alpinejs@^3.17.5, html5-qrcode@^2.3.8, qrcode@^1.5.4, sweetalert2@^11.26.25, devDependencies, autoprefixer@^10.6.2, axios@^1.11.0 (+28 more)

### Community 4 - "Community 4"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 5 - "Community 5"
Cohesion: 0.07
Nodes (29): assignCandidates(), calcCreateTotal(), calcEditTotal(), closeCreateModal(), closeEditModal(), confirmDeleteReport(), dateLabel(), fetchContributors() (+21 more)

### Community 6 - "Community 6"
Cohesion: 0.07
Nodes (23): checkIn(), checkInWithQr(), closeScanner(), confirmAttendance(), handleQrScanned(), init(), load(), loadReportStatus() (+15 more)

### Community 7 - "Community 7"
Cohesion: 0.06
Nodes (26): connectedDays(), init(), openPopup(), refresh(), statusLabel(), weekDays(), credentials, legacyRequire (+18 more)

### Community 8 - "Community 8"
Cohesion: 0.12
Nodes (8): AuthController, Controller, {closure#1}(), DailyWorkReportController, RecordingController, RecordingMatchingController, DailyWorkReport, {closure#1}()

### Community 9 - "Community 9"
Cohesion: 0.09
Nodes (6): Attendance, PayrollStatus, RecordStreakDay, StudioAccount, StudioRecording, PayrollPaymentStatus

### Community 13 - "Community 13"
Cohesion: 0.18
Nodes (4): RecordStreakController, {closure#1}(), {closure#4}(), RecordStreak

### Community 14 - "Community 14"
Cohesion: 0.16
Nodes (3): User, ContributorIdentity, PendingCarryOverTest

### Community 15 - "Community 15"
Cohesion: 0.20
Nodes (6): {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), ContributorController, ContributorPerformanceService

### Community 16 - "Community 16"
Cohesion: 0.24
Nodes (4): {closure#2}(), {closure#3}(), {closure#4}(), DeviceAccountController

### Community 18 - "Community 18"
Cohesion: 0.17
Nodes (7): drawQr(), init(), load(), loadConfig(), openModal(), renderQrCode(), saveSettings()

### Community 22 - "Community 22"
Cohesion: 0.17
Nodes (4): {closure#2}(), ShiftDayController, ShiftDay, ShiftConfiguration

### Community 25 - "Community 25"
Cohesion: 0.15
Nodes (3): {closure#1}(), {closure#2}(), {closure#2}()

### Community 26 - "Community 26"
Cohesion: 0.22
Nodes (3): fingerprints(), main(), run()

### Community 33 - "Community 33"
Cohesion: 0.33
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}()

### Community 34 - "Community 34"
Cohesion: 0.24
Nodes (3): ExampleTest, ShiftRosterTest, TestCase

### Community 36 - "Community 36"
Cohesion: 0.31
Nodes (4): {closure#1}(), SyncContributorLogins, Contributor, ContributorAccess

### Community 41 - "Community 41"
Cohesion: 0.22
Nodes (5): portal.icon, portal.layout, portal.streak, portal.layout, work-reports.form

### Community 49 - "Community 49"
Cohesion: 0.29
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 52 - "Community 52"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 53 - "Community 53"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 54 - "Community 54"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 70 - "Community 70"
Cohesion: 0.50
Nodes (3): layouts.workspace-sidebar, portal.icon, portal.navigation

## Knowledge Gaps
- **32 isolated node(s):** `portal.streak`, `portal.layout`, `work-reports.form`, `panel.partials.recording-matching`, `layouts.workspace-sidebar` (+27 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 412 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **92 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `SystemAuditRegressionTest` connect `Community 10` to `Community 23`?**
  _High betweenness centrality (0.029) - this node is a cross-community bridge._
- **What connects `portal.streak`, `portal.layout`, `work-reports.form` to the rest of the system?**
  _32 weakly-connected nodes found - possible missing edges._
- **Should `Community 0` be split into smaller, more focused modules?**
  _Cohesion score 0.053555750658472345 - nodes in this community are weakly interconnected._
- **Why does `PayrollMigrationTest` connect `Community 20` to `Community 25`, `Community 34`, `Community 23`?**
  _High betweenness centrality (0.025) - this node is a cross-community bridge._
- **Should `Community 1` be split into smaller, more focused modules?**
  _Cohesion score 0.07373271889400922 - nodes in this community are weakly interconnected._
- **Why does `AuthLoginTest` connect `Community 35` to `Community 23`?**
  _High betweenness centrality (0.016) - this node is a cross-community bridge._
- **Should `Community 2` be split into smaller, more focused modules?**
  _Cohesion score 0.055523085914669784 - nodes in this community are weakly interconnected._