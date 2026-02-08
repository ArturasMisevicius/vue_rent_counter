# Content Pipelines Template

**Hooks covered:** `content-orchestrator-master`, `content-recommendation-engine`, `newsletter-generator`, `it-news-curator`, `headline-optimizer`, `seo-content-optimizer`, `landing-content-sync`.  
Use this template when implementing content ingestion, personalization, SEO, or landing sync features.

## 1) Sources & Ingestion
- Allowed sources (APIs/feeds), polling cadence, dedupe rules.
- Normalization: strip tracking params, sanitize HTML, extract canonical URLs.
- Storage: fields (title, summary, body, author, published_at, source, tags), indexes for retrieval.

## 2) Moderation & Safety
- Apply `CONTENT_MODERATION_POLICY`: spam/abuse filters, link checks, PII/NSFW handling.
- Escalation path for questionable content; log decisions.

## 3) Ranking/Recommendation
- Signals: recency, source trust, topic relevance, click/open/dwell metrics, tenant/user preferences.
- Cold start: defaults by category/recency.
- Personalization: keep per-tenant/user profiles; respect opt-outs.

## 4) SEO & Presentation
- Titles/meta: length limits, keyword placement, canonical tags.
- Structured data (schema.org) if applicable.
- Image alt text, link hygiene (nofollow for untrusted).

## 5) Workflows
- Curation UI (approve/schedule), bulk actions (publish/unpublish), A/B variants (headline/hero).
- Landing sync: what fields map to landing pages; cache invalidation rules.
- Newsletter generation: audience segments, send windows, rate limits, unsubscribe compliance.

## 6) Metrics & Logging
- Track impressions, clicks/opens, CTR, bounce/dwell; store per tenant.
- Error/ingestion logs with source URL and tenant context.

## 7) Testing
- Unit tests for parsing/normalization; feature tests for end-to-end ingestion → display.
- Property-based tests for ranking inputs if applicable.
- Performance: assert query counts and cache hits on listing endpoints/pages.

## 8) Security & Privacy
- Sanitize all HTML; block scripts/iframes.
- Respect tenant boundaries; no cross-tenant recommendations.
- Honor unsubscribe/consent for email.

## 9) Rollout
- Feature flags for new pipelines; gradual rollout by tenant/audience.
- Backfill strategy for historical content if needed.

Update this template into a feature-specific doc when these hooks become active, and link it from [HOOKS_DOCUMENTATION_MAP.md](../reference/HOOKS_DOCUMENTATION_MAP.md).
