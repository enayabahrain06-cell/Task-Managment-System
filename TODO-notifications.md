# Fix Notifications Not Working

## Analysis:
- TaskObserver.php: No notification send on task create/assign.
- TaskAssigned.php: Ready for DB notifications.
- Settings control: notify_on_assign etc.
- UI bell icon ready.

## Steps:
- [x] 1. Edit app/Observers/TaskObserver.php: Add notify() in created()
- [ ] 2. Test: Assign task to user → check database.notifications table
- [ ] 3. Add UI for bell dropdown (notifications list)
