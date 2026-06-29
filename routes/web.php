<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Manager\DashboardController as ManagerDashboard;
use App\Http\Controllers\User\DashboardController as UserDashboard;
use App\Http\Controllers\User\TaskController as UserTaskController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\ActivitiesController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\MeetingController as AdminMeetingController;
use App\Http\Controllers\Admin\TaskApprovalController as AdminTaskApprovalController;
use App\Http\Controllers\Admin\TaskController as AdminTaskController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\OffboardingController as AdminOffboardingController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\ReportsController as AdminReportsController;
use App\Http\Controllers\Admin\SocialBudgetController as AdminSocialBudgetController;
use App\Http\Controllers\Admin\SocialAccountController as AdminSocialAccountController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\DomainController as AdminDomainController;
use App\Http\Controllers\User\LicensesController as UserLicensesController;
use App\Http\Controllers\User\ProjectController as UserProjectController;
use App\Http\Controllers\User\DomainsController as UserDomainsController;
use App\Http\Controllers\User\ReportsController as UserReportsController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ManagerMiddleware;
use App\Http\Middleware\UserMiddleware;
use App\Http\Middleware\MfaMiddleware;

// Home: redirect authenticated users to their dashboard, guests to login
Route::get('/', function () {
    if (auth()->check()) {
        return match(auth()->user()->role) {
            'admin'   => redirect()->route('admin.dashboard'),
            'manager' => redirect()->route('manager.dashboard'),
            default   => redirect()->route('user.dashboard'),
        };
    }
    return redirect()->route('login');
})->name('home');

// Auth routes
require __DIR__.'/auth.php';

// Agent — available to all authenticated users
Route::middleware(['auth'])->group(function () {
    Route::post('/agent/chat',    [AgentController::class, 'chat'])->name('agent.chat');
    Route::post('/agent/support', [AgentController::class, 'support'])->name('agent.support');
    Route::get('/agent/badge',      [AgentController::class, 'badge'])->name('agent.badge');
    Route::get('/agent/report-pdf', [AgentController::class, 'reportPdf'])->name('agent.report-pdf');
});

