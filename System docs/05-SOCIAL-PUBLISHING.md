# AI Marketing Employee — Social Publishing Architecture

## 1. Goal
Provide reliable, secure, provider-independent automatic publishing.

## 2. Core Contracts

### SocialPublisher
- getAuthorizationUrl()
- exchangeAuthorizationCode()
- refreshToken()
- getAccount()
- validateMedia()
- publish()
- delete() where supported
- getPostMetrics()

### SocialAccountService
- connect()
- disconnect()
- refresh()
- healthCheck()

## 3. Adapters
- FacebookPublisher
- InstagramPublisher
- LinkedInPublisher
- YouTubePublisher
- TikTokPublisher
- XPublisher

Do not put platform-specific logic in Content or Calendar domains.

## 4. OAuth
Flow:
1. User selects platform.
2. Backend creates signed state.
3. User authorizes platform.
4. Callback validates state.
5. Exchange authorization code.
6. Fetch account/page/channel information.
7. Encrypt tokens.
8. Save scopes and expiry.
9. Run health check.

Never expose access tokens to frontend JavaScript.

## 5. Scheduling
Content lifecycle:
draft → approved → scheduled → processing → published
Possible terminal/error states:
failed, cancelled

Publishing worker:
1. Acquire lock.
2. Verify job is due.
3. Check account health.
4. Validate media.
5. Build platform payload.
6. Publish.
7. Save external ID.
8. Mark published.
9. Emit analytics sync event.

## 6. Idempotency
Every publishing job has a unique idempotency key.
A retry must never create duplicate posts if the provider already accepted the original request.

Use database locks and provider-specific reconciliation where available.

## 7. Retry
Suggested:
- transient network/API errors: exponential backoff
- authentication errors: refresh token once, then fail
- validation errors: fail immediately with actionable message
- rate limit: retry according to provider response

Maximum attempts should be configurable.

## 8. Platform Capability Matrix
Store capabilities per provider:
- text
- image
- carousel
- video
- stories
- scheduling
- deletion
- analytics

The UI must only expose capabilities supported by the selected account/platform.

## 9. Auto-Publish Safeguards
Organization settings:
- approval required
- allowed platforms
- allowed content types
- maximum posts/day
- quiet hours
- blocked words/topics
- require quality score >= threshold
- require valid brand facts

## 10. Publishing Logs
Record:
- organization
- brand
- social account
- content
- attempt
- provider response code
- external ID
- duration
- error category
- timestamp

Never store secrets in logs.

## 11. Analytics Synchronization
Run scheduled metric sync jobs.
Use incremental updates where supported.
Keep raw provider metrics in metrics_json and normalize common metrics into columns.

## 12. API Compliance
Each platform integration must be implemented against its current official API requirements, scopes, review requirements, publishing limitations, and terms. Do not emulate browser actions or use unofficial scraping for publishing.
