<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Manager\DashboardController as ManagerDashboard;
use App\Http\Controllers\User\DashboardController as UserDashboard;
use App\Http\Controllers\User\TaskController as UserTaskController;
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
use App\Http\Controllers\User\ProjectController as UserProjectController;
use App\Http\Controllers\User\ReportsController as UserReportsController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ManagerMiddleware;
use App\Http\Middleware\UserMiddleware;

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

// Shared authenticated routes (accessible by all roles)
Route::middleware(['auth'])->group(function () {
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
    Route::get('/messages/conversation/{user}',           [MessagesController::class, 'conversation'])->name('messages.conversation');
    Route::post('/messages/send',                         [MessagesController::class, 'send'])->name('messages.send');
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
    Route::get('/notifications/count',          [NotificationsController::class, 'unreadCount'])->name('notifications.count');

    // Social media posting (accessible by any authenticated user)
    Route::get('/social/{task}',              [AdminTaskApprovalController::class, 'showSocial'])->name('social.show');
    Route::post('/social/{task}/add-post',    [AdminTaskApprovalController::class, 'addPost'])->name('social.add-post');
    Route::post('/social/{task}/posted',      [AdminTaskApprovalController::class, 'markPosted'])->name('social.posted'); // legacy
});

// Admin routes
Route::middleware([AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');
    Route::get('/dashboard/refresh', [AdminDashboard::class, 'refresh'])->name('dashboard.refresh');
    Route::get('/dashboard/working-hours', [AdminDashboard::class, 'workingHours'])->name('dashboard.working-hours');
    Route::get('/dashboard/chart-tasks',    [AdminDashboard::class, 'chartTasks'])->name('dashboard.chart-tasks');
    Route::get('/dashboard/workload-tasks', [AdminDashboard::class, 'workloadTasks'])->name('dashboard.workload-tasks');
    Route::get('/dashboard/social-posts',    [AdminDashboard::class, 'socialPosts'])->name('dashboard.social-posts');
    Route::get('/dashboard/analytics-tasks',[AdminDashboard::class, 'analyticsTasks'])->name('dashboard.analytics-tasks');
    Route::get('/reports',              [AdminReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/export-users', [AdminReportsController::class, 'exportUsers'])->name('reports.export-users');
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

    // Settings
    Route::get('settings',                        [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::post('settings/general',               [AdminSettingsController::class, 'updateGeneral'])->name('settings.general');
    Route::post('settings/branding',              [AdminSettingsController::class, 'updateBranding'])->name('settings.branding');
    Route::post('settings/team',                  [AdminSettingsController::class, 'updateTeam'])->name('settings.team');
    Route::post('settings/notifications',         [AdminSettingsController::class, 'updateNotifications'])->name('settings.notifications');
    Route::post('settings/security',              [AdminSettingsController::class, 'updateSecurity'])->name('settings.security');
    Route::post('settings/mail',                  [AdminSettingsController::class, 'updateMail'])->name('settings.mail');
    Route::post('settings/mail/test',             [AdminSettingsController::class, 'testMail'])->name('settings.mail.test');
    Route::post('settings/dev-mode',              [AdminSettingsController::class, 'toggleDevMode'])->name('settings.dev-mode');
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
    Route::post('settings/backup/restore',        [AdminSettingsController::class, 'restoreBackup'])->name('settings.backup.restore');
    Route::post('settings/clear',                 [AdminSettingsController::class, 'clearData'])->name('settings.clear');

    // Task approvals
    Route::get('approvals',                        [AdminTaskApprovalController::class, 'index'])->name('approvals.index');
    Route::post('tasks/{task}/approve',            [AdminTaskApprovalController::class, 'approve'])->name('tasks.approve');
    Route::post('tasks/{task}/reject',             [AdminTaskApprovalController::class, 'reject'])->name('tasks.reject');
    Route::post('tasks/{task}/social-assign',      [AdminTaskApprovalController::class, 'assignSocial'])->name('tasks.social.assign');
    Route::post('tasks/{task}/social-required',    [AdminTaskApprovalController::class, 'setSocialRequired'])->name('tasks.social.required');
    Route::put('social-posts/{post}',              [AdminTaskApprovalController::class, 'updateSocialPost'])->name('social-posts.update');
    Route::delete('social-posts/{post}',           [AdminTaskApprovalController::class, 'deleteSocialPost'])->name('social-posts.destroy');
    Route::post('tasks/{task}/social-reopen',      [AdminTaskApprovalController::class, 'reopenSocial'])->name('tasks.social.reopen');

    // Individual task management
    Route::get('tasks',                            [AdminTaskController::class, 'index'])->name('tasks.index');
    Route::get('tasks/trash',                      [AdminTaskController::class, 'trash'])->name('tasks.trash');
    Route::post('tasks/{id}/restore',              [AdminTaskController::class, 'restore'])->name('tasks.restore');
    Route::delete('tasks/{id}/force-delete',       [AdminTaskController::class, 'forceDelete'])->name('tasks.force-delete');
    Route::get('tasks/{task}/panel',               [AdminTaskController::class, 'panel'])->name('tasks.panel');
    Route::get('tasks/{task}',                     [AdminTaskController::class, 'show'])->name('tasks.show');
    Route::post('tasks/{task}/comment',            [AdminTaskController::class, 'comment'])->name('tasks.comment');
    Route::post('tasks/{task}/deliver',            [AdminTaskController::class, 'deliver'])->name('tasks.deliver');
    Route::post('tasks/{task}/reassign',           [AdminTaskController::class, 'reassign'])->name('tasks.reassign');
    Route::post('tasks/{task}/archive',            [AdminTaskController::class, 'archive'])->name('tasks.archive');
    Route::post('tasks/{task}/reopen',             [AdminTaskController::class, 'reopen'])->name('tasks.reopen');
    Route::delete('tasks/{task}',                  [AdminTaskController::class, 'destroy'])->name('tasks.destroy');

    // User task transfer
    Route::post('users/{user}/transfer-tasks',     [AdminUserController::class, 'transferTasks'])->name('users.transfer-tasks');
    // Hold / release account
    Route::post('users/{user}/hold',               [AdminUserController::class, 'hold'])->name('users.hold');
    // Restore archived user
    Route::post('users/{user}/restore',            [AdminUserController::class, 'restore'])->name('users.restore');
    // View user's dashboard (admin preview)
    Route::get('users/{user}/dashboard',           [AdminUserController::class, 'viewDashboard'])->name('users.dashboard');
    // Performance data (JSON)
    Route::get('users/{user}/performance',         [AdminUserController::class, 'performance'])->name('users.performance');

    // User offboarding
    Route::get('users/{user}/offboard',            [AdminOffboardingController::class, 'show'])->name('users.offboard');
    Route::post('users/{user}/offboard',           [AdminOffboardingController::class, 'process'])->name('users.offboard.process');

    // Audit log
    Route::get('audit',                            [AdminAuditLogController::class, 'index'])->name('audit.index');
});

// Manager routes
Route::middleware([ManagerMiddleware::class])->prefix('manager')->name('manager.')->group(function () {
    Route::get('/dashboard',         [ManagerDashboard::class, 'index'])->name('dashboard');
    Route::get('/dashboard/refresh', [ManagerDashboard::class, 'refresh'])->name('dashboard.refresh');
    Route::resource('projects', AdminProjectController::class)->only(['index', 'store']);
    Route::post('/tasks/quick',      [AdminProjectController::class, 'quickTaskStore'])->name('tasks.quick');
});

// User routes
Route::middleware([UserMiddleware::class])->prefix('user')->name('user.')->group(function () {
    Route::get('/dashboard', [UserDashboard::class, 'index'])->name('dashboard');
    Route::post('/report',   [UserDashboard::class, 'submitReport'])->name('report');
    Route::get('/tasks-modal', [UserDashboard::class, 'taskModal'])->name('tasks.modal');
    Route::get('/tasks', [UserTaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/{task}', [UserTaskController::class, 'show'])->name('tasks.show');
    Route::patch('/tasks/{task}/status', [UserTaskController::class, 'updateStatus'])->name('tasks.updateStatus');
    Route::post('/tasks/{task}/submit', [UserTaskController::class, 'submitVersion'])->name('tasks.submit');
    Route::post('/tasks/{task}/comment', [UserTaskController::class, 'addComment'])->name('tasks.comment');
    Route::get('/projects', [UserProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [UserProjectController::class, 'show'])->name('projects.show');
    Route::get('/reports', [UserReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [UserReportsController::class, 'exportTasks'])->name('reports.export');
});