// Shared authenticated routes (accessible by all roles)
Route::middleware(['auth', MfaMiddleware::class])->group(function () {
    // MQTT credentials endpoint — served per-session to avoid embedding in HTML source
    Route::get('/mqtt/credentials', function () {
        return response()->json([
            'wsUrl'    => env('MQTT_WS_URL', '/mqtt'),
            'username' => env('MQTT_BROWSER_USER', 'tm_browser'),
            'password' => env('MQTT_BROWSER_PASS', ''),
            'userId'   => auth()->id(),
        ])->header('Cache-Control', 'no-store');
    })->name('mqtt.credentials');

    // Profile update (all roles)
    Route::post('/profile/update', [UserDashboard::class, 'updateProfile'])->name('user.profile.update');

    // Presence / online status
    Route::post('/user/presence', function (\Illuminate\Http\Request $request) {
        $allowed = ['online', 'away', 'busy', 'offline'];
        $status  = $request->input('status');
        if (!in_array($status, $allowed)) abort(422);
        $user = auth()->user();
        $user->timestamps    = false;
        $user->presence_status = $status;
        $user->last_seen_at    = now();
        $user->save();
        \App\Services\MqttService::presenceUpdate($user->id, $status);
        return response()->json(['ok' => true]);
    })->name('user.presence');

    // Who is online (for admin/manager)
    Route::get('/online-users', function () {
        if (!in_array(auth()->user()->role, ['admin', 'manager'])) abort(403);
        $users = \App\Models\User::where('last_seen_at', '>=', now()->subMinutes(3))
            ->where('presence_status', '!=', 'offline')
            ->where('id', '!=', auth()->id())
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'avatar', 'presence_status', 'job_title', 'role']);
        return response()->json($users->map(fn($u) => [
            'id'             => $u->id,
            'name'           => $u->name,
            'avatar'         => $u->avatarUrl(),
            'initials'       => strtoupper(substr($u->name, 0, 1)),
            'presence_status'=> $u->presence_status,
            'dot_color'      => $u->presenceDotColor(),
            'job_title'      => $u->job_title,
            'role'           => $u->role,
        ]));
    })->name('online.users');

    Route::get('/messages',                               [MessagesController::class, 'index'])->name('messages.index');
    Route::get('/messages/unread',                        [MessagesController::class, 'unread'])->name('messages.unread');
    Route::get('/messages/poll/direct/{userId}',          [MessagesController::class, 'pollDirect'])->name('messages.poll.direct');
    Route::get('/messages/poll/group/{group}',            [MessagesController::class, 'pollGroup'])->name('messages.poll.group');
    Route::get('/messages/conversation/{user}',           [MessagesController::class, 'conversation'])->name('messages.conversation');
    Route::post('/messages/send',                         [MessagesController::class, 'send'])->name('messages.send');
    Route::post('/messages/{message}/react',               [MessagesController::class, 'react'])->name('messages.react');
    Route::patch('/messages/{message}',                   [MessagesController::class, 'editMessage'])->name('messages.edit');
    Route::delete('/messages/{message}',                  [MessagesController::class, 'deleteMessage'])->name('messages.delete');
    Route::delete('/messages/clear/direct/{userId}',      [MessagesController::class, 'clearDirectChat'])->name('messages.clear.direct');
    Route::delete('/messages/clear/group/{group}',        [MessagesController::class, 'clearGroupChat'])->name('messages.clear.group');
    Route::post('/messages/groups',                       [MessagesController::class, 'createGroup'])->name('messages.groups.create');
    Route::get('/messages/groups/{group}',                [MessagesController::class, 'groupConversation'])->name('messages.groups.conversation');
    Route::post('/messages/groups/{group}/send',          [MessagesController::class, 'sendToGroup'])->name('messages.groups.send');
    Route::post('/messages/groups/{group}/members',       [MessagesController::class, 'addGroupMember'])->name('messages.groups.add-member');
    Route::delete('/messages/groups/{group}/leave',       [MessagesController::class, 'leaveGroup'])->name('messages.groups.leave');
    Route::get('/activities', [ActivitiesController::class, 'index'])->name('activities.index');
    Route::post('/activities/release', [ActivitiesController::class, 'release'])->name('activities.release');
    Route::post('/activities/{log}/react', [ActivitiesController::class, 'react'])->name('activities.react');
    Route::post('/activities/{log}/reply', [ActivitiesController::class, 'reply'])->name('activities.reply');
    Route::delete('/activities/replies/{reply}', [ActivitiesController::class, 'deleteReply'])->name('activities.reply.delete');
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/team', [TeamController::class, 'index'])->name('team.index');
    Route::get('/notifications/read/{id}',    [NotificationsController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationsController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/clear-all',     [NotificationsController::class, 'clearAll'])->name('notifications.clear-all');
    Route::get('/notifications/count',          [NotificationsController::class, 'unreadCount'])->name('notifications.count');

    // Social media posting (accessible by any authenticated user)
    Route::get('/social/{task}',                    [AdminTaskApprovalController::class, 'showSocial'])->name('social.show');
    Route::post('/social/{task}/add-post',          [AdminTaskApprovalController::class, 'addPost'])->name('social.add-post');
    Route::post('/social/{task}/timer/start',       [AdminTaskApprovalController::class, 'startSocialTimer'])->name('social.timer.start');
    Route::post('/social/{task}/timer/pause',       [AdminTaskApprovalController::class, 'pauseSocialTimer'])->name('social.timer.pause');
    Route::post('/social/{task}/posted',            [AdminTaskApprovalController::class, 'markPosted'])->name('social.posted'); // legacy

    // Submission file download — shared across all roles (add ?inline=1 for browser preview)
    Route::get('/submissions/{submission}/file', function (\App\Models\TaskSubmission $submission) {
        abort_unless($submission->file_path, 404);
        $user = auth()->user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            $uid     = $user->id;
            $task    = $submission->task;
            $allowed = $task &&
                ($task->assigned_to == $uid
                 || $task->social_assigned_to == $uid
                 || \App\Models\Task::where('id', $task->id)
                     ->whereExists(fn ($x) => $x->selectRaw('1')->from('task_assignees')
                         ->whereColumn('task_assignees.task_id', 'tasks.id')
                         ->where('task_assignees.user_id', $uid))
                     ->exists());
            abort_unless($allowed, 403);
        }
        $inline = request()->boolean('inline');
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($submission->file_path)) {
            $fullPath = storage_path('app/public/' . $submission->file_path);
            return $inline
                ? response()->file($fullPath)
                : \Illuminate\Support\Facades\Storage::disk('public')->download($submission->file_path, $submission->original_filename ?? 'file');
        }
        if ($submission->nas_path) {
            return app(\App\Services\NasService::class)->downloadFromNas($submission->nas_path, $submission->original_filename ?? 'file', $inline);
        }
        abort(404, 'File not found.');
    })->name('submissions.file');
});

