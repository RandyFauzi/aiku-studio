# Graph Report - AIKU STUDIO  (2026-11-05)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 1029 nodes · 1862 edges · 97 communities (20 shown, 77 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 37 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `84208e2e`
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
- Community 42
- Community 43
- Community 44
- Community 45
- Community 46
- Community 47
- Community 48
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
- Community 68
- Community 69
- Community 70
- Community 71

## God Nodes (most connected - your core abstractions)
1. `SystemAuditRegressionTest` - 28 edges
2. `PayrollCalculator` - 25 edges
3. `User` - 21 edges
4. `PayrollMigrationTest` - 19 edges
5. `Robotics1618Sync` - 18 edges
6. `PayslipController` - 18 edges
7. `AttendanceQrWifiTest` - 17 edges
8. `AttendanceController` - 16 edges
9. `TestCase` - 16 edges
10. `Payslip` - 15 edges

## Surprising Connections (you probably didn't know these)
- `Robotics1618SyncTest` --references--> `User`  [EXTRACTED]
  tests/Feature/Robotics1618SyncTest.php → app/Models/User.php
- `DeviceAccountTest` --references--> `User`  [EXTRACTED]
  tests/Feature/DeviceAccountTest.php → app/Models/User.php
- `SyncController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/SyncController.php → app/Http/Controllers/Controller.php
- `PayslipController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/PayslipController.php → app/Http/Controllers/Controller.php
- `AttendanceController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/AttendanceController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (97 total, 77 thin omitted)

### Community 0 - "Community 0"
Cohesion: 0.05
Nodes (11): SyncController, Attendance, PayrollStatus, ShiftSetting, StudioAccount, StudioRecording, AtlasParser, AtlasSync (+3 more)

### Community 1 - "Community 1"
Cohesion: 0.05
Nodes (29): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}() (+21 more)

### Community 2 - "Community 2"
Cohesion: 0.07
Nodes (48): aggregate(), clone(), dateRange(), members(), patternForDate(), prepareConfig(), problems(), sessionRows() (+40 more)

### Community 3 - "Community 3"
Cohesion: 0.06
Nodes (51): "attendances", attendances_contributor_id_index, attendances_user_id_index, attendances_user_id_work_date_unique, attendances_work_date_index, "cache", cache_expiration_index, "cache_locks" (+43 more)

### Community 4 - "Community 4"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 5 - "Community 5"
Cohesion: 0.08
Nodes (8): {closure#1}(), DailyWorkReportController, {closure#2}(), {closure#3}(), {closure#4}(), DeviceAccountController, DailyWorkReport, DeviceAccount

### Community 6 - "Community 6"
Cohesion: 0.07
Nodes (22): checkIn(), checkInWithQr(), closeScanner(), handleQrScanned(), init(), load(), loadReportStatus(), loadSlips() (+14 more)

### Community 7 - "Community 7"
Cohesion: 0.08
Nodes (23): assignCandidates(), calcCreateTotal(), calcEditTotal(), closeCreateModal(), closeEditModal(), confirmDeleteReport(), dateLabel(), fetchContributors() (+15 more)

### Community 8 - "Community 8"
Cohesion: 0.08
Nodes (6): AtlasTick, AuditDataIntegrity, CreatePanelAdmin, ImportLegacyPayroll, SyncRobotics1618, VerifyLegacyPayroll

### Community 9 - "Community 9"
Cohesion: 0.09
Nodes (30): dependencies, alpinejs@^3.17.5, html5-qrcode@^2.3.8, qrcode@^1.5.4, sweetalert2@^11.26.25, devDependencies, autoprefixer@^10.6.2, axios@^1.11.0 (+22 more)

### Community 10 - "Community 10"
Cohesion: 0.17
Nodes (4): {closure#2}(), PayslipController, Payslip, PayrollCalculator

### Community 13 - "Community 13"
Cohesion: 0.17
Nodes (6): {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), ContributorController, ShiftSchedule

### Community 15 - "Community 15"
Cohesion: 0.12
Nodes (11): credentials, legacyRequire, puppeteer, report, root, {createRequire}, fs, mysql (+3 more)

### Community 16 - "Community 16"
Cohesion: 0.17
Nodes (7): drawQr(), init(), load(), loadConfig(), openModal(), renderQrCode(), saveSettings()

### Community 19 - "Community 19"
Cohesion: 0.22
Nodes (3): fingerprints(), main(), run()

### Community 20 - "Community 20"
Cohesion: 0.17
Nodes (4): ExampleTest, Robotics1618SyncTest, ShiftRosterTest, TestCase

### Community 25 - "Community 25"
Cohesion: 0.21
Nodes (4): {closure#2}(), ShiftDayController, ShiftDay, ShiftConfiguration

### Community 27 - "Community 27"
Cohesion: 0.29
Nodes (4): {closure#1}(), SyncContributorLogins, Contributor, ContributorAccess

### Community 47 - "Community 47"
Cohesion: 0.50
Nodes (3): portal.icon, portal.layout, work-reports.form

### Community 48 - "Community 48"
Cohesion: 0.50
Nodes (3): layouts.workspace-sidebar, portal.icon, portal.navigation

## Knowledge Gaps
- **34 isolated node(s):** `portal.icon`, `portal.layout`, `work-reports.form`, `layouts.workspace-sidebar`, `portal.icon` (+29 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 371 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **77 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Robotics1618Sync` connect `Community 0` to `Community 8`?**
  _High betweenness centrality (0.024) - this node is a cross-community bridge._
- **What connects `portal.icon`, `portal.layout`, `work-reports.form` to the rest of the system?**
  _34 weakly-connected nodes found - possible missing edges._
- **Should `Community 0` be split into smaller, more focused modules?**
  _Cohesion score 0.05081081081081081 - nodes in this community are weakly interconnected._
- **Why does `SystemAuditRegressionTest` connect `Community 11` to `Community 0`, `Community 18`, `Community 20`?**
  _High betweenness centrality (0.023) - this node is a cross-community bridge._
- **Should `Community 1` be split into smaller, more focused modules?**
  _Cohesion score 0.050724637681159424 - nodes in this community are weakly interconnected._
- **Why does `AttendanceQrWifiTest` connect `Community 21` to `Community 0`, `Community 18`, `Community 20`?**
  _High betweenness centrality (0.019) - this node is a cross-community bridge._
- **Should `Community 2` be split into smaller, more focused modules?**
  _Cohesion score 0.07019230769230769 - nodes in this community are weakly interconnected._