# News & Fact-Check Template

**Hooks covered:** `tech-news-writer`, `tech-fact-checker`, `it-news-curator`, `newsletter-generator`.  
Use this template when building news or fact-check workflows.

## Fact-Checking SOP
- Allowed sources: reputable tech outlets, vendor advisories, CVE/NVD, official docs.
- Requirements: cite sources, include publication date, avoid unverified claims.
- Output: summary with confidence, list of sources, flagged uncertainties.

## News Writing SOP
- Tone: professional, concise, technically accurate.
- Structure: headline, lede, key points, impact/risk, links to sources.
- Avoid: hype, unverified rumors, promotional language.

## Curation & Newsletter
- Selection: relevance to tenants/users, recency, diversity of sources.
- Scheduling: send windows, throttling, unsubscribe compliance.
- Personalization: optional segments by interest/role; respect opt-outs.

## Moderation & Safety
- Apply `CONTENT_MODERATION_POLICY` for user submissions/comments.
- Strip tracking params; validate links.

## Testing
- Validate markdown/HTML rendering, link safety, unsubscribe flows.
- If ranking/selection is automated, add tests around filters and ordering.

Update or split this template into feature-specific docs when these hooks become active, and link from [HOOKS_DOCUMENTATION_MAP.md](HOOKS_DOCUMENTATION_MAP.md).
