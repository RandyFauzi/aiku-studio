# Graph Report - AIKU STUDIO  (2026-11-05)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 843 nodes · 1460 edges · 86 communities (21 shown, 65 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 30 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `c25c4ad5`
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
- Community 38
- Community 39
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

## God Nodes (most connected - your core abstractions)
1. `User` - 31 edges
2. `Contributor` - 25 edges
3. `TestCase` - 22 edges
4. `PayrollCalculator` - 22 edges
5. `PayrollMigrationTest` - 19 edges
6. `PayslipController` - 19 edges
7. `AtlasSync` - 18 edges
8. `AttendanceQrWifiTest` - 17 edges
9. `Robotics1618Sync` - 17 edges
10. `Payslip` - 16 edges

## Surprising Connections (you probably didn't know these)
- `Robotics1618SyncTest` --references--> `User`  [EXTRACTED]
  tests/Feature/Robotics1618SyncTest.php → app/Models/User.php
- `DeviceAccountTest` --references--> `User`  [EXTRACTED]
  tests/Feature/DeviceAccountTest.php → app/Models/User.php
- `AuthController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/AuthController.php → app/Http/Controllers/Controller.php
- `DeviceAccountController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/DeviceAccountController.php → app/Http/Controllers/Controller.php
- `AttendanceCalendarTest` --inherits--> `TestCase`  [EXTRACTED]
  tests/Feature/AttendanceCalendarTest.php → tests/TestCase.php

## Import Cycles
- None detected.

## Communities (86 total, 65 thin omitted)

### Community 0 - "Community 0"
Cohesion: 0.05
Nodes (27): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}() (+19 more)

### Community 1 - "Community 1"
Cohesion: 0.06
Nodes (51): "attendances", attendances_contributor_id_index, attendances_user_id_index, attendances_user_id_work_date_unique, attendances_work_date_index, "cache", cache_expiration_index, "cache_locks" (+43 more)

### Community 2 - "Community 2"
Cohesion: 0.10
Nodes (7): PayrollController, {closure#1}(), PayslipController, Payslip, PayrollCalculator, PayrollPeriod, PayslipPdf

### Community 3 - "Community 3"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 4 - "Community 4"
Cohesion: 0.07
Nodes (21): checkIn(), checkInWithQr(), closeScanner(), handleQrScanned(), init(), load(), loadSlips(), onTimerFinished() (+13 more)

### Community 5 - "Community 5"
Cohesion: 0.09
Nodes (30): dependencies, alpinejs@^3.17.5, html5-qrcode@^2.3.8, qrcode@^1.5.4, sweetalert2@^11.26.25, devDependencies, autoprefixer@^10.6.2, axios@^1.11.0 (+22 more)

### Community 6 - "Community 6"
Cohesion: 0.08
Nodes (6): AtlasTick, CreatePanelAdmin, ImportLegacyPayroll, SyncRobotics1618, VerifyLegacyPayroll, PayrollFiles

### Community 7 - "Community 7"
Cohesion: 0.15
Nodes (5): SyncController, AtlasSync, {closure#1}(), {closure#2}(), Robotics1618Sync

### Community 8 - "Community 8"
Cohesion: 0.09
Nodes (7): attachCalendar(), conflicts(), dailyHours(), hours(), peopleAt(), production(), saveConfig()

### Community 9 - "Community 9"
Cohesion: 0.11
Nodes (11): assignCandidates(), dateLabel(), init(), labels, load(), phoneDurationsText(), phoneNames(), reload() (+3 more)

### Community 10 - "Community 10"
Cohesion: 0.11
Nodes (3): AuthController, EnsureActiveAccount, EnsureAdministrator

### Community 11 - "Community 11"
Cohesion: 0.19
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), DeviceAccountController, DeviceAccount

### Community 12 - "Community 12"
Cohesion: 0.16
Nodes (4): ExampleTest, Robotics1618SyncTest, ShiftRosterTest, TestCase

### Community 13 - "Community 13"
Cohesion: 0.12
Nodes (11): credentials, legacyRequire, puppeteer, report, root, {createRequire}, fs, mysql (+3 more)

### Community 14 - "Community 14"
Cohesion: 0.17
Nodes (7): drawQr(), init(), load(), loadConfig(), openModal(), renderQrCode(), saveSettings()

### Community 16 - "Community 16"
Cohesion: 0.22
Nodes (3): fingerprints(), main(), run()

### Community 19 - "Community 19"
Cohesion: 0.25
Nodes (5): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), ContributorController

### Community 20 - "Community 20"
Cohesion: 0.23
Nodes (4): {closure#1}(), SyncContributorLogins, Contributor, ContributorAccess

### Community 23 - "Community 23"
Cohesion: 0.22
Nodes (3): {closure#2}(), ShiftDay, ShiftConfiguration

### Community 25 - "Community 25"
Cohesion: 0.27
Nodes (3): DailyWorkReport, PayrollStatus, ShiftSetting

### Community 32 - "Community 32"
Cohesion: 0.25
Nodes (3): Controller, RecordingController, ShiftDayController

## Knowledge Gaps
- **20 isolated node(s):** `attendance.admin`, `layouts.sidebar`, `layouts.sidebar`, `shifts.workspace`, `layouts.sidebar` (+15 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 307 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **65 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `Community 29` to `Community 33`, `Community 34`, `Community 37`, `Community 6`, `Community 41`, `Community 42`, `Community 43`, `Community 11`, `Community 12`, `Community 17`, `Community 18`, `Community 19`, `Community 20`, `Community 24`, `Community 26`, `Community 30`, `Community 31`?**
  _High betweenness centrality (0.074) - this node is a cross-community bridge._
- **What connects `attendance.admin`, `layouts.sidebar`, `layouts.sidebar` to the rest of the system?**
  _20 weakly-connected nodes found - possible missing edges._
- **Should `Community 0` be split into smaller, more focused modules?**
  _Cohesion score 0.054563492063492064 - nodes in this community are weakly interconnected._
- **Why does `Contributor` connect `Community 20` to `Community 2`, `Community 34`, `Community 37`, `Community 43`, `Community 18`, `Community 19`, `Community 21`, `Community 22`, `Community 23`, `Community 24`, `Community 25`, `Community 30`?**
  _High betweenness centrality (0.027) - this node is a cross-community bridge._
- **Should `Community 1` be split into smaller, more focused modules?**
  _Cohesion score 0.06259426847662142 - nodes in this community are weakly interconnected._
- **Why does `PayrollMigrationTest` connect `Community 15` to `Community 24`, `Community 12`?**
  _High betweenness centrality (0.022) - this node is a cross-community bridge._
- **Should `Community 2` be split into smaller, more focused modules?**
  _Cohesion score 0.0977891156462585 - nodes in this community are weakly interconnected._