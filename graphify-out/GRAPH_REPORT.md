# Graph Report - AIKU STUDIO  (2026-11-05)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 922 nodes · 1594 edges · 93 communities (22 shown, 71 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 31 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `0a551eb0`
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
- Community 64
- Community 65
- Community 66

## God Nodes (most connected - your core abstractions)
1. `SystemAuditRegressionTest` - 28 edges
2. `User` - 23 edges
3. `PayslipController` - 19 edges
4. `PayrollMigrationTest` - 19 edges
5. `TestCase` - 18 edges
6. `Robotics1618Sync` - 18 edges
7. `AttendanceQrWifiTest` - 17 edges
8. `AttendanceController` - 17 edges
9. `PayrollPeriod` - 14 edges
10. `AtlasSync` - 14 edges

## Surprising Connections (you probably didn't know these)
- `Robotics1618SyncTest` --references--> `User`  [EXTRACTED]
  tests/Feature/Robotics1618SyncTest.php → app/Models/User.php
- `DeviceAccountTest` --references--> `User`  [EXTRACTED]
  tests/Feature/DeviceAccountTest.php → app/Models/User.php
- `ContributorPayslipTest` --inherits--> `TestCase`  [EXTRACTED]
  tests/Feature/ContributorPayslipTest.php → tests/TestCase.php
- `ContributorRoleManagementTest` --inherits--> `TestCase`  [EXTRACTED]
  tests/Feature/ContributorRoleManagementTest.php → tests/TestCase.php
- `DailyWorkReportTest` --inherits--> `TestCase`  [EXTRACTED]
  tests/Feature/DailyWorkReportTest.php → tests/TestCase.php

## Import Cycles
- None detected.

## Communities (93 total, 71 thin omitted)

### Community 0 - "Community 0"
Cohesion: 0.05
Nodes (27): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}() (+19 more)

### Community 1 - "Community 1"
Cohesion: 0.05
Nodes (10): AtlasTick, {closure#2}(), Attendance, PayrollStatus, ShiftDay, ShiftSetting, StudioAccount, StudioRecording (+2 more)

### Community 2 - "Community 2"
Cohesion: 0.08
Nodes (10): PayrollController, {closure#1}(), {closure#2}(), PayslipController, Payslip, {closure#2}(), PayrollCalculator, PayrollPaymentStatus (+2 more)

### Community 3 - "Community 3"
Cohesion: 0.06
Nodes (51): "attendances", attendances_contributor_id_index, attendances_user_id_index, attendances_user_id_work_date_unique, attendances_work_date_index, "cache", cache_expiration_index, "cache_locks" (+43 more)

### Community 4 - "Community 4"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 5 - "Community 5"
Cohesion: 0.07
Nodes (22): checkIn(), checkInWithQr(), closeScanner(), handleQrScanned(), init(), load(), loadReportStatus(), loadSlips() (+14 more)

### Community 6 - "Community 6"
Cohesion: 0.06
Nodes (6): AuditDataIntegrity, CreatePanelAdmin, ImportLegacyPayroll, SyncRobotics1618, VerifyLegacyPayroll, PayrollFiles

### Community 7 - "Community 7"
Cohesion: 0.14
Nodes (5): SyncController, AtlasSync, {closure#1}(), {closure#2}(), Robotics1618Sync

### Community 9 - "Community 9"
Cohesion: 0.09
Nodes (7): attachCalendar(), conflicts(), dailyHours(), hours(), peopleAt(), production(), saveConfig()

### Community 11 - "Community 11"
Cohesion: 0.11
Nodes (11): assignCandidates(), dateLabel(), init(), labels, load(), phoneDurationsText(), phoneNames(), reload() (+3 more)

### Community 12 - "Community 12"
Cohesion: 0.13
Nodes (18): devDependencies, autoprefixer@^10.6.2, axios@^1.11.0, concurrently@^9.0.1, laravel-vite-plugin@^2.0.0, postcss@^8.5.32, tailwindcss@^3.4.19, vite@^7.0.7 (+10 more)

### Community 13 - "Community 13"
Cohesion: 0.16
Nodes (4): ExampleTest, Robotics1618SyncTest, ShiftRosterTest, TestCase

### Community 14 - "Community 14"
Cohesion: 0.12
Nodes (11): credentials, legacyRequire, puppeteer, report, root, {createRequire}, fs, mysql (+3 more)

### Community 15 - "Community 15"
Cohesion: 0.26
Nodes (4): {closure#2}(), {closure#3}(), {closure#4}(), DeviceAccountController

### Community 16 - "Community 16"
Cohesion: 0.17
Nodes (7): drawQr(), init(), load(), loadConfig(), openModal(), renderQrCode(), saveSettings()

### Community 17 - "Community 17"
Cohesion: 0.24
Nodes (5): {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), ContributorController

### Community 18 - "Community 18"
Cohesion: 0.22
Nodes (3): fingerprints(), main(), run()

### Community 20 - "Community 20"
Cohesion: 0.14
Nodes (3): Controller, SettingController, ShiftDayController

### Community 24 - "Community 24"
Cohesion: 0.20
Nodes (3): AuthController, EnsureActiveAccount, EnsureAdministrator

### Community 26 - "Community 26"
Cohesion: 0.31
Nodes (4): {closure#1}(), SyncContributorLogins, Contributor, ContributorAccess

### Community 28 - "Community 28"
Cohesion: 0.27
Nodes (9): dependencies, alpinejs@^3.17.5, html5-qrcode@^2.3.8, qrcode@^1.5.4, sweetalert2@^11.26.25, alpinejs, html5-qrcode, qrcode (+1 more)

### Community 44 - "Community 44"
Cohesion: 0.67
Nodes (3): scripts, build, dev

## Knowledge Gaps
- **31 isolated node(s):** `attendance.admin`, `layouts.sidebar`, `portal.navigation`, `portal.icon`, `layouts.sidebar` (+26 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 355 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **71 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `Community 25` to `Community 1`, `Community 2`, `Community 33`, `Community 35`, `Community 6`, `Community 39`, `Community 40`, `Community 8`, `Community 13`, `Community 26`, `Community 29`, `Community 30`, `Community 31`?**
  _High betweenness centrality (0.034) - this node is a cross-community bridge._
- **What connects `attendance.admin`, `layouts.sidebar`, `portal.navigation` to the rest of the system?**
  _31 weakly-connected nodes found - possible missing edges._
- **Should `Community 0` be split into smaller, more focused modules?**
  _Cohesion score 0.054563492063492064 - nodes in this community are weakly interconnected._
- **Why does `SystemAuditRegressionTest` connect `Community 10` to `Community 1`, `Community 13`?**
  _High betweenness centrality (0.024) - this node is a cross-community bridge._
- **Should `Community 1` be split into smaller, more focused modules?**
  _Cohesion score 0.05384150030248034 - nodes in this community are weakly interconnected._
- **Why does `PayrollMigrationTest` connect `Community 8` to `Community 13`?**
  _High betweenness centrality (0.020) - this node is a cross-community bridge._
- **Should `Community 2` be split into smaller, more focused modules?**
  _Cohesion score 0.07622504537205081 - nodes in this community are weakly interconnected._