// Admin routes
Route::middleware([AdminMiddleware::class, MfaMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn() => redirect()->route('admin.dashboard'))->name('index');
    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');
    Route::get('/dashboard/refresh', [AdminDashboard::class, 'refresh'])->name('dashboard.refresh');
    Route::get('/dashboard/working-hours', [AdminDashboard::class, 'workingHours'])->name('dashboard.working-hours');
    Route::get('/dashboard/chart-tasks',    [AdminDashboard::class, 'chartTasks'])->name('dashboard.chart-tasks');
    Route::get('/dashboard/workload-tasks', [AdminDashboard::class, 'workloadTasks'])->name('dashboard.workload-tasks');
    Route::get('/dashboard/social-posts',    [AdminDashboard::class, 'socialPosts'])->name('dashboard.social-posts');
    Route::get('/dashboard/analytics-tasks',[AdminDashboard::class, 'analyticsTasks'])->name('dashboard.analytics-tasks');
    Route::get('/reports',                     [AdminReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/summary-data',        [AdminReportsController::class, 'summaryData'])->name('reports.summary-data');
    Route::get('/reports/export-users',        [AdminReportsController::class, 'exportUsers'])->name('reports.export-users');
    Route::get('/reports/user-detail',         [AdminReportsController::class, 'userDetail'])->name('reports.user-detail');
    Route::post('/reports/defer-customer-approval', [AdminReportsController::class, 'deferCustomerApproval'])->name('reports.defer-customer-approval');
    Route::resource('users', AdminUserController::class);
    Route::patch('users/{user}/permissions', [AdminUserController::class, 'updatePermissions'])->name('users.permissions');
    Route::post('roles',              [AdminRoleController::class, 'store'])->name('roles.store');
    Route::put('roles/{role}',        [AdminRoleController::class, 'update'])->name('roles.update');
    Route::delete('roles/{role}',     [AdminRoleController::class, 'destroy'])->name('roles.destroy');
    Route::resource('projects', AdminProjectController::class);
    Route::post('projects/{project}/reopen', [AdminProjectController::class, 'reopen'])->name('projects.reopen');
    Route::post('projects/{project}/close',  [AdminProjectController::class, 'close'])->name('projects.close');
    Route::get('projects/{project}/tasks/create', [AdminProjectController::class, 'tasksCreate'])->name('projects.tasks.create');
    Route::post('projects/{project}/tasks', [AdminProjectController::class, 'tasksStore'])->name('projects.tasks.store');
    Route::post('tasks/quick', [AdminProjectController::class, 'quickTaskStore'])->name('tasks.quick');
    Route::post('tasks/quick-sm', [AdminProjectController::class, 'quickSMPostStore'])->name('tasks.quick-sm');
    Route::get('customers/summary-data', [AdminCustomerController::class, 'summaryData'])->name('customers.summary-data');
    Route::resource('customers', AdminCustomerController::class);
    Route::get('customers/{customer}/report', [AdminCustomerController::class, 'report'])->name('customers.report');
    Route::post('customers/{customer}/report/ai-brief', [AdminCustomerController::class, 'aiBrief'])->name('customers.report.ai-brief');
    Route::get('customers-summary', [AdminCustomerController::class, 'summary'])->name('customers.summary');

    // Settings
    Route::get('settings',                        [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::post('settings/general',               [AdminSettingsController::class, 'updateGeneral'])->name('settings.general');
    Route::post('settings/branding',              [AdminSettingsController::class, 'updateBranding'])->name('settings.branding');
    Route::post('settings/team',                  [AdminSettingsController::class, 'updateTeam'])->name('settings.team');
    Route::post('settings/notifications',         [AdminSettingsController::class, 'updateNotifications'])->name('settings.notifications');
    Route::post('settings/security',              [AdminSettingsController::class, 'updateSecurity'])->name('settings.security');
    Route::post('settings/agent',                 [AdminSettingsController::class, 'updateAgent'])->name('settings.agent');
    Route::post('settings/hide-agent',            [AdminSettingsController::class, 'toggleHideAgent'])->name('settings.hide-agent');
    Route::post('settings/mail',                  [AdminSettingsController::class, 'updateMail'])->name('settings.mail');
    Route::post('settings/mail/test',             [AdminSettingsController::class, 'testMail'])->name('settings.mail.test');
    Route::post('settings/dev-mode',              [AdminSettingsController::class, 'toggleDevMode'])->name('settings.dev-mode');
    Route::post('settings/maintenance',           [AdminSettingsController::class, 'toggleMaintenance'])->name('settings.maintenance');
    Route::post('settings/manager-admin-access',  [AdminSettingsController::class, 'toggleManagerAdminAccess'])->name('settings.manager-admin-access');
    Route::post('settings/manager-roles-access',  [AdminSettingsController::class, 'toggleManagerRolesAccess'])->name('settings.manager-roles-access');
    Route::post('settings/approval-customer-notify', [AdminSettingsController::class, 'toggleApprovalCustomerNotify'])->name('settings.approval-customer-notify');
    Route::post('settings/hourly-rate',              [AdminSettingsController::class, 'toggleHourlyRate'])->name('settings.hourly-rate');
    Route::post('settings/hide-wa-web',              [AdminSettingsController::class, 'toggleHideWaWeb'])->name('settings.hide-wa-web');
    Route::post('settings/hide-summarize',           [AdminSettingsController::class, 'toggleHideSummarize'])->name('settings.hide-summarize');
    Route::post('settings/features/disable-all',        [AdminSettingsController::class, 'disableAllFeatures'])->name('settings.features.disable-all');
    Route::post('settings/features/gantt-chart',        [AdminSettingsController::class, 'toggleGanttChart'])->name('settings.features.gantt-chart');
    Route::post('settings/features/excel-export',       [AdminSettingsController::class, 'toggleExcelExport'])->name('settings.features.excel-export');
    Route::post('settings/features/workload-view',      [AdminSettingsController::class, 'toggleWorkloadView'])->name('settings.features.workload-view');
    Route::post('settings/features/task-dependencies',  [AdminSettingsController::class, 'toggleTaskDependencies'])->name('settings.features.task-dependencies');
    Route::post('settings/features/recurring-tasks',    [AdminSettingsController::class, 'toggleRecurringTasks'])->name('settings.features.recurring-tasks');
    Route::post('settings/features/time-tracking',      [AdminSettingsController::class, 'toggleTimeTracking'])->name('settings.features.time-tracking');
    Route::post('settings/features/task-templates',     [AdminSettingsController::class, 'toggleTaskTemplates'])->name('settings.features.task-templates');
    Route::post('settings/clear-cache',              [AdminSettingsController::class, 'clearCache'])->name('settings.clear-cache');
    Route::get('social-budget',                      [AdminSocialBudgetController::class, 'index'])->name('social-budget.index');
    Route::get('social-accounts/export/pdf', [AdminSocialAccountController::class, 'exportPdf'])->name('social-accounts.export.pdf');
    Route::resource('social-accounts', AdminSocialAccountController::class)->except(['create','edit','show']);
    Route::post('social-accounts/{socialAccount}/reveal-password', [AdminSocialAccountController::class, 'revealPassword'])->name('social-accounts.reveal-password');
    Route::post('settings/elements/toggle',       [AdminSettingsController::class, 'toggleElement'])->name('settings.elements.toggle');
    Route::post('settings/nav/toggle',            [AdminSettingsController::class, 'toggleNavItem'])->name('settings.nav.toggle');
    Route::post('meetings',                        [AdminMeetingController::class, 'store'])->name('meetings.store');
    Route::put('meetings/{meeting}',               [AdminMeetingController::class, 'update'])->name('meetings.update');
    Route::patch('meetings/{meeting}/reschedule',  [AdminMeetingController::class, 'reschedule'])->name('meetings.reschedule');
    Route::delete('meetings/{meeting}',            [AdminMeetingController::class, 'destroy'])->name('meetings.destroy');
    Route::get('settings/export/users',            [AdminSettingsController::class, 'exportUsers'])->name('settings.export.users');
    Route::get('settings/export/tasks',           [AdminSettingsController::class, 'exportTasks'])->name('settings.export.tasks');
    Route::get('settings/export/projects',        [AdminSettingsController::class, 'exportProjects'])->name('settings.export.projects');
    Route::post('settings/restore/users',         [AdminSettingsController::class, 'restoreUsers'])->name('settings.restore.users');
    Route::post('settings/restore/tasks',         [AdminSettingsController::class, 'restoreTasks'])->name('settings.restore.tasks');
    Route::post('settings/restore/projects',      [AdminSettingsController::class, 'restoreProjects'])->name('settings.restore.projects');
    Route::get('settings/backup/download',        [AdminSettingsController::class, 'downloadBackup'])->name('settings.backup.download');
    Route::get('settings/backup/download/sqlite', [AdminSettingsController::class, 'downloadBackupSqlite'])->name('settings.backup.download.sqlite');
    Route::post('settings/backup/restore',             [AdminSettingsController::class, 'restoreBackup'])->name('settings.backup.restore');
    Route::post('settings/backup/verify-password',     [AdminSettingsController::class, 'verifyRestorePassword'])->name('settings.backup.verify.password');
    Route::get('settings/backup/server-files',    [AdminSettingsController::class, 'listServerBackups'])->name('settings.backup.server.list');
    Route::post('settings/backup/restore-server', [AdminSettingsController::class, 'restoreFromServer'])->name('settings.backup.restore.server');
    Route::post('settings/backup/save-to-nas',        [AdminSettingsController::class, 'saveBackupToNas'])->name('settings.backup.save.nas');
    Route::post('settings/backup/save-to-nas/sqlite', [AdminSettingsController::class, 'saveBackupSqliteToNas'])->name('settings.backup.save.nas.sqlite');
    Route::get('settings/backup/nas-files',           [AdminSettingsController::class, 'listNasBackups'])->name('settings.backup.nas.list');
    Route::post('settings/backup/restore-from-nas',   [AdminSettingsController::class, 'restoreFromNas'])->name('settings.backup.restore.nas');
    Route::post('settings/clear',                 [AdminSettingsController::class, 'clearData'])->name('settings.clear');
    Route::post('settings/storage',                    [AdminSettingsController::class, 'updateStorage'])->name('settings.storage');
    Route::post('settings/storage/test/gdrive',        [AdminSettingsController::class, 'testStorageGdrive'])->name('settings.storage.test.gdrive');
    Route::post('settings/storage/backup/gdrive',      [AdminSettingsController::class, 'backupToGdrive'])->name('settings.storage.backup.gdrive');
    Route::post('settings/storage/test/onedrive',      [AdminSettingsController::class, 'testStorageOnedrive'])->name('settings.storage.test.onedrive');
    Route::post('settings/storage/test/omv',           [AdminSettingsController::class, 'testStorageOmv'])->name('settings.storage.test.omv');
    Route::post('settings/storage/migrate-to-nas',    [AdminSettingsController::class, 'migrateLocalToNas'])->name('settings.storage.migrate.nas');
    Route::post('settings/storage/recover-nas-paths', [AdminSettingsController::class, 'recoverNasPaths'])->name('settings.storage.recover.nas');
    Route::post('settings/nas-schema',                 [AdminSettingsController::class, 'updateNasSchema'])->name('settings.nas-schema');
    Route::post('settings/nas-schema/reset',           [AdminSettingsController::class, 'resetNasSchema'])->name('settings.nas-schema.reset');
    Route::post('settings/whatsapp',              [AdminSettingsController::class, 'updateWhatsapp'])->name('settings.whatsapp');
    Route::post('settings/whatsapp/test',         [AdminSettingsController::class, 'testWhatsapp'])->name('settings.whatsapp.test');
    Route::post('settings/whatsapp/broadcast',    [AdminSettingsController::class, 'broadcastWhatsapp'])->name('settings.whatsapp.broadcast');

    // Task approvals
    Route::get('approvals',                        [AdminTaskApprovalController::class, 'index'])->name('approvals.index');
    Route::post('tasks/{task}/approve',            [AdminTaskApprovalController::class, 'approve'])->name('tasks.approve');
    Route::post('tasks/{task}/reject',             [AdminTaskApprovalController::class, 'reject'])->name('tasks.reject');
    Route::post('tasks/{task}/pending-customer',   [AdminTaskApprovalController::class, 'pendingCustomer'])->name('tasks.pending-customer');
    Route::post('tasks/{task}/social-assign',      [AdminTaskApprovalController::class, 'assignSocial'])->name('tasks.social.assign');
    Route::post('tasks/{task}/social-required',    [AdminTaskApprovalController::class, 'setSocialRequired'])->name('tasks.social.required');
    Route::post('approvals/bulk-decide-later',     [AdminTaskApprovalController::class, 'bulkDecideLater'])->name('approvals.bulk-decide-later');
    Route::put('social-posts/{post}',              [AdminTaskApprovalController::class, 'updateSocialPost'])->name('social-posts.update');
    Route::delete('social-posts/{post}',           [AdminTaskApprovalController::class, 'deleteSocialPost'])->name('social-posts.destroy');
    Route::post('tasks/{task}/social-reopen',      [AdminTaskApprovalController::class, 'reopenSocial'])->name('tasks.social.reopen');
    Route::post('approvals/whatsapp-customer',       [AdminTaskApprovalController::class, 'sendWhatsappToCustomer'])->name('approvals.whatsapp-customer');
    Route::post('approvals/whatsapp-customer-media', [AdminTaskApprovalController::class, 'sendWhatsappMediaToCustomer'])->name('approvals.whatsapp-customer-media');

    // Individual task management
    Route::get('tasks',                            [AdminTaskController::class, 'index'])->name('tasks.index');
    Route::get('tasks/trash',                      [AdminTaskController::class, 'trash'])->name('tasks.trash');
    Route::post('tasks/{id}/restore',              [AdminTaskController::class, 'restore'])->name('tasks.restore');
    Route::delete('tasks/{id}/force-delete',       [AdminTaskController::class, 'forceDelete'])->name('tasks.force-delete');
    Route::get('tasks/{task}/panel',               [AdminTaskController::class, 'panel'])->name('tasks.panel');
    Route::get('tasks/{task}',                     [AdminTaskController::class, 'show'])->name('tasks.show');
    Route::post('tasks/{task}/comment',            [AdminTaskController::class, 'comment'])->name('tasks.comment');
    Route::patch('tasks/{task}/comments/{comment}',              [AdminTaskController::class, 'editComment'])->name('tasks.comments.edit');
    Route::patch('tasks/{task}/submissions/{submission}/note',   [AdminTaskController::class, 'editSubmissionNote'])->name('tasks.submissions.note');
    Route::post('tasks/{task}/deliver',            [AdminTaskController::class, 'deliver'])->name('tasks.deliver');
    Route::post('tasks/{task}/reassign',           [AdminTaskController::class, 'reassign'])->name('tasks.reassign');
    Route::patch('tasks/{task}/deadline',          [AdminTaskController::class, 'updateDeadline'])->name('tasks.deadline');
    Route::post('tasks/{task}/archive',            [AdminTaskController::class, 'archive'])->name('tasks.archive');
    Route::post('tasks/{task}/reopen',             [AdminTaskController::class, 'reopen'])->name('tasks.reopen');
    Route::post('tasks/{task}/force-close',        [AdminTaskController::class, 'forceClose'])->name('tasks.forceClose');
    Route::patch('tasks/{task}',                   [AdminTaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}',                  [AdminTaskController::class, 'destroy'])->name('tasks.destroy');

    // User task transfer
    Route::post('users/{user}/transfer-tasks',     [AdminUserController::class, 'transferTasks'])->name('users.transfer-tasks');
    // Hold / release account
    Route::post('users/{user}/hold',               [AdminUserController::class, 'hold'])->name('users.hold');
    Route::post('users/{user}/disable-mfa',           [AdminUserController::class, 'disableMfa'])->name('users.disable-mfa');
    Route::post('users/{user}/require-mfa',           [AdminUserController::class, 'requireMfa'])->name('users.require-mfa');
    Route::post('users/{user}/cancel-mfa-requirement',[AdminUserController::class, 'cancelMfaRequirement'])->name('users.cancel-mfa-requirement');
    Route::post('users/{user}/reset-mfa',             [AdminUserController::class, 'resetMfa'])->name('users.reset-mfa');
    Route::post('users/{user}/clone',              [AdminUserController::class, 'cloneUser'])->name('users.clone');
    // Restore archived user
    Route::post('users/{user}/restore',            [AdminUserController::class, 'restore'])->name('users.restore');
    // Permanently delete user
    Route::delete('users/{user}/permanent',        [AdminUserController::class, 'permanentDelete'])->name('users.permanent-delete');
    // View user's dashboard (admin preview)
    Route::get('users/{user}/dashboard',           [AdminUserController::class, 'viewDashboard'])->name('users.dashboard');
    Route::get('users/{user}/tasks-modal',         [AdminUserController::class, 'taskModal'])->name('users.tasks-modal');
    Route::get('users/{user}/tasks',               [AdminUserController::class, 'taskHistory'])->name('users.task-history');
    // Performance data (JSON)
    Route::get('users/{user}/performance',         [AdminUserController::class, 'performance'])->name('users.performance');

    // User offboarding
    Route::get('users/{user}/offboard',            [AdminOffboardingController::class, 'show'])->name('users.offboard');
    Route::post('users/{user}/offboard',           [AdminOffboardingController::class, 'process'])->name('users.offboard.process');

    // Audit log
    Route::get('audit',                            [AdminAuditLogController::class, 'index'])->name('audit.index');
    Route::post('audit/clear-logs',               [AdminAuditLogController::class, 'clearLogs'])->name('audit.clear-logs');

    // Domains
    Route::get('domains/export/pdf', [AdminDomainController::class, 'exportPdf'])->name('domains.export.pdf');
    Route::post('domains/{domain}/reveal-password', [AdminDomainController::class, 'revealPassword'])->name('domains.reveal-password');
    Route::resource('domains', AdminDomainController::class);
    Route::post('domains/{domain}/attachments',                               [AdminDomainController::class, 'storeAttachment'])->name('domains.attachments.store');
    Route::get('domains/{domain}/attachments/{attachment}/download',          [AdminDomainController::class, 'downloadAttachment'])->name('domains.attachments.download');
    Route::delete('domains/{domain}/attachments/{attachment}',                [AdminDomainController::class, 'destroyAttachment'])->name('domains.attachments.destroy');

    // Subscriptions & Licenses
    Route::get('subscriptions/export/pdf', [AdminSubscriptionController::class, 'exportPdf'])->name('subscriptions.export.pdf');
    Route::resource('subscriptions', AdminSubscriptionController::class);
    Route::post('subscriptions/{subscription}/reveal-password',              [AdminSubscriptionController::class, 'revealPassword'])->name('subscriptions.reveal-password');
    Route::post('subscriptions/{subscription}/assign-user',                  [AdminSubscriptionController::class, 'assignUser'])->name('subscriptions.assign-user');
    Route::delete('subscriptions/{subscription}/remove-user/{user}',         [AdminSubscriptionController::class, 'removeUser'])->name('subscriptions.remove-user');
    Route::post('subscriptions/{subscription}/attachments',                  [AdminSubscriptionController::class, 'uploadAttachment'])->name('subscriptions.attachments.upload');
    Route::delete('subscriptions/{subscription}/attachments/{attachment}',   [AdminSubscriptionController::class, 'deleteAttachment'])->name('subscriptions.attachments.delete');

    // Project attachment download (add ?inline=1 to serve inline for browser preview)
    Route::get('attachments/{attachment}/download', function (\App\Models\ProjectAttachment $attachment) {
        abort_unless($attachment->isFile(), 404);
        $inline = request()->boolean('inline');
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->path)) {
            $fullPath = storage_path('app/public/' . $attachment->path);
            return $inline
                ? response()->file($fullPath)
                : \Illuminate\Support\Facades\Storage::disk('public')->download($attachment->path, $attachment->name);
        }
        if ($attachment->nas_path) {
            return app(\App\Services\NasService::class)->downloadFromNas($attachment->nas_path, $attachment->name, $inline);
        }
        abort(404, 'File not found.');
    })->name('attachments.download');

    // Task attachment upload & delete
    Route::post('tasks/{task}/attachments',               [\App\Http\Controllers\Admin\TaskController::class, 'addAttachment'])->name('tasks.attachments.add');
    Route::delete('tasks/{task}/attachments/{attachment}', [\App\Http\Controllers\Admin\TaskController::class, 'deleteAttachment'])->name('tasks.attachments.delete');

    // Task submission file download (add ?inline=1 to serve inline for browser preview)
    Route::get('submissions/{submission}/download', function (\App\Models\TaskSubmission $submission) {
        abort_unless($submission->file_path, 404);
        $inline = request()->boolean('inline');
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($submission->file_path)) {
            $fullPath = storage_path('app/public/' . $submission->file_path);
            return $inline
                ? response()->file($fullPath)
                : \Illuminate\Support\Facades\Storage::disk('public')->download($submission->file_path, $submission->original_filename ?? 'file');
        }
        if ($submission->nas_path) {
            return app(\App\Services\NasService::class)->downloadFromNas($submission->nas_path, $submission->original_filename ?? 'file', $inline);
        }
        abort(404, 'File not found. It may have been removed from local storage.');
    })->name('submissions.download');

    // Task comment file download (add ?inline=1 to serve inline; ?file_index=N for multi-file comments)
    Route::get('task-comments/{comment}/file', function (\App\Models\TaskComment $comment) {
        $authUser = auth()->user();
        $isAdminOrManager = in_array($authUser->role, ['admin', 'manager']);
        $isCommentAuthor  = $comment->user_id === $authUser->id;
        $isTaskParticipant = false;
        if (! $isAdminOrManager && ! $isCommentAuthor) {
            $task = \App\Models\Task::find($comment->task_id);
            if ($task) {
                $isTaskParticipant = $task->assigned_to === $authUser->id
                    || $task->social_assigned_to === $authUser->id
                    || $task->assignees()->where('user_id', $authUser->id)->exists();
            }
        }
        abort_unless($isAdminOrManager || $isCommentAuthor || $isTaskParticipant, 403);

        $inline = request()->boolean('inline');
        $idx    = (int) request()->input('file_index', -1);

        // New multi-file format stored in `files` JSON column
        if ($comment->files && $idx >= 0 && isset($comment->files[$idx])) {
            $f        = $comment->files[$idx];
            $filePath = $f['path'];
            $filename = $f['original_filename'] ?? 'file';
            $nasPath  = $f['nas_path'] ?? null;
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($filePath)) {
                $fullPath = storage_path('app/public/' . $filePath);
                return $inline
                    ? response()->file($fullPath)
                    : \Illuminate\Support\Facades\Storage::disk('public')->download($filePath, $filename);
            }
            if ($nasPath) {
                return app(\App\Services\NasService::class)->downloadFromNas($nasPath, $filename, $inline);
            }
            abort(404, 'File not found.');
        }

        // Legacy single-file format
        abort_unless($comment->file_path, 404);
        $filename = $comment->original_filename ?? 'file';
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($comment->file_path)) {
            $fullPath = storage_path('app/public/' . $comment->file_path);
            return $inline
                ? response()->file($fullPath)
                : \Illuminate\Support\Facades\Storage::disk('public')->download($comment->file_path, $filename);
        }
        if ($comment->nas_path) {
            return app(\App\Services\NasService::class)->downloadFromNas($comment->nas_path, $filename, $inline);
        }
        abort(404, 'File not found.');
    })->name('task-comments.file');
});

