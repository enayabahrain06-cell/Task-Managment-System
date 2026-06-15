# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Full dev environment (artisan serve + queue:listen + pail + vite dev concurrently)
composer dev

# Setup from scratch
composer setup   # install deps, copy .env, generate key, migrate, npm install, npm build

# Testing
composer test                                        # full suite (clears config cache first)
php artisan test --filter=ClassName                  # single test class

# Linting / formatting
./vendor/bin/pint                                    # Laravel Pint (PSR-12)

# Database
php artisan migrate
php artisan migrate:fresh --seed                     # reset + seed (AdminSeeder, DemoSeeder)

# Storage (required for avatars, logos, task files)
php artisan storage:link

# Queue (database driver)
php artisan queue:listen --tries=1
php artisan queue:failed                             # inspect failed jobs
```

## Architecture Overview

**Laravel 12, PHP 8.2+, SQLite (dev), Vite + Tailwind CSS, FullCalendar, database queue/sessions.**

### Role-Based Access Control

Three built-in user roles (`admin`, `manager`, `user`) plus custom roles stored in the `roles` table. Permissions are resolved in this priority order:

1. **User-level** explicit permissions (JSON array on `users.permissions`)
2. **Role-level** permissions (JSON array on `roles.permissions`)
3. **Fallback**: admin/manager always allowed; others denied

There are 21 named permission keys across six categories (tasks, projects, communication, reports, admin features). Custom roles are created via `RoleController` and can be assigned any subset.

### Route / Middleware Layout

Routes in `routes/web.php` are grouped by role:
- `/admin/*` — `AdminMiddleware` (admin + manager, active status required)
- `/manager/*` — `ManagerMiddleware`
- `/user/*` — `UserMiddleware`
- Shared authenticated routes for messages, activities, calendar, presence, notifications

`UpdateLastSeen` middleware runs on every authenticated request, writing `last_seen_at` + `presence_status` to the user row (throttled to once per minute).

### Task State Machine

Tasks move through: `draft → assigned → viewed → in_progress → submitted → approved|revision_requested → delivered → archived`

Every state transition writes a `TaskLog` record (action type + metadata). `TaskSubmission` records versioned file uploads with admin review notes. `TaskTransfer` records reassignments between users.

### Activity Feed

The activity feed is built on `TaskLog`. Each log entry supports:
- **Reactions** — `ActivityReaction` (emoji per user per log)
- **Replies** — `ActivityReply` (threaded comments on a log entry)

`ActivitiesController` handles all three concerns.

### Notifications

All notifications use the `database` channel (synchronous, no queue). Twelve notification types cover the full task lifecycle (assigned, approved, rejected, completed, delivered, reassigned, transferred, viewed, comment posted) plus social media events and user report submission. All extend `Illuminate\Notifications\Notification` and live in `app/Notifications/`.

### Messaging

Two modes in `MessagesController`:
- **Direct (1-to-1)**: `messages` table with `sender_id`/`receiver_id`
- **Groups**: `message_groups` + `message_group_users` pivot (tracks `last_read_at` per member); unread counts are computed per-user at query time

### Settings

App configuration is stored as key-value pairs in the `settings` table (`Setting` model). A view composer in `AppServiceProvider` shares settings to all views. Settings cover branding (logo, favicon, primary color), feature flags (developer mode, hidden/shown elements), mail config, and security options. **Do not write to `.env` from application code.**

### Audit Logging

`AuditLogger` is a static service class. Call `AuditLogger::log(actor, action, subject, description, metadata)` to create an `AuditLog` record that captures action type, subject entity (type + ID), description, JSON metadata, and IP address.

### Quick Tasks Project

A special internal project named **"Quick Tasks"** exists with `is_quick = true`. It is used to hold tasks that are not associated with any real project. This project must **always be excluded** from any project counts, lists, stats, or queries shown to users. Every query on the `projects` table that is displayed in the UI — counts, dropdowns, charts, reports, index pages — must include `.where('is_quick', false)`. Never count or display the Quick Tasks project as a real project.

### Multi-Assignee Tasks

Tasks have a legacy `assigned_to` FK plus a `task_assignees` pivot table that supports multiple assignees with a `role_in_task` column. New code should prefer the pivot; `assigned_to` is kept for backward compatibility.

### Key Seeders

- `AdminSeeder` — creates the default admin account
- `DemoSeeder` — populates sample users, projects, tasks for development

### Frontend

Vite bundles `resources/css/app.css` and `resources/js/app.js`. Additional entry points: `resources/js/calendar.js`. Uses Motion (animation), Axios (AJAX), and FullCalendar. Blade templates are organized under `resources/views/` by role: `admin/`, `manager/`, `user/`, `layouts/`, `auth/`, `messages/`, `activities/`, `team/`, `social/`.

## Development Rules

### Rule 1 — Stat card count must match its modal list count
Every dashboard stat card that opens a popup modal must show exactly the count the modal query returns (the real DB total, not a display cap). Calculate the card value with the **identical query** used by the modal filter — never derive it from a separate collection with a different scope (e.g. `$allTasks` scoped only to `assigned_to` while the modal uses `$userScope` covering `assigned_to + social_assigned_to + task_assignees`). Any section that displays the same numbers (e.g. a Performance chart or stat grid) must also use these same card variables, not re-derive from the narrower scope.

### Rule 2 — Always take a file backup before editing a controller or view
Before making any changes to a controller or Blade view, run:
```bash
cp <file> <file>.bak
```
This applies every session, for every file edited, so the previous working state can be restored quickly if something breaks.

### Rule 4 — Always query the database BEFORE writing or submitting any code that displays data
This rule applies to **every task without exception** — stat cards, tabs, modals, charts, reports, dashboards, lists. Before writing a single line of query code:

1. Run a tinker query to see the real data for the test user.
2. Check every status, scope, and filter against the actual DB values.
3. If a card/tab/section shows 0 or empty, prove it is genuinely empty in the DB before accepting it.

**Never assume the code is correct because it looks reasonable — always verify with real data first.**

Common bugs caught by this rule:
- `whereNotNull` on a column that is `null` for legacy rows (e.g. `social_platforms`)
- Missing statuses in a list (e.g. `paused` missing from `in_progress`, `archived` missing from done)
- Wrong scope — `assigned_to` only instead of `assigned_to + social_assigned_to + task_assignees pivot`
- Date range filters that silently cut off older data

Example verification pattern:
```bash
php artisan tinker --execute="
\$u = 5; // test user id
\$s = fn(\$q) => \$q->where('assigned_to',\$u)->orWhere('social_assigned_to',\$u)
    ->orWhereExists(fn(\$x)=>\$x->selectRaw('1')->from('task_assignees')
        ->whereColumn('task_assignees.task_id','tasks.id')->where('task_assignees.user_id',\$u));
App\Models\Task::where(\$s)->get(['id','status','deadline'])->groupBy('status')->map->count()->each(fn(\$c,\$k)=>print(\$k.': '.\$c.PHP_EOL));
"
```

### Rule 3 — Always verify the live page before marking work as done
Before reporting a task as complete, open the affected URL on the live/staging server and visually confirm the change works as expected. Check the golden path and any related sections on the same page (e.g. if fixing a stat card, also check the Performance chart and any other section that uses the same data). Never mark work done based only on code review — always verify in the browser.

### Rule 5 — File upload fields must always use drag-and-drop and support multiple files
Every file attachment field in any form — modal, page, or inline — must:
1. Show a styled drop zone (`border: 2px dashed`) that responds to `@dragover`, `@dragleave`, and `@drop` events
2. Accept multiple files (`<input type="file" multiple>`)
3. Display a chip list of staged files with filename, size, icon, and an individual remove button
4. Sync the hidden `<input>` file list via `DataTransfer` when files are added or removed

Never use a plain bare `<input type="file">` as the sole UI. Use the `qtHandleFiles` / `qtRemoveFile` pattern from the Quick Task modal (`resources/views/admin/dashboard.blade.php`) as the reference implementation.
