# AI Marketing Employee — Architecture

## 1. Architecture Principles
- Modular monolith first; no premature microservices.
- Domain-oriented Laravel structure.
- AI and social integrations behind contracts.
- Queue all expensive/slow work.
- Database is source of truth.
- Every autonomous action is traceable.
- Tenant context is mandatory for business data access.

## 2. High-Level Architecture

Frontend
  ↓
Laravel API
  ├── Identity
  ├── Tenancy
  ├── Brand
  ├── Strategy
  ├── Content
  ├── Creative
  ├── Publishing
  ├── Analytics
  ├── AI
  ├── Billing
  └── Administration
       ↓
AI Provider Layer → Gemini provider
Social Provider Layer → Facebook / Instagram / LinkedIn / YouTube adapters
Storage Provider → Google Cloud Storage
Queue → Database initially; Redis later if required
Scheduler → Laravel Scheduler / Cloud Scheduler as deployment evolves

## 3. Recommended Stack
Backend:
- Laravel 12
- PHP 8.3+
- PostgreSQL
- Laravel Sanctum
- Laravel Queues
- Laravel Scheduler

Frontend:
- Vue 3 + TypeScript preferred
- Tailwind CSS or equivalent design system

Google-first:
- Gemini API
- Google Cloud Storage
- Cloud Run
- Secret Manager
- Firebase where useful
- Cloud Tasks/Scheduler when operational scale requires it

## 4. Domain Modules
app/Domains/
- Identity
- Tenancy
- Brand
- Strategy
- Content
- Creative
- Publishing
- Analytics
- AI
- Billing
- Administration

## 5. AI Layer
app/AI/
- Contracts/
- Providers/Gemini/
- Agents/
- Tools/
- Prompts/
- Schemas/

AI must return validated structured data wherever possible.

## 6. Social Layer
app/Social/
- Contracts/
- Facebook/
- Instagram/
- LinkedIn/
- YouTube/
- Common/

Every provider implements a common publisher contract and metric-sync contract.

## 7. Security Boundaries
- Tenant middleware establishes current organization.
- Policies verify resource ownership.
- OAuth secrets encrypted at rest.
- Secrets stored in environment/secret manager, not database plaintext.
- Uploaded files are treated as untrusted data.
- Prompt injection from documents/web pages must not override system instructions.
- Webhook signatures must be verified.
- Publishing requests require idempotency keys.

## 8. Async Jobs
- AnalyzeBrandDocumentJob
- BuildBrandBrainJob
- GenerateStrategyJob
- GenerateContentPlanJob
- GenerateContentJob
- GenerateCreativeJob
- RunQualityCheckJob
- PublishScheduledPostJob
- SyncSocialMetricsJob
- AnalyzePerformanceJob
- GenerateOptimizationRecommendationsJob

## 9. Observability
Track:
- request IDs
- organization ID
- job ID
- provider
- model
- token/usage estimates where available
- generation latency
- failure reason
- external platform IDs
- publishing attempt number

Do not log access tokens, secrets, or customer-sensitive content unnecessarily.
