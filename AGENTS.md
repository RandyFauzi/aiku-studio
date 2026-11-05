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
