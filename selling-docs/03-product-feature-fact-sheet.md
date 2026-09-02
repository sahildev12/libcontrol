# LibSpace — Product Feature Fact Sheet

> **Authoritative list of what is LIVE in the software today.**  
> If it is not on this list, do not sell it.

---

## Product summary

| Item | Detail |
|------|--------|
| **Product name** | LibSpace |
| **Vendor** | Phenomit.com |
| **Type** | Web application (browser-based, responsive) |
| **Primary users** | Library owners, branch managers, front-desk staff |
| **Timezone default** | Asia/Kolkata |
| **Auth** | Email + password; separate admin login URL for platform admins |

---

## Modules (live)

### 1. Dashboard
**Branch dashboard**
- KPIs: total seats, occupied, vacant, on trial
- Today's overview: new enquiries, new students, today's revenue, expiring plans
- Recent enquiries list
- Expiring plans table (next 7 days)
- Active seat allocations table

**Platform admin dashboard** *(Pro / platform admin)*
- Network KPIs: branches, students, seats, revenue
- Branch performance table with occupancy bars
- Revenue chart (3 / 6 / 12 month views)
- Date-range filtering
- Switch into any branch dashboard

---

### 2. Branch management *(platform admin)*
- Create / edit / delete branches
- Branch contact details, address, status
- Create branch login user + reset password
- Library logo upload (shown on branch login when multiple branches)
- Library hours: opening/closing, 24-hour mode, break times
- Expiry reminder days (per branch)

---

### 3. Halls
- Create / edit / delete halls per branch
- Seat capacity per hall (plan-limited)
- Optional **continue seat numbering** from another hall when creating a new hall
- Default: new hall starts at seat 1
- CSV export of halls
- Cannot delete hall or reduce capacity below assigned students

---

### 4. Seat map
- Visual grid per hall
- Status colours: vacant, occupied, trial, expiring soon, expired
- Full-day and custom-hours time slots (respects library hours)
- Click seat → assign student, view schedule, transfer
- Live refresh via polling (~15 seconds)
- Seat schedule view per seat

---

### 5. Trial seats
- Dedicated trial seat map view
- Assign trial student to seat with trial period
- Optional trial fee
- Convert trial booking to regular member
- Trial expiry visibility on map

---

### 6. Students
- Create / edit / delete students
- Required: name, email, phone, gender, date of birth
- Auto-generated student code (configurable prefix + padding)
- Photo and ID proof document upload
- Student ID card — view, print, download
- Bulk delete
- Search and filters on listing

**Public registration**
- Generate time-limited registration invite link
- Student self-registers via link (no login required)
- Link expires after use or timeout

---

### 7. Seat assignments
- Assign seat + fee plan to student
- Transfer seat (preserves fee context; conflict checks)
- Cancel assignment
- Bulk cancel
- Conflict detection for overlapping slots

---

### 8. Fees
**Fee types:** monthly, yearly, one_time, custom  
**Payment plans:** full payment, installments  
**Installment frequencies:** weekly, monthly, quarterly, half_yearly, yearly, custom  

**Capabilities:**
- Record fee amount and joining date
- Partial payments / receive payment
- Installment schedule generation
- Mark installments paid
- Plan expiry date auto-calculation (by fee type)
- Plan status: active, expiring soon, expired
- Fee listing with filters (payment status, plan status)
- Bulk remove / cancel fee records
- Edit existing fee plans

---

### 9. Notifications
- In-app alerts (expiring plans, attention items)
- Mark read / mark all read

---

### 10. Activity log *(Pro)*
- Logs logins, page views, creates, updates, deletes
- Field-level change history on updates
- Filterable listing; detail view per log entry

---

### 11. Settings
**Branch settings**
- Branch profile, library hours, branding logo
- Slot labels tied to library hours

**Platform settings** *(platform admin)*
- Student code prefix and padding
- Login branding (logo, favicon)
- Plan tier selection (Starter / Pro / Custom limits)

---

### 12. Authentication & security
- Branch login and separate admin login
- Password reset via email
- Platform admin cannot log in on branch login page
- Branch users scoped to their branch only
- Session-based auth

---

### 13. Email (when SMTP configured)
- Password reset emails
- Plan expiry reminder emails to students

---

### 14. Scheduled jobs
- Daily plan expiry reminder command (9:00 AM IST via Laravel scheduler)

---

## Plan limits (enforced in software)

| Plan | Seats | Halls | Branches |
|------|-------|-------|----------|
| **Starter** | 100 | 5 | 1 |
| **Pro** | 500 | 10 | 2 |
| **Custom** | Negotiated | Negotiated | Negotiated |

---

## NOT in the product today (do not sell)

| Feature | Status |
|---------|--------|
| Expenses / accounting module | **Not built** (marketing site may mention — app does not have it) |
| Native Android / iOS app | **Not available** (responsive web only) |
| Offline mode | **Not available** |
| WhatsApp fee reminders to students | **Not available** |
| Parent portal | **Not available** |
| Biometric / RFID attendance | **Not available** |
| Inventory / book lending | **Out of scope** |

---

## Technical notes (for IT buyers)

- PHP 8.2+ / Laravel 12
- MySQL/MariaDB recommended for production
- Requires `php artisan migrate` on deploy
- Frontend: Vite + Tailwind + Alpine.js
- File uploads stored on server (`storage` + public link)
