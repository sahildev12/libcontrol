# Weekly Finance Admin Digest Implementation Plan

> **For agentic workers:** Implement task-by-task. Steps use checkbox syntax.

**Goal:** Email library admins a single weekly finance digest (IST), with High/Medium/Low priority sections, without flooding SMTP.

**Architecture:** `FinanceDigestService` builds last-7-days IST metrics + overdue totals via `ProfitLossService` / installment queries, sends one `AutomationAlertMail` per admin with a delay between sends, deduped per admin+ISO week via `EmailAutomationLogger`. Scheduled Monday 08:00 `Asia/Kolkata`.

**Tech Stack:** Laravel scheduler, existing AutomationAlertMail, PlatformSetting toggle.

**Spec:** Approach 1 — one digest with priority sections (approved).

## Global Constraints

- Timezone: `config('libcontrol.timezone', 'Asia/Kolkata')` only
- Recipients: client admins only (`OwnerRecipientResolver`)
- Rate limit: configurable delay + max recipients per run
- No student emails in this job

## Tasks

- [x] Design locked (single digest, priority sections, Monday 08:00 IST)
- [x] Migration + PlatformSetting `email_finance_digest_enabled`
- [x] `FinanceDigestService` + `SendFinanceDigestEmails` + schedule
- [x] Settings UI/API payload
- [x] Feature test with Mail::fake
