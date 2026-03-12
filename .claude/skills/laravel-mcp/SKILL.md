---
name: laravel-mcp
description: "Maintains the project's native Laravel MCP server and tools (without Laravel Boost). Activate for MCP server registration, tool implementation, schema design, and MCP config integration across editors/agents."
license: MIT
metadata:
  author: tenanto
---

# Laravel MCP

## When to Apply

- Registering or changing MCP servers in `routes/ai.php`.
- Building MCP tools/resources/prompts under `app/Mcp`.
- Updating `.mcp.json`, `.cursor/mcp.json`, `.gemini/settings.json`, or `.codex/config.toml`.

## Project MCP Defaults

- Handle: `tenanto`
- Start command: `php artisan mcp:start tenanto`
- Inspector: `php artisan mcp:inspector tenanto`

## Design Guidelines

- Keep tool input schemas explicit and small.
- Return structured JSON responses when useful.
- Expose read-only introspection tools by default.
- Add tests for each tool/server behavior change.