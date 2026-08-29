# Marketly-AI — Autonomous AI Marketing Employee

> **Production-ready, multi-tenant AI-native SaaS platform** that plans, creates, schedules, publishes, analyzes, and optimizes multi-platform marketing content across Meta (Facebook & Instagram), LinkedIn, YouTube, TikTok, and X.

---

## 🌟 Product Vision

Marketly-AI acts as an autonomous marketing department for businesses and marketing agencies:
```
Business Onboarding → Brand Brain → AI Strategy → Content Studio → Creative Studio → Calendar → Publishing Engine → Analytics & Learning → AI Optimization
```

---

## 🛠️ Architecture & Tech Stack

### Backend
- **Framework**: Laravel 11 / 12 (PHP 8.2+)
- **Architecture**: Modular Domain Monolith (`app/Domains/`)
- **Authentication**: Laravel Sanctum (Bearer Token & Stateful API)
- **Database**: PostgreSQL / SQLite with strict typing & foreign keys
- **AI Contracts**: Replaceable provider abstraction (`AIProviderInterface`, `ImageProviderInterface`, `VideoProviderInterface`, `EmbeddingProviderInterface`) with Google Gemini as primary provider.
- **Social Contracts**: Provider-independent publishing contracts (`SocialPublisherInterface`, `SocialAccountServiceInterface`).
- **Queues & Jobs**: Asynchronous workers for document analysis, AI generation, and scheduled publishing with idempotency and retry backoff.

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
│   ├── Brand/            # Brand Brain, profiles, knowledge assets
│   ├── Strategy/         # Goals, pillars, cadence & campaigns
│   ├── Content/          # Post generation, hooks, CTAs, quality checks
│   ├── Creative/         # Image prompts, aspect ratios, video briefs
│   ├── Publishing/       # Social accounts, queue, idempotency
│   ├── Analytics/        # Normalized metrics & AI performance insights
│   ├── AI/               # AI agents, prompt schemas & cost tracking
│   ├── Billing/          # Subscriptions, quotas & payment gateways
│   └── Administration/   # Platform oversight, health & audit logs
│
├── AI/
│   └── Contracts/        # AIProvider, ImageProvider, VideoProvider, DTOs
│
├── Social/
│   └── Contracts/        # SocialPublisher, SocialAccountService, DTOs
│
└── Support/
    └── ApiResponse.php   # Standard API envelope { data, meta } / { message, code, errors }

resources/
├── js/
│   ├── App.vue           # Command Center interactive dashboard
│   ├── app.ts            # Vue 3 bootstrap
│   └── env.d.ts          # TypeScript declarations
└── css/
    └── app.css           # Design system tokens & typography
```

---

## 🚀 Quick Start & Local Setup

### Prerequisites
- PHP >= 8.2
- Composer >= 2.0
- Node.js >= 18.0 & npm
- SQLite / PostgreSQL

### Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/osamasparky/Marketly-AI.git
   cd Marketly-AI
   ```

2. **Install Backend Dependencies**:
   ```bash
   composer install
   ```

3. **Install Frontend Dependencies**:
   ```bash
   npm install
   ```

4. **Environment Setup**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Run Migrations**:
   ```bash
   php artisan migrate
   ```

6. **Build Frontend Assets**:
   ```bash
   npm run build
   ```

7. **Start Development Servers**:
   ```bash
   # Start Laravel API Server
   php artisan serve

   # In another terminal: Start Vite Dev Server
   npm run dev
   ```

8. Open `http://localhost:8000` in your browser.

---

## 🧪 Testing & Verification

Run the automated test suite:
```bash
php artisan test
```

Run TypeScript verification:
```bash
npx tsc --noEmit
```

---

## 🗺️ Master Implementation Roadmap

- [x] **Phase 0 — Foundation & Architecture**: Laravel foundation, Sanctum auth, Vue 3 + TS Command Center, AI & Social provider contracts, standard API response structure, tests.
- [ ] **Phase 1 — Identity & Multi-tenancy**: Organizations, roles, permissions, tenant middleware, policies, cross-tenant isolation tests.
- [ ] **Phase 2 — Brand Brain**: Knowledge document ingestion, structured business fact extraction, brand profile management & approval.
- [ ] **Phase 3 — AI Strategy**: Gemini provider integration, Strategy Agent, content pillars, 30-day campaign planning.
- [ ] **Phase 4 — Content Studio**: Multi-platform post generator, hooks, CTAs, Quality Agent, and Arabic/English dialect controls.
- [ ] **Phase 5 — Creative Studio**: AI image generation prompts (1:1, 4:5, 9:16, 16:9), video briefs, and media library.
- [ ] **Phase 6 — Content Calendar**: Interactive scheduling, drag-and-drop, approval flows.
- [ ] **Phase 7 — Social Integrations**: OAuth connections for Facebook, Instagram, LinkedIn, YouTube, TikTok, X.
- [ ] **Phase 8 — Automatic Publishing**: Asynchronous dispatch workers, database locking, idempotency keys, and retry backoff.
- [ ] **Phase 9 — Analytics & AI Optimization**: Metric synchronization, normalized KPI dashboards, Optimization Agent.
- [ ] **Phase 10 — AI Marketing Assistant**: Conversational assistant with tool calling and safe mutation confirmations.
- [ ] **Phase 11 — SaaS & Agency**: Agency client switching, quotas, AI cost/token tracking.
- [ ] **Phase 12 — Production Hardening**: Security audit, rate limiting, and end-to-end verification.
