# Editable Email Templates Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let libraries edit subject/body for student email templates from Settings → Emails with placeholders and reset-to-default.

**Architecture:** Catalog of default templates + JSON overrides on `platform_settings`. A resolver merges overrides and replaces placeholders. Student/fee/seat send paths call the resolver. Settings UI adds Edit/Save/Reset/Test per template.

**Tech Stack:** Laravel, Blade/Alpine settings tab, PHPUnit feature tests, SQLite test DB.

**Spec:** `docs/superpowers/specs/2026-11-13-editable-email-templates.md`

## Global Constraints

- No full HTML builder in v1.
- Keep existing enable/disable toggles.
- Tenant-scoped via existing `platform_settings` connection.
- LibControl mail identity unchanged (`noreply@libcontrol.in` for product mail; library name in body/signature).

## Review Focus

- Missing override falls back to default.
- Unknown placeholder left intact or stripped safely without crashing.
- Disabled toggle still blocks send even with custom template.
- Reset clears override for that key only.
- Fee/seat context fields render when present.

---

### Task 1: Catalog + resolver + migration

**Files:**
- Create: `app/Support/EmailTemplateCatalog.php`
- Create: `app/Services/EmailAutomation/EmailTemplateService.php`
- Create: `database/migrations/2026_11_13_120000_add_email_template_overrides_to_platform_settings_table.php`
- Modify: `app/Models/PlatformSetting.php`
- Test: `tests/Unit/EmailTemplateServiceTest.php`

- [ ] Write failing unit tests for defaults, override merge, placeholder replace, reset
- [ ] Run test — expect fail
- [ ] Implement catalog, service, migration, model cast
- [ ] Run test — expect pass

### Task 2: Wire send paths

**Files:**
- Modify: `app/Services/StudentEmailNotificationService.php`
- Modify: `app/Services/EmailAutomation/FeeAlertService.php`
- Modify: `app/Services/EmailAutomation/SeatAlertService.php`
- Test: `tests/Feature/EditableEmailTemplatesSendTest.php`

- [ ] Write failing feature tests that custom subject/body are used when sending welcome and fee due
- [ ] Run — fail
- [ ] Wire services to `EmailTemplateService`
- [ ] Run — pass

### Task 3: Settings API + UI

**Files:**
- Modify: `app/Http/Controllers/SettingsController.php`
- Modify: `app/Services/Settings/SettingsPayloadService.php`
- Modify: `routes/web.php`
- Modify: `resources/views/settings/partials/emails-tab.blade.php`
- Modify: settings Alpine in `resources/views/settings/index.blade.php` (or related JS)
- Test: `tests/Feature/EditableEmailTemplatesSettingsTest.php`

- [ ] Write failing tests for list/update/reset/test-send endpoints
- [ ] Run — fail
- [ ] Implement routes + controller + UI editor modal
- [ ] Run — pass

### Task 4: Local verification then deploy

- [ ] Run focused + related email tests locally
- [ ] Manual local smoke if app boots
- [ ] Deploy hub files + migrate tenants only after green
- [ ] Retest Settings → Emails on kats1
