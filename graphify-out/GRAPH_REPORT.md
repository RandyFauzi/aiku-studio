# Graph Report - AIKU STUDIO  (2026-11-05)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 955 nodes · 1670 edges · 83 communities (18 shown, 65 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 31 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `8dfb96a8`
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

## God Nodes (most connected - your core abstractions)
1. `SystemAuditRegressionTest` - 28 edges
2. `PayrollCalculator` - 25 edges
3. `User` - 21 edges
4. `PayrollMigrationTest` - 19 edges
5. `PayslipController` - 18 edges
6. `Robotics1618Sync` - 18 edges
7. `AttendanceQrWifiTest` - 17 edges
8. `AttendanceController` - 16 edges
9. `TestCase` - 16 edges
10. `Payslip` - 15 edges

## Surprising Connections (you probably didn't know these)
- `Robotics1618SyncTest` --references--> `User`  [EXTRACTED]
  tests/Feature/Robotics1618SyncTest.php → app/Models/User.php
- `DeviceAccountTest` --references--> `User`  [EXTRACTED]
  tests/Feature/DeviceAccountTest.php → app/Models/User.php
- `ContributorController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/ContributorController.php → app/Http/Controllers/Controller.php
- `DailyWorkReportController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/DailyWorkReportController.php → app/Http/Controllers/Controller.php
- `DeviceAccountController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/DeviceAccountController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (83 total, 65 thin omitted)

### Community 0 - "Community 0"
Cohesion: 0.05
Nodes (14): AttendanceController, AuthController, Controller, PayrollController, {closure#1}(), {closure#2}(), PayslipController, RecordingController (+6 more)

### Community 1 - "Community 1"
Cohesion: 0.05
Nodes (29): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}() (+21 more)

### Community 2 - "Community 2"
Cohesion: 0.05
Nodes (11): {closure#2}(), {closure#3}(), {closure#4}(), DeviceAccountController, DeviceAccount, PayrollStatus, ShiftSetting, StudioAccount (+3 more)

### Community 3 - "Community 3"
Cohesion: 0.06
Nodes (9): {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), ContributorController, {closure#1}(), DailyWorkReportController, DailyWorkReport (+1 more)

### Community 4 - "Community 4"
Cohesion: 0.06
Nodes (51): "attendances", attendances_contributor_id_index, attendances_user_id_index, attendances_user_id_work_date_unique, attendances_work_date_index, "cache", cache_expiration_index, "cache_locks" (+43 more)

### Community 5 - "Community 5"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 6 - "Community 6"
Cohesion: 0.07
Nodes (22): checkIn(), checkInWithQr(), closeScanner(), handleQrScanned(), init(), load(), loadReportStatus(), loadSlips() (+14 more)

### Community 7 - "Community 7"
Cohesion: 0.07
Nodes (6): {closure#2}(), ShiftDayController, EnsureActiveAccount, EnsureAdministrator, ShiftDay, ShiftConfiguration

### Community 8 - "Community 8"
Cohesion: 0.09
Nodes (30): dependencies, alpinejs@^3.17.5, html5-qrcode@^2.3.8, qrcode@^1.5.4, sweetalert2@^11.26.25, devDependencies, autoprefixer@^10.6.2, axios@^1.11.0 (+22 more)

### Community 9 - "Community 9"
Cohesion: 0.09
Nodes (7): AtlasTick, AuditDataIntegrity, CreatePanelAdmin, ImportLegacyPayroll, SyncRobotics1618, VerifyLegacyPayroll, PayrollFiles

### Community 10 - "Community 10"
Cohesion: 0.14
Nodes (5): SyncController, AtlasSync, {closure#1}(), {closure#2}(), Robotics1618Sync

### Community 11 - "Community 11"
Cohesion: 0.10
Nodes (15): assignCandidates(), calcEditTotal(), closeEditModal(), confirmDeleteReport(), dateLabel(), init(), labels, load() (+7 more)

### Community 12 - "Community 12"
Cohesion: 0.09
Nodes (7): attachCalendar(), conflicts(), dailyHours(), hours(), peopleAt(), production(), saveConfig()

### Community 14 - "Community 14"
Cohesion: 0.12
Nodes (11): credentials, legacyRequire, puppeteer, report, root, {createRequire}, fs, mysql (+3 more)

### Community 15 - "Community 15"
Cohesion: 0.17
Nodes (7): drawQr(), init(), load(), loadConfig(), openModal(), renderQrCode(), saveSettings()

### Community 17 - "Community 17"
Cohesion: 0.16
Nodes (4): ExampleTest, Robotics1618SyncTest, ShiftRosterTest, TestCase

### Community 18 - "Community 18"
Cohesion: 0.19
Nodes (5): {closure#1}(), SyncContributorLogins, Contributor, ContributorAccess, ShiftSchedule

### Community 20 - "Community 20"
Cohesion: 0.22
Nodes (3): fingerprints(), main(), run()

## Knowledge Gaps
- **28 isolated node(s):** `work-reports.form`, `attendance.admin`, `layouts.sidebar`, `portal.navigation`, `portal.icon` (+23 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 366 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **65 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `Community 19` to `Community 32`, `Community 0`, `Community 34`, `Community 2`, `Community 9`, `Community 17`, `Community 18`, `Community 22`, `Community 24`, `Community 29`?**
  _High betweenness centrality (0.028) - this node is a cross-community bridge._
- **What connects `work-reports.form`, `attendance.admin`, `layouts.sidebar` to the rest of the system?**
  _28 weakly-connected nodes found - possible missing edges._
- **Should `Community 0` be split into smaller, more focused modules?**
  _Cohesion score 0.05286006128702758 - nodes in this community are weakly interconnected._
- **Why does `SystemAuditRegressionTest` connect `Community 13` to `Community 17`, `Community 2`, `Community 22`?**
  _High betweenness centrality (0.023) - this node is a cross-community bridge._
- **Should `Community 1` be split into smaller, more focused modules?**
  _Cohesion score 0.050724637681159424 - nodes in this community are weakly interconnected._
- **Why does `PayrollMigrationTest` connect `Community 16` to `Community 0`, `Community 17`?**
  _High betweenness centrality (0.020) - this node is a cross-community bridge._
- **Should `Community 2` be split into smaller, more focused modules?**
  _Cohesion score 0.052083333333333336 - nodes in this community are weakly interconnected._