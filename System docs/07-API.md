# AI Marketing Employee — API Design

## 1. API Rules
- Versioned under /api/v1
- JSON responses
- Consistent validation errors
- Pagination
- Resource authorization
- Tenant context
- Idempotency for mutations where required

## 2. Auth
POST /api/v1/auth/register
POST /api/v1/auth/login
POST /api/v1/auth/logout
GET  /api/v1/me

## 3. Organizations
GET  /api/v1/organizations
POST /api/v1/organizations
GET  /api/v1/organizations/{organization}
PATCH /api/v1/organizations/{organization}

## 4. Brands
GET  /api/v1/brands
POST /api/v1/brands
GET  /api/v1/brands/{brand}
PATCH /api/v1/brands/{brand}

## 5. Brand Brain
GET  /api/v1/brands/{brand}/brain
POST /api/v1/brands/{brand}/brain/analyze
POST /api/v1/brands/{brand}/brain/approve

## 6. Knowledge Documents
POST /api/v1/brands/{brand}/documents
GET  /api/v1/brands/{brand}/documents
DELETE /api/v1/documents/{document}

Document analysis must be asynchronous.

## 7. Products/Services
CRUD:
- /brands/{brand}/products
- /brands/{brand}/services
- /brands/{brand}/offers

## 8. Strategy
GET  /api/v1/brands/{brand}/strategy
POST /api/v1/brands/{brand}/strategy/generate
POST /api/v1/brands/{brand}/strategy/recommend

## 9. Campaigns
GET  /api/v1/brands/{brand}/campaigns
POST /api/v1/brands/{brand}/campaigns
GET  /api/v1/campaigns/{campaign}
PATCH /api/v1/campaigns/{campaign}

## 10. Content
GET  /api/v1/brands/{brand}/content
POST /api/v1/brands/{brand}/content/generate
GET  /api/v1/content/{content}
PATCH /api/v1/content/{content}
POST /api/v1/content/{content}/regenerate
POST /api/v1/content/{content}/repurpose
POST /api/v1/content/{content}/quality-check

## 11. Creative
POST /api/v1/content/{content}/image
POST /api/v1/content/{content}/video
GET  /api/v1/media/{media}

Generation endpoints return a job/status representation; they should not block until media generation completes.

## 12. Calendar
GET  /api/v1/brands/{brand}/calendar
POST /api/v1/content/{content}/schedule
POST /api/v1/content/{content}/reschedule
POST /api/v1/content/{content}/approve
POST /api/v1/content/{content}/cancel

## 13. Social Accounts
GET  /api/v1/brands/{brand}/social-accounts
POST /api/v1/brands/{brand}/social-accounts/{platform}/connect
GET  /api/v1/social/callback/{platform}
POST /api/v1/social-accounts/{account}/refresh
DELETE /api/v1/social-accounts/{account}

OAuth callback must validate state and never expose tokens.

## 14. Publishing
GET  /api/v1/brands/{brand}/publishing/queue
GET  /api/v1/social-posts/{post}
POST /api/v1/social-posts/{post}/retry

## 15. Analytics
GET /api/v1/brands/{brand}/analytics/overview
GET /api/v1/brands/{brand}/analytics/content
GET /api/v1/content/{content}/metrics
POST /api/v1/brands/{brand}/analytics/sync

## 16. AI Assistant
POST /api/v1/brands/{brand}/assistant/messages

Assistant returns:
- response
- proposed actions
- tool calls performed
- generated resources
- confirmation requirements

## 17. Admin
GET /api/v1/admin/organizations
GET /api/v1/admin/users
GET /api/v1/admin/plans
GET /api/v1/admin/usage
GET /api/v1/admin/publishing
GET /api/v1/admin/audit-logs

## 18. Standard Response
{
  "data": {},
  "meta": {}
}

Errors:
{
  "message": "Human-readable message",
  "code": "machine_readable_code",
  "errors": {}
}

## 19. HTTP Semantics
- 200/201 success
- 202 for asynchronous generation
- 204 for successful delete where appropriate
- 401 unauthenticated
- 403 unauthorized
- 404 not found
- 409 conflict/idempotency
- 422 validation
- 429 rate limit
- 500 unexpected failure
