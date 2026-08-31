# Marketly-AI — Autonomous AI Marketing SaaS Platform

> **Production-ready, multi-tenant AI-native SaaS platform** that plans, creates, schedules, publishes, analyzes, and optimizes multi-platform marketing content across Meta (Facebook & Instagram), LinkedIn, YouTube, TikTok, and X.

---

## 🌟 Product Vision & Capabilities

Marketly-AI operates as an autonomous, multi-tenant AI marketing department for businesses and marketing agencies:
```
Organization Onboarding → Brand Brain → AI Strategy (Gemini) → Content Studio → Creative Studio → Calendar → Publishing Engine (LinkedIn live) → Analytics & Learning → Super Admin Governance
```

### Core Value Pillars
1. **Multi-Tenant SaaS with Plan Entitlements**:
   - **Starter Plan**: Free trial / Solopreneurs (0 social connections, 30 AI posts/month, 5 strategies/month).
   - **Growth Plan**: Scale-ups (5 connected social channels, 150 AI posts/month, 20 strategies/month, full analytics).
   - **Pro Plan**: Agencies & Enterprises (Unlimited social channels, unlimited AI generations, team collaboration, automation).
2. **Real AI Provider Integration**:
   - Primary engine powered by **Google Gemini REST API** (`GeminiAIProvider`) via `AIProviderInterface`.
   - Generates authentic Arabic (Saudi, Egyptian, Gulf, MSA) and English marketing copy with brand voice alignment and deterministic fallback protection.
3. **Real Social Publishing Engine**:
   - **LinkedIn**: Live OAuth 2.0 handshake, UserInfo profile extraction, and UGC Post publishing API (`LinkedInPublisherAdapter`).
   - **Facebook, Instagram, TikTok, X**: Architectural STUB implementations ready for app client credential pairing.
4. **Super Admin Platform Governance**:
   - Global KPIs (MRR, total organizations, active subscriptions, revenue by plan).
   - 1-Click "Login as Company" impersonation.
   - Subscription plan switching and company status moderation.
   - Real-time visibility into each organization's connected channels limit (`connected / limit`) and monthly AI quota consumption (`used / limit`).

---

## 🛠️ Architecture & Tech Stack

### Backend
- **Framework**: Laravel 11 / 12 (PHP 8.2+)
- **Architecture**: Modular Domain Monolith (`app/Domains/`)
- **Authentication**: Laravel Sanctum (Bearer Token & Stateful API)
- **Database**: SQLite (local & testing) / PostgreSQL (production) with strict multi-tenant isolation
- **AI Contracts**: `AIProviderInterface` with `GeminiAIProvider` registered as the container singleton.
- **Social Contracts**: `SocialPublisherInterface` with `LinkedInPublisherAdapter` implementing live OAuth 2.0 + UGC Posting.
- **Security & Isolation**: `TenantIsolationGuard` checking tenant ownership and RBAC permissions (`owner`, `admin`, `manager`, `editor`, `viewer`).

### Frontend
- **Framework**: Vue 3 (Composition API) + TypeScript
- **Tooling**: Vite + Tailwind CSS
- **Design System**: Spatial 3D Lighting, Glassmorphism, Dark/Light modes, and full bilingual support (English LTR & Arabic RTL with Cairo font).

---

## 📁 Repository Structure

```text
app/
├── Domains/
│   ├── Identity/         # User auth, credentials & sessions
│   ├── Tenancy/          # Multi-tenant organizations & isolation
│   ├── Brand/            # Brand Brain, profiles, audiences, products
│   ├── Strategy/         # AI strategy generator, pillars, campaigns
│   ├── Content/          # Content generator agent, hooks, CTAs, quality checks
│   ├── Creative/         # Image prompts, aspect ratios, video briefs
│   ├── Publishing/       # Social accounts, OAuth handshakes, publishing jobs
│   ├── Analytics/        # Normalized metrics & AI performance insights
│   ├── AI/               # AI prompt schemas & cost tracking
│   ├── Billing/          # Subscriptions, plan entitlements & limits
│   └── Administration/   # Super Admin governance, KPIs & impersonation
│
├── AI/
│   ├── Contracts/        # AIProviderInterface, DTOs (AIStructuredOutput, GenerationUsage)
│   └── Providers/        # GeminiAIProvider (Google Gemini 2.0 Flash / REST API)
│
├── Social/
│   └── Contracts/        # SocialPublisherInterface, DTOs (PublishPayload, PublishResult)
│
└── Support/
    └── ApiResponse.php   # Standard API envelope { data, meta } / { message, code, errors }
```

---

## 🚀 Environment Configuration

Add the following environment variables to your `.env`:

```env
APP_NAME=Marketly-AI
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

# Google Gemini AI Configuration
GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_MODEL=gemini-2.0-flash

# LinkedIn Developer App Configuration
LINKEDIN_CLIENT_ID=your_linkedin_client_id
LINKEDIN_CLIENT_SECRET=your_linkedin_client_secret
LINKEDIN_REDIRECT_URI=http://127.0.0.1:8000/api/v1/social/oauth/linkedin/callback
```

---

## 🧪 Testing & Verification

Run the entire automated test suite:
```bash
php artisan test
```

Default credentials seeded:
- **Super Admin**: `admin@marketly.ai` / `Password123!`
- **Test Org Admin**: Auto-created on organization registration or onboarding.

---

## 🗺️ Implementation Status

- [x] **Multi-Tenant Architecture & RBAC**: Tenant context, isolation guard, 5 system roles.
- [x] **Brand Brain Hub**: Brand identity, voice/tone, products/services, and target personas.
- [x] **Real AI Provider Integration**: Google Gemini 2.0 Flash REST provider with deterministic fallback.
- [x] **Subscription Entitlements & Limit Enforcement**: Plan-based restrictions on social channels & AI usage.
- [x] **Social Publishing Platform**: Live LinkedIn OAuth & UGC posting + stub matrix for other networks.
- [x] **Super Admin Governance**: Real-time KPIs, plan distribution, company impersonation, and quota monitoring.
