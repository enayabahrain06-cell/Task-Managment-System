@extends('layouts.app')
@section('title', 'Task Approvals')

@section('content')
<style>
/* ── Card ── */
.apv-card {
    background: #fff;
    border-radius: 18px;
    border: 1px solid #F3F4F6;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03);
    margin-bottom: 20px;
    overflow: hidden;
    transition: box-shadow .22s, border-color .22s, transform .22s;
    max-width: 860px;
}
.apv-card:hover {
    box-shadow: 0 8px 24px rgba(99,102,241,.1), 0 2px 8px rgba(0,0,0,.04);
    border-color: #E0E7FF;
    transform: translateY(-1px);
}
/* priority top accent bar */
.apv-card.pri-high   { border-top: 3px solid #EF4444; }
.apv-card.pri-medium { border-top: 3px solid #F59E0B; }
.apv-card.pri-low    { border-top: 3px solid #10B981; }

/* ── Sections ── */
.apv-header     { padding: 16px 20px 13px; }
.apv-submission { padding: 13px 20px 14px; background: #FAFBFF; border-top: 1px solid #F3F4F6; }
.apv-actions    { display: grid; grid-template-columns: 1fr 1fr; border-top: 1px solid #F3F4F6; }
.apv-approve    { padding: 14px 18px 16px; border-right: 1px solid #F3F4F6; }
.apv-reject     { padding: 14px 18px 16px; }
.apv-footer     { padding: 10px 20px 12px; border-top: 1px solid #F3F4F6; background: #FAFBFF; display: flex; gap: 10px; align-items: flex-start; }

/* ── Action inputs ── */
.apv-input {
    width: 100%; padding: 9px 12px; border-radius: 9px; font-size: 12px;
    color: #111827; box-sizing: border-box; outline: none;
    transition: border-color .15s, box-shadow .15s;
    margin-bottom: 9px;
}
.apv-input-green { border: 1.5px solid #BBF7D0; background: #F0FDF4; }
.apv-input-green:focus { border-color: #34D399; box-shadow: 0 0 0 3px rgba(52,211,153,.12); }
.apv-input-red   { border: 1.5px solid #FECACA; background: #FEF2F2; }
.apv-input-red:focus   { border-color: #F87171; box-shadow: 0 0 0 3px rgba(248,113,113,.12); }

/* ── Buttons ── */
.btn-approve {
    width: 100%; background: linear-gradient(135deg,#10B981,#059669); color: #fff;
    border: none; padding: 10px; border-radius: 10px; font-size: 13px; font-weight: 700;
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 7px;
    box-shadow: 0 2px 8px rgba(16,185,129,.25); transition: opacity .15s, box-shadow .15s, transform .1s;
}
.btn-approve:hover { opacity: .92; box-shadow: 0 4px 14px rgba(16,185,129,.35); transform: translateY(-1px); }
.btn-reject {
    width: 100%; background: linear-gradient(135deg,#EF4444,#DC2626); color: #fff;
    border: none; padding: 10px; border-radius: 10px; font-size: 13px; font-weight: 700;
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 7px;
    box-shadow: 0 2px 8px rgba(239,68,68,.25); transition: opacity .15s, box-shadow .15s, transform .1s;
}
.btn-reject:hover { opacity: .92; box-shadow: 0 4px 14px rgba(239,68,68,.35); transform: translateY(-1px); }

/* ── Thumbnail ── */
.apv-thumb {
    border-radius: 10px; overflow: hidden; border: 1.5px solid #DDE3F5;
    cursor: pointer; flex-shrink: 0; position: relative;
    transition: transform .18s, box-shadow .18s;
}
.apv-thumb:hover { transform: scale(1.02); box-shadow: 0 6px 20px rgba(99,102,241,.18); }

/* ── History table ── */
.hist-table { width: 100%; border-collapse: collapse; }
.hist-table thead th {
    padding: 11px 16px; text-align: left; font-size: 10.5px; font-weight: 700;
    color: #9CA3AF; text-transform: uppercase; letter-spacing: .06em;
    background: #F9FAFB; border-bottom: 1px solid #F0F0F0;
}
.hist-table tbody tr { border-bottom: 1px solid #F7F7F7; transition: background .12s; }
.hist-table tbody tr:hover { background: #F8FAFF; }
.hist-table tbody tr:last-child { border-bottom: none; }
.hist-table td { padding: 12px 16px; }

/* ── History card view ── */
.hist-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 14px;
}
.hist-card {
    background: #fff;
    border: 1px solid #EBEBEB;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(99,102,241,.06);
    overflow: hidden;
    transition: box-shadow .2s, transform .2s;
}
.hist-card:hover {
    box-shadow: 0 8px 24px rgba(99,102,241,.13);
    transform: translateY(-2px);
}
.hist-card.dec-approved { border-top: 3px solid #10B981; }
.hist-card.dec-rejected { border-top: 3px solid #EF4444; }
.hist-card-head { padding: 14px 16px 10px; }
.hist-card-body { padding: 10px 16px 12px; border-top: 1px solid #F3F4F6; background: #FAFBFF; }
.hist-card-foot { padding: 10px 16px 12px; border-top: 1px solid #F3F4F6; display: flex; gap: 6px; flex-wrap: wrap; }

/* ── View toggle ── */
.hist-view-toggle { display: flex; gap: 4px; }
.hist-view-btn {
    display: flex; align-items: center; gap: 5px;
    padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600;
    border: 1.5px solid #E5E7EB; cursor: pointer; transition: all .15s;
    background: #FAFAFA; color: #6B7280;
}
.hist-view-btn.active {
    background: #EEF2FF; color: #4F46E5; border-color: #C7D2FE;
}
.hist-view-btn:hover:not(.active) { background: #F3F4F6; }

/* ── Version history items ── */
.version-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 14px; border-radius: 10px; gap: 10px;
    background: #F5F7FF; border: 1px solid #EEF0FA;
    transition: background .12s;
}
.version-row:hover { background: #EEF2FF; }

/* ── Scrollable table wrapper ── */
.tbl-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.tbl-scroll .hist-table { min-width: 680px; }

/* ══ Responsive ═══════════════════════════════════════════════ */

/* Tablet (≤ 900px) */
@media (max-width: 900px) {
    .apv-card { max-width: 100%; }
}

/* Mobile landscape / small tablet (≤ 700px) */
@media (max-width: 700px) {
    /* Stack approve / reject vertically */
    .apv-actions { grid-template-columns: 1fr; }
    .apv-approve { border-right: none; border-bottom: 1px solid #F0F4F8; }

    /* Tighten card padding */
    .apv-header     { padding: 13px 14px 11px; }
    .apv-submission { padding: 11px 14px; }
    .apv-approve,
    .apv-reject     { padding: 13px 14px 15px; }
    .apv-footer     { padding: 10px 14px 12px; }

    /* Comment form stacks */
    .apv-footer > form { flex-wrap: wrap; }

    /* Version row: stack on narrow screens */
    .version-row { flex-wrap: wrap; }

    /* History / social table min-width handled by tbl-scroll */
    .hist-table th { font-size: 10px; padding: 9px 10px; }
    .hist-table td { padding: 9px 10px; font-size: 12px; }

    /* Submission body: stack thumb + note */
    .apv-sub-body { flex-direction: column !important; }
    .apv-thumb    { width: 100% !important; }
    .apv-thumb img,
    .apv-thumb > div:first-child { width: 100% !important; height: 140px !important; }
}

/* Mobile portrait (≤ 480px) */
@media (max-width: 480px) {
    /* Page header */
    .apv-page-header { gap: 10px; }
    .apv-page-header h1 { font-size: 18px; }

    /* Tabs: allow horizontal scroll */
    .apv-tabs-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 4px; }
    .apv-tabs-scroll::-webkit-scrollbar { height: 3px; }
    .apv-tabs-scroll::-webkit-scrollbar-thumb { background: #C7D2FE; border-radius: 2px; }
    .apv-tabs { white-space: nowrap; width: max-content; }

    /* Task title truncation relief */
    .apv-task-title { max-width: 200px !important; }

    /* Filter bar: full-width inputs */
    .hist-filter-bar > div,
    .hist-filter-bar input[type="text"],
    .hist-filter-bar input[type="date"],
    .hist-filter-bar select { width: 100%; min-width: 0; }

    /* Reduce empty-state padding */
    .apv-empty { padding: 44px 20px !important; }

    /* Table wider on small phones */
    .tbl-scroll .hist-table { min-width: 580px; }

    .hist-cards-grid { grid-template-columns: 1fr; }
}

/* Auto-switch: on screens ≤ 900px the JS will default to card view */
@media (max-width: 900px) {
    .hist-cards-grid { grid-template-columns: 1fr; }
}

/* ── Pending list view ── */
.pend-table { width: 100%; border-collapse: collapse; }
.pend-table thead th {
    padding: 10px 14px; text-align: left; font-size: 10.5px; font-weight: 700;
    color: #9CA3AF; text-transform: uppercase; letter-spacing: .06em;
    background: #F9FAFB; border-bottom: 1px solid #F0F0F0;
}
.pend-table thead th:first-child { border-radius: 12px 0 0 0; }
.pend-table thead th:last-child  { border-radius: 0 12px 0 0; }
.pend-table tbody tr { border-bottom: 1px solid #F7F7F7; transition: background .12s; }
.pend-table tbody tr:hover > td { background: #F8FAFF; }
.pend-table tbody tr:last-child  { border-bottom: none; }
.pend-table td { padding: 12px 14px; vertical-align: top; }
.pend-list-wrap {
    background: #fff; border-radius: 16px; border: 1px solid #EBEBEB;
    box-shadow: 0 2px 10px rgba(99,102,241,.07); overflow: hidden;
}
/* Reject expand area */
.pend-reject-row { display: none; }
.pend-reject-row td {
    background: #FFF8F8; border-top: 1px dashed #FECACA;
    padding: 10px 14px;
}
.pend-reject-row.open { display: table-row; }
</style>

<div x-data="approvalPage()" @keydown.escape.window="if(viewer) closeViewer(); else if(approvalModal) approvalModal=false; else if(rejectModal) rejectModal=false; else closeModal()"
>

{{-- ═══════════ FILE VIEWER LIGHTBOX ═══════════ --}}
<template x-teleport="body">
    <div x-show="viewer" x-cloak
         @click.self="closeViewer()"
         style="position:fixed;inset:0;background:rgba(5,7,20,.92);z-index:999999;display:flex;align-items:center;justify-content:center;padding:24px;backdrop-filter:blur(4px);">
        <div @click.stop style="position:relative;display:flex;flex-direction:column;align-items:center;max-width:92vw;max-height:92vh;">
            <div style="display:flex;align-items:center;justify-content:space-between;width:100%;margin-bottom:14px;gap:16px;">
                <p style="color:rgba(255,255,255,.75);font-size:13px;font-weight:500;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;" x-text="viewerFile ? viewerFile.filename : ''"></p>
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                    <a :href="viewerFile ? viewerFile.url : '#'" download
                       style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;background:rgba(255,255,255,.1);color:#fff;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;border:1px solid rgba(255,255,255,.15);transition:background .15s;"
                       onmouseover="this.style.background='rgba(255,255,255,.18)'" onmouseout="this.style.background='rgba(255,255,255,.1)'">
                        <i class="fas fa-download" style="font-size:10px;"></i> Download
                    </a>
                    <button @click="closeViewer()"
                            style="width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);cursor:pointer;color:#fff;font-size:14px;display:flex;align-items:center;justify-content:center;transition:background .15s;"
                            onmouseover="this.style.background='rgba(255,255,255,.2)'" onmouseout="this.style.background='rgba(255,255,255,.1)'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <template x-if="viewerFile && viewerFile.type === 'image'">
                <img :src="viewerFile.url" :alt="viewerFile.filename" style="max-width:88vw;max-height:82vh;border-radius:12px;object-fit:contain;display:block;box-shadow:0 20px 60px rgba(0,0,0,.5);">
            </template>
            <template x-if="viewerFile && viewerFile.type === 'video'">
                <video :src="viewerFile.url" controls autoplay style="max-width:88vw;max-height:82vh;border-radius:12px;outline:none;display:block;box-shadow:0 20px 60px rgba(0,0,0,.5);">
                    Your browser does not support the video tag.
                </video>
            </template>
            <template x-if="viewerFile && viewerFile.type === 'pdf'">
                <iframe :src="viewerFile.url" style="width:82vw;height:82vh;border:none;border-radius:12px;background:#fff;display:block;box-shadow:0 20px 60px rgba(0,0,0,.5);"></iframe>
            </template>
            <template x-if="viewerFile && !['image','video','pdf'].includes(viewerFile.type)">
                <div style="background:#1A1F2E;border-radius:18px;padding:52px 72px;text-align:center;border:1px solid rgba(255,255,255,.08);">
                    <div x-html="viewerIconHtml()" style="margin-bottom:18px;"></div>
                    <p style="color:#fff;font-size:15px;font-weight:600;margin:0 0 6px;" x-text="viewerFile.filename"></p>
                    <p style="color:#6B7280;font-size:13px;margin:0 0 26px;">Preview not available for this file type</p>
                    <a :href="viewerFile.url" download style="display:inline-flex;align-items:center;gap:8px;padding:11px 30px;background:linear-gradient(135deg,#6366F1,#8B5CF6);color:#fff;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;box-shadow:0 4px 14px rgba(99,102,241,.4);">
                        <i class="fas fa-download"></i> Download File
                    </a>
                </div>
            </template>
        </div>
    </div>
</template>

{{-- ═══════════ DETAIL MODAL ═══════════ --}}
<template x-teleport="body">
    <div x-show="modal" x-cloak
         style="position:fixed;inset:0;background:rgba(15,18,40,.55);z-index:99999;backdrop-filter:blur(3px);">
        <div @click.self="closeModal()" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:20px;">
        <div style="background:#fff;border-radius:20px;width:100%;max-width:520px;box-shadow:0 24px 70px rgba(0,0,0,.22);overflow:hidden;max-height:90vh;display:flex;flex-direction:column;">
            <div style="padding:20px 24px 16px;border-bottom:1px solid #F0F2F8;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;background:linear-gradient(135deg,#F8F9FF,#fff);">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-file-lines" style="color:#fff;font-size:14px;"></i>
                    </div>
                    <div>
                        <h3 style="font-size:15px;font-weight:700;color:#111827;margin:0;" x-text="'Version ' + (sub ? sub.version : '')"></h3>
                        <p style="font-size:11px;color:#9CA3AF;margin:0;" x-text="sub ? sub.task : ''"></p>
                    </div>
                </div>
                <button @click="closeModal()" style="width:30px;height:30px;border-radius:8px;background:#F3F4F6;border:none;cursor:pointer;color:#6B7280;font-size:13px;display:flex;align-items:center;justify-content:center;transition:background .15s;" onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="padding:20px 24px;overflow-y:auto;flex:1;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
                    <span x-html="sub ? statusBadge(sub.status) : ''"></span>
                    <span style="font-size:12px;color:#9CA3AF;" x-text="sub ? sub.date : ''"></span>
                    <span style="font-size:12px;color:#9CA3AF;" x-text="sub ? '· by ' + sub.user : ''"></span>
                </div>
                <template x-if="sub && sub.note">
                    <div style="background:#F8FAFC;border-radius:10px;padding:14px 16px;margin-bottom:16px;border:1px solid #EEF2F8;">
                        <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 6px;">Submission Note</p>
                        <p style="font-size:13px;color:#374151;margin:0;line-height:1.65;" x-text="sub.note"></p>
                    </div>
                </template>
                <template x-if="sub && !sub.note">
                    <div style="background:#F9FAFB;border-radius:10px;padding:12px 16px;margin-bottom:16px;text-align:center;border:1px dashed #E5E7EB;">
                        <p style="font-size:12px;color:#D1D5DB;margin:0;">No note provided</p>
                    </div>
                </template>
                <template x-if="sub && sub.file">
                    <div>
                        <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 8px;">Attached File</p>
                        <template x-if="fileType(sub.filename) === 'image'">
                            <div @click="openViewer(sub.file, sub.filename)" style="cursor:pointer;margin-bottom:10px;border-radius:12px;overflow:hidden;border:1.5px solid #DDE3F5;position:relative;box-shadow:0 2px 8px rgba(99,102,241,.1);">
                                <img :src="sub.file" :alt="sub.filename" style="width:100%;max-height:220px;object-fit:cover;display:block;">
                                <div style="position:absolute;inset:0;background:rgba(0,0,0,0);display:flex;align-items:center;justify-content:center;transition:background .2s;" onmouseover="this.style.background='rgba(0,0,0,.28)';this.querySelector('div').style.opacity='1'" onmouseout="this.style.background='rgba(0,0,0,0)';this.querySelector('div').style.opacity='0'">
                                    <div style="width:46px;height:46px;border-radius:50%;background:rgba(255,255,255,.92);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .2s;box-shadow:0 4px 14px rgba(0,0,0,.2);">
                                        <i class="fas fa-expand" style="color:#4F46E5;font-size:15px;"></i>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="fileType(sub.filename) === 'video'">
                            <div @click="openViewer(sub.file, sub.filename)" style="cursor:pointer;margin-bottom:10px;border-radius:12px;overflow:hidden;border:1.5px solid #DDE3F5;position:relative;">
                                <video :src="sub.file" style="width:100%;max-height:180px;object-fit:cover;display:block;" preload="metadata" muted></video>
                                <div style="position:absolute;inset:0;background:rgba(0,0,0,.28);display:flex;align-items:center;justify-content:center;">
                                    <div style="width:50px;height:50px;border-radius:50%;background:rgba(255,255,255,.94);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(0,0,0,.2);">
                                        <i class="fas fa-play" style="color:#4F46E5;font-size:17px;margin-left:3px;"></i>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div @click="openViewer(sub.file, sub.filename)" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:1.5px solid #DDE3F5;border-radius:11px;cursor:pointer;background:#F8FAFF;transition:background .15s,border-color .15s;" onmouseover="this.style.background='#EEF2FF';this.style.borderColor='#C7D2FE'" onmouseout="this.style.background='#F8FAFF';this.style.borderColor='#DDE3F5'">
                            <div style="width:36px;height:36px;border-radius:9px;background:linear-gradient(135deg,#EEF2FF,#E0E7FF);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas fa-paperclip" style="color:#6366F1;font-size:13px;"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <p style="font-size:13px;font-weight:600;color:#4F46E5;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="sub.filename"></p>
                                <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;" x-text="['pdf'].includes(fileType(sub.filename)) ? 'Click to view inline' : ['image','video'].includes(fileType(sub.filename)) ? 'Click to view fullscreen' : 'Click to download'"></p>
                            </div>
                            <i class="fas fa-expand" style="color:#A5B4FC;font-size:11px;flex-shrink:0;"></i>
                        </div>
                    </div>
                </template>
                <template x-if="sub && !sub.file">
                    <div style="background:#F9FAFB;border-radius:10px;padding:12px 16px;text-align:center;border:1px dashed #E5E7EB;">
                        <p style="font-size:12px;color:#D1D5DB;margin:0;">No file attached</p>
                    </div>
                </template>
                <template x-if="sub && sub.adminNote">
                    <div style="margin-top:14px;background:#FEF2F2;border-radius:10px;padding:14px 16px;border:1px solid #FECACA;">
                        <p style="font-size:10px;font-weight:700;color:#DC2626;text-transform:uppercase;letter-spacing:.06em;margin:0 0 6px;">Admin Feedback</p>
                        <p style="font-size:13px;color:#7F1D1D;margin:0;line-height:1.65;" x-text="sub.adminNote"></p>
                    </div>
                </template>
            </div>
            <div style="padding:14px 24px;border-top:1px solid #F0F2F8;display:flex;justify-content:flex-end;flex-shrink:0;background:#FAFBFF;">
                <button @click="closeModal()" style="padding:9px 24px;background:#F3F4F6;color:#374151;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;transition:background .15s;" onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                    Close
                </button>
            </div>
        </div>
        </div>
    </div>
</template>

{{-- ═══════════ APPROVAL MODAL ═══════════ --}}
<template x-teleport="body">
    <div x-show="approvalModal" x-cloak
         style="position:fixed;inset:0;z-index:99999;backdrop-filter:blur(4px);background:rgba(15,18,40,.6);">
        <div @click.self="approvalModal=false"
             style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:20px;">
        <div style="background:#fff;border-radius:22px;width:100%;max-width:500px;box-shadow:0 28px 80px rgba(0,0,0,.25);overflow:hidden;display:flex;flex-direction:column;">

            {{-- Header --}}
            <div style="padding:22px 26px 18px;border-bottom:1px solid #F0F4F8;background:linear-gradient(135deg,#F0FDF4,#fff);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#10B981,#059669);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(16,185,129,.3);">
                        <i class="fas fa-circle-check" style="color:#fff;font-size:16px;"></i>
                    </div>
                    <div>
                        <h3 style="font-size:16px;font-weight:700;color:#111827;margin:0;">Approve Submission</h3>
                        <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;" x-text="'Submitted by ' + (approvalTask?.assignee ?? '')"></p>
                    </div>
                </div>
                <button @click="approvalModal=false"
                        style="width:32px;height:32px;border-radius:9px;background:#F3F4F6;border:none;cursor:pointer;color:#6B7280;font-size:13px;display:flex;align-items:center;justify-content:center;transition:background .15s;"
                        onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Task title strip --}}
            <div style="padding:14px 26px;background:#F8FAFF;border-bottom:1px solid #F0F4F8;">
                <p style="font-size:11px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 4px;">Task</p>
                <p style="font-size:14px;font-weight:600;color:#111827;margin:0;line-height:1.4;" x-text="approvalTask?.title"></p>
            </div>

            {{-- Form body --}}
            <form :action="approvalTask ? approvalTask.url : '#'" method="POST" style="padding:20px 26px 24px;overflow-y:auto;">
                @csrf

                {{-- Approval note --}}
                <div style="margin-bottom:20px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                        Approval Note <span style="font-size:11px;font-weight:400;color:#9CA3AF;">— optional</span>
                    </label>
                    <input type="text" name="note" x-model="approvalNote"
                           placeholder="Great work! The deliverable looks perfect..."
                           style="width:100%;padding:10px 13px;border:1.5px solid #BBF7D0;background:#F0FDF4;border-radius:10px;font-size:13px;color:#111827;outline:none;box-sizing:border-box;transition:border-color .15s,box-shadow .15s;"
                           onfocus="this.style.borderColor='#34D399';this.style.boxShadow='0 0 0 3px rgba(52,211,153,.12)'"
                           onblur="this.style.borderColor='#BBF7D0';this.style.boxShadow='none'">
                </div>

                {{-- Social media question --}}
                <div style="background:#F8FAFF;border:1px solid #EEF2FF;border-radius:14px;padding:18px;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                        <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#EEF2FF,#DDD6FE);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-share-nodes" style="color:#6366F1;font-size:11px;"></i>
                        </div>
                        <div>
                            <p style="font-size:13px;font-weight:700;color:#111827;margin:0;">Social Media Posting</p>
                            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Does this task need to be posted on social media?</p>
                        </div>
                    </div>

                    {{-- Yes / No / Later buttons --}}
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:14px;">
                        <button type="button" @click="approvalSocial = 'yes'"
                                :style="approvalSocial === 'yes'
                                    ? 'padding:10px 6px;border-radius:10px;border:2px solid #6366F1;background:#EEF2FF;color:#4F46E5;font-size:12px;font-weight:700;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;transition:all .15s;'
                                    : 'padding:10px 6px;border-radius:10px;border:1.5px solid #E5E7EB;background:#fff;color:#6B7280;font-size:12px;font-weight:600;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;transition:all .15s;'">
                            <i class="fas fa-check-circle" :style="approvalSocial === 'yes' ? 'font-size:16px;color:#6366F1;' : 'font-size:16px;color:#D1D5DB;'"></i>
                            Yes, assign
                        </button>
                        <button type="button" @click="approvalSocial = 'no'"
                                :style="approvalSocial === 'no'
                                    ? 'padding:10px 6px;border-radius:10px;border:2px solid #6B7280;background:#F3F4F6;color:#374151;font-size:12px;font-weight:700;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;transition:all .15s;'
                                    : 'padding:10px 6px;border-radius:10px;border:1.5px solid #E5E7EB;background:#fff;color:#6B7280;font-size:12px;font-weight:600;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;transition:all .15s;'">
                            <i class="fas fa-ban" :style="approvalSocial === 'no' ? 'font-size:16px;color:#6B7280;' : 'font-size:16px;color:#D1D5DB;'"></i>
                            Not needed
                        </button>
                        <button type="button" @click="approvalSocial = 'later'"
                                :style="approvalSocial === 'later'
                                    ? 'padding:10px 6px;border-radius:10px;border:2px solid #D97706;background:#FFFBEB;color:#D97706;font-size:12px;font-weight:700;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;transition:all .15s;'
                                    : 'padding:10px 6px;border-radius:10px;border:1.5px solid #E5E7EB;background:#fff;color:#6B7280;font-size:12px;font-weight:600;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;transition:all .15s;'">
                            <i class="fas fa-clock" :style="approvalSocial === 'later' ? 'font-size:16px;color:#D97706;' : 'font-size:16px;color:#D1D5DB;'"></i>
                            Decide later
                        </button>
                    </div>

                    {{-- User assignment dropdown (shown only when "Yes") --}}
                    <div x-show="approvalSocial === 'yes'" x-transition style="margin-top:4px;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                            Assign social post to <span style="color:#EF4444;">*</span>
                        </label>
                        <select name="social_assigned_to" x-model="approvalSocialUser"
                                style="width:100%;padding:9px 12px;border:1.5px solid #C7D2FE;background:#fff;border-radius:10px;font-size:13px;color:#374151;outline:none;cursor:pointer;box-sizing:border-box;"
                                onfocus="this.style.borderColor='#6366F1';this.style.boxShadow='0 0 0 3px rgba(99,102,241,.12)'"
                                onblur="this.style.borderColor='#C7D2FE';this.style.boxShadow='none'">
                            <option value="">— Select team member —</option>
                            @foreach($socialUsers as $su)
                            <option value="{{ $su->id }}">{{ $su->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Hidden field: social_required value sent based on selection --}}
                    <input type="hidden" name="social_required"
                           :value="approvalSocial === 'yes' ? '1' : (approvalSocial === 'no' ? '0' : '')">
                </div>

                {{-- Footer buttons --}}
                <div style="display:flex;gap:10px;margin-top:20px;">
                    <button type="button" @click="approvalModal=false"
                            style="flex:1;padding:11px;background:#F3F4F6;color:#374151;border:none;border-radius:11px;font-size:13px;font-weight:600;cursor:pointer;transition:background .15s;"
                            onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                        Cancel
                    </button>
                    <button type="submit"
                            :disabled="approvalSocial === 'yes' && !approvalSocialUser"
                            :style="(approvalSocial === 'yes' && !approvalSocialUser)
                                ? 'flex:2;padding:11px;background:#D1FAE5;color:#6EE7B7;border:none;border-radius:11px;font-size:13px;font-weight:700;cursor:not-allowed;display:flex;align-items:center;justify-content:center;gap:7px;'
                                : 'flex:2;padding:11px;background:linear-gradient(135deg,#10B981,#059669);color:#fff;border:none;border-radius:11px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;box-shadow:0 4px 14px rgba(16,185,129,.35);transition:opacity .15s;'">
                        <i class="fas fa-circle-check"></i>
                        <span x-text="approvalSocial === 'yes' && !approvalSocialUser ? 'Select a team member first' : 'Confirm Approval'"></span>
                    </button>
                </div>
            </form>

        </div>
        </div>
    </div>
</template>

{{-- ═══════════ REJECT MODAL ═══════════ --}}
<template x-teleport="body">
    <div x-show="rejectModal" x-cloak
         style="position:fixed;inset:0;z-index:99999;backdrop-filter:blur(4px);background:rgba(15,18,40,.6);">
        <div @click.self="rejectModal=false"
             style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:20px;">
        <div style="background:#fff;border-radius:22px;width:100%;max-width:440px;box-shadow:0 28px 80px rgba(0,0,0,.25);overflow:hidden;display:flex;flex-direction:column;">

            <div style="padding:22px 26px 18px;border-bottom:1px solid #F0F4F8;background:linear-gradient(135deg,#FFF8F8,#fff);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#EF4444,#DC2626);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(239,68,68,.3);">
                        <i class="fas fa-rotate-left" style="color:#fff;font-size:15px;"></i>
                    </div>
                    <div>
                        <h3 style="font-size:16px;font-weight:700;color:#111827;margin:0;">Request Revision</h3>
                        <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;" x-text="'Submitted by ' + (rejectTask?.assignee ?? '')"></p>
                    </div>
                </div>
                <button @click="rejectModal=false"
                        style="width:32px;height:32px;border-radius:9px;background:#F3F4F6;border:none;cursor:pointer;color:#6B7280;font-size:13px;display:flex;align-items:center;justify-content:center;transition:background .15s;"
                        onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div style="padding:14px 26px;background:#FFF8F8;border-bottom:1px solid #FEE2E2;">
                <p style="font-size:11px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 4px;">Task</p>
                <p style="font-size:14px;font-weight:600;color:#111827;margin:0;line-height:1.4;" x-text="rejectTask?.title"></p>
            </div>

            <form :action="rejectTask ? rejectTask.url : '#'" method="POST" style="padding:20px 26px 24px;">
                @csrf
                <div style="margin-bottom:20px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                        Reason for revision <span style="color:#EF4444;">*</span>
                    </label>
                    <textarea name="note" required x-model="rejectNote" rows="3"
                              placeholder="Explain what needs to be changed..."
                              style="width:100%;padding:10px 13px;border:1.5px solid #FECACA;background:#FEF2F2;border-radius:10px;font-size:13px;color:#111827;outline:none;box-sizing:border-box;resize:vertical;transition:border-color .15s,box-shadow .15s;font-family:inherit;"
                              onfocus="this.style.borderColor='#F87171';this.style.boxShadow='0 0 0 3px rgba(248,113,113,.12)'"
                              onblur="this.style.borderColor='#FECACA';this.style.boxShadow='none'"></textarea>
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="button" @click="rejectModal=false"
                            style="flex:1;padding:11px;background:#F3F4F6;color:#374151;border:none;border-radius:11px;font-size:13px;font-weight:600;cursor:pointer;transition:background .15s;"
                            onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                        Cancel
                    </button>
                    <button type="submit"
                            :disabled="!rejectNote.trim()"
                            :style="!rejectNote.trim()
                                ? 'flex:2;padding:11px;background:#FEE2E2;color:#FCA5A5;border:none;border-radius:11px;font-size:13px;font-weight:700;cursor:not-allowed;display:flex;align-items:center;justify-content:center;gap:7px;'
                                : 'flex:2;padding:11px;background:linear-gradient(135deg,#EF4444,#DC2626);color:#fff;border:none;border-radius:11px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;box-shadow:0 4px 14px rgba(239,68,68,.35);transition:opacity .15s;'">
                        <i class="fas fa-rotate-left"></i>
                        Request Revision
                    </button>
                </div>
            </form>
        </div>
        </div>
    </div>
</template>

{{-- ═══════════ PAGE HEADER ═══════════ --}}
<div class="apv-page-header" style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:14px;">
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0 0 4px;letter-spacing:-.3px;">Task Approvals</h1>
        <p style="font-size:13px;color:#9CA3AF;margin:0;">Review submitted work and manage approval decisions</p>
    </div>
    @if($tasks->total() > 0)
    <div style="display:flex;align-items:center;gap:8px;">
        <div style="display:flex;align-items:center;gap:6px;background:linear-gradient(135deg,#EDE9FE,#DDD6FE);padding:8px 16px;border-radius:12px;border:1px solid #C4B5FD;">
            <div style="width:8px;height:8px;border-radius:50%;background:#7C3AED;animation:pulse 2s infinite;"></div>
            <span style="font-size:13px;font-weight:700;color:#5B21B6;">{{ $tasks->total() }} pending {{ Str::plural('review', $tasks->total()) }}</span>
        </div>
    </div>
    @endif
</div>

{{-- ── Tabs ── --}}
<div class="apv-tabs-scroll" style="margin-bottom:24px;">
<div class="apv-tabs" style="display:flex;gap:3px;background:#F1F2F6;border-radius:13px;padding:4px;width:fit-content;">
    <a href="{{ route('admin.approvals.index') }}?tab=pending"
       style="display:flex;align-items:center;gap:7px;padding:9px 20px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;transition:all .18s;
              {{ $tab === 'pending' ? 'background:#fff;color:#4F46E5;box-shadow:0 2px 8px rgba(99,102,241,.12);' : 'color:#6B7280;' }}">
        <i class="fas fa-clock" style="font-size:11px;"></i> Pending
        @if($tasks->total() > 0)
        <span style="background:linear-gradient(135deg,#EDE9FE,#DDD6FE);color:#7C3AED;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;">{{ $tasks->total() }}</span>
        @endif
    </a>
    <a href="{{ route('admin.approvals.index') }}?tab=history"
       style="display:flex;align-items:center;gap:7px;padding:9px 20px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;transition:all .18s;
              {{ $tab === 'history' ? 'background:#fff;color:#4F46E5;box-shadow:0 2px 8px rgba(99,102,241,.12);' : 'color:#6B7280;' }}">
        <i class="fas fa-clock-rotate-left" style="font-size:11px;"></i> History
        @if($history->total() > 0)
        <span style="background:#F3F4F6;color:#6B7280;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;">{{ $history->total() }}</span>
        @endif
    </a>
    <a href="{{ route('admin.approvals.index') }}?tab=social"
       style="display:flex;align-items:center;gap:7px;padding:9px 20px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;transition:all .18s;
              {{ $tab === 'social' ? 'background:#fff;color:#4F46E5;box-shadow:0 2px 8px rgba(99,102,241,.12);' : 'color:#6B7280;' }}">
        <i class="fas fa-share-nodes" style="font-size:11px;"></i> Social Media
        @php $socialPending = $socialTasks->total(); @endphp
        @if($socialTasks->total() > 0)
        <span style="background:{{ $tab === 'social' ? 'linear-gradient(135deg,#EDE9FE,#DDD6FE)' : '#F3F4F6' }};color:{{ $tab === 'social' ? '#7C3AED' : '#6B7280' }};font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;">{{ $socialTasks->total() }}</span>
        @endif
    </a>
    <a href="{{ route('admin.approvals.index') }}?tab=published"
       style="display:flex;align-items:center;gap:7px;padding:9px 20px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;transition:all .18s;
              {{ $tab === 'published' ? 'background:#fff;color:#4F46E5;box-shadow:0 2px 8px rgba(99,102,241,.12);' : 'color:#6B7280;' }}">
        <i class="fas fa-circle-check" style="font-size:11px;"></i> Published Posts
        @if($publishedSocialTasks->total() > 0)
        <span style="background:{{ $tab === 'published' ? 'linear-gradient(135deg,#D1FAE5,#A7F3D0)' : '#F3F4F6' }};color:{{ $tab === 'published' ? '#065F46' : '#6B7280' }};font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;">{{ $publishedSocialTasks->total() }}</span>
        @endif
    </a>
</div>{{-- .apv-tabs --}}
</div>{{-- .apv-tabs-scroll --}}

@if(session('success'))
<div style="background:linear-gradient(135deg,#ECFDF5,#D1FAE5);border:1px solid #A7F3D0;border-radius:12px;padding:13px 18px;margin-bottom:20px;color:#065F46;font-size:14px;display:flex;gap:10px;align-items:center;">
    <div style="width:22px;height:22px;border-radius:50%;background:#10B981;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="fa fa-check" style="color:#fff;font-size:10px;"></i>
    </div>
    {{ session('success') }}
</div>
@endif

{{-- ══════════════════════ PENDING TAB ══════════════════════ --}}
@if($tab === 'pending')

{{-- View toggle --}}
@if($tasks->total() > 0)
<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
    <div class="hist-view-toggle" id="pendViewToggle">
        <button class="hist-view-btn active" id="pendBtnList" onclick="setPendView('list')" title="Table view">
            <i class="fas fa-table-list" style="font-size:11px;"></i> Table
        </button>
        <button class="hist-view-btn" id="pendBtnCards" onclick="setPendView('cards')" title="Card view">
            <i class="fas fa-th-large" style="font-size:11px;"></i> Cards
        </button>
    </div>
    <span style="font-size:12px;color:#9CA3AF;">{{ $tasks->total() }} task{{ $tasks->total() !== 1 ? 's' : '' }} awaiting review</span>
</div>
@endif

{{-- ── CARD VIEW ── --}}
<div id="pendingCardsView">
@forelse($tasks as $task)
@php
    $latestSub    = $task->submissions->first();
    $isOverdue    = $task->deadline->isPast();
    $priTopColor  = ['high'=>'#EF4444','medium'=>'#F59E0B','low'=>'#10B981'][$task->priority] ?? '#6B7280';
    $priColors    = ['high'=>['#FEE2E2','#DC2626'],'medium'=>['#FEF3C7','#D97706'],'low'=>['#D1FAE5','#059669']];
    [$pbg,$pco]   = $priColors[$task->priority] ?? ['#F3F4F6','#6B7280'];
    $allAssignees = $task->assignees->isNotEmpty() ? $task->assignees : ($task->assignee ? collect([$task->assignee]) : collect());
    $shownMembers = $allAssignees->take(4);
    $extraCount   = max(0, $allAssignees->count() - 4);
    $avatarColors = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6','#EC4899','#06B6D4'];
@endphp
@if($loop->first)
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-5">
@endif

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-indigo-100 transition group flex flex-col overflow-hidden cursor-pointer"
     style="border-top:3px solid {{ $priTopColor }};"
     onclick="window.location='{{ route('admin.tasks.show', $task) }}'">

    <div class="p-5 flex flex-col gap-3 flex-1">

        {{-- Priority badge + version count --}}
        <div class="flex items-center justify-between gap-2">
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                  style="background:{{ $pbg }};color:{{ $pco }};">
                {{ ucfirst($task->priority) }}
            </span>
            <span class="text-xs text-gray-400 flex items-center gap-1">
                <i class="fas fa-code-branch" style="font-size:10px;"></i>
                v{{ $latestSub?->version ?? 1 }}
            </span>
        </div>

        {{-- Title --}}
        <h3 class="text-sm font-semibold text-gray-800 leading-snug group-hover:text-indigo-600 transition line-clamp-2">
            {{ $task->title }}
        </h3>

        {{-- Project + submitted time --}}
        <div class="flex flex-col gap-1 -mt-1">
            <p class="text-xs text-gray-400 flex items-center gap-1 m-0">
                <i class="fas fa-folder" style="font-size:10px;"></i>
                {{ $task->project->name ?? '—' }}
            </p>
            @if($latestSub)
            <div class="flex items-center gap-1.5">
                <span class="text-xs px-1.5 py-0.5 rounded font-semibold" style="background:#EDE9FE;color:#7C3AED;">Submitted</span>
                <span class="text-xs text-gray-400">{{ $latestSub->created_at->diffForHumans() }}</span>
            </div>
            @endif
        </div>

        {{-- Assignees --}}
        <div class="flex items-center gap-2 mt-auto">
            @if($shownMembers->isNotEmpty())
            <div class="flex items-center">
                @foreach($shownMembers as $mi => $member)
                @php $aColor = $avatarColors[$member->id % 8]; @endphp
                <div class="w-6 h-6 rounded-full border-2 border-white overflow-hidden flex-shrink-0"
                     style="margin-left:{{ $mi > 0 ? '-8px' : '0' }};position:relative;z-index:{{ 10 - $mi }};"
                     title="{{ $member->name }}">
                    @if($member->avatar)
                        <img src="{{ Storage::url($member->avatar) }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-white font-bold"
                             style="background:{{ $aColor }};font-size:9px;">
                            {{ strtoupper(substr($member->name,0,1)) }}
                        </div>
                    @endif
                </div>
                @endforeach
                @if($extraCount > 0)
                <div class="w-6 h-6 rounded-full border-2 border-white bg-gray-100 flex items-center justify-center flex-shrink-0"
                     style="margin-left:-8px;font-size:8px;font-weight:700;color:#6B7280;">
                    +{{ $extraCount }}
                </div>
                @endif
            </div>
            <span class="text-xs text-gray-500">{{ $allAssignees->count() }} assignee{{ $allAssignees->count() !== 1 ? 's' : '' }}</span>
            @else
            <i class="fas fa-user text-gray-200 text-xs"></i>
            <span class="text-xs text-gray-300">Unassigned</span>
            @endif
        </div>

        {{-- Deadline + actions --}}
        <div class="flex items-center justify-between gap-1.5 pt-2.5 border-t border-gray-50">
            <div class="flex items-center gap-1.5">
                @if($isOverdue)
                <i class="fas fa-triangle-exclamation text-red-400 text-xs"></i>
                <span class="text-xs font-semibold text-red-500">Overdue · {{ $task->deadline->format('M d') }}</span>
                @else
                <i class="fas fa-calendar-days text-gray-300 text-xs"></i>
                <span class="text-xs text-gray-400">Due {{ $task->deadline->format('M d, Y') }}</span>
                @endif
            </div>
            <div class="flex items-center gap-1" onclick="event.stopPropagation()">
                <button @click.stop="openApprovalModal({
                            id:       {{ $task->id }},
                            title:    @js($task->title),
                            assignee: @js($task->assignee->name ?? 'Unknown'),
                            url:      '{{ route('admin.tasks.approve', $task) }}'
                        })"
                        class="w-6 h-6 rounded-lg bg-green-50 hover:bg-green-100 flex items-center justify-center text-green-500 hover:text-green-600 transition"
                        title="Approve">
                    <i class="fas fa-check" style="font-size:10px;"></i>
                </button>
                <button @click.stop="openRejectModal({
                            id:       {{ $task->id }},
                            title:    @js($task->title),
                            assignee: @js($task->assignee->name ?? 'Unknown'),
                            url:      '{{ route('admin.tasks.reject', $task) }}'
                        })"
                        class="w-6 h-6 rounded-lg bg-red-50 hover:bg-red-100 flex items-center justify-center text-red-400 hover:text-red-500 transition"
                        title="Request revision">
                    <i class="fas fa-rotate-left" style="font-size:10px;"></i>
                </button>
                <a href="{{ route('admin.tasks.show', $task) }}"
                   class="w-6 h-6 rounded-lg bg-gray-100 hover:bg-indigo-100 flex items-center justify-center text-gray-400 hover:text-indigo-600 transition"
                   style="text-decoration:none;" title="View task"
                   onclick="event.stopPropagation()">
                    <i class="fa fa-arrow-up-right-from-square" style="font-size:10px;"></i>
                </a>
            </div>
        </div>
    </div>
</div>

@if($loop->last)
</div>{{-- /grid --}}
@endif

@empty
<div class="apv-empty" style="background:#fff;border-radius:18px;border:1px solid #EBEBEB;padding:72px 40px;text-align:center;box-shadow:0 2px 10px rgba(99,102,241,.06);">
    <div style="width:64px;height:64px;border-radius:20px;background:linear-gradient(135deg,#D1FAE5,#A7F3D0);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
        <i class="fa fa-circle-check" style="color:#10B981;font-size:28px;"></i>
    </div>
    <p style="font-size:16px;font-weight:700;color:#111827;margin:0 0 6px;">All caught up!</p>
    <p style="font-size:13px;color:#9CA3AF;margin:0;">No tasks are pending review right now. Check back later.</p>
</div>
@endforelse
</div>{{-- #pendingCardsView --}}

{{-- ── LIST VIEW ── --}}
@if($tasks->count() > 0)
<div id="pendingListView" style="display:none;">
    <div class="pend-list-wrap tbl-scroll">
    <table class="pend-table">
        <thead>
            <tr>
                <th>Task</th>
                <th>Assignee</th>
                <th>Project</th>
                <th>Priority</th>
                <th>Deadline</th>
                <th>Vers.</th>
                <th style="text-align:right;">Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach($tasks as $task)
        @php
            $latestSub2 = $task->submissions->first();
            $isOverdue2 = $task->deadline && $task->deadline->isPast();
            $priColors2 = ['high'=>['#FEE2E2','#DC2626'],'medium'=>['#FEF3C7','#D97706'],'low'=>['#D1FAE5','#059669']];
            [$pbg2,$pco2] = $priColors2[$task->priority] ?? ['#F3F4F6','#6B7280'];
        @endphp
        {{-- Main row --}}
        <tr id="pend-row-{{ $task->id }}">
            {{-- Task --}}
            <td style="max-width:240px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff;flex-shrink:0;">
                        {{ strtoupper(substr($task->assignee->name ?? 'U', 0, 1)) }}
                    </div>
                    <p style="font-size:13px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:180px;" title="{{ $task->title }}">{{ $task->title }}</p>
                </div>
            </td>
            {{-- Assignee --}}
            <td>
                <span style="font-size:12px;font-weight:600;color:#4F46E5;">{{ $task->assignee->name ?? '—' }}</span>
            </td>
            {{-- Project --}}
            <td>
                <span style="font-size:12px;color:#6B7280;"><i class="fas fa-folder" style="font-size:10px;color:#A5B4FC;margin-right:4px;"></i>{{ $task->project->name ?? '—' }}</span>
            </td>
            {{-- Priority --}}
            <td>
                <span style="font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;background:{{ $pbg2 }};color:{{ $pco2 }};white-space:nowrap;">{{ ucfirst($task->priority) }}</span>
            </td>
            {{-- Deadline --}}
            <td>
                @if($task->deadline)
                <span style="font-size:12px;{{ $isOverdue2 ? 'color:#DC2626;font-weight:600;' : 'color:#6B7280;' }}white-space:nowrap;">
                    {{ $isOverdue2 ? '⚠ ' : '' }}{{ $task->deadline->format('M d, Y') }}
                </span>
                @else
                <span style="font-size:12px;color:#D1D5DB;">—</span>
                @endif
            </td>
            {{-- Versions --}}
            <td style="text-align:center;">
                <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;background:#EDE9FE;color:#7C3AED;">{{ $task->submissions->count() }}</span>
            </td>
            {{-- Actions --}}
            <td style="text-align:right;white-space:nowrap;">
                <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end;flex-wrap:wrap;">
                    {{-- Approve --}}
                    <button type="button"
                            @click="openApprovalModal({
                                id:       {{ $task->id }},
                                title:    @js($task->title),
                                assignee: @js($task->assignee->name ?? 'Unknown'),
                                url:      '{{ route('admin.tasks.approve', $task) }}'
                            })"
                            style="display:flex;align-items:center;gap:5px;padding:6px 12px;background:linear-gradient(135deg,#10B981,#059669);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;box-shadow:0 2px 6px rgba(16,185,129,.25);transition:opacity .15s;"
                            onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                        <i class="fas fa-circle-check" style="font-size:10px;"></i> Approve
                    </button>
                    {{-- Reject toggle --}}
                    <button type="button"
                            onclick="togglePendReject({{ $task->id }})"
                            id="pend-rej-btn-{{ $task->id }}"
                            style="display:flex;align-items:center;gap:5px;padding:6px 12px;background:#FEF2F2;color:#DC2626;border:1.5px solid #FECACA;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all .15s;"
                            onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'">
                        <i class="fas fa-rotate-left" style="font-size:10px;"></i> Reject
                    </button>
                    {{-- Full View --}}
                    <a href="{{ route('admin.tasks.show', $task) }}"
                       style="display:flex;align-items:center;gap:5px;padding:6px 11px;background:#F3F4F6;color:#374151;border:1px solid #E5E7EB;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;transition:background .15s;"
                       onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                        <i class="fa fa-arrow-up-right-from-square" style="font-size:10px;"></i>
                    </a>
                </div>
            </td>
        </tr>
        {{-- Inline reject row --}}
        <tr class="pend-reject-row" id="pend-rej-{{ $task->id }}">
            <td colspan="7">
                <form method="POST" action="{{ route('admin.tasks.reject', $task) }}" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    @csrf
                    <i class="fas fa-rotate-left" style="color:#EF4444;font-size:12px;flex-shrink:0;"></i>
                    <input type="text" name="note" required placeholder="Reason for revision (required)..."
                           style="flex:1;min-width:200px;padding:8px 12px;border:1.5px solid #FECACA;background:#FEF2F2;border-radius:8px;font-size:12px;color:#111827;outline:none;box-sizing:border-box;transition:border-color .15s,box-shadow .15s;"
                           onfocus="this.style.borderColor='#F87171';this.style.boxShadow='0 0 0 3px rgba(248,113,113,.12)'"
                           onblur="this.style.borderColor='#FECACA';this.style.boxShadow='none'">
                    <button type="submit"
                            style="display:flex;align-items:center;gap:5px;padding:8px 14px;background:linear-gradient(135deg,#EF4444,#DC2626);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;box-shadow:0 2px 6px rgba(239,68,68,.25);white-space:nowrap;flex-shrink:0;">
                        <i class="fas fa-rotate-left" style="font-size:10px;"></i> Send Revision
                    </button>
                    <button type="button" onclick="togglePendReject({{ $task->id }})"
                            style="padding:8px 12px;background:#F3F4F6;color:#6B7280;border:none;border-radius:8px;font-size:12px;cursor:pointer;">
                        Cancel
                    </button>
                </form>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif

@if($tasks->hasPages())
<div style="margin-top:20px;">{{ $tasks->links() }}</div>
@endif

@endif {{-- end pending tab --}}

{{-- ══════════════════════ HISTORY TAB ══════════════════════ --}}
@if($tab === 'history')
@php
    $hParams = array_filter(['tab'=>'history','hsort'=>$hSort,'hdir'=>$hDir,'hfrom'=>$hFrom,'hto'=>$hTo,'hdecision'=>$hDecision,'hsearch'=>$hSearch], fn($v) => $v !== null && $v !== '');
    $hHasFilters = $hFrom || $hTo || $hDecision || $hSearch;
    if (!function_exists('hSortUrl')) {
        function hSortUrl($col, $sort, $dir, $params) {
            $newDir = ($sort === $col && $dir === 'desc') ? 'asc' : 'desc';
            return '?' . http_build_query(array_merge($params, ['hsort' => $col, 'hdir' => $newDir]));
        }
    }
    if (!function_exists('hSortIcon')) {
        function hSortIcon($col, $sort, $dir) {
            if ($sort !== $col) return '<i class="fas fa-sort" style="font-size:9px;color:#D1D5DB;margin-left:4px;"></i>';
            return $dir === 'asc'
                ? '<i class="fas fa-sort-up" style="font-size:9px;color:#4F46E5;margin-left:4px;"></i>'
                : '<i class="fas fa-sort-down" style="font-size:9px;color:#4F46E5;margin-left:4px;"></i>';
        }
    }
@endphp

{{-- ── Filter / Sort Bar ── --}}
<form method="GET" action="{{ route('admin.approvals.index') }}" class="hist-filter-bar"
      style="background:#fff;border-radius:14px;border:1px solid #E5E7EB;padding:12px 16px;margin-bottom:14px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
    <input type="hidden" name="tab" value="history">

    {{-- Search --}}
    <div style="position:relative;flex:1;min-width:180px;">
        <i class="fas fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:11px;pointer-events:none;"></i>
        <input type="text" name="hsearch" value="{{ $hSearch }}" placeholder="Search task, assignee, reviewer…"
               style="width:100%;padding:7px 10px 7px 30px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#374151;outline:none;background:#FAFAFA;box-sizing:border-box;"
               onfocus="this.style.borderColor='#A5B4FC';this.style.background='#fff'" onblur="this.style.borderColor='#E5E7EB';this.style.background='#FAFAFA'">
    </div>

    {{-- Date From --}}
    <div style="display:flex;align-items:center;gap:5px;">
        <label style="font-size:11px;font-weight:600;color:#6B7280;white-space:nowrap;">From</label>
        <input type="date" name="hfrom" value="{{ $hFrom }}"
               style="padding:6px 8px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#374151;outline:none;background:#FAFAFA;"
               onfocus="this.style.borderColor='#A5B4FC'" onblur="this.style.borderColor='#E5E7EB'">
    </div>

    {{-- Date To --}}
    <div style="display:flex;align-items:center;gap:5px;">
        <label style="font-size:11px;font-weight:600;color:#6B7280;white-space:nowrap;">To</label>
        <input type="date" name="hto" value="{{ $hTo }}"
               style="padding:6px 8px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#374151;outline:none;background:#FAFAFA;"
               onfocus="this.style.borderColor='#A5B4FC'" onblur="this.style.borderColor='#E5E7EB'">
    </div>

    {{-- Decision --}}
    <select name="hdecision"
            style="padding:7px 28px 7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#374151;outline:none;background:#FAFAFA url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' fill='%239CA3AF' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E\") no-repeat right 10px center;-webkit-appearance:none;">
        <option value="">All Decisions</option>
        <option value="approved"  {{ $hDecision === 'approved'  ? 'selected' : '' }}>Approved</option>
        <option value="rejected"  {{ $hDecision === 'rejected'  ? 'selected' : '' }}>Rejected</option>
    </select>

    {{-- Sort --}}
    <select name="hsort"
            style="padding:7px 28px 7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;color:#374151;outline:none;background:#FAFAFA url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' fill='%239CA3AF' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E\") no-repeat right 10px center;-webkit-appearance:none;">
        <option value="date"     {{ $hSort === 'date'     ? 'selected' : '' }}>Sort: Date</option>
        <option value="task"     {{ $hSort === 'task'     ? 'selected' : '' }}>Sort: Task Name</option>
        <option value="assignee" {{ $hSort === 'assignee' ? 'selected' : '' }}>Sort: Assignee</option>
        <option value="reviewer" {{ $hSort === 'reviewer' ? 'selected' : '' }}>Sort: Reviewer</option>
        <option value="decision" {{ $hSort === 'decision' ? 'selected' : '' }}>Sort: Decision</option>
    </select>

    {{-- Direction toggle --}}
    <button type="submit" name="hdir" value="{{ $hDir === 'asc' ? 'desc' : 'asc' }}"
            title="{{ $hDir === 'asc' ? 'Currently ascending — click for descending' : 'Currently descending — click for ascending' }}"
            style="padding:7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;background:#FAFAFA;color:#6B7280;cursor:pointer;font-size:12px;display:flex;align-items:center;gap:4px;"
            onmouseover="this.style.background='#EEF2FF';this.style.borderColor='#C7D2FE';this.style.color='#4F46E5'" onmouseout="this.style.background='#FAFAFA';this.style.borderColor='#E5E7EB';this.style.color='#6B7280'">
        <i class="fas fa-arrow-{{ $hDir === 'asc' ? 'up' : 'down' }}-wide-short" style="font-size:11px;"></i>
        {{ $hDir === 'asc' ? 'Asc' : 'Desc' }}
    </button>

    {{-- Apply --}}
    <button type="submit"
            style="padding:7px 16px;background:linear-gradient(135deg,#6366F1,#8B5CF6);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:5px;">
        <i class="fas fa-filter" style="font-size:10px;"></i> Apply
    </button>

    {{-- Clear --}}
    @if($hHasFilters)
    <a href="{{ route('admin.approvals.index') }}?tab=history"
       style="padding:7px 13px;background:#FEF2F2;color:#DC2626;border:1.5px solid #FECACA;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;white-space:nowrap;display:flex;align-items:center;gap:4px;"
       onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'">
        <i class="fas fa-xmark" style="font-size:10px;"></i> Clear
    </a>
    @endif
</form>

{{-- Result count + view toggle --}}
<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;flex-wrap:wrap;">
    <p style="font-size:12px;color:#9CA3AF;margin:0;">
        {{ $history->total() }} {{ Str::plural('result', $history->total()) }}
        @if($hHasFilters)<span style="color:#4F46E5;font-weight:600;"> (filtered)</span>@endif
    </p>
    <div class="hist-view-toggle">
        <button id="histBtnTable" onclick="setHistView('table')" class="hist-view-btn active" title="Table view">
            <i class="fas fa-table-list" style="font-size:11px;"></i> Table
        </button>
        <button id="histBtnCards" onclick="setHistView('cards')" class="hist-view-btn" title="Card view">
            <i class="fas fa-table-cells-large" style="font-size:11px;"></i> Cards
        </button>
    </div>
</div>

@if($history->total() === 0)
<div class="apv-empty" style="background:#fff;border-radius:18px;border:1px solid #EBEBEB;padding:56px 40px;text-align:center;">
    <i class="fas fa-magnifying-glass" style="font-size:28px;color:#D1D5DB;margin-bottom:12px;display:block;"></i>
    <p style="font-size:15px;font-weight:700;color:#111827;margin:0 0 5px;">No results found</p>
    <p style="font-size:12px;color:#9CA3AF;margin:0;">Try adjusting your filters or <a href="{{ route('admin.approvals.index') }}?tab=history" style="color:#4F46E5;">clear all filters</a>.</p>
</div>
@else

{{-- ══ TABLE VIEW ══ --}}
<div id="histTableView">
<div style="background:#fff;border-radius:18px;border:1px solid #EBEBEB;box-shadow:0 2px 10px rgba(99,102,241,.06);overflow:hidden;">
<div class="tbl-scroll">
    <table class="hist-table">
        <thead>
            <tr>
                <th><a href="{{ hSortUrl('task', $hSort, $hDir, $hParams) }}" style="color:inherit;text-decoration:none;display:flex;align-items:center;">Task {!! hSortIcon('task', $hSort, $hDir) !!}</a></th>
                <th><a href="{{ hSortUrl('assignee', $hSort, $hDir, $hParams) }}" style="color:inherit;text-decoration:none;display:flex;align-items:center;">Assignee {!! hSortIcon('assignee', $hSort, $hDir) !!}</a></th>
                <th>Project</th>
                <th>Ver.</th>
                <th><a href="{{ hSortUrl('decision', $hSort, $hDir, $hParams) }}" style="color:inherit;text-decoration:none;display:flex;align-items:center;">Decision {!! hSortIcon('decision', $hSort, $hDir) !!}</a></th>
                <th><a href="{{ hSortUrl('reviewer', $hSort, $hDir, $hParams) }}" style="color:inherit;text-decoration:none;display:flex;align-items:center;">Reviewed By {!! hSortIcon('reviewer', $hSort, $hDir) !!}</a></th>
                <th><a href="{{ hSortUrl('date', $hSort, $hDir, $hParams) }}" style="color:inherit;text-decoration:none;display:flex;align-items:center;">Date {!! hSortIcon('date', $hSort, $hDir) !!}</a></th>
                <th>Social Media</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($history as $sub)
            @php
                $isApproved  = $sub->status === 'approved';
                $decisionBg  = $isApproved ? 'linear-gradient(135deg,#D1FAE5,#A7F3D0)' : 'linear-gradient(135deg,#FEE2E2,#FECACA)';
                $decisionCo  = $isApproved ? '#065F46' : '#991B1B';
                $decisionIco = $isApproved ? 'fa-circle-check' : 'fa-rotate-left';
                $decisionLbl = $isApproved ? 'Approved' : 'Rejected';
                $socialAssignee = $sub->task?->socialAssignee;
                $postedAt       = $sub->task?->social_posted_at;
            @endphp
            <tr>
                <td>
                    <p style="font-size:13px;font-weight:600;color:#111827;margin:0;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $sub->task->title ?? '—' }}</p>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;">
                            {{ strtoupper(substr($sub->task->assignee->name ?? 'U', 0, 1)) }}
                        </div>
                        <span style="font-size:12px;font-weight:500;color:#374151;">{{ $sub->task->assignee->name ?? '—' }}</span>
                    </div>
                </td>
                <td>
                    <span style="font-size:12px;color:#6B7280;display:flex;align-items:center;gap:4px;">
                        <i class="fas fa-folder" style="font-size:10px;color:#C4B5FD;"></i>
                        {{ $sub->task->project->name ?? '—' }}
                    </span>
                </td>
                <td>
                    <span style="font-size:11px;font-weight:700;color:#4F46E5;background:linear-gradient(135deg,#EEF2FF,#E0E7FF);padding:3px 9px;border-radius:20px;border:1px solid #C7D2FE;">v{{ $sub->version }}</span>
                </td>
                <td>
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:4px 11px;border-radius:20px;background:{{ $decisionBg }};color:{{ $decisionCo }};">
                        <i class="fa {{ $decisionIco }}" style="font-size:10px;"></i> {{ $decisionLbl }}
                    </span>
                </td>
                <td>
                    <span style="font-size:12px;color:#374151;">{{ $sub->reviewer->name ?? '—' }}</span>
                </td>
                <td>
                    <span style="font-size:12px;color:#6B7280;white-space:nowrap;">{{ $sub->reviewed_at?->format('M d, Y') }}</span>
                    <p style="font-size:10px;color:#D1D5DB;margin:2px 0 0;">{{ $sub->reviewed_at?->diffForHumans() }}</p>
                </td>

                {{-- Social Media Column --}}
                <td style="min-width:200px;">
                    @if($isApproved && $sub->task_id)
                    @php
                        $t               = $sub->task;
                        $socialRequired  = $t?->social_required;
                        $taskSocialPosts = $t?->socialPosts ?? collect();
                        $pIcons = ['facebook'=>['fa-facebook','#1877F2'],'instagram'=>['fa-instagram','#E1306C'],'twitter'=>['fa-x-twitter','#000000'],'linkedin'=>['fa-linkedin','#0A66C2'],'tiktok'=>['fa-tiktok','#010101'],'youtube'=>['fa-youtube','#FF0000'],'snapchat'=>['fa-snapchat','#F7CA00'],'other'=>['fa-share-nodes','#6366F1']];
                    @endphp
                        @if($taskSocialPosts->isNotEmpty())
                        <div style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;background:#D1FAE5;color:#065F46;font-size:11px;font-weight:700;margin-bottom:6px;">
                            <i class="fas fa-circle-check" style="font-size:9px;"></i> Posted
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:4px;">
                            @foreach($taskSocialPosts as $sp)
                            @php [$spIcon,$spColor] = $pIcons[$sp->platform] ?? $pIcons['other']; @endphp
                            @if($sp->post_url)
                            <a href="{{ $sp->post_url }}" target="_blank" rel="noopener" title="{{ $sp->platformLabel() }}"
                               style="width:24px;height:24px;border-radius:6px;background:#F3F4F6;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;"
                               onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                                <i class="fab {{ $spIcon }}" style="font-size:12px;color:{{ $spColor }};"></i>
                            </a>
                            @else
                            <span title="{{ $sp->platformLabel() }}" style="width:24px;height:24px;border-radius:6px;background:#F3F4F6;display:inline-flex;align-items:center;justify-content:center;">
                                <i class="fab {{ $spIcon }}" style="font-size:12px;color:{{ $spColor }};"></i>
                            </span>
                            @endif
                            @endforeach
                        </div>
                        <p style="font-size:10px;color:#9CA3AF;margin:0;">
                            {{ $postedAt?->format('M d, Y · H:i') }}
                            @if($socialAssignee) · {{ $socialAssignee->name }}@endif
                        </p>
                        @else
                        <span style="color:#D1D5DB;font-size:13px;">—</span>
                        @endif
                    @else
                        <span style="font-size:11px;color:#E5E7EB;">—</span>
                    @endif
                </td>

                <td>
                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                        @if($sub->task_id)
                        <button onclick="openTaskPanel({{ $sub->task_id }})"
                                style="display:flex;align-items:center;gap:5px;padding:6px 13px;background:#EEF2FF;color:#4F46E5;border:1.5px solid #C7D2FE;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;transition:all .15s;"
                                onmouseover="this.style.background='#E0E7FF';this.style.borderColor='#A5B4FC'" onmouseout="this.style.background='#EEF2FF';this.style.borderColor='#C7D2FE'">
                            <i class="fas fa-eye" style="font-size:10px;"></i> View
                        </button>
                        <a href="{{ route('admin.tasks.show', $sub->task_id) }}"
                           style="display:flex;align-items:center;gap:5px;padding:6px 13px;background:#F3F4F6;color:#6B7280;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;white-space:nowrap;transition:all .15s;"
                           onmouseover="this.style.background='#EEF2FF';this.style.color='#4F46E5';this.style.borderColor='#C7D2FE'" onmouseout="this.style.background='#F3F4F6';this.style.color='#6B7280';this.style.borderColor='#E5E7EB'">
                            <i class="fas fa-arrow-up-right-from-square" style="font-size:10px;"></i> Task
                        </a>
                        @if($isApproved)
                        <form method="POST" action="{{ route('admin.tasks.reopen', $sub->task_id) }}"
                              onsubmit="return confirm('Reopen this task and set it back to In Progress?')">
                            @csrf
                            <button type="submit"
                                    style="display:flex;align-items:center;gap:5px;padding:6px 13px;background:#FFFBEB;color:#D97706;border:1.5px solid #FCD34D;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;transition:all .15s;"
                                    onmouseover="this.style.background='#FEF3C7';this.style.borderColor='#F59E0B'" onmouseout="this.style.background='#FFFBEB';this.style.borderColor='#FCD34D'">
                                <i class="fas fa-rotate-right" style="font-size:10px;"></i> Reopen
                            </button>
                        </form>
                        @endif
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>{{-- .tbl-scroll --}}
</div>{{-- card --}}
</div>{{-- #histTableView --}}

{{-- ══ CARD VIEW ══ --}}
<div id="histCardsView" style="display:none;">
<div class="hist-cards-grid">
    @foreach($history as $sub)
    @php
        $isApproved  = $sub->status === 'approved';
        $decisionBg  = $isApproved ? 'linear-gradient(135deg,#D1FAE5,#A7F3D0)' : 'linear-gradient(135deg,#FEE2E2,#FECACA)';
        $decisionCo  = $isApproved ? '#065F46' : '#991B1B';
        $decisionIco = $isApproved ? 'fa-circle-check' : 'fa-rotate-left';
        $decisionLbl = $isApproved ? 'Approved' : 'Rejected';
        $socialAssignee = $sub->task?->socialAssignee;
        $postedAt       = $sub->task?->social_posted_at;
        $taskSocialPosts = $sub->task?->socialPosts ?? collect();
        $pIcons = ['facebook'=>['fa-facebook','#1877F2'],'instagram'=>['fa-instagram','#E1306C'],'twitter'=>['fa-x-twitter','#000000'],'linkedin'=>['fa-linkedin','#0A66C2'],'tiktok'=>['fa-tiktok','#010101'],'youtube'=>['fa-youtube','#FF0000'],'snapchat'=>['fa-snapchat','#F7CA00'],'other'=>['fa-share-nodes','#6366F1']];
    @endphp
    <div class="hist-card dec-{{ $isApproved ? 'approved' : 'rejected' }}" onclick="openTaskPanel({{ $sub->task_id ?? 'null' }})" style="cursor:pointer;">

        {{-- Card header: task + badges --}}
        <div class="hist-card-head">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:10px;">
                <p style="font-size:14px;font-weight:700;color:#111827;margin:0;line-height:1.4;flex:1;">{{ $sub->task->title ?? '—' }}</p>
                <span style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px;background:{{ $decisionBg }};color:{{ $decisionCo }};white-space:nowrap;flex-shrink:0;">
                    <i class="fa {{ $decisionIco }}" style="font-size:9px;"></i> {{ $decisionLbl }}
                </span>
            </div>
            {{-- Meta row --}}
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                {{-- Assignee --}}
                <div style="display:flex;align-items:center;gap:6px;">
                    <div style="width:26px;height:26px;border-radius:7px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0;">
                        {{ strtoupper(substr($sub->task->assignee->name ?? 'U', 0, 1)) }}
                    </div>
                    <span style="font-size:12px;font-weight:500;color:#374151;">{{ $sub->task->assignee->name ?? '—' }}</span>
                </div>
                {{-- Project --}}
                <span style="font-size:12px;color:#6B7280;display:flex;align-items:center;gap:4px;">
                    <i class="fas fa-folder" style="font-size:10px;color:#C4B5FD;"></i>
                    {{ $sub->task->project->name ?? '—' }}
                </span>
                {{-- Version --}}
                <span style="font-size:10px;font-weight:700;color:#4F46E5;background:linear-gradient(135deg,#EEF2FF,#E0E7FF);padding:2px 8px;border-radius:20px;border:1px solid #C7D2FE;">v{{ $sub->version }}</span>
            </div>
        </div>

        {{-- Card body: reviewer + date + social --}}
        <div class="hist-card-body">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                <div>
                    <p style="font-size:10px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin:0 0 2px;">Reviewed by</p>
                    <p style="font-size:12px;font-weight:600;color:#374151;margin:0;">{{ $sub->reviewer->name ?? '—' }}</p>
                </div>
                <div style="text-align:right;">
                    <p style="font-size:12px;color:#6B7280;margin:0;white-space:nowrap;">{{ $sub->reviewed_at?->format('M d, Y') }}</p>
                    <p style="font-size:10px;color:#D1D5DB;margin:2px 0 0;">{{ $sub->reviewed_at?->diffForHumans() }}</p>
                </div>
            </div>

            {{-- Social Media --}}
            @if($isApproved && $taskSocialPosts->isNotEmpty())
            <div style="margin-top:10px;padding-top:10px;border-top:1px solid #EEF0FA;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:20px;background:#D1FAE5;color:#065F46;font-size:10px;font-weight:700;">
                        <i class="fas fa-circle-check" style="font-size:8px;"></i> Posted
                    </span>
                    <div style="display:flex;gap:4px;flex-wrap:wrap;">
                        @foreach($taskSocialPosts as $sp)
                        @php [$spIcon,$spColor] = $pIcons[$sp->platform] ?? $pIcons['other']; @endphp
                        @if($sp->post_url)
                        <a href="{{ $sp->post_url }}" target="_blank" rel="noopener" title="{{ $sp->platformLabel() }}"
                           style="width:22px;height:22px;border-radius:6px;background:#F3F4F6;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;"
                           onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                            <i class="fab {{ $spIcon }}" style="font-size:11px;color:{{ $spColor }};"></i>
                        </a>
                        @else
                        <span title="{{ $sp->platformLabel() }}" style="width:22px;height:22px;border-radius:6px;background:#F3F4F6;display:inline-flex;align-items:center;justify-content:center;">
                            <i class="fab {{ $spIcon }}" style="font-size:11px;color:{{ $spColor }};"></i>
                        </span>
                        @endif
                        @endforeach
                    </div>
                    @if($postedAt)<span style="font-size:10px;color:#9CA3AF;">{{ $postedAt->format('M d · H:i') }}</span>@endif
                </div>
            </div>
            @endif
        </div>

        {{-- Card footer: action buttons --}}
        <div class="hist-card-foot" onclick="event.stopPropagation()">
            @if($sub->task_id)
            <button onclick="openTaskPanel({{ $sub->task_id }})"
                    style="display:flex;align-items:center;gap:5px;padding:7px 14px;background:#EEF2FF;color:#4F46E5;border:1.5px solid #C7D2FE;border-radius:9px;font-size:12px;font-weight:600;cursor:pointer;transition:all .15s;"
                    onmouseover="this.style.background='#E0E7FF';this.style.borderColor='#A5B4FC'" onmouseout="this.style.background='#EEF2FF';this.style.borderColor='#C7D2FE'">
                <i class="fas fa-eye" style="font-size:10px;"></i> View
            </button>
            <a href="{{ route('admin.tasks.show', $sub->task_id) }}"
               style="display:flex;align-items:center;gap:5px;padding:7px 14px;background:#F3F4F6;color:#6B7280;border:1.5px solid #E5E7EB;border-radius:9px;font-size:12px;font-weight:600;text-decoration:none;transition:all .15s;"
               onmouseover="this.style.background='#EEF2FF';this.style.color='#4F46E5';this.style.borderColor='#C7D2FE'" onmouseout="this.style.background='#F3F4F6';this.style.color='#6B7280';this.style.borderColor='#E5E7EB'">
                <i class="fas fa-arrow-up-right-from-square" style="font-size:10px;"></i> Task
            </a>
            @if($isApproved)
            <form method="POST" action="{{ route('admin.tasks.reopen', $sub->task_id) }}"
                  onsubmit="return confirm('Reopen this task and set it back to In Progress?')">
                @csrf
                <button type="submit"
                        style="display:flex;align-items:center;gap:5px;padding:7px 14px;background:#FFFBEB;color:#D97706;border:1.5px solid #FCD34D;border-radius:9px;font-size:12px;font-weight:600;cursor:pointer;transition:all .15s;"
                        onmouseover="this.style.background='#FEF3C7';this.style.borderColor='#F59E0B'" onmouseout="this.style.background='#FFFBEB';this.style.borderColor='#FCD34D'">
                    <i class="fas fa-rotate-right" style="font-size:10px;"></i> Reopen
                </button>
            </form>
            @endif
            @endif
        </div>

    </div>
    @endforeach
</div>
</div>{{-- #histCardsView --}}

@if($history->hasPages())
<div style="margin-top:20px;">{{ $history->appends($hParams)->links() }}</div>
@endif

@endif {{-- end @else (results found) --}}

@endif {{-- end history tab --}}

{{-- ══════════════════════ PUBLISHED POSTS TAB ══════════════════════ --}}
@if($tab === 'published')
@php
$pubIcons = ['facebook'=>['fa-facebook','#1877F2'],'instagram'=>['fa-instagram','#E1306C'],'twitter'=>['fa-x-twitter','#000000'],'linkedin'=>['fa-linkedin','#0A66C2'],'tiktok'=>['fa-tiktok','#010101'],'youtube'=>['fa-youtube','#FF0000'],'snapchat'=>['fa-snapchat','#F7CA00'],'other'=>['fa-share-nodes','#6366F1']];
$pubPlatforms = ['facebook'=>'Facebook','instagram'=>'Instagram','twitter'=>'Twitter / X','linkedin'=>'LinkedIn','tiktok'=>'TikTok','youtube'=>'YouTube','snapchat'=>'Snapchat','other'=>'Other'];
@endphp
<style>
.pub-edit-btn{display:inline-flex;align-items:center;gap:3px;padding:3px 8px;background:#F3F4F6;color:#6B7280;border:1.5px solid #E5E7EB;border-radius:6px;font-size:10px;font-weight:600;cursor:pointer;transition:all .15s;white-space:nowrap;}
.pub-edit-btn:hover{background:#EEF2FF;color:#4F46E5;border-color:#C7D2FE;}
.pub-del-btn{display:inline-flex;align-items:center;gap:3px;padding:3px 8px;background:#FFF5F5;color:#DC2626;border:1.5px solid #FEE2E2;border-radius:6px;font-size:10px;font-weight:600;cursor:pointer;transition:all .15s;white-space:nowrap;}
.pub-del-btn:hover{background:#FEE2E2;color:#B91C1C;border-color:#FECACA;}
.pub-save-btn{padding:5px 13px;background:linear-gradient(135deg,#6366F1,#8B5CF6);color:#fff;border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;}
.pub-cancel-btn{padding:5px 11px;background:#F3F4F6;color:#6B7280;border:1.5px solid #E5E7EB;border-radius:7px;font-size:11px;font-weight:600;cursor:pointer;}
</style>
@if($publishedSocialTasks->isEmpty())
<div class="apv-empty" style="background:#fff;border-radius:18px;border:1px solid #EBEBEB;padding:72px 40px;text-align:center;box-shadow:0 2px 10px rgba(99,102,241,.06);">
    <div style="width:64px;height:64px;border-radius:20px;background:linear-gradient(135deg,#D1FAE5,#A7F3D0);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
        <i class="fas fa-circle-check" style="color:#059669;font-size:26px;"></i>
    </div>
    <p style="font-size:16px;font-weight:700;color:#111827;margin:0 0 6px;">No published posts yet</p>
    <p style="font-size:13px;color:#9CA3AF;margin:0;">Once social media tasks are posted, they will appear here.</p>
</div>
@else
<div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;">
        @foreach($publishedSocialTasks as $pt)
        @foreach($pt->socialPosts as $sp)
        @php [$pIcon,$pColor] = $pubIcons[$sp->platform] ?? $pubIcons['other']; @endphp

        <div x-data="{ editing: false }" class="bg-white rounded-2xl border border-gray-100 shadow-sm flex flex-col overflow-hidden"
             style="transition:box-shadow .15s;"
             onmouseover="this.style.boxShadow='0 4px 14px rgba(99,102,241,.1)'" onmouseout="this.style.boxShadow=''">

            {{-- Platform header --}}
            <div style="display:flex;align-items:center;gap:10px;padding:14px 14px 12px;border-bottom:1px solid #F3F4F6;">
                <div style="width:40px;height:40px;border-radius:11px;background:#F9FAFB;border:1px solid #E5E7EB;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fab {{ $pIcon }}" style="font-size:20px;color:{{ $pColor }};"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:13px;font-weight:700;color:#111827;margin:0;">{{ $sp->platformLabel() }}</p>
                    @if($sp->note)
                    <p style="font-size:11px;color:#9CA3AF;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $sp->note }}">{{ $sp->note }}</p>
                    @endif
                </div>
                <div style="display:flex;gap:4px;flex-shrink:0;">
                    <button type="button" @click="editing=!editing" class="pub-edit-btn">
                        <i class="fas fa-pen" style="font-size:9px;"></i>
                        <span x-text="editing ? 'Cancel' : 'Edit'"></span>
                    </button>
                    <form method="POST" action="{{ route('admin.social-posts.destroy', $sp->id) }}" style="margin:0;"
                          onsubmit="return confirm('Remove this {{ $sp->platformLabel() }} post record?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="pub-del-btn">
                            <i class="fas fa-trash" style="font-size:9px;"></i>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Inline edit form --}}
            <div x-show="editing" x-cloak style="padding:10px 12px;background:#F9FAFB;border-bottom:1px solid #E5E7EB;">
                <form method="POST" action="{{ route('admin.social-posts.update', $sp->id) }}">
                    @csrf @method('PUT')
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <div>
                            <label style="font-size:10px;font-weight:700;color:#374151;display:block;margin-bottom:3px;">Platform</label>
                            <select name="platform" style="width:100%;font-size:12px;padding:5px 8px;border:1.5px solid #D1D5DB;border-radius:7px;background:#fff;color:#111827;outline:none;">
                                @foreach($pubPlatforms as $val => $label)
                                <option value="{{ $val }}" {{ $sp->platform === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="font-size:10px;font-weight:700;color:#374151;display:block;margin-bottom:3px;">Post URL</label>
                            <input type="url" name="post_url" value="{{ $sp->post_url }}" placeholder="https://..."
                                   style="width:100%;font-size:12px;padding:5px 8px;border:1.5px solid #D1D5DB;border-radius:7px;background:#fff;color:#111827;outline:none;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="font-size:10px;font-weight:700;color:#374151;display:block;margin-bottom:3px;">Note</label>
                            <input type="text" name="note" value="{{ $sp->note }}" placeholder="Optional note..."
                                   style="width:100%;font-size:12px;padding:5px 8px;border:1.5px solid #D1D5DB;border-radius:7px;background:#fff;color:#111827;outline:none;box-sizing:border-box;">
                        </div>
                        <div style="display:flex;gap:6px;justify-content:flex-end;">
                            <button type="button" @click="editing=false" class="pub-cancel-btn">Cancel</button>
                            <button type="submit" class="pub-save-btn">Save</button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Post URL --}}
            <div x-show="!editing" style="padding:12px 14px;flex:1;">
                @if($sp->post_url)
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:11px;color:#6B7280;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $sp->post_url }}">
                        {{ $sp->post_url }}
                    </span>
                    <a href="{{ $sp->post_url }}" target="_blank" rel="noopener"
                       style="display:inline-flex;align-items:center;gap:4px;padding:5px 11px;background:linear-gradient(135deg,#EEF2FF,#E0E7FF);color:#4F46E5;border:1px solid #C7D2FE;border-radius:7px;font-size:11px;font-weight:700;text-decoration:none;flex-shrink:0;transition:all .15s;"
                       onmouseover="this.style.background='#C7D2FE'" onmouseout="this.style.background='linear-gradient(135deg,#EEF2FF,#E0E7FF)'">
                        <i class="fas fa-arrow-up-right-from-square" style="font-size:9px;"></i> Open
                    </a>
                </div>
                @else
                <span style="font-size:11px;color:#D1D5DB;font-style:italic;">No link recorded</span>
                @endif
            </div>

            {{-- Footer: task / project info --}}
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 14px;border-top:1px solid #F3F4F6;background:#FAFBFF;margin-top:auto;">
                <div style="min-width:0;flex:1;">
                    <p style="font-size:12px;font-weight:600;color:#374151;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $pt->title }}">{{ $pt->title }}</p>
                    <p style="font-size:10px;color:#9CA3AF;margin:0;display:flex;align-items:center;gap:3px;flex-wrap:wrap;">
                        <i class="fas fa-folder" style="font-size:8px;color:#C4B5FD;"></i>
                        {{ $pt->project->name ?? '—' }}
                        <span style="color:#E5E7EB;">·</span>
                        {{ $pt->social_posted_at->format('M d, Y') }}
                    </p>
                </div>
                <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;background:#D1FAE5;color:#065F46;font-size:10px;font-weight:700;flex-shrink:0;">
                    <i class="fas fa-circle-check" style="font-size:8px;"></i> Posted
                </span>
            </div>
        </div>
        @endforeach
        @endforeach
    </div>

    @if($publishedSocialTasks->hasPages())
    <div style="margin-top:16px;">{{ $publishedSocialTasks->appends(['tab' => 'published'])->links() }}</div>
    @endif
</div>
@endif

@endif {{-- end published tab --}}

{{-- ══════════════════════ SOCIAL MEDIA TAB ══════════════════════ --}}
@if($tab === 'social')

<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
    <p style="font-size:13px;color:#6B7280;margin:0;">Tasks pending social media posting. Once posted, they move to the <a href="{{ route('admin.approvals.index') }}?tab=published" style="color:#4F46E5;text-decoration:none;font-weight:600;">Published Posts tab</a>.</p>
</div>

@if($socialTasks->isEmpty())
<div class="apv-empty" style="background:#fff;border-radius:18px;border:1px solid #EBEBEB;padding:72px 40px;text-align:center;box-shadow:0 2px 10px rgba(99,102,241,.06);">
    <div style="width:64px;height:64px;border-radius:20px;background:linear-gradient(135deg,#EEF2FF,#E0E7FF);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
        <i class="fas fa-share-nodes" style="color:#6366F1;font-size:26px;"></i>
    </div>
    <p style="font-size:16px;font-weight:700;color:#111827;margin:0 0 6px;">No social media assignments yet</p>
    <p style="font-size:13px;color:#9CA3AF;margin:0;">No pending social media tasks. All posted tasks are recorded in the <a href="{{ route('admin.approvals.index') }}?tab=published" style="color:#4F46E5;text-decoration:none;font-weight:600;">Published Posts tab</a>.</p>
</div>
@else
<div style="background:#fff;border-radius:18px;border:1px solid #EBEBEB;box-shadow:0 2px 10px rgba(99,102,241,.06);overflow:hidden;">
<div class="tbl-scroll">
    <table class="hist-table">
        <thead>
            <tr>
                <th>Task</th>
                <th>Project</th>
                <th>Assigned To</th>
                <th>Assigned</th>
                <th>Social Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($socialTasks as $st)
            <tr>
                <td>
                    <p style="font-size:13px;font-weight:600;color:#111827;margin:0;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $st->title }}</p>
                    @if($st->assignee)
                    <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">by {{ $st->assignee->name }}</p>
                    @endif
                </td>
                <td>
                    <span style="font-size:12px;color:#6B7280;display:flex;align-items:center;gap:4px;">
                        <i class="fas fa-folder" style="font-size:10px;color:#C4B5FD;"></i>
                        {{ $st->project->name ?? '—' }}
                    </span>
                </td>
                <td>
                    @if($st->socialAssignee)
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;">
                            {{ strtoupper(substr($st->socialAssignee->name, 0, 1)) }}
                        </div>
                        <span style="font-size:12px;font-weight:600;color:#374151;">{{ $st->socialAssignee->name }}</span>
                    </div>
                    @else
                    <span style="color:#D1D5DB;font-size:12px;">—</span>
                    @endif
                </td>
                <td>
                    <span style="font-size:12px;color:#6B7280;">{{ $st->updated_at->format('M d, Y') }}</span>
                </td>
                {{-- ── Social Status: always pending here ── --}}
                <td style="min-width:200px;">
                    <div style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:4px 11px;border-radius:20px;background:#FEF3C7;color:#D97706;margin-bottom:7px;">
                        <i class="fas fa-clock" style="font-size:9px;"></i> Pending
                    </div>
                    @if($st->socialAssignee)
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                        <div style="width:20px;height:20px;border-radius:6px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff;flex-shrink:0;">
                            {{ strtoupper(substr($st->socialAssignee->name, 0, 1)) }}
                        </div>
                        <span style="font-size:11px;color:#374151;font-weight:500;">{{ $st->socialAssignee->name }}</span>
                    </div>
                    <p style="font-size:10px;color:#9CA3AF;margin:0;">
                        <i class="fas fa-hourglass-half" style="font-size:9px;margin-right:3px;color:#FBB040;"></i>
                        Waiting since {{ $st->updated_at->diffForHumans() }}
                    </p>
                    @else
                    <p style="font-size:11px;color:#D1D5DB;margin:0;">No one assigned yet</p>
                    @endif
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                        <a href="{{ route('admin.tasks.show', $st->id) }}"
                           style="display:flex;align-items:center;gap:5px;padding:6px 13px;background:#F3F4F6;color:#6B7280;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;white-space:nowrap;transition:all .15s;"
                           onmouseover="this.style.background='#EEF2FF';this.style.color='#4F46E5';this.style.borderColor='#C7D2FE'" onmouseout="this.style.background='#F3F4F6';this.style.color='#6B7280';this.style.borderColor='#E5E7EB'">
                            <i class="fas fa-arrow-up-right-from-square" style="font-size:10px;"></i> Task
                        </a>
                        <form method="POST" action="{{ route('admin.tasks.social.assign', $st->id) }}" style="display:flex;gap:4px;align-items:center;">
                            @csrf
                            <select name="social_user_id" required style="font-size:11px;padding:4px 8px;border:1.5px solid #E5E7EB;border-radius:7px;background:#fff;color:#374151;outline:none;max-width:130px;">
                                <option value="">Reassign...</option>
                                @foreach($socialUsers as $su)
                                <option value="{{ $su->id }}" {{ $su->id == $st->social_assigned_to ? 'selected' : '' }}>{{ $su->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit"
                                    style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:linear-gradient(135deg,#6366F1,#8B5CF6);color:#fff;border:none;border-radius:7px;font-size:11px;font-weight:600;cursor:pointer;white-space:nowrap;">
                                <i class="fas fa-arrows-rotate" style="font-size:10px;"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>{{-- .tbl-scroll --}}
</div>{{-- card --}}

@if($socialTasks->hasPages())
<div style="margin-top:20px;">{{ $socialTasks->appends(['tab' => 'social'])->links() }}</div>
@endif

@endif {{-- social tasks not empty --}}

@endif {{-- end social tab --}}

</div>

{{-- ═══════════════ TASK PANEL MODAL ═══════════════ --}}
<div id="taskPanelOverlay" style="display:none;position:fixed;inset:0;z-index:9998;background:rgba(10,14,35,.6);backdrop-filter:blur(4px);" onclick="closeTaskPanel()"></div>
<div id="taskPanelDrawer" style="display:none;position:fixed;top:0;right:0;bottom:0;width:min(680px,100vw);z-index:9999;overflow-y:auto;background:#F8F9FC;box-shadow:-12px 0 48px rgba(10,14,35,.18);transition:transform .3s cubic-bezier(.22,1,.36,1);transform:translateX(100%);">

    {{-- Loading state --}}
    <div id="taskPanelLoading" style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:16px;">
        <div style="width:44px;height:44px;border:3px solid #E0E7FF;border-top-color:#6366F1;border-radius:50%;animation:tpSpin .7s linear infinite;"></div>
        <p style="font-size:13px;color:#9CA3AF;margin:0;">Loading task details…</p>
    </div>

    {{-- Content (filled by JS) --}}
    <div id="taskPanelContent" style="display:none;">

        {{-- Sticky header --}}
        <div id="tpHeader" style="position:sticky;top:0;z-index:10;padding:20px 24px 16px;background:linear-gradient(135deg,#4F46E5,#7C3AED);">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:12px;">
                <h2 id="tpTitle" style="font-size:18px;font-weight:800;color:#fff;margin:0;line-height:1.3;flex:1;"></h2>
                <div style="display:flex;gap:8px;flex-shrink:0;">
                    <a id="tpOpenBtn" href="#" style="display:flex;align-items:center;gap:5px;padding:7px 14px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);border-radius:9px;color:#fff;font-size:12px;font-weight:600;text-decoration:none;transition:background .15s;" onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
                        <i class="fas fa-arrow-up-right-from-square" style="font-size:10px;"></i> Open Task
                    </a>
                    <button onclick="closeTaskPanel()" style="width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;" onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            {{-- Status + Priority + Deadline strip --}}
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <span id="tpStatusBadge" style="font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;"></span>
                <span id="tpPriorityBadge" style="font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;"></span>
                <span id="tpDeadline" style="font-size:11px;color:rgba(255,255,255,.8);display:flex;align-items:center;gap:5px;"></span>
            </div>
        </div>

        {{-- Info strip --}}
        <div id="tpInfoStrip" style="background:#fff;border-bottom:1px solid #EBEBEB;padding:14px 24px;display:flex;gap:20px;flex-wrap:wrap;"></div>

        {{-- Scrollable body --}}
        <div style="padding:20px 24px;display:flex;flex-direction:column;gap:24px;">

            {{-- About --}}
            <div id="tpAbout" class="tp-section"></div>

            {{-- Submission History --}}
            <div id="tpSubmissions" class="tp-section"></div>

            {{-- Activity Timeline --}}
            <div id="tpTimeline" class="tp-section"></div>

            {{-- Comments --}}
            <div id="tpComments" class="tp-section"></div>

            {{-- Social Media --}}
            <div id="tpSocial" class="tp-section"></div>

        </div>
    </div>
</div>

<style>
.tp-section-head {
    font-size:11px;font-weight:800;color:#9CA3AF;text-transform:uppercase;letter-spacing:.08em;
    margin:0 0 12px;display:flex;align-items:center;gap:8px;
}
.tp-section-head::after {
    content:'';flex:1;height:1px;background:#F0F0F0;
}
.tp-chip {
    display:inline-flex;align-items:center;gap:5px;
    font-size:11px;font-weight:600;padding:4px 10px;border-radius:20px;
    background:#F3F4F6;color:#6B7280;
}
@keyframes tpSlideIn { from { transform:translateX(100%); } to { transform:translateX(0); } }
@keyframes tpSlideOut { from { transform:translateX(0); } to { transform:translateX(100%); } }
@keyframes tpSpin { to { transform:rotate(360deg); } }
</style>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: .4; }
}
[x-cloak] { display: none !important; }
</style>

<script>
function approvalPage() {
    return {
        modal: false, sub: null,
        viewer: false, viewerFile: null,

        // ── Approval modal ──
        approvalModal: false,
        approvalTask:       null,
        approvalNote:       '',
        approvalSocial:     null,   // 'yes' | 'no' | 'later' | null
        approvalSocialUser: '',

        openApprovalModal(task) {
            this.approvalTask       = task;
            this.approvalNote       = '';
            this.approvalSocial     = null;
            this.approvalSocialUser = '';
            this.approvalModal      = true;
        },

        // ── Reject modal ──
        rejectModal: false,
        rejectTask:  null,
        rejectNote:  '',

        openRejectModal(task) {
            this.rejectTask  = task;
            this.rejectNote  = '';
            this.rejectModal = true;
        },

        openModal(data)  { this.sub = data; this.modal = true; },
        closeModal()     { this.modal = false; this.sub = null; },
        openViewer(url, filename) {
            this.viewerFile = { url, filename, type: this.fileType(filename) };
            this.viewer = true;
        },
        closeViewer()    { this.viewer = false; this.viewerFile = null; },

        fileType(filename) {
            if (!filename) return 'other';
            const ext = filename.split('.').pop().toLowerCase();
            if (['jpg','jpeg','png','gif','webp','svg'].includes(ext)) return 'image';
            if (['mp4','mov','avi','mkv','webm'].includes(ext))        return 'video';
            if (ext === 'pdf')                                          return 'pdf';
            if (['doc','docx'].includes(ext))                          return 'word';
            if (['xls','xlsx'].includes(ext))                          return 'excel';
            if (['ppt','pptx'].includes(ext))                          return 'powerpoint';
            if (['zip','rar','7z'].includes(ext))                      return 'zip';
            return 'other';
        },

        viewerIconHtml() {
            if (!this.viewerFile) return '';
            const icons = {
                word:       { cls: 'fa-file-word',       color: '#2563EB' },
                excel:      { cls: 'fa-file-excel',      color: '#16A34A' },
                powerpoint: { cls: 'fa-file-powerpoint', color: '#EA580C' },
                zip:        { cls: 'fa-file-zipper',     color: '#CA8A04' },
            };
            const ic = icons[this.viewerFile.type] || { cls: 'fa-file', color: '#9CA3AF' };
            return `<i class="fas ${ic.cls}" style="font-size:64px;color:${ic.color};display:block;"></i>`;
        },

        statusBadge(status) {
            const map = {
                submitted: 'background:linear-gradient(135deg,#EEF2FF,#E0E7FF);color:#4F46E5;border:1px solid #C7D2FE',
                approved:  'background:linear-gradient(135deg,#D1FAE5,#A7F3D0);color:#065F46;border:1px solid #6EE7B7',
                rejected:  'background:linear-gradient(135deg,#FEE2E2,#FECACA);color:#991B1B;border:1px solid #FCA5A5',
            };
            const s = map[status] || 'background:#F3F4F6;color:#6B7280';
            const label = status ? status.charAt(0).toUpperCase() + status.slice(1) : '';
            return `<span style="font-size:11px;font-weight:700;padding:4px 11px;border-radius:20px;${s}">${label}</span>`;
        }
    }
}

// ── Task Panel ───────────────────────────────────────────────────────────
var _tpOpen = false;

function openTaskPanel(taskId) {
    if (!taskId) return;
    var overlay = document.getElementById('taskPanelOverlay');
    var drawer  = document.getElementById('taskPanelDrawer');
    var loading = document.getElementById('taskPanelLoading');
    var content = document.getElementById('taskPanelContent');

    overlay.style.display = 'block';
    drawer.style.display  = 'block';
    setTimeout(function(){ drawer.style.transform = 'translateX(0)'; }, 10);
    document.body.style.overflow = 'hidden';
    _tpOpen = true;

    loading.style.display = 'flex';
    content.style.display = 'none';

    fetch('/admin/tasks/' + taskId + '/panel', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r){ return r.json(); })
        .then(function(d){ renderTaskPanel(d); })
        .catch(function(){ loading.innerHTML = '<p style="color:#EF4444;font-size:13px;padding:40px;">Failed to load task.</p>'; });
}

function closeTaskPanel() {
    var overlay = document.getElementById('taskPanelOverlay');
    var drawer  = document.getElementById('taskPanelDrawer');
    drawer.style.transform = 'translateX(100%)';
    setTimeout(function(){
        overlay.style.display = 'none';
        drawer.style.display  = 'none';
        document.body.style.overflow = '';
    }, 300);
    _tpOpen = false;
}

document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && _tpOpen) closeTaskPanel(); });

function _esc(str) {
    if (str == null) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

var _spIcons = {facebook:['fa-facebook','#1877F2'],instagram:['fa-instagram','#E1306C'],twitter:['fa-x-twitter','#000'],linkedin:['fa-linkedin','#0A66C2'],tiktok:['fa-tiktok','#010101'],youtube:['fa-youtube','#FF0000'],snapchat:['fa-snapchat','#F7CA00'],other:['fa-share-nodes','#6366F1']};

function renderTaskPanel(d) {
    var loading = document.getElementById('taskPanelLoading');
    var content = document.getElementById('taskPanelContent');

    // ── Header ──
    document.getElementById('tpTitle').textContent = d.title;
    document.getElementById('tpOpenBtn').href = d.taskUrl;

    var statusBadge = document.getElementById('tpStatusBadge');
    statusBadge.textContent = d.statusLabel;
    statusBadge.style.cssText = 'font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;background:' + d.statusBg + ';color:' + d.statusColor + ';';

    var priBadge = document.getElementById('tpPriorityBadge');
    if (d.priorityMeta) {
        priBadge.textContent = d.priorityMeta.label + ' Priority';
        priBadge.style.cssText = 'font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;background:' + d.priorityMeta.bg + ';color:' + d.priorityMeta.color + ';';
        priBadge.style.display = 'inline-flex';
    } else { priBadge.style.display = 'none'; }

    var deadlineEl = document.getElementById('tpDeadline');
    if (d.deadline) {
        var dlIcon = d.isOverdue ? '<i class="fas fa-triangle-exclamation" style="color:#FCA5A5;font-size:10px;"></i>' : '<i class="fas fa-calendar-days" style="font-size:10px;"></i>';
        deadlineEl.innerHTML = dlIcon + '<span style="color:' + (d.isOverdue ? '#FCA5A5' : 'rgba(255,255,255,.8)') + ';">' + (d.isOverdue ? 'Overdue · ' : '') + _esc(d.deadline) + '</span>';
    } else { deadlineEl.innerHTML = ''; }

    // ── Info strip ──
    var strip = document.getElementById('tpInfoStrip');
    var stripItems = [];
    if (d.project)  stripItems.push('<div><p style="font-size:10px;color:#9CA3AF;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin:0 0 3px;">Project</p><p style="font-size:13px;font-weight:600;color:#111827;margin:0;display:flex;align-items:center;gap:5px;"><i class="fas fa-folder" style="font-size:10px;color:#C4B5FD;"></i>' + _esc(d.project.name) + '</p></div>');
    if (d.assignee) stripItems.push('<div><p style="font-size:10px;color:#9CA3AF;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin:0 0 3px;">Assignee</p><p style="font-size:13px;font-weight:600;color:#111827;margin:0;display:flex;align-items:center;gap:6px;"><span style="width:22px;height:22px;border-radius:6px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:inline-flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff;">' + _esc(d.assignee.initials) + '</span>' + _esc(d.assignee.name) + '</p></div>');
    if (d.creator)  stripItems.push('<div><p style="font-size:10px;color:#9CA3AF;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin:0 0 3px;">Created By</p><p style="font-size:13px;font-weight:600;color:#111827;margin:0;display:flex;align-items:center;gap:6px;"><span style="width:22px;height:22px;border-radius:6px;background:linear-gradient(135deg,#F59E0B,#D97706);display:inline-flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff;">' + _esc(d.creator.initials) + '</span>' + _esc(d.creator.name) + '</p></div>');
    if (d.reviewer) stripItems.push('<div><p style="font-size:10px;color:#9CA3AF;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin:0 0 3px;">Reviewer</p><p style="font-size:13px;font-weight:600;color:#111827;margin:0;display:flex;align-items:center;gap:6px;"><span style="width:22px;height:22px;border-radius:6px;background:linear-gradient(135deg,#10B981,#059669);display:inline-flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff;">' + _esc(d.reviewer.initials) + '</span>' + _esc(d.reviewer.name) + '</p></div>');
    strip.innerHTML = stripItems.join('<div style="width:1px;background:#F0F0F0;align-self:stretch;"></div>');

    // ── About ──
    var about = document.getElementById('tpAbout');
    var aboutHtml = '<p class="tp-section-head"><i class="fas fa-circle-info" style="font-size:10px;color:#A5B4FC;"></i> About this Task</p>';
    if (d.description) {
        aboutHtml += '<div style="background:#fff;border:1px solid #EBEBEB;border-radius:12px;padding:14px 16px;font-size:13px;color:#374151;line-height:1.65;white-space:pre-wrap;margin-bottom:12px;">' + _esc(d.description) + '</div>';
    } else {
        aboutHtml += '<p style="font-size:12px;color:#D1D5DB;font-style:italic;margin-bottom:12px;">No description provided.</p>';
    }
    aboutHtml += '<div style="display:flex;gap:8px;flex-wrap:wrap;">';
    aboutHtml += '<span class="tp-chip"><i class="fas fa-clock" style="font-size:9px;color:#A5B4FC;"></i> Created ' + _esc(d.createdAt) + '</span>';
    aboutHtml += '<span class="tp-chip"><i class="fas fa-pen" style="font-size:9px;color:#A5B4FC;"></i> Updated ' + _esc(d.updatedAt) + '</span>';
    aboutHtml += '</div>';
    about.innerHTML = aboutHtml;

    // ── Submissions ──
    var subEl = document.getElementById('tpSubmissions');
    var subHtml = '<p class="tp-section-head"><i class="fas fa-layer-group" style="font-size:10px;color:#A5B4FC;"></i> Submission History <span style="font-weight:400;color:#C4B5FD;font-size:10px;">(' + d.submissions.length + ' version' + (d.submissions.length !== 1 ? 's' : '') + ')</span></p>';
    if (!d.submissions.length) {
        subHtml += '<p style="font-size:12px;color:#D1D5DB;font-style:italic;">No submissions yet.</p>';
    } else {
        d.submissions.forEach(function(s) {
            var isAppr = s.status === 'approved';
            var isRej  = s.status === 'rejected' || s.status === 'revision_requested';
            var decBg    = isAppr ? '#D1FAE5' : (isRej ? '#FEE2E2' : '#EEF2FF');
            var decColor = isAppr ? '#065F46' : (isRej ? '#991B1B' : '#4F46E5');
            var decIcon  = isAppr ? 'fa-circle-check' : (isRej ? 'fa-rotate-left' : 'fa-hourglass-half');
            var decLabel = isAppr ? 'Approved' : (isRej ? 'Revision Requested' : ucfirstJs(s.status));
            subHtml += '<div style="background:#fff;border:1px solid #EBEBEB;border-radius:14px;overflow:hidden;margin-bottom:10px;">';
            // version header bar
            subHtml += '<div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid #F3F4F6;background:#FAFBFF;">';
            subHtml += '<div style="display:flex;align-items:center;gap:10px;">';
            subHtml += '<span style="font-size:11px;font-weight:800;color:#4F46E5;background:linear-gradient(135deg,#EEF2FF,#E0E7FF);padding:3px 10px;border-radius:20px;border:1px solid #C7D2FE;">v' + s.version + '</span>';
            subHtml += '<span style="font-size:11px;color:#9CA3AF;">by <strong style="color:#374151;">' + _esc(s.user || '—') + '</strong></span>';
            subHtml += '<span style="font-size:10px;color:#D1D5DB;">' + _esc(s.submittedAt) + '</span>';
            subHtml += '</div>';
            subHtml += '<span style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px;background:' + decBg + ';color:' + decColor + ';"><i class="fa ' + decIcon + '" style="font-size:9px;"></i> ' + _esc(decLabel) + '</span>';
            subHtml += '</div>';
            // body
            subHtml += '<div style="padding:12px 16px;display:flex;gap:14px;flex-wrap:wrap;">';
            // thumbnail / file
            if (s.fileUrl) {
                if (s.fileType === 'image') {
                    subHtml += '<a href="' + _esc(s.fileUrl) + '" target="_blank" rel="noopener" style="flex-shrink:0;display:block;width:80px;height:64px;border-radius:10px;overflow:hidden;border:1.5px solid #E0E7FF;">';
                    subHtml += '<img src="' + _esc(s.fileUrl) + '" style="width:100%;height:100%;object-fit:cover;" alt="' + _esc(s.filename) + '">';
                    subHtml += '</a>';
                } else {
                    subHtml += '<a href="' + _esc(s.fileUrl) + '" target="_blank" rel="noopener" style="flex-shrink:0;display:flex;flex-direction:column;align-items:center;justify-content:center;width:80px;height:64px;border-radius:10px;border:1.5px solid #E0E7FF;background:#F5F7FF;text-decoration:none;gap:4px;">';
                    subHtml += '<i class="fas ' + (s.fileType==='pdf'?'fa-file-pdf':'fa-file') + '" style="font-size:22px;color:#A5B4FC;"></i>';
                    subHtml += '<span style="font-size:9px;color:#9CA3AF;text-align:center;overflow:hidden;width:72px;text-overflow:ellipsis;white-space:nowrap;">' + _esc(s.filename) + '</span>';
                    subHtml += '</a>';
                }
            }
            // notes
            subHtml += '<div style="flex:1;min-width:0;">';
            if (s.note) subHtml += '<div style="margin-bottom:8px;"><p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;margin:0 0 3px;">Submission Note</p><p style="font-size:12px;color:#374151;margin:0;line-height:1.55;">' + _esc(s.note) + '</p></div>';
            if (s.adminNote) subHtml += '<div style="padding:8px 10px;background:#FFF7ED;border-radius:8px;border-left:3px solid #F59E0B;"><p style="font-size:10px;font-weight:700;color:#D97706;text-transform:uppercase;letter-spacing:.05em;margin:0 0 2px;">Admin Feedback</p><p style="font-size:12px;color:#374151;margin:0;line-height:1.55;">' + _esc(s.adminNote) + '</p></div>';
            if (!s.note && !s.adminNote) subHtml += '<p style="font-size:12px;color:#D1D5DB;font-style:italic;margin:0;">No notes.</p>';
            if (s.reviewer && s.reviewedAt) subHtml += '<p style="font-size:10px;color:#9CA3AF;margin:8px 0 0;"><i class="fas fa-user-check" style="font-size:9px;margin-right:3px;"></i>Reviewed by <strong>' + _esc(s.reviewer) + '</strong> · ' + _esc(s.reviewedAt) + '</p>';
            subHtml += '</div>';
            subHtml += '</div></div>';
        });
    }
    subEl.innerHTML = subHtml;

    // ── Activity Timeline ──
    var tlEl = document.getElementById('tpTimeline');
    var tlHtml = '<p class="tp-section-head"><i class="fas fa-timeline" style="font-size:10px;color:#A5B4FC;"></i> Activity Timeline <span style="font-weight:400;color:#C4B5FD;font-size:10px;">(' + d.logs.length + ' event' + (d.logs.length !== 1 ? 's' : '') + ')</span></p>';
    if (!d.logs.length) {
        tlHtml += '<p style="font-size:12px;color:#D1D5DB;font-style:italic;">No activity logged.</p>';
    } else {
        tlHtml += '<div style="position:relative;padding-left:28px;">';
        tlHtml += '<div style="position:absolute;left:10px;top:6px;bottom:6px;width:2px;background:linear-gradient(to bottom,#E0E7FF,#F3F4F6);border-radius:2px;"></div>';
        d.logs.forEach(function(log, i) {
            var icon = log.style[0], fgColor = log.style[1], bgColor = log.style[2];
            tlHtml += '<div style="position:relative;margin-bottom:' + (i < d.logs.length-1 ? '14' : '0') + 'px;">';
            tlHtml += '<div style="position:absolute;left:-24px;top:2px;width:22px;height:22px;border-radius:50%;background:' + bgColor + ';display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.1);">';
            tlHtml += '<i class="fas ' + icon + '" style="font-size:9px;color:' + fgColor + ';"></i></div>';
            tlHtml += '<div style="background:#fff;border:1px solid #EBEBEB;border-radius:10px;padding:10px 13px;">';
            tlHtml += '<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:' + (log.note ? '5' : '0') + 'px;">';
            tlHtml += '<span style="font-size:12px;font-weight:700;color:#111827;">' + _esc(log.label) + '</span>';
            tlHtml += '<div style="display:flex;align-items:center;gap:6px;">';
            if (log.user) tlHtml += '<span style="font-size:10px;color:#6B7280;">' + _esc(log.user) + '</span><span style="font-size:10px;color:#D1D5DB;">·</span>';
            tlHtml += '<span style="font-size:10px;color:#9CA3AF;" title="' + _esc(log.createdAt) + '">' + _esc(log.diffHumans) + '</span>';
            tlHtml += '</div></div>';
            if (log.note) tlHtml += '<p style="font-size:12px;color:#6B7280;margin:0;line-height:1.5;">' + _esc(log.note) + '</p>';
            tlHtml += '</div></div>';
        });
        tlHtml += '</div>';
    }
    tlEl.innerHTML = tlHtml;

    // ── Comments ──
    var cmEl = document.getElementById('tpComments');
    if (d.comments && d.comments.length) {
        var cmHtml = '<p class="tp-section-head"><i class="fas fa-comments" style="font-size:10px;color:#A5B4FC;"></i> Comments <span style="font-weight:400;color:#C4B5FD;font-size:10px;">(' + d.comments.length + ')</span></p>';
        d.comments.forEach(function(c) {
            cmHtml += '<div style="display:flex;gap:10px;margin-bottom:10px;">';
            cmHtml += '<div style="width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;">' + _esc(c.initials) + '</div>';
            cmHtml += '<div style="flex:1;background:#fff;border:1px solid #EBEBEB;border-radius:12px;padding:10px 13px;">';
            cmHtml += '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;">';
            cmHtml += '<span style="font-size:12px;font-weight:700;color:#111827;">' + _esc(c.user || 'Unknown') + '</span>';
            cmHtml += '<span style="font-size:10px;color:#9CA3AF;" title="' + _esc(c.createdAt) + '">' + _esc(c.diffHumans) + '</span>';
            cmHtml += '</div>';
            cmHtml += '<p style="font-size:12px;color:#374151;margin:0;line-height:1.6;">' + _esc(c.body) + '</p>';
            cmHtml += '</div></div>';
        });
        cmEl.innerHTML = cmHtml;
    } else { cmEl.innerHTML = ''; }

    // ── Social Media ──
    var soEl = document.getElementById('tpSocial');
    if (d.socialPosts && d.socialPosts.length) {
        var soHtml = '<p class="tp-section-head"><i class="fas fa-share-nodes" style="font-size:10px;color:#A5B4FC;"></i> Social Media Posts <span style="font-weight:400;color:#C4B5FD;font-size:10px;">(' + d.socialPosts.length + ')</span></p>';
        d.socialPosts.forEach(function(sp) {
            var ico = _spIcons[sp.platform] || _spIcons['other'];
            soHtml += '<div style="display:flex;align-items:flex-start;gap:10px;background:#fff;border:1px solid #EBEBEB;border-radius:12px;padding:12px 14px;margin-bottom:8px;">';
            soHtml += '<div style="width:34px;height:34px;border-radius:9px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fab ' + ico[0] + '" style="font-size:16px;color:' + ico[1] + ';"></i></div>';
            soHtml += '<div style="flex:1;min-width:0;">';
            soHtml += '<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:' + (sp.caption ? '5' : '0') + 'px;">';
            soHtml += '<span style="font-size:13px;font-weight:700;color:#111827;text-transform:capitalize;">' + _esc(sp.platform) + '</span>';
            soHtml += '<span style="font-size:10px;color:#9CA3AF;">' + _esc(sp.postedAt) + '</span>';
            soHtml += '</div>';
            if (sp.caption) soHtml += '<p style="font-size:12px;color:#6B7280;margin:0 0 6px;line-height:1.5;">' + _esc(sp.caption) + '</p>';
            if (sp.postUrl) soHtml += '<a href="' + _esc(sp.postUrl) + '" target="_blank" rel="noopener" style="font-size:11px;color:#6366F1;text-decoration:none;font-weight:600;display:inline-flex;align-items:center;gap:4px;"><i class="fas fa-arrow-up-right-from-square" style="font-size:9px;"></i> View Post</a>';
            soHtml += '</div></div>';
        });
        soEl.innerHTML = soHtml;
    } else { soEl.innerHTML = ''; }

    loading.style.display = 'none';
    content.style.display = 'block';
    document.getElementById('taskPanelDrawer').scrollTop = 0;
}

function ucfirstJs(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1).replace(/_/g, ' ');
}

// ── History view toggle (Table / Cards) ──────────────────────────────────
function setHistView(view) {
    var tableEl  = document.getElementById('histTableView');
    var cardsEl  = document.getElementById('histCardsView');
    var btnTable = document.getElementById('histBtnTable');
    var btnCards = document.getElementById('histBtnCards');
    if (!tableEl || !cardsEl) return;

    if (view === 'cards') {
        tableEl.style.display = 'none';
        cardsEl.style.display = 'block';
        btnTable.classList.remove('active');
        btnCards.classList.add('active');
    } else {
        tableEl.style.display = 'block';
        cardsEl.style.display = 'none';
        btnTable.classList.add('active');
        btnCards.classList.remove('active');
    }
    try { localStorage.setItem('histView', view); } catch(e) {}
}

(function initHistView() {
    var saved = null;
    try { saved = localStorage.getItem('histView'); } catch(e) {}
    // Auto-switch to cards on narrow screens unless user explicitly chose table
    if (!saved) {
        saved = window.innerWidth <= 900 ? 'cards' : 'table';
    }
    setHistView(saved);

    // Re-check on resize so auto-switch still works if user hasn't manually toggled
    window.addEventListener('resize', function() {
        try { if (localStorage.getItem('histView')) return; } catch(e) {}
        setHistView(window.innerWidth <= 900 ? 'cards' : 'table');
    });
})();

// ── Pending tab: Card / List toggle ────────────────────────────────────
function setPendView(view) {
    var cardsEl  = document.getElementById('pendingCardsView');
    var listEl   = document.getElementById('pendingListView');
    var btnCards = document.getElementById('pendBtnCards');
    var btnList  = document.getElementById('pendBtnList');
    if (!cardsEl || !listEl) return;

    if (view === 'list') {
        cardsEl.style.display = 'none';
        listEl.style.display  = 'block';
        if (btnCards) btnCards.classList.remove('active');
        if (btnList)  btnList.classList.add('active');
    } else {
        cardsEl.style.display = 'block';
        listEl.style.display  = 'none';
        if (btnCards) btnCards.classList.add('active');
        if (btnList)  btnList.classList.remove('active');
    }
    try { localStorage.setItem('pendView', view); } catch(e) {}
}

function togglePendReject(taskId) {
    var row = document.getElementById('pend-rej-' + taskId);
    var btn = document.getElementById('pend-rej-btn-' + taskId);
    if (!row) return;
    var isOpen = row.classList.contains('open');
    row.classList.toggle('open');
    if (btn) {
        btn.style.background   = isOpen ? '#FEF2F2' : '#FEE2E2';
        btn.style.borderColor  = isOpen ? '#FECACA' : '#F87171';
    }
    if (!isOpen) {
        var input = row.querySelector('input[name="note"]');
        if (input) setTimeout(function() { input.focus(); }, 60);
    }
}

(function initPendView() {
    var saved = null;
    try { saved = localStorage.getItem('pendView'); } catch(e) {}
    if (!saved) saved = 'list'; // default: list
    setPendView(saved);
    window.addEventListener('resize', function() {
        try { if (localStorage.getItem('pendView')) return; } catch(e) {}
        setPendView('list');
    });
})();
</script>

@endsection
