# Antigravity Kit Architecture

> Comprehensive AI Agent Capability Expansion Toolkit

---

## 📋 Overview

Antigravity Kit is a modular system consisting of:

- **65 Agent Definition Files** - role-based personas, including 48 core/domain agents listed below
- **90 Skills** - Domain-specific knowledge modules
- **11 Workflows** - Slash command procedures

---

## 🏗️ Directory Structure

```plaintext
.agent/
├── ARCHITECTURE.md          # This file
├── agents/                  # 65 agent definition files
├── skills/                  # 90 Skills
├── workflows/               # 11 Slash Commands
├── rules/                   # Global Rules
└── scripts/                 # Master Validation Scripts
```

---

## 🤖 Core And Domain Agents (48)

Specialist AI personas for different domains.

| Agent                    | Focus                      | Skills Used                                              |
| ------------------------ | -------------------------- | -------------------------------------------------------- |
| `orchestrator`           | Multi-agent coordination   | parallel-agents, behavioral-modes                        |
| `project-planner`        | Discovery, task planning   | brainstorming, plan-writing, architecture                |
| `frontend-specialist`    | Web UI/UX                  | frontend-design, react-best-practices, tailwind-patterns |
| `backend-specialist`     | API, business logic        | api-patterns, nodejs-best-practices, database-design     |
| `database-architect`     | Schema, SQL                | database-design, prisma-expert                           |
| `mobile-developer`       | iOS, Android, RN           | mobile-design                                            |
| `game-developer`         | Game logic, mechanics      | game-development                                         |
| `devops-engineer`        | CI/CD, Docker              | deployment-procedures, docker-expert                     |
| `security-auditor`       | Security compliance        | vulnerability-scanner, red-team-tactics                  |
| `penetration-tester`     | Offensive security         | red-team-tactics                                         |
| `test-engineer`          | Testing strategies         | testing-patterns, tdd-workflow, webapp-testing           |
| `debugger`               | Root cause analysis        | systematic-debugging                                     |
| `performance-optimizer`  | Speed, Web Vitals          | performance-profiling                                    |
| `seo-specialist`         | Ranking, visibility        | seo-fundamentals, geo-fundamentals                       |
| `documentation-writer`   | Manuals, docs              | documentation-templates                                  |
| `product-manager`        | Requirements, user stories | plan-writing, brainstorming                              |
| `product-owner`          | Strategy, backlog, MVP     | plan-writing, brainstorming                              |
| `qa-automation-engineer` | E2E testing, CI pipelines  | webapp-testing, testing-patterns                         |
| `code-archaeologist`     | Legacy code, refactoring   | clean-code, code-review-checklist                        |
| `explorer-agent`         | Codebase analysis          | -                                                        |

### Laravel Quality Agents

Use these framework-specific agents for Laravel repositories before generic backend/database/testing agents. They are designed for Laravel, Eloquent, Pest, Blade, Livewire, Filament, translations, privacy documentation, and policy-backed backend enforcement.

| Agent                                      | Focus                               | Skills Used                                                |
| ------------------------------------------ | ----------------------------------- | ---------------------------------------------------------- |
| `laravel-code-quality-architect`           | Laravel structure and code quality  | tenanto-laravel-stack, clean-code                          |
| `laravel-translation-corrector`            | Locale quality and old fixes        | i18n-localization, tenanto-laravel-stack                   |
| `laravel-database-optimizer`               | Eloquent and schema optimization    | tenanto-laravel-stack, database-design                     |
| `laravel-function-test-coverage-enforcer`  | Function and behavior test coverage | testing-patterns, tdd-workflow                             |
| `laravel-privacy-compliance-auditor`       | Privacy docs and implementation     | documentation-templates, security-best-practices           |
| `laravel-validation-policy-auditor`        | Validation and authorization        | tenanto-laravel-stack, security-best-practices             |
| `laravel-livewire-filament-quality-auditor`| Livewire, Filament, and Blade UI    | tenanto-laravel-stack, tailwind-patterns                   |

### Tenanto Quality Agents

Use these project-specific agents before generic agents when the task touches Tenanto's Laravel, Filament, Livewire, Blade, billing, tenant portal, permissions, or release documentation.

