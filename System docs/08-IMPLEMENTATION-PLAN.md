# AI Marketing Employee — Implementation Plan

## Phase 0 — Project Setup
Goal: clean runnable repository.

Tasks:
- Laravel 12 / PHP 8.3+
- PostgreSQL
- Vue 3 + TypeScript
- authentication
- API versioning
- code formatting/linting
- PHPUnit/Pest tests
- static analysis
- .env.example
- Docker/local setup if useful
- README

Definition of done:
- fresh install works
- test suite passes
- authenticated API works

## Phase 1 — Tenancy + Identity
Implement:
- users
- organizations
- memberships
- roles
- permissions
- tenant middleware
- policies
- audit logs

Do not continue until cross-tenant access tests pass.

## Phase 2 — Brand Brain
Implement:
- brands
- profiles
- products
- services
- offers
- audiences
- competitors
- assets
- knowledge documents
- document upload
- async document analysis
- Brand Brain generation/review/approval

Demo milestone:
Upload a business PDF and logo → AI creates a useful Brand Brain.

## Phase 3 — AI Strategy
Implement:
- AI provider contract
- Gemini provider
- structured schemas
- strategy agent
- content pillars
- campaigns
- monthly plan

Demo milestone:
Brand Brain → Generate Strategy → Generate 30-day calendar.

## Phase 4 — Content Studio
Implement:
- content posts
- variations
- content generation
- rewrite/regenerate
- platform variants
- Arabic/English/local tone controls
- quality agent

Demo milestone:
One click produces a polished post with caption, hook, CTA, hashtags and visual brief.

## Phase 5 — Creative Studio
Implement:
- media assets
- image provider
- image generation
- brand-aware prompts
- aspect-ratio variants
- video provider abstraction
- Reel script generation
- video generation workflow

Demo milestone:
Content → Generate Visual → branded image; Content → Create Reel → script + visual/video pipeline.

## Phase 6 — Calendar
Implement:
- month/week views
- drag/drop
- scheduling
- approval
- content statuses
- bulk generation

Demo milestone:
Generate month → review → schedule.

## Current Implementation Status

| Feature / Domain | Status | Notes |
| :--- | :--- | :--- |
| **Phase 0 — Project Setup** | ✅ Completed | Laravel 11/12, Vue 3, TS, Sanctum auth, modular domain architecture. |
| **Phase 1 — Tenancy + Identity** | ✅ Completed | Strict isolation via `TenantIsolationGuard`, 5 RBAC roles, audit logs. |
| **Phase 2 — Brand Brain** | ✅ Completed | Brand profiles, audience personas, products/services, knowledge documents. |
| **Phase 3 — AI Strategy & Providers** | ✅ Completed | Real `GeminiAIProvider` with structured schema generation and deterministic fallback. |
| **Phase 4 — Content Studio** | ✅ Completed | AI Copywriting with dialect options (Saudi, Egyptian, MSA, EN), quality checks. |
| **Phase 5 — Creative Studio** | ✅ Completed | Visual briefs, image generation schemas, aspect ratio mappings. |
| **Phase 6 — Calendar & Scheduling** | ✅ Completed | Scheduling posts, queue jobs, lifecycle transitions. |
| **Phase 7 & 8 — Social Integrations & Publishing** | ✅ Completed | **LinkedIn live OAuth 2.0 & UGC API**. Other 4 networks clearly marked as STUB. Plan connection limits enforced. |
| **Phase 9 — Analytics & KPIs** | ✅ Completed | Metric aggregation, normalized post metrics. |
| **Phase 11 — SaaS, Billing & Super Admin** | ✅ Completed | Plans (Starter, Growth, Pro), entitlement enforcement, Super Admin KPIs, company impersonation. |

## Phase 9 — Analytics + Learning
Implement:
- metric sync
- normalized metrics
- dashboards
- top content
- AI analytics agent
- optimization recommendations

Demo milestone:
Published content → metrics → AI explains performance → recommendation appears.

## Phase 10 — AI Assistant
Implement:
- conversational UI
- function calling
- safe action execution
- confirmation rules
- generated resource references

Demo:
"Create a 7-day campaign for our new offer."

## Phase 11 — SaaS + Agency
Implement:
- plans
- quotas
- usage
- subscription abstraction
- agency client management
- organization switching
- admin dashboard
- AI cost dashboard

## Phase 12 — Production Hardening
- security review
- dependency updates
- rate limiting
- queue monitoring
- backup strategy
- logging
- alerting
- privacy controls
- data export/delete
- API provider compliance review
- load testing
- end-to-end tests

## Development Rules for Antigravity
1. Never rewrite working modules unnecessarily.
2. Inspect the existing codebase before creating files.
3. Work one phase at a time.
4. Run tests after each meaningful change.
5. Fix errors before moving on.
6. Do not fake external API integrations.
7. Use official API documentation for each social provider.
8. Keep AI providers replaceable.
9. Keep platform-specific publishing code isolated.
10. Never store secrets in source control.
11. Never log access tokens.
12. Every autonomous action must be auditable.
13. Use database transactions for multi-record state changes.
14. Use idempotency for external side effects.
15. Prefer explicit typed DTOs/schemas over arbitrary arrays at domain boundaries.

## First Antigravity Task
Before writing feature code:
- inspect repository
- confirm runtime versions
- initialize architecture
- create domains/contracts
- create migrations
- configure authentication and tenancy
- create tests
- create a health endpoint
- document local setup

Then proceed to Phase 1.
