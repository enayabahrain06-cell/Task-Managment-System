<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Task;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class GanttController extends Controller
{
    public function index(Request $request)
    {
        if (Setting::get('show_gantt_chart', '1') !== '1') {
            abort(403, 'Gantt Chart feature is disabled.');
        }

        $projects = Project::where('is_quick', false)
            ->with(['tasks' => function ($q) {
                $q->whereNotNull('deadline')
                  ->with(['assignee:id,name', 'assignees:id,name'])
                  ->orderBy('deadline');
            }])
            ->orderBy('name')
            ->get()
            ->filter(fn($p) => $p->tasks->isNotEmpty())
            ->values();

        // Compute overall date range
        $allDates = $projects->flatMap(fn($p) => $p->tasks->flatMap(fn($t) => [
            $t->created_at,
            $t->deadline,
        ]))->filter()->sort();

        $rangeStart = $allDates->first()
            ? Carbon::parse($allDates->first())->startOfWeek()->subWeek()
            : now()->startOfMonth();
        $rangeEnd   = $allDates->last()
            ? Carbon::parse($allDates->last())->endOfMonth()->addWeek()
            : now()->addMonths(2)->endOfMonth();

        // Build JSON-serialisable data for the chart
        $statusColors = [
            'draft'               => ['bg' => '#F3F4F6', 'text' => '#6B7280', 'label' => 'Draft'],
            'assigned'            => ['bg' => '#DBEAFE', 'text' => '#1D4ED8', 'label' => 'Assigned'],
            'viewed'              => ['bg' => '#E0E7FF', 'text' => '#4338CA', 'label' => 'Viewed'],
            'in_progress'         => ['bg' => '#EDE9FE', 'text' => '#6D28D9', 'label' => 'In Progress'],
            'submitted'           => ['bg' => '#FEF3C7', 'text' => '#B45309', 'label' => 'Submitted'],
            'approved'            => ['bg' => '#D1FAE5', 'text' => '#065F46', 'label' => 'Approved'],
            'revision_requested'  => ['bg' => '#FEE2E2', 'text' => '#991B1B', 'label' => 'Revision'],
            'delivered'           => ['bg' => '#ECFDF5', 'text' => '#047857', 'label' => 'Delivered'],
            'archived'            => ['bg' => '#F9FAFB', 'text' => '#9CA3AF', 'label' => 'Archived'],
        ];

        $chartData = $projects->map(function ($project) use ($statusColors) {
            return [
                'id'    => $project->id,
                'name'  => $project->name,
                'tasks' => $project->tasks->map(function ($task) use ($statusColors) {
                    $color = $statusColors[$task->status] ?? $statusColors['draft'];
                    $assignee = $task->assignee?->name ?? ($task->assignees->first()?->name ?? '—');
                    return [
                        'id'       => $task->id,
                        'title'    => $task->title,
                        'status'   => $task->status,
                        'color'    => $color,
                        'assignee' => $assignee,
                        'start'    => $task->created_at->format('Y-m-d'),
                        'end'      => $task->deadline->format('Y-m-d'),
                        'url'      => route('admin.tasks.show', $task->id),
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return view('admin.gantt', [
            'chartData'  => $chartData,
            'rangeStart' => $rangeStart->format('Y-m-d'),
            'rangeEnd'   => $rangeEnd->format('Y-m-d'),
            'projects'   => $projects,
        ]);
    }

    public function exportPdf()
    {
        if (Setting::get('show_gantt_chart', '1') !== '1') {
            abort(403);
        }

        $projects = Project::where('is_quick', false)
            ->with(['tasks' => function ($q) {
                $q->whereNotNull('deadline')
                  ->with(['assignee:id,name', 'assignees:id,name'])
                  ->orderBy('deadline');
            }])
            ->orderBy('name')
            ->get()
            ->filter(fn($p) => $p->tasks->isNotEmpty())
            ->values();

        $statusColors = [
            'draft'              => ['bg' => '#F3F4F6', 'color' => '#6B7280',  'label' => 'Draft'],
            'assigned'           => ['bg' => '#DBEAFE', 'color' => '#1D4ED8',  'label' => 'Assigned'],
            'viewed'             => ['bg' => '#E0E7FF', 'color' => '#4338CA',  'label' => 'Viewed'],
            'in_progress'        => ['bg' => '#FEF3C7', 'color' => '#D97706',  'label' => 'In Progress'],
            'paused'             => ['bg' => '#FFFBEB', 'color' => '#92400E',  'label' => 'Paused'],
            'submitted'          => ['bg' => '#EDE9FE', 'color' => '#6D28D9',  'label' => 'In Review'],
            'approved'           => ['bg' => '#D1FAE5', 'color' => '#065F46',  'label' => 'Approved'],
            'revision_requested' => ['bg' => '#FEE2E2', 'color' => '#991B1B',  'label' => 'Revision'],
            'delivered'          => ['bg' => '#ECFDF5', 'color' => '#047857',  'label' => 'Delivered'],
            'archived'           => ['bg' => '#F9FAFB', 'color' => '#9CA3AF',  'label' => 'Archived'],
            'pending_customer'   => ['bg' => '#FFF7ED', 'color' => '#C2410C',  'label' => 'Awaiting Client'],
        ];

        $doneStatuses  = ['approved', 'delivered', 'archived'];
        $totalTasks    = $projects->sum(fn($p) => $p->tasks->count());
        $overdueTasks  = $projects->sum(fn($p) => $p->tasks->filter(
            fn($t) => $t->deadline->isPast() && !in_array($t->status, $doneStatuses)
        )->count());
        $doneTasks     = $projects->sum(fn($p) => $p->tasks->whereIn('status', $doneStatuses)->count());

        $settings = Setting::pluck('value', 'key');
        $appName  = $settings['app_name'] ?? config('app.name');
        $logoPath = null;
        if (!empty($settings['logo_path'])) {
            $path = storage_path('app/public/' . ltrim($settings['logo_path'], '/'));
            if (file_exists($path)) {
                $ext      = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mime     = $ext === 'jpg' ? 'jpeg' : $ext;
                $logoPath = 'data:image/' . $mime . ';base64,' . base64_encode(file_get_contents($path));
            }
        }

        $summary = [
            'total_projects' => $projects->count(),
            'total_tasks'    => $totalTasks,
            'overdue_tasks'  => $overdueTasks,
            'done_tasks'     => $doneTasks,
            'generated_at'   => now()->format('d M Y, H:i'),
        ];

        // Fit everything on one A4 landscape page.
        // Conservative available height = 580px (accounts for DomPDF padding/borders).
        // Project separators are (rh+6) each; task rows are rh.
        // rh = (580 - projCount*6) / (projCount + taskCount)
        $projCount = $projects->count();
        $dataRows  = $projCount + $totalTasks;
        $available = 580 - ($projCount * 6);
        $rowHeight = $dataRows > 0 ? (int) max(13, min(48, floor($available / $dataRows))) : 20;

        $pdf = Pdf::loadView('admin.gantt-pdf', compact(
            'projects', 'summary', 'statusColors', 'appName', 'logoPath', 'settings', 'rowHeight'
        ))
            ->setPaper('a4', 'landscape')
            ->setOptions(['defaultFont' => 'sans-serif', 'isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true, 'dpi' => 96]);

        return $pdf->download('gantt-chart-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportPng()
    {
        if (Setting::get('show_gantt_chart', '1') !== '1') {
            abort(403);
        }

        $projects = Project::where('is_quick', false)
            ->with(['tasks' => function ($q) {
                $q->whereNotNull('deadline')
                  ->with(['assignee:id,name', 'assignees:id,name'])
                  ->orderBy('deadline');
            }])
            ->orderBy('name')
            ->get()
            ->filter(fn($p) => $p->tasks->isNotEmpty())
            ->values();

        $statusColors = [
            'draft'              => ['bg' => '#F3F4F6', 'color' => '#6B7280',  'label' => 'Draft'],
            'assigned'           => ['bg' => '#DBEAFE', 'color' => '#1D4ED8',  'label' => 'Assigned'],
            'viewed'             => ['bg' => '#E0E7FF', 'color' => '#4338CA',  'label' => 'Viewed'],
            'in_progress'        => ['bg' => '#FEF3C7', 'color' => '#D97706',  'label' => 'In Progress'],
            'paused'             => ['bg' => '#FFFBEB', 'color' => '#92400E',  'label' => 'Paused'],
            'submitted'          => ['bg' => '#EDE9FE', 'color' => '#6D28D9',  'label' => 'In Review'],
            'approved'           => ['bg' => '#D1FAE5', 'color' => '#065F46',  'label' => 'Approved'],
            'revision_requested' => ['bg' => '#FEE2E2', 'color' => '#991B1B',  'label' => 'Revision'],
            'delivered'          => ['bg' => '#ECFDF5', 'color' => '#047857',  'label' => 'Delivered'],
            'archived'           => ['bg' => '#F9FAFB', 'color' => '#9CA3AF',  'label' => 'Archived'],
            'pending_customer'   => ['bg' => '#FFF7ED', 'color' => '#C2410C',  'label' => 'Awaiting Client'],
        ];

        $doneStatuses = ['approved', 'delivered', 'archived'];
        $totalTasks   = $projects->sum(fn($p) => $p->tasks->count());
        $overdueTasks = $projects->sum(fn($p) => $p->tasks->filter(
            fn($t) => $t->deadline->isPast() && !in_array($t->status, $doneStatuses)
        )->count());
        $doneTasks = $projects->sum(fn($p) => $p->tasks->whereIn('status', $doneStatuses)->count());

        $settings = Setting::pluck('value', 'key');
        $appName  = $settings['app_name'] ?? config('app.name');

        $summary = [
            'total_projects' => $projects->count(),
            'total_tasks'    => $totalTasks,
            'overdue_tasks'  => $overdueTasks,
            'done_tasks'     => $doneTasks,
            'generated_at'   => now()->format('d M Y, H:i'),
        ];

        // A4 landscape at 150 DPI: 1754 × 1240 px
        $a4W = 1754;
        $a4H = 1240;

        $dataRows  = $projects->count() + $totalTasks;
        $rowHeight = $dataRows > 0 ? (int) max(20, min(70, floor(960 / $dataRows))) : 32;

        $html = view('admin.gantt-pdf', compact(
            'projects', 'summary', 'statusColors', 'appName', 'settings', 'rowHeight'
        ))->with('logoPath', null)->render();

        // Write HTML to temp file
        $tmpHtml = tempnam(sys_get_temp_dir(), 'gantt_') . '.html';
        $tmpPng  = tempnam(sys_get_temp_dir(), 'gantt_') . '.png';
        file_put_contents($tmpHtml, $html);

        $cmd = escapeshellcmd("wkhtmltoimage") .
            " --quiet --format png" .
            " --width {$a4W}" .
            " --disable-smart-width" .
            " " . escapeshellarg($tmpHtml) .
            " " . escapeshellarg($tmpPng);

        exec($cmd . ' 2>/dev/null', $out, $code);

        if ($code !== 0 || !file_exists($tmpPng) || filesize($tmpPng) === 0) {
            @unlink($tmpHtml);
            @unlink($tmpPng);
            abort(500, 'PNG generation failed. Code: ' . $code);
        }

        // Load rendered image and place on exact A4 canvas using GD
        $rendered = @imagecreatefrompng($tmpPng);
        $canvas   = imagecreatetruecolor($a4W, $a4H);
        $white    = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        if ($rendered) {
            $rH = imagesy($rendered);
            if ($rH <= $a4H) {
                // Content shorter than A4 — centre vertically
                $offsetY = (int) (($a4H - $rH) / 2);
                imagecopy($canvas, $rendered, 0, $offsetY, 0, 0, $a4W, $rH);
            } else {
                // Content taller — scale down to fit
                imagecopyresampled($canvas, $rendered, 0, 0, 0, 0, $a4W, $a4H, $a4W, $rH);
            }
            imagedestroy($rendered);
        }

        ob_start();
        imagepng($canvas, null, 6);
        $png = ob_get_clean();
        imagedestroy($canvas);

        @unlink($tmpHtml);
        @unlink($tmpPng);

        $filename = 'gantt-chart-' . now()->format('Y-m-d') . '.png';
        return response($png, 200, [
            'Content-Type'        => 'image/png',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ]);
    }

    public function exportCsv()
    {
        if (Setting::get('show_gantt_chart', '1') !== '1') {
            abort(403);
        }

        $projects = Project::where('is_quick', false)
            ->with(['tasks' => function ($q) {
                $q->whereNotNull('deadline')
                  ->with(['assignee:id,name', 'assignees:id,name'])
                  ->orderBy('deadline');
            }])
            ->orderBy('name')
            ->get()
            ->filter(fn($p) => $p->tasks->isNotEmpty())
            ->values();

        $settings     = Setting::pluck('value', 'key');
        $appName      = $settings['app_name'] ?? config('app.name');
        $primaryHex   = ltrim($settings['primary_color'] ?? '#4F46E5', '#');

        $doneStatuses = ['approved', 'delivered', 'archived'];
        $totalTasks   = $projects->sum(fn($p) => $p->tasks->count());
        $overdueTasks = $projects->sum(fn($p) => $p->tasks->filter(
            fn($t) => $t->deadline->isPast() && !in_array($t->status, $doneStatuses)
        )->count());
        $doneTasks    = $projects->sum(fn($p) => $p->tasks->whereIn('status', $doneStatuses)->count());
        $inProgTasks  = $projects->sum(fn($p) => $p->tasks->whereIn('status', ['in_progress', 'paused'])->count());

        // Status labels + badge colors (bg hex, text hex)
        $statusMeta = [
            'draft'              => ['label' => 'Draft',              'bg' => 'F3F4F6', 'fg' => '6B7280'],
            'assigned'           => ['label' => 'Assigned',           'bg' => 'DBEAFE', 'fg' => '1D4ED8'],
            'viewed'             => ['label' => 'Viewed',             'bg' => 'E0E7FF', 'fg' => '4338CA'],
            'in_progress'        => ['label' => 'In Progress',        'bg' => 'FEF3C7', 'fg' => 'D97706'],
            'paused'             => ['label' => 'Paused',             'bg' => 'FFFBEB', 'fg' => '92400E'],
            'submitted'          => ['label' => 'In Review',          'bg' => 'EDE9FE', 'fg' => '6D28D9'],
            'approved'           => ['label' => 'Approved',           'bg' => 'D1FAE5', 'fg' => '065F46'],
            'revision_requested' => ['label' => 'Revision Requested', 'bg' => 'FEE2E2', 'fg' => '991B1B'],
            'delivered'          => ['label' => 'Delivered',          'bg' => 'ECFDF5', 'fg' => '047857'],
            'archived'           => ['label' => 'Archived',           'bg' => 'F9FAFB', 'fg' => '9CA3AF'],
            'pending_customer'   => ['label' => 'Awaiting Client',    'bg' => 'FFF7ED', 'fg' => 'C2410C'],
        ];

        // ── Build spreadsheet ─────────────────────────────────────────
        $spread = new Spreadsheet();
        $sheet  = $spread->getActiveSheet();
        $sheet->setTitle('Gantt Chart');

        // Darken primary for project-header rows
        $pr = hexdec(substr($primaryHex, 0, 2));
        $pg = hexdec(substr($primaryHex, 2, 2));
        $pb = hexdec(substr($primaryHex, 4, 2));
        $darkHex = sprintf('%02X%02X%02X', (int)($pr * .55), (int)($pg * .55), (int)($pb * .55));
        $midHex  = sprintf('%02X%02X%02X', (int)($pr * .78), (int)($pg * .78), (int)($pb * .78));

        $cols = ['A','B','C','D','E','F','G','H','I'];
        $colWidths = [28, 38, 20, 22, 14, 14, 16, 20, 10];
        foreach ($cols as $i => $col) {
            $sheet->getColumnDimension($col)->setWidth($colWidths[$i]);
        }
        $sheet->getDefaultRowDimension()->setRowHeight(18);

        // ── Row 1: Big title ─────────────────────────────────────────
        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', $appName . ' — Gantt Chart Report');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => strtoupper($primaryHex)]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(36);

        // ── Row 2: Generated ─────────────────────────────────────────
        $sheet->mergeCells('A2:I2');
        $sheet->setCellValue('A2', 'Generated: ' . now()->format('d M Y, H:i') . '   |   Report: Gantt Chart Timeline');
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => strtoupper($midHex)]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // ── Row 3: Summary stats ──────────────────────────────────────
        $summaryData = [
            'A3' => ['label' => 'Projects',    'value' => $projects->count()],
            'C3' => ['label' => 'Total Tasks', 'value' => $totalTasks],
            'E3' => ['label' => 'In Progress', 'value' => $inProgTasks],
            'G3' => ['label' => 'Completed',   'value' => $doneTasks],
            'I3' => ['label' => 'Overdue',     'value' => $overdueTasks],
        ];
        foreach ($summaryData as $cell => $data) {
            $sheet->setCellValue($cell, $data['label'] . ': ' . $data['value']);
        }
        $sheet->getStyle('A3:I3')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => strtoupper($primaryHex)]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F4FF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(22);

        // ── Row 4: Blank spacer ───────────────────────────────────────
        $sheet->getRowDimension(4)->setRowHeight(6);

        // ── Row 5: Column headers ─────────────────────────────────────
        $headers = ['Project', 'Task', 'Status', 'Assignee', 'Start Date', 'Deadline', 'Duration (Days)', 'Days Remaining', 'Overdue'];
        foreach ($cols as $i => $col) {
            $sheet->setCellValue($col . '5', $headers[$i]);
        }
        $sheet->getStyle('A5:I5')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => strtoupper($darkHex)]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $sheet->getRowDimension(5)->setRowHeight(22);

        // Freeze rows 1-5 + column A
        $sheet->freezePane('B6');

        // ── Data rows ─────────────────────────────────────────────────
        $row = 6;
        foreach ($projects as $project) {
            // Project separator row
            $sheet->mergeCells("A{$row}:I{$row}");
            $sheet->setCellValue("A{$row}", strtoupper($project->name));
            $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => strtoupper($primaryHex)]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(20);
            $row++;

            foreach ($project->tasks as $task) {
                $assignee = $task->assignee?->name ?? ($task->assignees->first()?->name ?? '—');
                $isOver   = $task->deadline->isPast() && !in_array($task->status, $doneStatuses);
                $duration = (int) $task->created_at->diffInDays($task->deadline);
                $diffDays = (int) now()->diffInDays($task->deadline, false);
                $daysRem  = $isOver
                    ? abs($diffDays) . ' days overdue'
                    : ($diffDays === 0 ? 'Due today' : $diffDays . ' days left');

                $meta = $statusMeta[$task->status] ?? ['label' => ucfirst($task->status), 'bg' => 'F3F4F6', 'fg' => '6B7280'];

                $sheet->setCellValue("A{$row}", $project->name);
                $sheet->setCellValue("B{$row}", $task->title);
                $sheet->setCellValue("C{$row}", $meta['label']);
                $sheet->setCellValue("D{$row}", $assignee);
                $sheet->setCellValue("E{$row}", $task->created_at->format('d M Y'));
                $sheet->setCellValue("F{$row}", $task->deadline->format('d M Y'));
                $sheet->setCellValue("G{$row}", $duration);
                $sheet->setCellValue("H{$row}", $daysRem);
                $sheet->setCellValue("I{$row}", $isOver ? 'Yes' : 'No');

                // Row base style
                $rowBg = ($row % 2 === 0) ? 'FFFFFF' : 'F8FAFC';
                $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rowBg]],
                    'font'      => ['size' => 9],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
                ]);

                // Status badge color on column C
                $sheet->getStyle("C{$row}")->applyFromArray([
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $meta['bg']]],
                    'font'      => ['bold' => true, 'size' => 8.5, 'color' => ['rgb' => $meta['fg']]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Overdue highlighting
                if ($isOver) {
                    $sheet->getStyle("F{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'DC2626']],
                    ]);
                    $sheet->getStyle("H{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'DC2626']],
                    ]);
                    $sheet->getStyle("I{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']],
                        'font' => ['bold' => true, 'color' => ['rgb' => 'DC2626']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                } else {
                    $sheet->getStyle("I{$row}")->applyFromArray([
                        'font'      => ['color' => ['rgb' => '9CA3AF']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                }

                // Days remaining color
                $daysColor = $isOver ? 'DC2626' : ($diffDays <= 3 ? 'D97706' : '059669');
                $sheet->getStyle("H{$row}")->getFont()->getColor()->setRGB($daysColor);

                // Numeric center
                $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getRowDimension($row)->setRowHeight(18);
                $row++;
            }

            // Thin spacer row between projects
            $sheet->getRowDimension($row)->setRowHeight(4);
            $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            ]);
            $row++;
        }

        // Outer border around data area
        if ($row > 6) {
            $sheet->getStyle("A5:I" . ($row - 1))->applyFromArray([
                'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => strtoupper($primaryHex)]]],
            ]);
        }

        // ── Stream download ───────────────────────────────────────────
        $filename = 'gantt-chart-' . now()->format('Y-m-d') . '.xlsx';
        $spread->getProperties()
            ->setTitle('Gantt Chart Report')
            ->setCreator($appName);

        $writer = new XlsxWriter($spread);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return response($content, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