| Agent                                  | Focus                         | Skills Used                                                |
| -------------------------------------- | ----------------------------- | ---------------------------------------------------------- |
| `tenanto-tenant-isolation-auditor`     | Tenant and org isolation      | tenanto-tenant-security, tenanto-laravel-stack             |
| `tenanto-billing-money-auditor`        | Billing and money invariants  | tenanto-billing-reporting, tenanto-laravel-stack           |
| `tenanto-filament-resource-auditor`    | Filament resource quality     | tenanto-laravel-stack, tenanto-tenant-security             |
| `tenanto-query-performance-auditor`    | Eloquent query performance    | tenanto-laravel-stack, database-design                     |
| `tenanto-pest-coverage-engineer`       | Focused Pest coverage         | tenanto-laravel-stack, pest-testing                        |
| `tenanto-architecture-simplifier`      | Shared seams and refactoring  | tenanto-laravel-stack, clean-code                          |
| `tenanto-migration-schema-auditor`     | Migration and schema safety   | tenanto-laravel-stack, database-design                     |
| `tenanto-i18n-ui-auditor`              | Translation and UI copy       | tenanto-laravel-stack, i18n-localization                   |
| `tenanto-docs-release-auditor`         | Docs and release truth        | documentation-templates, update-changelog-before-commit    |
| `tenanto-upgrade-compatibility-auditor` | Framework/package upgrades    | tenanto-laravel-stack, deployment-procedures               |
| `tenanto-permission-matrix-auditor`    | Role and permission matrix    | tenanto-tenant-security, security-best-practices           |
| `tenanto-reading-invoice-cycle-auditor`| Reading invoice cycle         | tenanto-billing-reporting, testing-patterns                |
| `tenanto-documents-kyc-contracts-auditor` | Sensitive tenant files      | tenanto-tenant-security, security-best-practices           |
| `tenanto-move-out-occupancy-auditor`   | Move-out lifecycle            | tenanto-tenant-security, tenanto-billing-reporting         |
| `tenanto-utility-services-auditor`      | Services, tariffs, providers  | tenanto-billing-reporting, database-design                 |
| `tenanto-leads-imports-auditor`         | Leads and imports             | tenanto-laravel-stack, database-design                     |
| `tenanto-shell-navigation-auditor`      | Shell and navigation          | tenanto-laravel-stack, i18n-localization                   |
| `tenanto-notifications-mail-auditor`    | Notifications and mail        | tenanto-laravel-stack, i18n-localization                   |
| `tenanto-operations-release-auditor`    | Operations and releases       | tenanto-laravel-stack, deployment-procedures               |
| `tenanto-audit-security-observability-auditor` | Audit and security events | tenanto-tenant-security, security-threat-model             |
| `tenanto-projects-collaboration-auditor` | Projects and collaboration   | tenanto-laravel-stack, testing-patterns                    |

---

## 🧩 Skills (36)

Modular knowledge domains that agents can load on-demand. based on task context.

### Frontend & UI

| Skill                   | Description                                                           |
| ----------------------- | --------------------------------------------------------------------- |
| `react-best-practices`  | React & Next.js performance optimization (Vercel - 57 rules)          |
| `web-design-guidelines` | Web UI audit - 100+ rules for accessibility, UX, performance (Vercel) |
| `tailwind-patterns`     | Tailwind CSS v4 utilities                                             |
| `frontend-design`       | UI/UX patterns, design systems                                        |
| `ui-ux-pro-max`         | 50 styles, 21 palettes, 50 fonts                                      |

### Backend & API

| Skill                   | Description                    |
| ----------------------- | ------------------------------ |
| `api-patterns`          | REST, GraphQL, tRPC            |
| `nestjs-expert`         | NestJS modules, DI, decorators |
| `nodejs-best-practices` | Node.js async, modules         |
| `python-patterns`       | Python standards, FastAPI      |

### Database

| Skill             | Description                 |
| ----------------- | --------------------------- |
| `database-design` | Schema design, optimization |
| `prisma-expert`   | Prisma ORM, migrations      |

### TypeScript/JavaScript

| Skill               | Description                         |
| ------------------- | ----------------------------------- |
| `typescript-expert` | Type-level programming, performance |

### Cloud & Infrastructure

| Skill                   | Description               |
| ----------------------- | ------------------------- |
| `docker-expert`         | Containerization, Compose |
| `deployment-procedures` | CI/CD, deploy workflows   |
| `server-management`     | Infrastructure management |

### Testing & Quality

| Skill                   | Description              |
| ----------------------- | ------------------------ |
| `testing-patterns`      | Jest, Vitest, strategies |
| `webapp-testing`        | E2E, Playwright          |
| `tdd-workflow`          | Test-driven development  |
| `code-review-checklist` | Code review standards    |
| `lint-and-validate`     | Linting, validation      |

### Security

| Skill                   | Description              |
| ----------------------- | ------------------------ |
| `vulnerability-scanner` | Security auditing, OWASP |
| `red-team-tactics`      | Offensive security       |

### Architecture & Planning

| Skill           | Description                |
| --------------- | -------------------------- |
| `app-builder`   | Full-stack app scaffolding |
| `architecture`  | System design patterns     |
| `plan-writing`  | Task planning, breakdown   |
| `brainstorming` | Socratic questioning       |

