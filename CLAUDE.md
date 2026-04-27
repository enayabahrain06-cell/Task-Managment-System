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

### Multi-Assignee Tasks

Tasks have a legacy `assigned_to` FK plus a `task_assignees` pivot table that supports multiple assignees with a `role_in_task` column. New code should prefer the pivot; `assigned_to` is kept for backward compatibility.

### Key Seeders

- `AdminSeeder` — creates the default admin account
- `DemoSeeder` — populates sample users, projects, tasks for development

### Frontend

Vite bundles `resources/css/app.css` and `resources/js/app.js`. Additional entry points: `resources/js/calendar.js`. Uses Motion (animation), Axios (AJAX), and FullCalendar. Blade templates are organized under `resources/views/` by role: `admin/`, `manager/`, `user/`, `layouts/`, `auth/`, `messages/`, `activities/`, `team/`, `social/`.
