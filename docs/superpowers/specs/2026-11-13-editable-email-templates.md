# Editable student email templates

**Status:** Approved  
**Date:** 2026-11-13

## Goal

Let each library customize the subject and body of student notification emails from Settings → Emails (WordPress-style wording), while keeping LibControl’s shared email chrome.

## Scope (v1)

Editable templates:

- `welcome`
- `birthday`
- `recovery`
- Fee student alerts: `fee_due_in_5_days`, `fee_due_in_2_days`, `fee_due_today`, `fee_overdue_3_days`, `fee_overdue_7_days`, `fee_payment_received`
- Seat student alerts: `seat_booking_confirmed`, `seat_expiry_in_3_days`, `seat_expiry_in_1_day`

Out of v1: promotion compose-at-send, owner digests, growth/marketing/subscription LibControl emails, full HTML builders.

## Behavior

- Per-library overrides stored on the tenant DB.
- Empty override = product default.
- Placeholders: `{name}`, `{library}`, `{student_code}`, and fee/seat fields where relevant (`{amount}`, `{due_date}`, `{hall}`, `{seat}`, `{plan_ends}`, `{payment_date}`, `{reference}`).
- UI: each template has Edit → subject, body, placeholder help, Reset to default, Save; optional Send test to the signed-in admin email.
- Toggles that already exist still gate whether the mail is sent.

## Non-goals

Drag-and-drop HTML builders; per-branch template overrides.
