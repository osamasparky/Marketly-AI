# AI Marketing Employee — AI Architecture

## 1. AI Principles
- AI is a controlled application capability, not the system of record.
- Laravel orchestrates all actions.
- Every AI output is schema-validated.
- AI tools have explicit permissions.
- Customer documents and web content are untrusted inputs.
- Important autonomous actions are auditable.
- Providers are replaceable.

## 2. Provider Contracts

### AIProvider
Methods:
- generateStructured()
- generateText()
- analyzeImage()
- analyzeDocument()
- callWithTools()

### ImageProvider
- generate()
- edit()
- validate()

### VideoProvider
- generate()
- getStatus()
- download()

### EmbeddingProvider (future)
- embed()
- search()

## 3. Agents

### Brand Analyst
Inputs:
- business profile
- uploaded documents
- website data
- products/services
- brand assets

Outputs:
- business summary
- value proposition
- audiences
- USPs
- tone
- content pillars
- restrictions
- normalized facts

### Strategy Agent
Outputs:
- goals
- content pillars
- posting cadence
- campaigns
- calendar strategy
- recommended formats

### Content Agent
Outputs:
- title
- hook
- caption
- CTA
- hashtags
- visual brief
- Reel script
- platform variants

### Creative Agent
Creates:
- image prompts
- image generation requests
- video briefs
- aspect-ratio variants

### Quality Agent
Checks:
- brand consistency
- spelling
- contact information
- offer accuracy
- unsupported claims
- duplicate content
- platform suitability
- unsafe/prohibited content

### Analytics Agent
Analyzes:
- post performance
- content type
- topic
- hook
- timing
- engagement

### Optimization Agent
Produces evidence-based recommendations and suggested strategy changes.

## 4. Tool Contracts
Examples:
- getBrandProfile
- getProducts
- getServices
- getOffers
- getPastContent
- getPostMetrics
- createDraftContent
- createCampaign
- generateCreative
- schedulePost
- getConnectedAccounts
- getAnalytics

Tools must:
- enforce tenant context
- validate inputs
- authorize actions
- return structured results
- log significant mutations

## 5. Brand Context
Do not send the entire database to the model every time.

Build a compact Brand Context from:
- approved Brand Brain
- relevant products/services
- current campaign
- audience
- content history
- performance insights

## 6. Prompt Injection Defense
Never allow:
- document text
- website text
- customer comments
- imported social content
to override system/developer instructions.

Treat external content as quoted data. Tool permissions remain enforced by Laravel regardless of model instructions.

## 7. Structured Output
Use strict schemas for:
- Brand Brain
- strategy
- content
- quality checks
- analytics insights
- recommendations

Reject and retry malformed outputs with bounded retries.

## 8. Generation Cost Controls
Track:
- model
- operation
- tokens/usage
- media generations
- estimated cost

Use cheaper models for classification/extraction and stronger models for complex strategy/creative tasks where appropriate.

## 9. AI Feedback
Store:
- user approved/rejected
- edit distance where practical
- regeneration reason
- quality score
- performance outcome

Use these signals for recommendations, not uncontrolled model fine-tuning.
