# Graph Report - AIKU STUDIO  (2026-11-05)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 1068 nodes · 1943 edges · 101 communities (26 shown, 75 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 39 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `e323445a`
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
- Community 20
- Community 21
- Community 22
- Community 23
- Community 24
- Community 25
- Community 26
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
- Community 72
- Community 73
- Community 74
- Community 75

## God Nodes (most connected - your core abstractions)
1. `SystemAuditRegressionTest` - 28 edges
2. `PayrollCalculator` - 25 edges
3. `PayrollMigrationTest` - 19 edges
4. `User` - 19 edges
5. `Robotics1618Sync` - 18 edges
6. `DailyWorkReportTest` - 18 edges
7. `PayslipController` - 18 edges
8. `AttendanceQrWifiTest` - 17 edges
9. `AttendanceController` - 16 edges
10. `DailyWorkReport` - 15 edges

## Surprising Connections (you probably didn't know these)
- `DeviceAccountTest` --references--> `User`  [EXTRACTED]
  tests/Feature/DeviceAccountTest.php → app/Models/User.php
- `Robotics1618SyncTest` --references--> `User`  [EXTRACTED]
  tests/Feature/Robotics1618SyncTest.php → app/Models/User.php
- `DeviceAccountController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/DeviceAccountController.php → app/Http/Controllers/Controller.php
- `PayrollController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/PayrollController.php → app/Http/Controllers/Controller.php
- `PayslipController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/PayslipController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (101 total, 75 thin omitted)

### Community 0 - "Community 0"
Cohesion: 0.07
Nodes (14): AttendanceController, AuthController, {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), ContributorController, Controller (+6 more)

### Community 1 - "Community 1"
Cohesion: 0.07
Nodes (48): aggregate(), clone(), dateRange(), members(), patternForDate(), prepareConfig(), problems(), sessionRows() (+40 more)

### Community 2 - "Community 2"
Cohesion: 0.07
Nodes (8): PayrollController, {closure#1}(), {closure#2}(), PayslipController, Payslip, PayrollCalculator, PayrollPeriod, PayslipPdf

### Community 3 - "Community 3"
Cohesion: 0.06
Nodes (51): "attendances", attendances_contributor_id_index, attendances_user_id_index, attendances_user_id_work_date_unique, attendances_work_date_index, "cache", cache_expiration_index, "cache_locks" (+43 more)

### Community 4 - "Community 4"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 5 - "Community 5"
Cohesion: 0.07
Nodes (29): assignCandidates(), calcCreateTotal(), calcEditTotal(), closeCreateModal(), closeEditModal(), confirmDeleteReport(), dateLabel(), fetchContributors() (+21 more)

### Community 6 - "Community 6"
Cohesion: 0.07
Nodes (22): checkIn(), checkInWithQr(), closeScanner(), handleQrScanned(), init(), load(), loadReportStatus(), loadSlips() (+14 more)

### Community 7 - "Community 7"
Cohesion: 0.07
Nodes (9): AuditDataIntegrity, CreatePanelAdmin, ImportLegacyPayroll, {closure#1}(), SyncContributorLogins, SyncRobotics1618, VerifyLegacyPayroll, ContributorAccess (+1 more)

### Community 8 - "Community 8"
Cohesion: 0.10
Nodes (6): {closure#2}(), {closure#3}(), {closure#4}(), DeviceAccountController, DeviceAccount, DeviceAccountTest

### Community 9 - "Community 9"
Cohesion: 0.09
Nodes (30): dependencies, alpinejs@^3.17.5, html5-qrcode@^2.3.8, qrcode@^1.5.4, sweetalert2@^11.26.25, devDependencies, autoprefixer@^10.6.2, axios@^1.11.0 (+22 more)

### Community 10 - "Community 10"
Cohesion: 0.14
Nodes (6): AtlasTick, SyncController, AtlasSync, {closure#1}(), {closure#2}(), Robotics1618Sync

### Community 11 - "Community 11"
Cohesion: 0.09
Nodes (5): ContributorPayslipTest, ExampleTest, PayrollMigrationTest, ShiftRosterTest, TestCase

### Community 12 - "Community 12"
Cohesion: 0.08
Nodes (19): credentials, legacyRequire, puppeteer, report, root, {createRequire}, fs, mysql (+11 more)

### Community 16 - "Community 16"
Cohesion: 0.17
Nodes (7): drawQr(), init(), load(), loadConfig(), openModal(), renderQrCode(), saveSettings()

### Community 17 - "Community 17"
Cohesion: 0.18
Nodes (3): SettingController, {closure#1}(), ShiftPatterns

### Community 20 - "Community 20"
Cohesion: 0.18
Nodes (3): {closure#2}(), ShiftDay, ShiftConfiguration

### Community 21 - "Community 21"
Cohesion: 0.22
Nodes (3): fingerprints(), main(), run()

### Community 25 - "Community 25"
Cohesion: 0.16
Nodes (3): {closure#1}(), {closure#1}(), {closure#1}()

### Community 26 - "Community 26"
Cohesion: 0.19
Nodes (4): PayrollStatus, ShiftSetting, {closure#2}(), PayrollPaymentStatus

### Community 29 - "Community 29"
Cohesion: 0.33
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}()

### Community 34 - "Community 34"
Cohesion: 0.29
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 38 - "Community 38"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 39 - "Community 39"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 40 - "Community 40"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 51 - "Community 51"
Cohesion: 0.50
Nodes (3): portal.icon, portal.layout, work-reports.form

### Community 52 - "Community 52"
Cohesion: 0.50
Nodes (3): layouts.workspace-sidebar, portal.icon, portal.navigation

## Knowledge Gaps
- **34 isolated node(s):** `portal.icon`, `portal.layout`, `work-reports.form`, `layouts.workspace-sidebar`, `portal.icon` (+29 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 384 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **75 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `Community 24` to `Community 7`, `Community 8`, `Community 11`, `Community 14`, `Community 48`, `Community 49`, `Community 23`, `Community 30`?**
  _High betweenness centrality (0.027) - this node is a cross-community bridge._
- **What connects `portal.icon`, `portal.layout`, `work-reports.form` to the rest of the system?**
  _34 weakly-connected nodes found - possible missing edges._
- **Should `Community 0` be split into smaller, more focused modules?**
  _Cohesion score 0.0662004662004662 - nodes in this community are weakly interconnected._
- **Why does `Robotics1618Sync` connect `Community 10` to `Community 27`, `Community 23`, `Community 7`?**
  _High betweenness centrality (0.023) - this node is a cross-community bridge._
- **Should `Community 1` be split into smaller, more focused modules?**
  _Cohesion score 0.07019230769230769 - nodes in this community are weakly interconnected._
- **Why does `PayrollMigrationTest` connect `Community 11` to `Community 14`, `Community 23`?**
  _High betweenness centrality (0.020) - this node is a cross-community bridge._
- **Should `Community 2` be split into smaller, more focused modules?**
  _Cohesion score 0.06821787414066631 - nodes in this community are weakly interconnected._