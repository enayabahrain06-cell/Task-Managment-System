# Comprehensive System Testing Plan - Ensure Works with Data

## Status: Planning → Implementation

**Demo Data Overview:**
- Users: admin@taskmgmt.com/password, manager@taskmgmt.com/password, users like alice.chen@taskmgmt.com/password
- Projects: 6 (Website Redesign, Mobile App, etc.)
- Tasks: 40+ across statuses (pending/in_progress/completed), priorities
- Ready for testing flows.

## Steps:
- [ ] 1. Update tests/TestCase.php: Add RefreshDatabase trait for DB isolation
- [ ] 2. Create tests/Feature/AuthTest.php: Login redirects, role access
- [ ] 3. Create tests/Feature/TaskTest.php: CRUD, assign, status update, notifications
- [ ] 4. Create tests/Feature/ProjectTest.php: Project mgmt + nested tasks
- [ ] 5. Run phpunit: Verify 10+ tests pass with data
- [ ] 6. Coverage: pecl install pcov
- [ ] 7. Manual: php artisan db:seed --class=DemoSeeder (dev DB), visit /login

**Run tests:** `./vendor/bin/phpunit`

