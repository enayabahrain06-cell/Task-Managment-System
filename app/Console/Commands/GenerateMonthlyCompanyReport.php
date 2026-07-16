<?php

namespace App\Console\Commands;

use App\Services\NasService;
use App\Services\ReportPdfService;
use Illuminate\Console\Command;

class GenerateMonthlyCompanyReport extends Command
{
    protected $signature   = 'reports:monthly-company-summary';
    protected $description = 'Generate the company-wide task % by customer summary report and push it to the NAS';

    public function handle(NasService $nas, ReportPdfService $reportPdfService): int
    {
        if (!$nas->isEnabled()) {
            $this->info('NAS is not configured — skipping monthly company summary sync.');

            return self::SUCCESS;
        }

        $month = now()->format('Y-m');

        $pdfContent = $reportPdfService->buildCompanySummaryReportPdf();
        $tmpPath    = sys_get_temp_dir() . '/' . uniqid('monthly_company_report_') . '.pdf';
        file_put_contents($tmpPath, $pdfContent);
        $nas->copyToNasCompanyReport($tmpPath, "monthly-summary-{$month}.pdf");
        @unlink($tmpPath);

        $this->info('Monthly company summary report synced to NAS.');

        return self::SUCCESS;
    }
}
