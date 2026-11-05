# Graphify for AIKu Studio

Development tool: official `Graphify-Labs/graphify`, PyPI `graphifyy[sql]` 0.9.102.
Reviewed 2026-11-05. The upstream default branch is `v8` (not the old `main`).
Project-scoped Codex skill: `.codex/skills/graphify/SKILL.md`; navigation rules:
`AGENTS.md`; Laravel-specific relationships and investigation guide: `AI_CONTEXT.md`.

## Setup and refresh

Requires Python 3.10+, PHP with pdo_sqlite, Composer dependencies and `graphify`
available on PATH. No AI API key, Python web server or hosting changes required.

```powershell
python -m pip install -r requirements-graphify.txt
graphify install --project --platform codex
python scripts/graphify_project.py build
python scripts/graphify_project.py check
```

The build applies migrations only to SQLite `:memory:`, exports DDL (no rows),
extracts PHP/JavaScript/Python/SQL locally, clusters without LLM labels, and creates
a report, browser visualization and wiki. It does not call external AI APIs or
read operational records. Do not replace `--code-only` with a semantic/full scan
without first checking the scope of documents and data being indexed.

Commit graph.json, GRAPH_REPORT.md and source-hashes.json with source changes.
Caches, visualization and wiki are local generated artifacts. Build regenerates
them for a fresh checkout. There is no background watcher or automatic Git hook;
the AI instructions require refreshing after changes. Fingerprints detect source
changes, not live database drift. The SQLite DDL describes migrations, with
driver-specific MySQL behavior still defined by the PHP migrations.

## Focused queries

```powershell
graphify query "DailyWorkReportController" --budget 1200
graphify explain "PayrollCalculator"
graphify affected "ContributorAccess" --depth 2
graphify path "PayslipController" "PayrollCalculator"
```

Use `graphify-out/wiki/index.md` for broad navigation and
`graphify-out/graph.html` for interactive local browsing. Exact symbol names work
better than long broad questions. Confirm each finding in the located source
and run a targeted feature test. Graphify assists investigation; it does not
automatically find/fix every bug or guarantee a particular token reduction.
Query budgets limit retrieved context, but token savings have not been benchmarked
for this repository.

## Upstream references

- https://github.com/Graphify-Labs/graphify/tree/v8
- https://docs.graphify.com/reference/overview
- https://docs.graphify.com/reference/configuration

Recent capabilities used here: project-scoped Codex registration, local SQL/AST
extraction, incremental extraction, query budgets, impact traversal and graph
schema/version metadata. No Graphify Cloud account or paid subscription was created.
