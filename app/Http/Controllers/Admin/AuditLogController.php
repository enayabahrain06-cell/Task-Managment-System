<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('view_audit_log')) {
            abort(403, 'You do not have permission to view the Audit Log.');
        }

        $query = AuditLog::with('actor')->latest();

        if ($request->filled('action')) {
            $query->where('action', 'like', $request->action . '%');
        }

        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->actor_id);
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs  = $query->get();
        $users = User::orderBy('name')->get(['id', 'name']);

        $actionGroups = [
            'user'     => ['user.created','user.updated','user.deleted','user.deactivated','user.reactivated','user.role_changed','user.password_changed','user.archived','user.restored','user.held','user.released'],
            'tasks'    => ['task.approved','task.rejected','task.reassigned','task.deleted','task.force_deleted','task.reopened','task.archived','task.delivered','tasks.bulk_transferred'],
            'projects' => ['project.created','project.updated','project.deleted','project.reopened','project.closed'],
            'roles'    => ['role.created','role.updated','role.deleted'],
            'settings' => ['settings.updated','data.cleared','system.restored'],
        ];

        $errorLogs   = $this->parseErrorLogs();
        $logFileSize = $this->logFileSize();

        return view('admin.audit.index', compact('logs', 'users', 'actionGroups', 'errorLogs', 'logFileSize'));
    }

    public function clearLogs(Request $request)
    {
        if (!auth()->user()->hasPermission('view_audit_log')) {
            abort(403);
        }

        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            file_put_contents($logPath, '');
        }

        AuditLogger::log(
            'data.cleared',
            null,
            'Error logs cleared by ' . auth()->user()->name,
            ['file' => 'laravel.log']
        );

        return redirect()->route('admin.audit.index', ['tab' => 'errors'])
            ->with('success', 'Error log file cleared.');
    }

    private function parseErrorLogs(): array
    {
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath) || filesize($logPath) === 0) {
            return [];
        }

        // Read last 600 KB to avoid memory issues on large files
        $size     = filesize($logPath);
        $readSize = min($size, 614400);
        $fp       = fopen($logPath, 'r');
        fseek($fp, $size - $readSize);
        if ($readSize < $size) fgets($fp); // skip the partial first line
        $content  = fread($fp, $readSize);
        fclose($fp);

        $entries = [];
        $current = null;

        foreach (explode("\n", $content) as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*)$/', $line, $m)) {
                if ($current) {
                    $entries[] = $current;
                }
                // Strip trailing JSON context blob from the display message
                $msg = preg_replace('/ \{"exception":.*$/s', '', $m[4]);
                $msg = trim($msg);
                $current = [
                    'datetime' => $m[1],
                    'channel'  => $m[2],
                    'level'    => strtoupper($m[3]),
                    'message'  => $msg ?: $m[4],
                    'full'     => $m[4],
                    'trace'    => '',
                ];
            } elseif ($current !== null) {
                $current['trace'] .= $line . "\n";
            }
        }
        if ($current) {
            $entries[] = $current;
        }

        $levels = ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY', 'WARNING'];

        // Newest first, errors/warnings only, max 400 entries
        return array_values(array_slice(
            array_reverse(
                array_filter($entries, fn($e) => in_array($e['level'], $levels))
            ),
            0, 400
        ));
    }

    private function logFileSize(): string
    {
        $path = storage_path('logs/laravel.log');
        if (!file_exists($path)) return '0 B';
        $bytes = filesize($path);
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
