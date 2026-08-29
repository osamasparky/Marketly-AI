# AI Marketing Employee — Database Design

## 1. Tenancy
### organizations
- id
- name
- slug
- type
- timezone
- locale
- status
- created_at
- updated_at

### organization_users
- id
- organization_id
- user_id
- role
- status
- timestamps

## 2. Brand
### brands
- id
- organization_id
- name
- industry
- description
- website
- phone
- whatsapp
- timezone
- locale
- status

### brand_profiles
- brand_id
- mission
- value_proposition
- tone
- preferred_language
- brand_colors_json
- typography_json
- audience_summary
- restrictions_json
- timestamps

### brand_assets
- id
- brand_id
- type
- file_path
- mime_type
- metadata_json
- is_primary
- timestamps

### products
- id
- brand_id
- name
- description
- price
- currency
- url
- metadata_json
- active

### services
- id
- brand_id
- name
- description
- price
- currency
- metadata_json
- active

### offers
- id
- brand_id
- title
- description
- starts_at
- ends_at
- metadata_json
- active

### audiences
- id
- brand_id
- name
- description
- pain_points_json
- interests_json
- buying_intent
- active

### competitors
- id
- brand_id
- name
- website
- notes
- metadata_json

### brand_brains
- id
- brand_id
- version
- summary_json
- source_hash
- approved_at
- approved_by
- timestamps

### knowledge_documents
- id
- brand_id
- original_name
- storage_path
- mime_type
- extraction_status
- extracted_facts_json
- checksum
- timestamps

## 3. Strategy
### marketing_goals
- id
- brand_id
- name
- type
- priority
- target_json
- active

### content_pillars
- id
- brand_id
- name
- description
- percentage
- active

### campaigns
- id
- brand_id
- name
- objective
- starts_at
- ends_at
- status
- strategy_json
- timestamps

## 4. Content
### content_plans
- id
- brand_id
- campaign_id
- period_start
- period_end
- strategy_json
- status

### content_posts
- id
- brand_id
- campaign_id
- content_plan_id
- type
- title
- hook
- caption
- cta
- hashtags_json
- language
- objective
- status
- scheduled_at
- published_at
- metadata_json

### content_variations
- id
- content_post_id
- platform
- format
- body_json
- status

### media_assets
- id
- brand_id
- content_post_id
- type
- provider
- storage_path
- mime_type
- width
- height
- duration
- generation_metadata_json
- status

### ai_generations
- id
- organization_id
- brand_id
- type
- provider
- model
- input_hash
- output_json
- usage_json
- latency_ms
- status
- error_code
- timestamps

## 5. Publishing
### social_accounts
- id
- brand_id
- platform
- external_account_id
- account_name
- access_token_encrypted
- refresh_token_encrypted
- token_expires_at
- scopes_json
- metadata_json
- status

### social_posts
- id
- content_post_id
- social_account_id
- external_post_id
- status
- published_at
- last_error
- metadata_json

### publishing_jobs
- id
- social_post_id
- scheduled_at
- attempts
- idempotency_key
- locked_at
- completed_at
- status
- last_error

## 6. Analytics
### analytics_snapshots
- id
- social_account_id
- captured_at
- metrics_json

### post_metrics
- id
- social_post_id
- captured_at
- views
- reach
- likes
- comments
- shares
- saves
- clicks
- followers_delta
- metrics_json

### ai_recommendations
- id
- brand_id
- type
- title
- explanation
- evidence_json
- action_json
- status
- applied_at

## 7. SaaS/Admin
### plans
- id
- name
- limits_json
- pricing_json
- active

### subscriptions
- id
- organization_id
- plan_id
- status
- external_subscription_id
- period_start
- period_end

### ai_usage
- id
- organization_id
- brand_id
- provider
- model
- operation
- units
- estimated_cost
- metadata_json
- created_at

### audit_logs
- id
- organization_id
- user_id
- action
- entity_type
- entity_id
- metadata_json
- ip_address
- created_at

## 8. Indexing
Add indexes for:
- organization_id on all tenant-owned tables
- brand_id on brand-owned tables
- status + scheduled_at on publishing_jobs
- social_account_id + captured_at on analytics
- content_post_id on social_posts and media_assets
- checksum on knowledge_documents
- idempotency_key unique on publishing_jobs
