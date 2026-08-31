# LibSpace — Post-Sale Onboarding Checklist

> **For:** Sales → delivery handoff after deal close  
> **Goal:** Client live within 5 business days

---

## Handoff from sales (sales completes)

- [ ] Signed agreement / payment confirmed  
- [ ] Plan tier recorded: Starter / Pro / Custom  
- [ ] Billing cycle: monthly / yearly  
- [ ] Branch count and expected seats/halls  
- [ ] Primary contact (owner) + daily user (desk manager) with phone/email  
- [ ] Hosting decision: Phenomit cloud / client server  
- [ ] Any custom scope document attached (Custom only)  
- [ ] Discovery notes: current register process, fee types used, trial policy  

**Handoff email to delivery:** info@phenomit.com with above checklist filled.

---

## Technical setup (delivery / IT)

- [ ] Server ready: PHP 8.2+, MySQL, HTTPS domain  
- [ ] `.env` configured: `APP_DEBUG=false`, `APP_URL`, database, mail SMTP  
- [ ] `composer install --no-dev`  
- [ ] `php artisan migrate --force`  
- [ ] `php artisan storage:link`  
- [ ] `npm run build` (production assets)  
- [ ] `php artisan config:cache`  
- [ ] Cron: `* * * * * php artisan schedule:run`  
- [ ] Platform admin account created; default passwords changed  
- [ ] Plan tier set in Platform Settings  

---

## Client configuration (day 1–2)

- [ ] Create branch(es) with correct name, address, phone  
- [ ] Upload library logo (branch login branding)  
- [ ] Set library hours (open/close, breaks, 24h if needed)  
- [ ] Configure student code prefix (e.g. `LIB-001`)  
- [ ] Create halls with capacities  
- [ ] Decide seat numbering: fresh per hall or continue from selected hall  
- [ ] Create branch staff login(s)  
- [ ] Test branch login URL  

---

## Data migration (if applicable)

- [ ] Student list received from client (Excel template)  
- [ ] Import plan agreed — manual entry vs bulk (scope)  
- [ ] Existing seat numbers mapped to halls  
- [ ] Active memberships with expiry dates entered as fees  

---

## Training (day 2–3)

**Session 1 — Desk staff (60 min)**
- [ ] Login  
- [ ] Seat map: assign, transfer, read colours  
- [ ] Trial seat assignment and conversion  
- [ ] Add student + enquiry  
- [ ] Record fee / receive payment  

**Session 2 — Owner / manager (45 min)**
- [ ] Dashboard KPIs  
- [ ] Fees list — expiring and overdue  
- [ ] Settings: hours, branding  
- [ ] *(Pro)* Platform admin dashboard  
- [ ] *(Pro)* Activity log  

**Leave-behind:** Link to demo recording + `09-one-pager-elevator-pitch.pdf` (if printed)

---

## Go-live (day 3–5)

- [ ] Parallel run optional: 1–2 days register + LibSpace together  
- [ ] Go-live date confirmed with client  
- [ ] SMTP tested — password reset + expiry reminder sample  
- [ ] Registration invite link tested (if used)  
- [ ] WhatsApp support group or contact channel shared with client  
- [ ] Client confirms first real assign + fee recorded successfully  

---

## 7-day post go-live check

- [ ] Call client — any blockers?  
- [ ] Seat map in daily use?  
- [ ] At least one fee recorded this week?  
- [ ] Ask for testimonial / referral if satisfied  

---

## 30-day success metrics

| Metric | Target |
|--------|--------|
| Active logins/week | ≥3 days |
| Students in system | ≥50% of active members |
| Fees recorded | ≥80% of paying members |
| Support tickets | Trending down after week 2 |
| Renewal captured in system | Owner confirms visibility |

---

## Renewal / upsell triggers (account management)

| Signal | Action |
|--------|--------|
| Approaching 100 seats on Starter | Pitch Pro before they hit wall |
| Client opens 2nd branch | Upgrade to Pro |
| Asks for 3rd branch | Custom quote |
| High satisfaction at 30 days | Ask referral + Google review |

---

## Support contacts (give to client)

| | |
|---|---|
| **Phone / WhatsApp** | +91 89012-23423 |
| **Email** | info@phenomit.com |
| **Hours** | Mon–Sat, 9:00–18:00 IST |