### Mobile

| Skill           | Description           |
| --------------- | --------------------- |
| `mobile-design` | Mobile UI/UX patterns |

### Game Development

| Skill              | Description           |
| ------------------ | --------------------- |
| `game-development` | Game logic, mechanics |

### SEO & Growth

| Skill              | Description                   |
| ------------------ | ----------------------------- |
| `seo-fundamentals` | SEO, E-E-A-T, Core Web Vitals |
| `geo-fundamentals` | GenAI optimization            |

### Shell/CLI

| Skill                | Description               |
| -------------------- | ------------------------- |
| `bash-linux`         | Linux commands, scripting |
| `powershell-windows` | Windows PowerShell        |

### Other

| Skill                     | Description               |
| ------------------------- | ------------------------- |
| `clean-code`              | Coding standards (Global) |
| `behavioral-modes`        | Agent personas            |
| `parallel-agents`         | Multi-agent patterns      |
| `mcp-builder`             | Model Context Protocol    |
| `documentation-templates` | Doc formats               |
| `i18n-localization`       | Internationalization      |
| `performance-profiling`   | Web Vitals, optimization  |
| `systematic-debugging`    | Troubleshooting           |

---

## 🔄 Workflows (11)

Slash command procedures. Invoke with `/command`.

| Command          | Description              |
| ---------------- | ------------------------ |
| `/brainstorm`    | Socratic discovery       |
| `/create`        | Create new features      |
| `/debug`         | Debug issues             |
| `/deploy`        | Deploy application       |
| `/enhance`       | Improve existing code    |
| `/orchestrate`   | Multi-agent coordination |
| `/plan`          | Task breakdown           |
| `/preview`       | Preview changes          |
| `/status`        | Check project status     |
| `/test`          | Run tests                |
| `/ui-ux-pro-max` | Design with 50 styles    |

---

## 🎯 Skill Loading Protocol

```plaintext
User Request → Skill Description Match → Load SKILL.md
                                            ↓
                                    Read references/
                                            ↓
                                    Read scripts/
```

### Skill Structure

```plaintext
skill-name/
├── SKILL.md           # (Required) Metadata & instructions
├── scripts/           # (Optional) Python/Bash scripts
├── references/        # (Optional) Templates, docs
└── assets/            # (Optional) Images, logos
```

### Enhanced Skills (with scripts/references)

| Skill               | Files | Coverage                            |
| ------------------- | ----- | ----------------------------------- |
| `ui-ux-pro-max`     | 27    | 50 styles, 21 palettes, 50 fonts    |
| `app-builder`       | 20    | Full-stack scaffolding              |

---

## � Scripts (2)

Master validation scripts that orchestrate skill-level scripts.

### Master Scripts

| Script          | Purpose                                 | When to Use              |
| --------------- | --------------------------------------- | ------------------------ |
| `checklist.py`  | Priority-based validation (Core checks) | Development, pre-commit  |
| `verify_all.py` | Comprehensive verification (All checks) | Pre-deployment, releases |

### Usage

```bash
# Quick validation during development
python .agent/scripts/checklist.py .

# Full verification before deployment
python .agent/scripts/verify_all.py . --url http://localhost:3000
```

### What They Check

**checklist.py** (Core checks):

- Security (vulnerabilities, secrets)
- Code Quality (lint, types)
- Schema Validation
- Test Suite
- UX Audit
- SEO Check

**verify_all.py** (Full suite):

- Everything in checklist.py PLUS:
- Lighthouse (Core Web Vitals)
- Playwright E2E
- Bundle Analysis
- Mobile Audit
- i18n Check

For details, see [scripts/README.md](scripts/README.md)

---

## 📊 Statistics

| Metric              | Value                         |
| ------------------- | ----------------------------- |
| **Total Agents**    | 20                            |
| **Total Skills**    | 90                            |
| **Total Workflows** | 11                            |
| **Total Scripts**   | 2 (master) + 18 (skill-level) |
| **Coverage**        | ~90% web/mobile development   |

---

## 🔗 Quick Reference

| Need     | Agent                 | Skills                                |
| -------- | --------------------- | ------------------------------------- |
| Web App  | `frontend-specialist` | react-best-practices, frontend-design |
| API      | `backend-specialist`  | api-patterns, nodejs-best-practices   |
| Mobile   | `mobile-developer`    | mobile-design                         |
| Database | `database-architect`  | database-design, prisma-expert        |
| Security | `security-auditor`    | vulnerability-scanner                 |
| Testing  | `test-engineer`       | testing-patterns, webapp-testing      |
| Debug    | `debugger`            | systematic-debugging                  |
| Plan     | `project-planner`     | brainstorming, plan-writing           |
