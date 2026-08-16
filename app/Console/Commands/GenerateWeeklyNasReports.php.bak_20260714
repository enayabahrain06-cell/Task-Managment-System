<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\User;
use App\Services\NasService;
use App\Services\ReportPdfService;
use Illuminate\Console\Command;

class GenerateWeeklyNasReports extends Command
{
    protected $signature   = 'reports:weekly-nas-sync';
    protected $description = 'Generate a weekly PDF report for every active user and every customer, and push them to the NAS';

    public function handle(NasService $nas, ReportPdfService $reportPdfService): int
    {
        if (!$nas->isEnabled()) {
            $this->info('NAS is not configured — skipping weekly report sync.');

            return self::SUCCESS;
        }

        $week = now()->format('Y-\WW');

        $usersSynced = 0;
        foreach (User::where('status', 'active')->get() as $user) {
            if (!$reportPdfService->userHasTasks($user)) continue;

            $pdfContent = $reportPdfService->buildUserReportPdf($user);
            $tmpPath    = sys_get_temp_dir() . '/' . uniqid('weekly_user_report_') . '.pdf';
            file_put_contents($tmpPath, $pdfContent);
            $nas->copyToNasUserReport($user, $tmpPath, "weekly-report-{$week}.pdf");
            @unlink($tmpPath);
            $usersSynced++;
        }

        $customersSynced = 0;
        foreach (Customer::all() as $customer) {
            if (!$reportPdfService->customerHasTasks($customer)) continue;

            $pdfContent = $reportPdfService->buildCustomerReportPdf($customer);
            $tmpPath    = sys_get_temp_dir() . '/' . uniqid('weekly_customer_report_') . '.pdf';
            file_put_contents($tmpPath, $pdfContent);
            $nas->copyToNasCustomerReport($customer, $tmpPath, "weekly-report-{$week}.pdf");
            @unlink($tmpPath);
            $customersSynced++;
        }

        $this->info("Weekly NAS reports: {$usersSynced} user report(s), {$customersSynced} customer report(s) synced.");

        return self::SUCCESS;
    }
}
