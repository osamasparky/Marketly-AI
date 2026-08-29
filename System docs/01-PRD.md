# AI Marketing Employee — Product Requirements Document

## 1. Product Vision
AI Marketing Employee is a multi-tenant SaaS platform that acts as an AI marketing employee for businesses and agencies. A business provides its information once; the platform builds a Brand Brain, creates strategy, generates content and creatives, schedules and publishes content, analyzes performance, and improves future recommendations.

## 2. Target Users
- Small and medium businesses
- Marketing teams
- Digital agencies
- Freelancers managing multiple brands

## 3. Core Promise
Plan → Create → Approve → Publish → Measure → Learn.

## 4. MVP Scope
### Brand Brain
- Business profile
- Products/services
- Offers
- Target audiences
- Competitors
- Brand voice
- Brand colors/fonts
- Logo and assets
- Website and contact details
- PDF/document uploads
- AI extraction and structured business facts

### Strategy
- Marketing goals
- Content pillars
- Monthly strategy
- Campaign generation
- Recommended posting frequency
- Suggested posting times
- Seasonal/event opportunities

### Content
- Single posts
- Carousels
- Stories
- Reels
- Captions
- Hooks
- CTAs
- Hashtags
- Arabic/English
- Egyptian Arabic/Saudi Arabic
- Platform-specific variations
- Content repurposing

### Creative
- AI image generation
- Branded creative prompts
- Multiple aspect ratios
- Reel scripts
- Video-generation provider abstraction
- AI-generated short video where provider/API access is available

### Calendar
- 7/30-day calendar
- Drag and drop
- Draft/approval/scheduled/published states
- Regenerate/edit/duplicate

### Publishing
Initial provider architecture:
- Facebook Pages
- Instagram
- LinkedIn
- YouTube
- Extensible adapters for TikTok and X
- OAuth
- Scheduled publishing
- Queue/retry
- Idempotency
- Publishing logs
- Failure notifications

### Analytics
- Reach/views
- Engagement
- Likes/comments/shares/saves
- Clicks where available
- Follower changes where available
- Per-post metrics
- Best-performing content
- AI insights
- AI recommendations

### AI Assistant
Natural-language commands such as:
- Create next week's content
- Build a campaign
- Explain why engagement changed
- Repurpose this post
- Create a Reel from this idea

### Agency
- Multiple client organizations/brands
- Client switching
- Organization-level roles
- Usage tracking

### SaaS Administration
- Organizations
- Users
- Plans
- Usage limits
- AI usage/cost tracking
- Storage usage
- Publishing health
- Failed jobs
- Audit logs

## 5. Key User Journey
1. Register.
2. Create organization/business.
3. Complete conversational onboarding.
4. Upload logo, catalog, PDFs, website, and business information.
5. AI builds Brand Brain.
6. User reviews and approves Brand Brain.
7. AI generates marketing strategy.
8. AI generates 30-day content calendar.
9. User reviews or enables auto mode.
10. Content and creatives are generated asynchronously.
11. Quality Agent validates each asset.
12. Approved content is scheduled.
13. Publishing workers publish to connected accounts.
14. Analytics workers collect results.
15. Optimization Agent generates recommendations for future content.

## 6. Auto Mode
Two modes:
- Approval Mode: AI creates → user approves → scheduler publishes.
- Autonomous Mode: AI creates → quality checks → schedules/publishes automatically within configured rules.

Autonomous Mode must never bypass platform/API restrictions or user-defined safeguards.

## 7. Non-Functional Requirements
- Strict tenant isolation
- Secure OAuth/token storage
- Queue-based expensive operations
- Idempotent publishing
- Retry with backoff
- Auditability
- Rate limiting
- Responsive UI
- Accessible UX
- Testable domain services
- Provider-independent AI and social integrations

## 8. Success Metrics
- Time from onboarding to first approved post
- % of generated content approved
- Publishing success rate
- AI generation failure rate
- Weekly active businesses
- Content generated per active business
- Engagement improvement over baseline
- Cost per active organization

## 9. MVP Definition of Done
A new customer can onboard a business, have AI create a Brand Brain and 30-day plan, generate branded content, connect supported social accounts, schedule/publish content, and view analytics and AI recommendations from one product.