// Manager routes
Route::middleware([ManagerMiddleware::class, MfaMiddleware::class])->prefix('manager')->name('manager.')->group(function () {
    Route::get('/dashboard',         [ManagerDashboard::class, 'index'])->name('dashboard');
    Route::get('/dashboard/refresh', [ManagerDashboard::class, 'refresh'])->name('dashboard.refresh');
    Route::resource('projects', AdminProjectController::class)->only(['index', 'store']);
    Route::post('/tasks/quick',      [AdminProjectController::class, 'quickTaskStore'])->name('tasks.quick');
    Route::post('meetings',                       [AdminMeetingController::class, 'store'])->name('meetings.store');
    Route::put('meetings/{meeting}',              [AdminMeetingController::class, 'update'])->name('meetings.update');
    Route::patch('meetings/{meeting}/reschedule', [AdminMeetingController::class, 'reschedule'])->name('meetings.reschedule');
    Route::delete('meetings/{meeting}',           [AdminMeetingController::class, 'destroy'])->name('meetings.destroy');
});

// User routes
Route::middleware([UserMiddleware::class, MfaMiddleware::class])->prefix('user')->name('user.')->group(function () {
    Route::get('/dashboard', [UserDashboard::class, 'index'])->name('dashboard');
    Route::get('/licenses',  [UserLicensesController::class, 'index'])->name('licenses.index');
    Route::get('/domains',   [UserDomainsController::class, 'index'])->name('domains.index');
    Route::post('/licenses/{subscription}/reveal-password', [UserLicensesController::class, 'revealPassword'])->name('licenses.reveal-password');
    Route::post('/report',   [UserDashboard::class, 'submitReport'])->name('report');
    Route::get('/tasks-modal', [UserDashboard::class, 'taskModal'])->name('tasks.modal');
    Route::get('/tasks', [UserTaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/{task}', [UserTaskController::class, 'show'])->name('tasks.show');
    Route::patch('/tasks/{task}/status', [UserTaskController::class, 'updateStatus'])->name('tasks.updateStatus');
    Route::post('/tasks/{task}/submit', [UserTaskController::class, 'submitVersion'])->name('tasks.submit');
    Route::post('/tasks/{task}/comment', [UserTaskController::class, 'addComment'])->name('tasks.comment');
    Route::patch('/tasks/{task}/comments/{comment}',             [UserTaskController::class, 'editComment'])->name('tasks.comments.edit');
    Route::patch('/tasks/{task}/submissions/{submission}/note',  [UserTaskController::class, 'editSubmissionNote'])->name('tasks.submissions.note');
    Route::post('/tasks/{task}/timer/start',             [UserTaskController::class, 'startTimer'])->name('tasks.timer.start');
    Route::post('/tasks/{task}/timer/pause',             [UserTaskController::class, 'pauseTimer'])->name('tasks.timer.pause');
    Route::post('/tasks/{task}/acknowledge-revision',    [UserTaskController::class, 'acknowledgeRevision'])->name('tasks.acknowledge-revision');
    Route::get('/projects', [UserProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [UserProjectController::class, 'show'])->name('projects.show');
    Route::get('/reports', [UserReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [UserReportsController::class, 'exportTasks'])->name('reports.export');
    Route::get('/reports/export-pdf', [UserReportsController::class, 'exportPdf'])->name('reports.export.pdf');
    Route::get('/attachments/{attachment}/download', function (\App\Models\ProjectAttachment $attachment) {
        $userId = auth()->id();
        $allowed = \App\Models\Task::where('project_id', $attachment->project_id)
            ->where(function ($q) use ($userId) {
                $q->where('assigned_to', $userId)
                  ->orWhereExists(fn ($x) => $x->selectRaw('1')->from('task_assignees')
                      ->whereColumn('task_assignees.task_id', 'tasks.id')
                      ->where('task_assignees.user_id', $userId));
            })
            ->exists();
        abort_unless($allowed, 403);
        abort_unless($attachment->isFile(), 404);
        $inline = request()->boolean('inline');
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->path)) {
            $fullPath = storage_path('app/public/' . $attachment->path);
            return $inline
                ? response()->file($fullPath)
                : \Illuminate\Support\Facades\Storage::disk('public')->download($attachment->path, $attachment->name);
        }
        if ($attachment->nas_path) {
            return app(\App\Services\NasService::class)->downloadFromNas($attachment->nas_path, $attachment->name, $inline);
        }
        abort(404, 'File not found.');
    })->name('attachments.download');
});
