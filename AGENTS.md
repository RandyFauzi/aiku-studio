## Coordination with Antigravity

Before each task, run `git pull origin main` and read `.agent/STATE.json`.
Do not edit files locked by another agent. Claim the files needed for the task
in STATE.json with active_agent CODEX, locked_files, task and an ISO updated_at.
Include generated assets/graphs in the lock when rebuilding them. Stage only
your own files; never use an indiscriminate `git add .` in this shared workspace.

Before completion, run `php vendor/bin/phpunit` and `npm run build`. Prepend a
2–3 line handover to `.agent/HANDOVER.md`, set active_agent to IDLE and
locked_files to [], then commit and immediately push to origin/main.
Preserve existing handover entries and other agents' changes.

## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

When the user types `/graphify`, use the installed graphify skill or instructions before doing anything else.

Rules:
- When graphify-out/graph.json exists, run `graphify query "<question>"` before your first grep, search or source-file read for a codebase question, and put this rule in every subagent prompt that explores code. Read raw files only for lines the graph or an earlier read already located. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- Dirty graphify-out/ files are expected after hooks or incremental updates; dirty graph files are not a reason to skip graphify. Only skip graphify if the task is about stale or incorrect graph output, or the user explicitly says not to use it.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code or migrations, run `python scripts/graphify_project.py build` to refresh the isolated schema, AST graph, report and wiki (no API cost). Before trusting an existing graph run `python scripts/graphify_project.py check`.
- A committed graphify-out/graph.json is rewritten whole on every build, so a large diff to it in a pull request is expected. Do not flag it as churn or suggest skipping the graph rebuild or its hooks.

## AIKu project rules

- Read `docs/AI_CONTEXT.md` for logical database joins, subsystem entry points, invariants and test mapping. Graph edges do not replace runtime verification or authorization checks.
- Start queries with `--budget 1200`; narrow by exact symbol before increasing the budget. Never load the entire graph JSON into the model context.
- Graphify is development-only. Do not deploy graphs, skills or developer scripts into public_html, install Python on shared hosting, or expose the graph through a public route.
- Preserve `.graphifyignore` exclusions for private data and generated dependencies. Export schema only with `scripts/graphify-schema.php`, which uses SQLite :memory:, never the operational database.
- Run the relevant existing feature tests for behavior changes; SQLite test databases must remain isolated from real data. Never import or restore production data to refresh a graph.

## Multi-Agent Co-Working (Antigravity & CODEX)

- **Start of task**: Run `git pull origin main`, read `.agent/STATE.json`. If a target file is in `locked_files` by another agent, DO NOT edit it. Otherwise, set `"active_agent"`, `"task"`, and list your `"locked_files"` in `.agent/STATE.json`.
- **End of task**: Verify with `vendor/bin/phpunit` and `npm run build`. Set `"active_agent": "IDLE"` and `"locked_files": []` in `.agent/STATE.json`. Append a concise summary in `.agent/HANDOVER.md`. Commit and `git push origin main`.
