<?php

namespace Database\Seeders;

use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Users ───────────────────────────────────────────────────
        $admin = User::updateOrCreate(['email' => 'admin@taskmgmt.com'], [
            'name'     => 'Admin User',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);

        $mgr1 = User::updateOrCreate(['email' => 'manager@taskmgmt.com'], [
            'name'     => 'Sarah Johnson',
            'password' => Hash::make('password'),
            'role'     => 'manager',
        ]);

        $mgr2 = User::updateOrCreate(['email' => 'david.kim@taskmgmt.com'], [
            'name'     => 'David Kim',
            'password' => Hash::make('password'),
            'role'     => 'manager',
        ]);

        $alice  = User::updateOrCreate(['email' => 'alice.chen@taskmgmt.com'],   ['name' => 'Alice Chen',    'password' => Hash::make('password'), 'role' => 'user']);
        $bob    = User::updateOrCreate(['email' => 'bob.martinez@taskmgmt.com'], ['name' => 'Bob Martinez',  'password' => Hash::make('password'), 'role' => 'user']);
        $emma   = User::updateOrCreate(['email' => 'emma.wilson@taskmgmt.com'],  ['name' => 'Emma Wilson',   'password' => Hash::make('password'), 'role' => 'user']);
        $james  = User::updateOrCreate(['email' => 'james.lee@taskmgmt.com'],    ['name' => 'James Lee',     'password' => Hash::make('password'), 'role' => 'user']);
        $olivia = User::updateOrCreate(['email' => 'olivia.brown@taskmgmt.com'], ['name' => 'Olivia Brown',  'password' => Hash::make('password'), 'role' => 'user']);
        $ryan   = User::updateOrCreate(['email' => 'ryan.patel@taskmgmt.com'],   ['name' => 'Ryan Patel',    'password' => Hash::make('password'), 'role' => 'user']);

        // ── 2. Projects ────────────────────────────────────────────────
        $p1 = Project::firstOrCreate(['name' => 'Website Redesign'], [
            'description' => 'Complete overhaul of the company website with new branding, improved UX, and mobile-first design.',
            'deadline'    => Carbon::now()->addDays(30),
            'created_by'  => $admin->id,
            'status'      => 'active',
        ]);

        $p2 = Project::firstOrCreate(['name' => 'Mobile App Development'], [
            'description' => 'Build a cross-platform mobile app for iOS and Android to extend our web platform to mobile users.',
            'deadline'    => Carbon::now()->addDays(60),
            'created_by'  => $admin->id,
            'status'      => 'active',
        ]);

        $p3 = Project::firstOrCreate(['name' => 'CRM Integration'], [
            'description' => 'Integrate Salesforce CRM with our internal systems to streamline sales and customer management.',
            'deadline'    => Carbon::now()->addDays(45),
            'created_by'  => $admin->id,
            'status'      => 'active',
        ]);

        $p4 = Project::firstOrCreate(['name' => 'Marketing Campaign Q2'], [
            'description' => 'Digital marketing campaign for Q2 including email, social media, and paid ads.',
            'deadline'    => Carbon::now()->subDays(5),
            'created_by'  => $admin->id,
            'status'      => 'completed',
        ]);

        $p5 = Project::firstOrCreate(['name' => 'Analytics Dashboard'], [
            'description' => 'Internal analytics dashboard to monitor KPIs, user engagement, and system performance in real time.',
            'deadline'    => Carbon::now()->addDays(20),
            'created_by'  => $admin->id,
            'status'      => 'active',
        ]);

        $p6 = Project::firstOrCreate(['name' => 'API Gateway Setup'], [
            'description' => 'Design and deploy a centralised API gateway with rate limiting, authentication, and monitoring.',
            'deadline'    => Carbon::now()->subDays(10),
            'created_by'  => $admin->id,
            'status'      => 'overdue',
        ]);

        // ── 3. Tasks ───────────────────────────────────────────────────
        $tasks = [
            // Website Redesign
            ['project_id' => $p1->id, 'title' => 'Homepage UI Design',          'assigned_to' => $alice->id,  'status' => 'completed',   'priority' => 'high',   'deadline' => Carbon::now()->subDays(8),  'description' => 'Design the new homepage layout with hero section, features grid, and testimonials.'],
            ['project_id' => $p1->id, 'title' => 'Navigation Component',        'assigned_to' => $bob->id,    'status' => 'completed',   'priority' => 'high',   'deadline' => Carbon::now()->subDays(5),  'description' => 'Build responsive navigation with dropdown menus and mobile hamburger.'],
            ['project_id' => $p1->id, 'title' => 'Landing Page Development',    'assigned_to' => $alice->id,  'status' => 'in_progress', 'priority' => 'high',   'deadline' => Carbon::now()->addDays(4),  'description' => 'Develop the landing page based on approved Figma designs.'],
            ['project_id' => $p1->id, 'title' => 'Mobile Responsiveness',       'assigned_to' => $emma->id,   'status' => 'in_progress', 'priority' => 'medium', 'deadline' => Carbon::now()->addDays(7),  'description' => 'Ensure all pages render correctly on phones and tablets.'],
            ['project_id' => $p1->id, 'title' => 'SEO Optimisation',            'assigned_to' => $james->id,  'status' => 'pending',     'priority' => 'medium', 'deadline' => Carbon::now()->addDays(14), 'description' => 'Add meta tags, structured data, and sitemap for all pages.'],
            ['project_id' => $p1->id, 'title' => 'Performance Audit',           'assigned_to' => $bob->id,    'status' => 'pending',     'priority' => 'low',    'deadline' => Carbon::now()->addDays(20), 'description' => 'Run Lighthouse audits and optimise images, fonts, and JS bundles.'],

            // Mobile App
            ['project_id' => $p2->id, 'title' => 'Login & Auth Screens',        'assigned_to' => $alice->id,  'status' => 'completed',   'priority' => 'high',   'deadline' => Carbon::now()->subDays(12), 'description' => 'Implement email/password and OAuth login screens for iOS and Android.'],
            ['project_id' => $p2->id, 'title' => 'Dashboard Screen',            'assigned_to' => $emma->id,   'status' => 'in_progress', 'priority' => 'high',   'deadline' => Carbon::now()->addDays(6),  'description' => 'Build the main dashboard screen showing task summaries and quick actions.'],
            ['project_id' => $p2->id, 'title' => 'Push Notifications',          'assigned_to' => $james->id,  'status' => 'in_progress', 'priority' => 'medium', 'deadline' => Carbon::now()->addDays(10), 'description' => 'Integrate FCM for push notifications on task deadlines and mentions.'],
            ['project_id' => $p2->id, 'title' => 'User Profile Screen',         'assigned_to' => $bob->id,    'status' => 'pending',     'priority' => 'medium', 'deadline' => Carbon::now()->addDays(15), 'description' => 'Create profile screen with avatar upload, settings, and logout.'],
            ['project_id' => $p2->id, 'title' => 'App Store Submission',        'assigned_to' => $olivia->id, 'status' => 'pending',     'priority' => 'low',    'deadline' => Carbon::now()->addDays(50), 'description' => 'Prepare app screenshots, descriptions, and submit to App Store and Play Store.'],

            // CRM Integration
            ['project_id' => $p3->id, 'title' => 'API Documentation Review',   'assigned_to' => $james->id,  'status' => 'completed',   'priority' => 'high',   'deadline' => Carbon::now()->subDays(6),  'description' => 'Review Salesforce REST API docs and identify endpoints needed for integration.'],
            ['project_id' => $p3->id, 'title' => 'OAuth 2.0 Setup',            'assigned_to' => $bob->id,    'status' => 'in_progress', 'priority' => 'high',   'deadline' => Carbon::now()->addDays(5),  'description' => 'Configure OAuth 2.0 connected app in Salesforce and implement token flow.'],
            ['project_id' => $p3->id, 'title' => 'Data Migration Script',      'assigned_to' => $emma->id,   'status' => 'pending',     'priority' => 'high',   'deadline' => Carbon::now()->addDays(18), 'description' => 'Write migration script to sync existing contacts and leads from legacy system.'],
            ['project_id' => $p3->id, 'title' => 'Integration Testing & QA',   'assigned_to' => $ryan->id,   'status' => 'pending',     'priority' => 'medium', 'deadline' => Carbon::now()->addDays(35), 'description' => 'End-to-end testing of all CRM sync operations in staging environment.'],

            // Marketing Campaign (completed project)
            ['project_id' => $p4->id, 'title' => 'Campaign Strategy Document', 'assigned_to' => $emma->id,   'status' => 'completed',   'priority' => 'high',   'deadline' => Carbon::now()->subDays(20), 'description' => 'Define target audience, channels, budget allocation, and success metrics.'],
            ['project_id' => $p4->id, 'title' => 'Email Newsletter Templates', 'assigned_to' => $olivia->id, 'status' => 'completed',   'priority' => 'medium', 'deadline' => Carbon::now()->subDays(15), 'description' => 'Design and code 3 email templates (welcome, promo, weekly digest).'],
            ['project_id' => $p4->id, 'title' => 'Social Media Content',       'assigned_to' => $ryan->id,   'status' => 'completed',   'priority' => 'medium', 'deadline' => Carbon::now()->subDays(8),  'description' => 'Create 30 social media posts for LinkedIn, Twitter, and Instagram.'],
            ['project_id' => $p4->id, 'title' => 'Paid Ads Setup (Google)',    'assigned_to' => $james->id,  'status' => 'completed',   'priority' => 'high',   'deadline' => Carbon::now()->subDays(6),  'description' => 'Set up Google Ads campaigns with keyword targeting and conversion tracking.'],

            // Analytics Dashboard
            ['project_id' => $p5->id, 'title' => 'Requirements Gathering',     'assigned_to' => $mgr1->id,   'status' => 'completed',   'priority' => 'high',   'deadline' => Carbon::now()->subDays(10), 'description' => 'Meet with stakeholders to document KPIs and dashboard requirements.'],
            ['project_id' => $p5->id, 'title' => 'Chart Components Library',   'assigned_to' => $bob->id,    'status' => 'in_progress', 'priority' => 'high',   'deadline' => Carbon::now()->addDays(3),  'description' => 'Build reusable line, bar, donut, and heatmap chart components.'],
            ['project_id' => $p5->id, 'title' => 'Data Pipeline Setup',        'assigned_to' => $emma->id,   'status' => 'in_progress', 'priority' => 'high',   'deadline' => Carbon::now()->addDays(8),  'description' => 'Configure data ingestion pipeline from PostgreSQL, Redis, and S3 to dashboard.'],
            ['project_id' => $p5->id, 'title' => 'Report Export Feature',      'assigned_to' => $ryan->id,   'status' => 'pending',     'priority' => 'medium', 'deadline' => Carbon::now()->addDays(16), 'description' => 'Add PDF and CSV export for all dashboard reports.'],

            // API Gateway (overdue project)
            ['project_id' => $p6->id, 'title' => 'Architecture Design',        'assigned_to' => $james->id,  'status' => 'completed',   'priority' => 'high',   'deadline' => Carbon::now()->subDays(30), 'description' => 'Design gateway architecture: routing, load balancing, and failover strategy.'],
            ['project_id' => $p6->id, 'title' => 'Rate Limiting Implementation','assigned_to' => $bob->id,   'status' => 'in_progress', 'priority' => 'high',   'deadline' => Carbon::now()->subDays(12), 'description' => 'Implement token-bucket rate limiting per API key and IP address.'],
            ['project_id' => $p6->id, 'title' => 'Security Audit',             'assigned_to' => $olivia->id, 'status' => 'pending',     'priority' => 'high',   'deadline' => Carbon::now()->subDays(5),  'description' => 'Conduct security review: injection, auth bypass, and DDoS protection.'],
            ['project_id' => $p6->id, 'title' => 'Monitoring & Alerting',      'assigned_to' => $ryan->id,   'status' => 'pending',     'priority' => 'medium', 'deadline' => Carbon::now()->subDays(2),  'description' => 'Set up Prometheus + Grafana dashboards and PagerDuty alerts for gateway.'],
        ];

        $createdTasks = [];
        foreach ($tasks as $taskData) {
            $task = Task::firstOrCreate(
                ['title' => $taskData['title'], 'project_id' => $taskData['project_id']],
                $taskData
            );
            $createdTasks[] = $task;
        }

        // ── 4. Task Logs ───────────────────────────────────────────────
        $logEntries = [
            ['task' => 'Homepage UI Design',        'user' => $alice->id,  'action' => 'Task created and assigned',      'note' => 'Starting with wireframes before moving to high-fidelity designs.'],
            ['task' => 'Homepage UI Design',        'user' => $alice->id,  'action' => 'Status changed to in_progress',  'note' => 'Wireframes approved, moving to Figma designs.'],
            ['task' => 'Homepage UI Design',        'user' => $admin->id,  'action' => 'Status changed to completed',    'note' => 'Design approved by client. Great work Alice!'],
            ['task' => 'Navigation Component',      'user' => $bob->id,    'action' => 'Task created and assigned',      'note' => 'Based on the new design system.'],
            ['task' => 'Navigation Component',      'user' => $bob->id,    'action' => 'Status changed to completed',    'note' => 'All breakpoints tested. Merged to main.'],
            ['task' => 'Landing Page Development',  'user' => $alice->id,  'action' => 'Status changed to in_progress',  'note' => 'Pixel-perfect implementation in progress.'],
            ['task' => 'Login & Auth Screens',      'user' => $alice->id,  'action' => 'Status changed to completed',    'note' => 'Both iOS and Android tested. OAuth working correctly.'],
            ['task' => 'Dashboard Screen',          'user' => $emma->id,   'action' => 'Status changed to in_progress',  'note' => 'Base layout done, wiring up API calls now.'],
            ['task' => 'API Documentation Review',  'user' => $james->id,  'action' => 'Status changed to completed',    'note' => 'Identified 12 endpoints needed. Documentation shared with team.'],
            ['task' => 'Campaign Strategy Document','user' => $emma->id,   'action' => 'Status changed to completed',    'note' => 'Strategy signed off by marketing director.'],
            ['task' => 'Email Newsletter Templates','user' => $olivia->id, 'action' => 'Status changed to completed',    'note' => 'Templates tested in Mailchimp and Sendgrid. All good.'],
            ['task' => 'Requirements Gathering',    'user' => $mgr1->id,   'action' => 'Status changed to completed',    'note' => '3 stakeholder meetings completed, spec document finalised.'],
            ['task' => 'Chart Components Library',  'user' => $bob->id,    'action' => 'Status changed to in_progress',  'note' => 'Line and bar charts done, working on donut and heatmap.'],
            ['task' => 'Architecture Design',       'user' => $james->id,  'action' => 'Status changed to completed',    'note' => 'Architecture doc reviewed by lead architect and approved.'],
            ['task' => 'Rate Limiting Implementation','user' => $bob->id,  'action' => 'Status changed to in_progress',  'note' => 'Token-bucket logic implemented, integration tests failing — investigating.'],
        ];

        // Map task titles to IDs
        $taskMap = Task::pluck('id', 'title')->toArray();

        foreach ($logEntries as $log) {
            if (! isset($taskMap[$log['task']])) continue;
            DB::table('task_logs')->insertOrIgnore([[
                'task_id'    => $taskMap[$log['task']],
                'user_id'    => $log['user'],
                'action'     => $log['action'],
                'note'       => $log['note'],
                'created_at' => Carbon::now()->subHours(rand(1, 240)),
                'updated_at' => Carbon::now()->subHours(rand(1, 100)),
            ]]);
        }

        // ── 5. Calendar Events ─────────────────────────────────────────
        $today    = Carbon::today();
        $adminId  = $admin->id;

        $events = [
            // Today
            ['user_id' => $adminId,  'title' => 'Sprint Planning',          'description' => 'Plan tasks for the upcoming two-week sprint with the full team.',       'start_date' => $today,                  'end_date' => $today,                  'type' => 'manual'],
            ['user_id' => $mgr1->id, 'title' => 'Client Review — Website',  'description' => 'Demo the new homepage design to the client and gather feedback.',        'start_date' => $today,                  'end_date' => $today,                  'type' => 'manual'],
            // Tomorrow
            ['user_id' => $adminId,  'title' => 'Daily Standup',            'description' => 'Daily 15-minute standup to share progress and blockers.',                'start_date' => $today->copy()->addDay(), 'end_date' => $today->copy()->addDay(), 'type' => 'manual'],
            ['user_id' => $mgr2->id, 'title' => 'Design System Review',     'description' => 'Review the updated design system tokens and component library.',         'start_date' => $today->copy()->addDay(), 'end_date' => $today->copy()->addDay(), 'type' => 'manual'],
            // This week
            ['user_id' => $adminId,  'title' => 'Project Demo — Mobile App','description' => 'Internal demo of the mobile app dashboard screen and auth flows.',      'start_date' => $today->copy()->addDays(3), 'end_date' => $today->copy()->addDays(3), 'type' => 'project'],
            ['user_id' => $mgr1->id, 'title' => 'Analytics Kickoff',        'description' => 'Kick off the analytics dashboard project with the data team.',           'start_date' => $today->copy()->addDays(4), 'end_date' => $today->copy()->addDays(4), 'type' => 'project'],
            // Next week
            ['user_id' => $adminId,  'title' => 'Quarterly Review',         'description' => 'Q2 performance review covering all projects, KPIs, and team feedback.',  'start_date' => $today->copy()->addDays(7), 'end_date' => $today->copy()->addDays(7), 'type' => 'manual'],
            ['user_id' => $mgr2->id, 'title' => 'Security Audit Debrief',   'description' => 'Review findings from the API gateway security audit.',                   'start_date' => $today->copy()->addDays(9), 'end_date' => $today->copy()->addDays(9), 'type' => 'task'],
            // Later this month
            ['user_id' => $adminId,  'title' => 'Team Retrospective',       'description' => 'Sprint retrospective — what went well, what to improve.',               'start_date' => $today->copy()->addDays(14), 'end_date' => $today->copy()->addDays(14), 'type' => 'manual'],
            ['user_id' => $mgr1->id, 'title' => 'Product Roadmap Session',  'description' => 'Plan the product roadmap for Q3 with all stakeholders.',                'start_date' => $today->copy()->addDays(18), 'end_date' => $today->copy()->addDays(18), 'type' => 'manual'],
        ];

        foreach ($events as $event) {
            CalendarEvent::firstOrCreate(
                ['title' => $event['title'], 'start_date' => $event['start_date']],
                $event
            );
        }
    }
}
