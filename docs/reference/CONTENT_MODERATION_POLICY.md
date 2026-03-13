# Content Moderation Policy

**Hook coverage:** `content-moderation-specialist`.  
**Purpose:** Consistent, safe handling of user-facing text (comments, feedback, notes). Adjust scope if new surfaces are added.

## Allowed
- Technical discussion, critiques, professional disagreements.
- Relevant, non-promotional links.
- Clarifying questions and constructive feedback.

## Not Allowed
- Harassment, personal attacks, hate speech, doxxing.
- Spam/promotional or irrelevant links/content.
- Malware/exploit sharing, credential fishing.
- Off-topic content, bulk copyrighted material.
- Misinformation presented as fact (flag for review).

## Decision Framework
- Default to **review** when context is insufficient.
- Mask/strip links that are promotional or untrusted; remove on repeat offenders.
- Apply tenant/user policies if stricter rules exist.

## Output Format (for automated moderation)
When automation is used, return JSON:
```json
{
  "moderation_decision": "approve|review|remove|flag",
  "confidence": 0-100,
  "spam_score": 0-100,
  "sentiment": "positive|neutral|negative|toxic",
  "toxicity_score": 0-100,
  "technical_value": "high|medium|low",
  "relevance": "high|medium|low",
  "detected_issues": [
    { "issue_type": "spam|abuse|misinfo|off-topic|copyright|other", "severity": "low|medium|high", "snippet": "..." }
  ],
  "reasoning": "short justification",
  "suggested_action": "optional next step (edit, redact link, etc.)",
  "requires_human_review": true,
  "category_tags": ["Moderation", "Technical"]
}
```

## Escalation
- If in doubt, set `moderation_decision` to `review` and `requires_human_review` to true.
- Escalate high-severity abuse, hate speech, or legal-risk content to human reviewers immediately.

## When to Update
- New user-generated content surfaces are added.
- Policy changes for abuse/spam detection or link handling.
- Integration with external moderation services changes behavior or thresholds.
