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
use App\Http\Controllers\User\ProjectController as UserProjectController;
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
    Route::get('/messages',                          [MessagesController::class, 'index'])->name('messages.index');
    Route::get('/messages/unread',                   [MessagesController::class, 'unread'])->name('messages.unread');
    Route::get('/messages/conversation/{user}',      [MessagesController::class, 'conversation'])->name('messages.conversation');
    Route::post('/messages/send',                    [MessagesController::class, 'send'])->name('messages.send');
    Route::get('/activities', [ActivitiesController::class, 'index'])->name('activities.index');
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/team', [TeamController::class, 'index'])->name('team.index');
    Route::get('/notifications/read/{id}',    [NotificationsController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationsController::class, 'markAllRead'])->name('notifications.mark-all-read');
});

// Admin routes
Route::middleware([AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');
    Route::resource('users', AdminUserController::class);
    Route::resource('projects', AdminProjectController::class);
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
    Route::post('meetings',                        [AdminMeetingController::class, 'store'])->name('meetings.store');
    Route::put('meetings/{meeting}',               [AdminMeetingController::class, 'update'])->name('meetings.update');
    Route::delete('meetings/{meeting}',            [AdminMeetingController::class, 'destroy'])->name('meetings.destroy');
    Route::get('settings/export/users',            [AdminSettingsController::class, 'exportUsers'])->name('settings.export.users');
    Route::get('settings/export/tasks',           [AdminSettingsController::class, 'exportTasks'])->name('settings.export.tasks');
    Route::get('settings/export/projects',        [AdminSettingsController::class, 'exportProjects'])->name('settings.export.projects');

    // Task approvals
    Route::get('approvals',                        [AdminTaskApprovalController::class, 'index'])->name('approvals.index');
    Route::post('tasks/{task}/approve',            [AdminTaskApprovalController::class, 'approve'])->name('tasks.approve');
    Route::post('tasks/{task}/reject',             [AdminTaskApprovalController::class, 'reject'])->name('tasks.reject');
});

// Manager routes
Route::middleware([ManagerMiddleware::class])->prefix('manager')->name('manager.')->group(function () {
    Route::get('/dashboard', [ManagerDashboard::class, 'index'])->name('dashboard');
});

// User routes
Route::middleware([UserMiddleware::class])->prefix('user')->name('user.')->group(function () {
    Route::get('/dashboard', [UserDashboard::class, 'index'])->name('dashboard');
    Route::post('/report',   [UserDashboard::class, 'submitReport'])->name('report');
    Route::get('/tasks', [UserTaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/{task}', [UserTaskController::class, 'show'])->name('tasks.show');
    Route::patch('/tasks/{task}/status', [UserTaskController::class, 'updateStatus'])->name('tasks.updateStatus');
    Route::post('/tasks/{task}/submit', [UserTaskController::class, 'submitVersion'])->name('tasks.submit');
    Route::get('/projects', [UserProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [UserProjectController::class, 'show'])->name('projects.show');
});
