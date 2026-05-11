# Marketing File Organization System

## Overview
This document proposes a file and folder organization model for a marketing system that serves multiple companies, where each company has recurring monthly projects, approval workflows, rejected items, and reusable shared assets such as logos and brand files. The recommended approach is to organize operational work by company, year, month, and project, while storing reusable brand assets in a single centralized library per company to avoid duplicates and version confusion.[cite:1][cite:2][cite:56][cite:57]

## Core structure
The base structure should separate reusable company assets from project-specific work. This keeps shared brand materials permanent and stable, while monthly project folders remain focused on active work, review, delivery, and history.[cite:1][cite:2][cite:56][cite:67]

```text
Marketing_System/
├── Companies/
│   ├── Company_A/
│   │   ├── Brand_Assets/
│   │   │   ├── Logos/
│   │   │   │   ├── Approved/
│   │   │   │   ├── Old_Versions/
│   │   │   │   └── Do_Not_Use/
│   │   │   ├── Brand_Guidelines/
│   │   │   ├── Fonts/
│   │   │   ├── Templates/
│   │   │   └── Product_Photos/
│   │   ├── Projects/
│   │   │   ├── 2026/
│   │   │   │   ├── 2026-05/
│   │   │   │   │   ├── PRJ-001_Summer_Campaign/
│   │   │   │   │   │   ├── 00_Admin/
│   │   │   │   │   │   ├── 01_Brief/
│   │   │   │   │   │   ├── 02_Source/
│   │   │   │   │   │   ├── 03_Working/
│   │   │   │   │   │   ├── 04_Review/
│   │   │   │   │   │   ├── 05_Approved/
│   │   │   │   │   │   ├── 06_Rejected/
│   │   │   │   │   │   ├── 07_Delivered/
│   │   │   │   │   │   └── 08_Archive/
│   │   │   │   │   ├── PRJ-002_Menu_Design/
│   │   │   │   │   └── PRJ-003_Reels/
│   │   │   └── 2026-06/
│   └── Company_B/
├── Internal_Templates/
├── Shared_Resources/
└── Archive/
```

## Shared asset model
Reusable files such as logos, brand guidelines, font packages, and standard templates should be stored only once in `Brand_Assets`. Projects should not contain duplicate copies of those files unless a file has been customized specifically for that project.[cite:56][cite:57][cite:63]

This is a single-source-of-truth model, which is commonly recommended in digital asset management because it reduces duplicate storage, helps teams use the latest approved materials, and preserves consistent brand usage across projects.[cite:56][cite:57][cite:58]

Inside each project, a small reference record can point to the shared asset instead of copying it:

```text
00_Project_References/
- uses: Company_A/Brand_Assets/Logos/Approved/main-logo-ai-v03.ai
- uses: Company_A/Brand_Assets/Brand_Guidelines/brand-book-v02.pdf
```

## Workflow stages
Project folders should reflect the lifecycle of work so that team members can identify status immediately. Approval-focused systems work best when review steps, versions, and outcomes are explicit rather than implied.[cite:7][cite:13][cite:15]

Recommended stage folders:
- `00_Admin` for history log, notes, approval register, and internal coordination.
- `01_Brief` for client brief, scope, and planning documents.
- `02_Source` for raw files received from the client or source material.
- `03_Working` for drafts and active edits.
- `04_Review` for files currently awaiting approval or client feedback.
- `05_Approved` for accepted versions.
- `06_Rejected` for declined versions kept only for history.
- `07_Delivered` for final exported files sent to the client.
- `08_Archive` for closed project materials.[cite:7][cite:15]

## File naming convention
A strict naming convention should be used for all project files so versions remain readable and sortable. Version-control guidance commonly recommends stable names with structured version numbers and avoiding ambiguous labels such as “final” or “last.”[cite:9]

Recommended pattern:

```text
[CompanyCode]_[ProjectCode]_[AssetType]_[Description]_[YYYY-MM-DD]_v01.ext
```

Examples:

```text
ACME_PRJ001_InstagramPost_EidOffer_2026-05-07_v03.psd
ACME_PRJ001_InstagramPost_EidOffer_2026-05-07_v03.jpg
ACME_PRJ001_Video_ReelLaunch_2026-05-07_v02.mp4
```

Rules:
- Use `YYYY-MM-DD` for dates.
- Use `v01`, `v02`, `v03` for revisions.
- Do not use `final`, `final2`, or `latest` in filenames.
- Move approved files into `05_Approved` rather than renaming them inconsistently.
- Keep rejected files in `06_Rejected` with related comments or review notes.[cite:6][cite:9][cite:12]

## History tracking
Every project should keep a compact history log so reassignments, review decisions, approvals, and deliveries remain traceable. Approval systems are stronger when they preserve who changed what, when it was reviewed, and which version became official.[cite:7][cite:15]

Example history log:

```text
project-history.md
- 2026-05-01: Project created by Admin
- 2026-05-03: First design uploaded v01
- 2026-05-04: Client requested changes
- 2026-05-05: Revised design uploaded v02
- 2026-05-06: v02 approved by client
- 2026-05-07: Final export delivered
```

Suggested tracked fields:
- Project ID
- Company name
- Month
- Status
- Current approved version
- Rejected versions
- Reviewer name
- Approval date
- Delivery date[cite:7][cite:15]

## System design recommendation
The strongest software implementation is to store reusable files as assets with unique IDs, then let many projects reference those assets through a linking table instead of uploading duplicate copies. Asset-centric systems and duplicate detection workflows are commonly used to prevent wasted storage and avoid outdated copies circulating across teams.[cite:61][cite:64][cite:70]

Recommended logical data model:
- `companies`
- `projects`
- `assets`
- `project_assets`
- `asset_versions`
- `asset_status`
- `project_history`
- `approvals`
- `rejections`[cite:56][cite:57][cite:70]

Operational rules:
- Store each shared file physically once in the asset library.
- Let projects save only references to shared assets.
- Create a new version record when a logo or shared asset changes.
- Keep older versions for traceability, but mark them archived or do-not-use.
- Use file-hash detection such as MD5 or SHA to detect duplicate uploads and stop redundant storage.[cite:61][cite:62][cite:64][cite:70]

## Recommended policy
The best operating policy for this type of marketing system is a hybrid structure: recurring project work is organized by company, year, month, and project, while shared resources live in a permanent company asset library. This gives operational clarity for monthly work and also prevents logos and other standard files from being stored many times in different folders.[cite:1][cite:2][cite:56][cite:57]
