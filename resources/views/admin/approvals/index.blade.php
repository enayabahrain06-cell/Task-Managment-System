@extends('layouts.app')
@section('title', 'Task Approvals')

@section('content')
@php
    $waApiEnabled  = \App\Models\Setting::get('wa_enabled','0') === '1'
                  && \App\Models\Setting::get('wa_token','') !== '';
    $waSendRoute   = route('admin.approvals.whatsapp-customer');
    $waMediaRoute  = route('admin.approvals.whatsapp-customer-media');
    $waCsrf        = csrf_token();
@endphp
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
.tbl-scroll .hist-table { min-width: 860px; table-layout: fixed; width: 100%; }

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
    .tbl-scroll .hist-table { min-width: 800px; }

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

<div x-data="approvalPage()" @keydown.escape.window="if(viewer) closeViewer(); else if(approvalModal) approvalModal=false; else if(rejectModal) rejectModal=false; else if(qvModal) closeQuickView(); else closeModal()"
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
                <video :src="viewerFile.url" controls autoplay x-init="$el.load()"
                       onerror="this.insertAdjacentHTML('afterend','<div style=\'padding:32px;text-align:center;color:#fff\'><i class=\'fas fa-video-slash\' style=\'font-size:32px;color:#F87171;display:block;margin-bottom:12px\'></i><p style=\'margin:0;font-size:14px\'>Video file could not be loaded</p></div>');this.remove()"
                       style="max-width:88vw;max-height:82vh;border-radius:12px;outline:none;display:block;box-shadow:0 20px 60px rgba(0,0,0,.5);">
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
                                <video :src="sub.file" x-init="$el.load()"
                                       onerror="this.style.display='none';this.parentElement.querySelector('.vid-err')?.style.setProperty('display','flex')"
                                       style="width:100%;max-height:180px;object-fit:cover;display:block;" preload="metadata" muted></video>
                                <div class="vid-err" style="display:none;align-items:center;justify-content:center;flex-direction:column;padding:18px;background:#FEF2F2;min-height:80px;">
                                    <i class="fas fa-video-slash" style="color:#F87171;font-size:18px;margin-bottom:6px;"></i>
                                    <p style="font-size:11px;color:#EF4444;margin:0;">File not found</p>
                                </div>
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

{{-- ═══════════ QUICK VIEW MODAL ═══════════ --}}
<template x-teleport="body">
    <div x-show="qvModal" x-cloak
         style="position:fixed;inset:0;z-index:99998;backdrop-filter:blur(5px);background:rgba(10,12,30,.6);">
        <div @click.self="closeQuickView()"
             style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:20px;">
        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             style="background:#fff;border-radius:24px;width:100%;max-width:600px;box-shadow:0 32px 90px rgba(0,0,0,.28);overflow:hidden;display:flex;flex-direction:column;max-height:90vh;">

            {{-- ── Header ── --}}
            <div style="background:linear-gradient(135deg,#6366F1 0%,#8B5CF6 100%);padding:22px 24px 20px;position:relative;overflow:hidden;flex-shrink:0;">
                <div style="position:absolute;right:-24px;top:-24px;width:110px;height:110px;border-radius:50%;background:rgba(255,255,255,.07);pointer-events:none;"></div>
                <div style="position:absolute;right:50px;bottom:-28px;width:72px;height:72px;border-radius:50%;background:rgba(255,255,255,.05);pointer-events:none;"></div>
                <div style="display:flex;align-items:flex-start;gap:14px;position:relative;z-index:1;">
                    <div style="width:48px;height:48px;border-radius:14px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;color:#fff;flex-shrink:0;"
                         x-text="qvTask?.initial ?? '?'"></div>
                    <div style="flex:1;min-width:0;">
                        <h3 style="font-size:16px;font-weight:700;color:#fff;margin:0 0 6px;line-height:1.3;" x-text="qvTask?.title"></h3>
                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                            <span style="font-size:11px;color:rgba(255,255,255,.8);display:inline-flex;align-items:center;gap:4px;">
                                <i class="fas fa-user" style="font-size:9px;"></i> <span x-text="qvTask?.assignee"></span>
                            </span>
                            <span style="color:rgba(255,255,255,.35);font-size:11px;">·</span>
                            <span style="font-size:11px;color:rgba(255,255,255,.8);display:inline-flex;align-items:center;gap:4px;">
                                <i class="fas fa-folder" style="font-size:9px;"></i> <span x-text="qvTask?.project"></span>
                            </span>
                            <span style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;background:rgba(255,255,255,.15);border-radius:20px;font-size:10px;font-weight:700;color:#fff;">
                                <i class="fas fa-hourglass-half" style="font-size:9px;"></i> In Review
                            </span>
                        </div>
                    </div>
                    <button @click="closeQuickView()"
                            style="width:32px;height:32px;border-radius:10px;background:rgba(255,255,255,.15);border:none;cursor:pointer;color:#fff;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .15s;"
                            onmouseover="this.style.background='rgba(255,255,255,.28)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            {{-- ── Body ── --}}
            <div style="overflow-y:auto;flex:1;padding:20px 24px;">

                {{-- Meta chips --}}
                <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin-bottom:18px;">
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;"
                          :style="`background:${qvTask?.priority_bg};color:${qvTask?.priority_color};`">
                        <i class="fas fa-flag" style="font-size:9px;"></i>
                        <span x-text="qvTask?.priority"></span>
                    </span>
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;background:#EDE9FE;color:#7C3AED;">
                        <i class="fas fa-layer-group" style="font-size:9px;"></i>
                        <span x-text="(qvTask?.versions ?? 0) + ' version' + ((qvTask?.versions ?? 0) !== 1 ? 's' : '')"></span>
                    </span>
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;"
                          :style="qvTask?.is_overdue ? 'background:#FEE2E2;color:#DC2626;' : 'background:#F3F4F6;color:#6B7280;'">
                        <i class="fas fa-calendar-days" style="font-size:9px;"></i>
                        <span x-text="qvTask?.deadline ?? 'No deadline'"></span>
                        <template x-if="qvTask?.is_overdue"><span style="font-weight:700;"> · Overdue</span></template>
                    </span>
                </div>

                {{-- File preview section --}}
                <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.07em;margin:0 0 8px;">
                    <i class="fas fa-paperclip" style="font-size:9px;margin-right:3px;"></i> Latest Submission
                </p>

                <template x-if="qvTask?.submission_url">
                    <div>
                        {{-- Image --}}
                        <template x-if="fileType(qvTask?.submission_name) === 'image'">
                            <div @click="openViewer(qvTask?.submission_url, qvTask?.submission_name)"
                                 style="cursor:pointer;border-radius:14px;overflow:hidden;border:1.5px solid #DDE3F5;position:relative;box-shadow:0 4px 18px rgba(99,102,241,.1);margin-bottom:8px;">
                                <img :src="qvTask?.submission_url" :alt="qvTask?.submission_name"
                                     style="width:100%;max-height:280px;object-fit:cover;display:block;">
                                <div style="position:absolute;inset:0;background:rgba(0,0,0,0);display:flex;align-items:center;justify-content:center;transition:background .2s;"
                                     onmouseover="this.style.background='rgba(0,0,0,.28)';this.querySelector('.qv-lens').style.opacity='1'"
                                     onmouseout="this.style.background='rgba(0,0,0,0)';this.querySelector('.qv-lens').style.opacity='0'">
                                    <div class="qv-lens" style="width:52px;height:52px;border-radius:50%;background:rgba(255,255,255,.95);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .2s;box-shadow:0 4px 16px rgba(0,0,0,.22);">
                                        <i class="fas fa-expand" style="color:#4F46E5;font-size:16px;"></i>
                                    </div>
                                </div>
                            </div>
                        </template>
                        {{-- Video --}}
                        <template x-if="fileType(qvTask?.submission_name) === 'video'">
                            <div x-data="{ videoError: false }">
                                <div x-show="!videoError" @click="openViewer(qvTask?.submission_url, qvTask?.submission_name)"
                                     style="cursor:pointer;border-radius:14px;overflow:hidden;border:1.5px solid #DDE3F5;position:relative;box-shadow:0 4px 18px rgba(99,102,241,.1);margin-bottom:8px;">
                                    <video :src="qvTask?.submission_url" x-init="$el.load()"
                                           x-on:error="videoError = true"
                                           style="width:100%;max-height:240px;object-fit:cover;display:block;" preload="metadata" muted></video>
                                    <div style="position:absolute;inset:0;background:rgba(0,0,0,.32);display:flex;align-items:center;justify-content:center;">
                                        <div style="width:54px;height:54px;border-radius:50%;background:rgba(255,255,255,.95);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(0,0,0,.22);">
                                            <i class="fas fa-play" style="color:#4F46E5;font-size:18px;margin-left:3px;"></i>
                                        </div>
                                    </div>
                                </div>
                                <div x-show="videoError"
                                     style="border-radius:14px;border:1.5px dashed #FCA5A5;background:#FEF2F2;padding:24px;text-align:center;margin-bottom:8px;">
                                    <i class="fas fa-video-slash" style="color:#F87171;font-size:22px;display:block;margin-bottom:8px;"></i>
                                    <p style="font-size:12px;font-weight:600;color:#EF4444;margin:0 0 2px;">Video file not found</p>
                                    <p style="font-size:11px;color:#9CA3AF;margin:0;">The file may have been deleted. Ask the assignee to re-submit.</p>
                                </div>
                            </div>
                        </template>
                        {{-- Other file types --}}
                        <template x-if="!['image','video'].includes(fileType(qvTask?.submission_name))">
                            <div @click="openViewer(qvTask?.submission_url, qvTask?.submission_name)"
                                 style="display:flex;align-items:center;gap:12px;padding:14px 16px;border:1.5px solid #DDE3F5;border-radius:12px;cursor:pointer;background:#F8FAFF;transition:all .15s;margin-bottom:8px;"
                                 onmouseover="this.style.background='#EEF2FF';this.style.borderColor='#C7D2FE'" onmouseout="this.style.background='#F8FAFF';this.style.borderColor='#DDE3F5'">
                                <div style="width:42px;height:42px;border-radius:11px;background:linear-gradient(135deg,#EEF2FF,#E0E7FF);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fas fa-paperclip" style="color:#6366F1;font-size:16px;"></i>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <p style="font-size:13px;font-weight:600;color:#4F46E5;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="qvTask?.submission_name"></p>
                                    <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Click to preview / download</p>
                                </div>
                                <i class="fas fa-expand" style="color:#A5B4FC;font-size:12px;flex-shrink:0;"></i>
                            </div>
                        </template>
                        {{-- Download link --}}
                        <a :href="qvTask?.submission_url" :download="qvTask?.submission_name"
                           style="display:inline-flex;align-items:center;gap:5px;font-size:11px;color:#6366F1;text-decoration:none;font-weight:600;"
                           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                            <i class="fas fa-download" style="font-size:9px;"></i> Download file
                        </a>
                    </div>
                </template>
                <template x-if="!qvTask?.submission_url">
                    <div style="background:#F9FAFB;border:1.5px dashed #E5E7EB;border-radius:12px;padding:24px;text-align:center;margin-bottom:8px;">
                        <i class="fas fa-file-circle-xmark" style="color:#D1D5DB;font-size:24px;display:block;margin-bottom:6px;"></i>
                        <p style="font-size:12px;color:#9CA3AF;margin:0;">No file submitted yet</p>
                    </div>
                </template>
            </div>

            {{-- ── Footer actions ── --}}
            <template x-if="qvTask">
            <div style="padding:14px 24px;border-top:1px solid #F0F2F8;display:flex;align-items:center;gap:8px;flex-wrap:wrap;background:#FAFBFF;flex-shrink:0;">
                <button @click="openApprovalModal({ id: qvTask.id, title: qvTask.title, assignee: qvTask.assignee, url: qvTask.approve_url, pending_customer_url: qvTask.pending_customer_url, customer_name: qvTask.customer_name, customer_email: null, customer_phone: qvTask.customer_phone, submission_url: qvTask.submission_url, submission_name: qvTask.submission_name }); closeQuickView()"
                        style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:linear-gradient(135deg,#10B981,#059669);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;box-shadow:0 2px 8px rgba(16,185,129,.28);transition:opacity .15s;white-space:nowrap;"
                        onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                    <i class="fas fa-circle-check" style="font-size:12px;"></i> Approve
                </button>
                <button @click="openRejectModal({ id: qvTask.id, title: qvTask.title, assignee: qvTask.assignee, url: qvTask.reject_url }); closeQuickView()"
                        style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:#FEF2F2;color:#DC2626;border:1.5px solid #FECACA;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s;white-space:nowrap;"
                        onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'">
                    <i class="fas fa-rotate-left" style="font-size:11px;"></i> Request Revision
                </button>
                <div style="flex:1;"></div>
                <template x-if="qvTask?.customer_phone">
                    <button type="button"
                            @click="quickWhatsApp(qvTask.customer_phone, qvTask.customer_name, qvTask.title, qvTask.submission_url)"
                            :disabled="qvWaSending"
                            :style="qvWaSending ? 'width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;background:#9CA3AF;border:1.5px solid #9CA3AF;border-radius:10px;cursor:not-allowed;' : 'width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;background:#DCFCE7;border:1.5px solid #BBF7D0;border-radius:10px;cursor:pointer;transition:all .15s;'"
                            onmouseover="if(this.style.cursor!=='not-allowed'){this.style.background='#25D366';this.style.borderColor='#25D366';}"
                            onmouseout="if(this.style.cursor!=='not-allowed'){this.style.background='#DCFCE7';this.style.borderColor='#BBF7D0';}"
                            title="Send preview via WhatsApp API">
                        <i class="fab fa-whatsapp" x-show="!qvWaSending" style="font-size:16px;color:#25D366;"></i>
                        <i class="fas fa-spinner fa-spin" x-show="qvWaSending" x-cloak style="font-size:14px;color:#fff;"></i>
                    </button>
                </template>
                <div x-show="qvWaResult" x-cloak
                     :style="qvWaResult?.ok ? 'font-size:11px;color:#16A34A;display:flex;align-items:center;gap:3px;' : 'font-size:11px;color:#DC2626;display:flex;align-items:center;gap:3px;'">
                    <i :class="qvWaResult?.ok ? 'fas fa-circle-check' : 'fas fa-circle-xmark'" style="font-size:10px;"></i>
                    <span x-text="qvWaResult?.message ?? ''"></span>
                </div>
                <a :href="qvTask?.task_url"
                   style="display:inline-flex;align-items:center;gap:6px;padding:9px 14px;background:#F3F4F6;color:#374151;border:1px solid #E5E7EB;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;transition:all .15s;white-space:nowrap;"
                   onmouseover="this.style.background='#EEF2FF';this.style.borderColor='#C7D2FE';this.style.color='#4F46E5'" onmouseout="this.style.background='#F3F4F6';this.style.borderColor='#E5E7EB';this.style.color='#374151'">
                    <i class="fa fa-arrow-up-right-from-square" style="font-size:11px;"></i> Full View
                </a>
            </div>
            </template>

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
            <form x-ref="approvalForm" :action="approvalTask ? approvalTask.url : '#'" method="POST" style="padding:20px 26px 24px;overflow-y:auto;">
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

                {{-- Notify Customer --}}
                @if(($appSettings['hide_approval_customer_notify'] ?? '0') !== '1')
                <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:14px;padding:16px;margin-bottom:20px;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                        <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#D1FAE5,#A7F3D0);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-paper-plane" style="color:#059669;font-size:11px;"></i>
                        </div>
                        <div>
                            <p style="font-size:13px;font-weight:700;color:#111827;margin:0;">
                                Send to Customer
                                <span style="font-size:10px;font-weight:400;color:#9CA3AF;margin-left:4px;">(optional)</span>
                            </p>
                            <p style="font-size:11px;color:#6B7280;margin:2px 0 0;">
                                Send the approved design by email or WhatsApp before social posting
                            </p>
                        </div>
                    </div>

                    {{-- Design file preview --}}
                    <template x-if="approvalTask?.submission_url">
                        <div style="display:flex;align-items:center;gap:10px;padding:9px 12px;background:#fff;border:1px solid #D1FAE5;border-radius:10px;margin-bottom:12px;">
                            <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#EEF2FF,#DDD6FE);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas fa-file-image" style="color:#6366F1;font-size:13px;"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <p style="margin:0;font-size:11px;font-weight:700;color:#374151;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="approvalTask.submission_name ?? 'Design file'"></p>
                                <p style="margin:2px 0 0;font-size:10px;color:#9CA3AF;">Latest submission</p>
                            </div>
                            <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                                <a :href="approvalTask.submission_url" target="_blank" rel="noopener"
                                   style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:#EEF2FF;color:#4F46E5;border-radius:7px;font-size:11px;font-weight:600;text-decoration:none;transition:background .15s;"
                                   onmouseover="this.style.background='#DDD6FE'" onmouseout="this.style.background='#EEF2FF'">
                                    <i class="fas fa-eye" style="font-size:10px;"></i> View
                                </a>
                                <a :href="approvalTask.submission_url" :download="approvalTask.submission_name ?? 'design'"
                                   style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:#D1FAE5;color:#065F46;border-radius:7px;font-size:11px;font-weight:600;text-decoration:none;transition:background .15s;"
                                   onmouseover="this.style.background='#A7F3D0'" onmouseout="this.style.background='#D1FAE5'">
                                    <i class="fas fa-download" style="font-size:10px;"></i> Download
                                </a>
                            </div>
                        </div>
                    </template>

                    {{-- Channel toggles --}}
                    <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
                        <button type="button"
                                @click="approvalNotifyEmail = !approvalNotifyEmail"
                                :style="approvalNotifyEmail
                                    ? 'padding:7px 14px;border-radius:9px;border:2px solid #059669;background:#D1FAE5;color:#065F46;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;transition:all .15s;'
                                    : 'padding:7px 14px;border-radius:9px;border:1.5px solid #BBF7D0;background:#fff;color:#6B7280;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px;transition:all .15s;'">
                            <i class="fas fa-envelope" :style="approvalNotifyEmail ? 'font-size:11px;color:#059669;' : 'font-size:11px;color:#9CA3AF;'"></i>
                            <span x-text="approvalNotifyEmail ? '✓ Email' : 'Email'"></span>
                        </button>
                        <button type="button"
                                @click="approvalNotifyWhatsapp = !approvalNotifyWhatsapp"
                                :style="approvalNotifyWhatsapp
                                    ? 'padding:7px 14px;border-radius:9px;border:2px solid #25D366;background:#DCFCE7;color:#065F46;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;transition:all .15s;'
                                    : 'padding:7px 14px;border-radius:9px;border:1.5px solid #BBF7D0;background:#fff;color:#6B7280;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px;transition:all .15s;'">
                            <i class="fab fa-whatsapp" :style="approvalNotifyWhatsapp ? 'font-size:12px;color:#25D366;' : 'font-size:12px;color:#9CA3AF;'"></i>
                            <span x-text="approvalNotifyWhatsapp ? '✓ WhatsApp' : 'WhatsApp'"></span>
                        </button>
                    </div>

                    {{-- Email section --}}
                    <div x-show="approvalNotifyEmail" x-transition style="margin-bottom:10px;">
                        <label style="display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:5px;">
                            <i class="fas fa-envelope" style="font-size:10px;color:#059669;margin-right:3px;"></i>
                            Customer Email
                        </label>
                        <template x-if="approvalTask?.customer_email">
                            <div style="display:flex;align-items:center;gap:6px;padding:7px 10px;background:#D1FAE5;border-radius:8px;margin-bottom:6px;">
                                <i class="fas fa-circle-check" style="font-size:11px;color:#059669;"></i>
                                <span style="font-size:12px;color:#065F46;font-weight:600;" x-text="approvalTask.customer_email"></span>
                            </div>
                        </template>
                        <template x-if="!approvalTask?.customer_email">
                            <input type="email" name="customer_email_override" x-model="approvalManualEmail"
                                   placeholder="Enter customer email address…"
                                   style="width:100%;padding:8px 12px;border:1.5px solid #BBF7D0;background:#fff;border-radius:9px;font-size:12px;color:#374151;outline:none;box-sizing:border-box;"
                                   onfocus="this.style.borderColor='#34D399'" onblur="this.style.borderColor='#BBF7D0'">
                        </template>
                        <template x-if="approvalTask?.submission_url">
                            <p style="margin:5px 0 0;font-size:11px;color:#059669;display:flex;align-items:center;gap:4px;">
                                <i class="fas fa-paperclip" style="font-size:10px;"></i>
                                Design file will be attached to the email
                            </p>
                        </template>
                    </div>

                    {{-- WhatsApp section --}}
                    <div x-show="approvalNotifyWhatsapp" x-transition style="margin-bottom:10px;">
                        <label style="display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:5px;">
                            <i class="fab fa-whatsapp" style="font-size:11px;color:#25D366;margin-right:3px;"></i>
                            WhatsApp Number
                        </label>
                        <template x-if="approvalTask?.customer_phone">
                            <div style="display:flex;align-items:center;gap:6px;padding:7px 10px;background:#DCFCE7;border-radius:8px;margin-bottom:8px;">
                                <i class="fas fa-circle-check" style="font-size:11px;color:#25D366;"></i>
                                <span style="font-size:12px;color:#065F46;font-weight:600;" x-text="approvalTask.customer_phone"></span>
                            </div>
                        </template>
                        <template x-if="!approvalTask?.customer_phone">
                            <input type="text" x-model="approvalManualPhone"
                                   placeholder="Enter WhatsApp number (e.g. +971501234567)…"
                                   style="width:100%;padding:8px 12px;border:1.5px solid #BBF7D0;background:#fff;border-radius:9px;font-size:12px;color:#374151;outline:none;box-sizing:border-box;margin-bottom:8px;"
                                   onfocus="this.style.borderColor='#25D366'" onblur="this.style.borderColor='#BBF7D0'">
                        </template>
                        {{-- Smart WhatsApp send: API if configured, wa.me link as fallback --}}
                        <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;">
                            @if($waApiEnabled)
                            <button type="button" @click="sendWhatsAppApi()"
                                    :disabled="waSendState === 'sending'"
                                    :style="waSendState === 'sent'
                                        ? 'display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#16A34A;color:#fff;border:none;border-radius:9px;font-size:12px;font-weight:600;cursor:default;box-shadow:0 2px 8px rgba(22,163,74,.25);'
                                        : waSendState === 'sending'
                                        ? 'display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#9CA3AF;color:#fff;border:none;border-radius:9px;font-size:12px;font-weight:600;cursor:not-allowed;'
                                        : 'display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#25D366;color:#fff;border:none;border-radius:9px;font-size:12px;font-weight:600;cursor:pointer;box-shadow:0 2px 8px rgba(37,211,102,.3);transition:opacity .15s;'"
                                    style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#25D366;color:#fff;border:none;border-radius:9px;font-size:12px;font-weight:600;cursor:pointer;box-shadow:0 2px 8px rgba(37,211,102,.3);">
                                <i class="fab fa-whatsapp" style="font-size:13px;" x-show="waSendState !== 'sending'"></i>
                                <i class="fas fa-spinner fa-spin" style="font-size:12px;" x-show="waSendState === 'sending'" x-cloak></i>
                                <span x-text="waSendState === 'sent' ? '✓ Sent!' : waSendState === 'sending' ? 'Sending…' : 'Send via WhatsApp API'">Send via WhatsApp API</span>
                            </button>
                            <a href="#" @click.prevent="openCustomerWhatsApp()"
                               style="font-size:11px;color:#6B7280;text-decoration:underline;cursor:pointer;">Open link ↗</a>
                            @else
                            <button type="button" @click="openCustomerWhatsApp()"
                                    style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#25D366;color:#fff;border:none;border-radius:9px;font-size:12px;font-weight:600;cursor:pointer;box-shadow:0 2px 8px rgba(37,211,102,.3);transition:opacity .15s;"
                                    onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                                <i class="fab fa-whatsapp" style="font-size:13px;"></i>
                                Open WhatsApp ↗
                            </button>
                            <a href="{{ route('admin.settings.index') }}#whatsapp"
                               style="font-size:10px;color:#9CA3AF;text-decoration:none;display:inline-flex;align-items:center;gap:3px;" target="_blank">
                                <i class="fas fa-gear" style="font-size:9px;"></i> Configure API
                            </a>
                            @endif
                        </div>

                        {{-- API send status message --}}
                        @if($waApiEnabled)
                        <div x-show="waSendMsg" x-cloak
                             :style="waSendState === 'error'
                                 ? 'margin-top:6px;font-size:11px;color:#DC2626;display:flex;align-items:center;gap:4px;'
                                 : 'margin-top:6px;font-size:11px;color:#16A34A;display:flex;align-items:center;gap:4px;'">
                            <i :class="waSendState === 'error' ? 'fas fa-circle-exclamation' : 'fas fa-circle-check'" style="font-size:10px;"></i>
                            <span x-text="waSendMsg"></span>
                        </div>
                        @endif

                        <template x-if="approvalTask?.submission_url">
                            <p style="margin:6px 0 0;font-size:11px;color:#059669;display:flex;align-items:center;gap:4px;">
                                <i class="fas fa-paperclip" style="font-size:10px;"></i>
                                Design link will be included in the message
                            </p>
                        </template>
                    </div>

                    {{-- Shared message --}}
                    <div x-show="approvalNotifyEmail || approvalNotifyWhatsapp" x-transition>
                        <label style="display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:5px;">
                            <i class="fas fa-comment-dots" style="font-size:10px;color:#059669;margin-right:3px;"></i>
                            Message to Customer <span style="font-weight:400;color:#9CA3AF;">(optional)</span>
                        </label>
                        <textarea x-model="approvalCustomerMsg" name="customer_message" rows="2"
                                  placeholder="Your design is ready for review. Please check and let us know your feedback…"
                                  style="width:100%;padding:9px 12px;border:1.5px solid #BBF7D0;background:#fff;border-radius:9px;font-size:12px;color:#374151;outline:none;resize:vertical;box-sizing:border-box;line-height:1.55;"
                                  onfocus="this.style.borderColor='#34D399';this.style.boxShadow='0 0 0 2px rgba(52,211,153,.12)'"
                                  onblur="this.style.borderColor='#BBF7D0';this.style.boxShadow='none'"></textarea>
                    </div>

                    <input type="hidden" name="notify_customer_email" :value="approvalNotifyEmail ? '1' : '0'">
                    <input type="hidden" name="notify_customer_whatsapp" :value="approvalNotifyWhatsapp ? '1' : '0'">
                    <input type="hidden" name="customer_phone_override" :value="approvalManualPhone">
                </div>
                @endif

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

                    {{-- User assignment + description (shown only when "Yes") --}}
                    <div x-show="approvalSocial === 'yes'" x-transition style="margin-top:4px;display:flex;flex-direction:column;gap:10px;">
                        <div>
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
                        {{-- Platform selector --}}
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:8px;">
                                <i class="fas fa-share-nodes" style="font-size:10px;margin-right:4px;color:#6366F1;"></i>
                                Platforms <span style="font-weight:400;color:#9CA3AF;">(select all that apply)</span>
                            </label>
                            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;">
                                @php
                                $approvalPlatforms = [
                                    'facebook'  => ['Facebook',   'fa-facebook',   '#1877F2','#EBF3FF','#C3DCF7'],
                                    'instagram' => ['Instagram',  'fa-instagram',  '#E1306C','#FFF0F5','#F9C0D4'],
                                    'twitter'   => ['Twitter / X','fa-x-twitter',  '#000000','#F5F5F5','#D1D5DB'],
                                    'linkedin'  => ['LinkedIn',   'fa-linkedin',   '#0A66C2','#EAF2FB','#B3D4EF'],
                                    'tiktok'    => ['TikTok',     'fa-tiktok',     '#010101','#F5F5F5','#D1D5DB'],
                                    'youtube'   => ['YouTube',    'fa-youtube',    '#FF0000','#FFF0F0','#FFBBBB'],
                                    'snapchat'  => ['Snapchat',   'fa-snapchat',   '#F7CA00','#FFFDE7','#FDE68A'],
                                    'other'     => ['Other',      'fa-share-nodes','#6366F1','#EEF2FF','#C7D2FE'],
                                ];
                                @endphp
                                @foreach($approvalPlatforms as $pKey => [$pLabel, $pIcon, $pColor, $pBg, $pBorder])
                                <button type="button"
                                        @click="approvalSocialPlatforms.includes('{{ $pKey }}') ? approvalSocialPlatforms.splice(approvalSocialPlatforms.indexOf('{{ $pKey }}'), 1) : approvalSocialPlatforms.push('{{ $pKey }}')"
                                        :style="approvalSocialPlatforms.includes('{{ $pKey }}')
                                            ? 'display:flex;flex-direction:column;align-items:center;gap:4px;padding:8px 4px;border-radius:10px;border:2px solid {{ $pColor }};background:{{ $pBg }};cursor:pointer;transition:all .15s;'
                                            : 'display:flex;flex-direction:column;align-items:center;gap:4px;padding:8px 4px;border-radius:10px;border:1.5px solid #E5E7EB;background:#fff;cursor:pointer;transition:all .15s;'">
                                    <i class="fab {{ $pIcon }}"
                                       :style="approvalSocialPlatforms.includes('{{ $pKey }}') ? 'font-size:18px;color:{{ $pColor }};' : 'font-size:18px;color:#D1D5DB;'"></i>
                                    <span :style="approvalSocialPlatforms.includes('{{ $pKey }}') ? 'font-size:9px;font-weight:700;color:{{ $pColor }};' : 'font-size:9px;font-weight:600;color:#9CA3AF;'"
                                          style="line-height:1.2;text-align:center;">{{ $pLabel }}</span>
                                </button>
                                @endforeach
                            </div>
                            {{-- Hidden inputs for selected platforms --}}
                            <template x-for="p in approvalSocialPlatforms" :key="p">
                                <input type="hidden" name="social_platforms[]" :value="p">
                            </template>
                        </div>

                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                                <i class="fas fa-align-left" style="font-size:10px;margin-right:4px;color:#6366F1;"></i>
                                Posting Instructions <span style="font-weight:400;color:#9CA3AF;">(optional)</span>
                            </label>
                            <textarea name="social_description" rows="2"
                                      placeholder="General notes, tone, hashtags…"
                                      style="width:100%;padding:10px 12px;border:1.5px solid #C7D2FE;background:#fff;border-radius:10px;font-size:13px;color:#374151;outline:none;resize:vertical;box-sizing:border-box;line-height:1.55;"
                                      onfocus="this.style.borderColor='#6366F1';this.style.boxShadow='0 0 0 3px rgba(99,102,241,.12)'"
                                      onblur="this.style.borderColor='#C7D2FE';this.style.boxShadow='none'"></textarea>
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                                <i class="fas fa-pen-nib" style="font-size:10px;margin-right:4px;color:#8B5CF6;"></i>
                                Ad Caption <span style="font-weight:400;color:#9CA3AF;">(optional)</span>
                            </label>
                            <textarea name="social_caption" rows="3"
                                      placeholder="Exact post copy / advertisement caption to publish…"
                                      style="width:100%;padding:10px 12px;border:1.5px solid #DDD6FE;background:#fff;border-radius:10px;font-size:13px;color:#374151;outline:none;resize:vertical;box-sizing:border-box;line-height:1.55;"
                                      onfocus="this.style.borderColor='#8B5CF6';this.style.boxShadow='0 0 0 3px rgba(139,92,246,.12)'"
                                      onblur="this.style.borderColor='#DDD6FE';this.style.boxShadow='none'"></textarea>
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                                <i class="fas fa-wallet" style="font-size:10px;margin-right:4px;color:#D97706;"></i>
                                Ad Budget <span style="font-weight:400;color:#9CA3AF;">(optional)</span>
                            </label>
                            <input type="text" name="social_budget"
                                   placeholder="e.g. $200, 500 AED, unlimited…"
                                   style="width:100%;padding:10px 12px;border:1.5px solid #FDE68A;background:#fff;border-radius:10px;font-size:13px;color:#374151;outline:none;box-sizing:border-box;"
                                   onfocus="this.style.borderColor='#F59E0B';this.style.boxShadow='0 0 0 3px rgba(245,158,11,.12)'"
                                   onblur="this.style.borderColor='#FDE68A';this.style.boxShadow='none'">
                        </div>
                    </div>

                    {{-- Hidden field: social_required value sent based on selection --}}
                    <input type="hidden" name="social_required"
                           :value="approvalSocial === 'yes' ? '1' : (approvalSocial === 'no' ? '0' : '')">
                </div>

                {{-- Footer buttons --}}
                <div style="display:flex;flex-direction:column;gap:8px;margin-top:20px;">
                    {{-- Awaiting Customer Approval button --}}
                    <button type="button"
                            @click="$refs.approvalForm.action = approvalTask.pending_customer_url; $refs.approvalForm.requestSubmit()"
                            style="width:100%;padding:10px;background:linear-gradient(135deg,#FEF3C7,#FDE68A);color:#92400E;border:1.5px solid #FCD34D;border-radius:11px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;transition:opacity .15s;"
                            onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                        <i class="fas fa-hourglass-half" style="font-size:12px;"></i>
                        Awaiting Customer Approval
                    </button>
                    <div style="display:flex;gap:10px;">
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

{{-- ═══════════ PUBLISHED POST DELETE CONFIRMATION MODAL ═══════════ --}}
<template x-if="pubDeleteModal">
    <div x-show="pubDeleteModal" x-cloak
         style="position:fixed;inset:0;z-index:9000;background:rgba(17,24,39,.45);backdrop-filter:blur(3px);">
        <div @click.self="pubDeleteModal=false" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:20px;">
            <div style="background:#fff;border-radius:20px;width:100%;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,.18);overflow:hidden;" @click.stop>
                {{-- Header --}}
                <div style="display:flex;align-items:center;gap:12px;padding:20px 22px 16px;border-bottom:1px solid #F3F4F6;">
                    <div style="width:38px;height:38px;border-radius:11px;background:#FEE2E2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-trash" style="color:#DC2626;font-size:15px;"></i>
                    </div>
                    <div>
                        <p style="font-size:15px;font-weight:800;color:#111827;margin:0;">Remove Post Record</p>
                        <p style="font-size:12px;color:#9CA3AF;margin:0;">This action cannot be undone</p>
                    </div>
                    <button @click="pubDeleteModal=false"
                            style="margin-left:auto;width:30px;height:30px;border-radius:8px;background:#F3F4F6;border:none;cursor:pointer;color:#6B7280;font-size:13px;display:flex;align-items:center;justify-content:center;"
                            onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
                {{-- Body --}}
                <div style="padding:18px 22px;">
                    <p style="font-size:13px;color:#374151;margin:0 0 6px;">Are you sure you want to remove this post record?</p>
                    <p style="font-size:12px;color:#6B7280;margin:0 0 20px;" x-text="pubDeleteLabel"></p>
                    <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:10px 13px;display:flex;align-items:flex-start;gap:8px;margin-bottom:20px;">
                        <i class="fas fa-triangle-exclamation" style="color:#EF4444;font-size:12px;margin-top:1px;flex-shrink:0;"></i>
                        <p style="font-size:12px;color:#B91C1C;margin:0;line-height:1.5;">The post record will be permanently deleted. The task itself will not be affected.</p>
                    </div>
                    <div style="display:flex;gap:10px;">
                        <button type="button" @click="pubDeleteModal=false"
                                style="flex:1;padding:10px;background:#F3F4F6;color:#374151;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;transition:background .15s;"
                                onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                            Cancel
                        </button>
                        <button type="button" @click="confirmPubDelete()"
                                style="flex:2;padding:10px;background:linear-gradient(135deg,#EF4444,#DC2626);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;box-shadow:0 4px 14px rgba(239,68,68,.3);">
                            <i class="fas fa-trash"></i> Yes, Remove It
                        </button>
                    </div>
                </div>
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
    <a href="{{ route('admin.approvals.index') }}?tab=awaiting"
       style="display:flex;align-items:center;gap:7px;padding:9px 20px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;transition:all .18s;
              {{ $tab === 'awaiting' ? 'background:#fff;color:#D97706;box-shadow:0 2px 8px rgba(217,119,6,.12);' : 'color:#6B7280;' }}">
        <i class="fas fa-hourglass-half" style="font-size:11px;"></i> Awaiting Customer
        @if($awaitingTasks->total() > 0)
        <span style="background:{{ $tab === 'awaiting' ? 'linear-gradient(135deg,#FEF3C7,#FDE68A)' : '#F3F4F6' }};color:{{ $tab === 'awaiting' ? '#92400E' : '#6B7280' }};font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;">{{ $awaitingTasks->total() }}</span>
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
                <span class="text-xs text-gray-400">Due {{ $task->deadline->format(config('app.date_format', 'M d, Y')) }}</span>
                @endif
            </div>
            <div class="flex items-center gap-1" onclick="event.stopPropagation()">
                <button @click.stop="openApprovalModal({
                            id:                   {{ $task->id }},
                            title:                @js($task->title),
                            assignee:             @js($task->assignee->name ?? 'Unknown'),
                            url:                  '{{ route('admin.tasks.approve', $task) }}',
                            pending_customer_url: '{{ route('admin.tasks.pending-customer', $task) }}',
                            customer_name:        @js($task->customer?->name ?? $task->project?->customer?->name ?? null),
                            customer_email:       @js($task->customer?->email ?? $task->project?->customer?->email ?? null),
                            customer_phone:       @js($task->customer?->phone ?? $task->project?->customer?->phone ?? null),
                            submission_url:       @js($latestSub?->file_path ? url(Storage::url($latestSub->file_path)) : null),
                            submission_name:      @js($latestSub?->original_filename ?? ($latestSub?->file_path ? basename($latestSub->file_path) : null)),
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
                @if(($appSettings['hide_approval_customer_notify'] ?? '0') !== '1')
                @php
                    $cwPhone = $task->customer?->phone ?? $task->project?->customer?->phone ?? '';
                    $cwName  = $task->customer?->name  ?? $task->project?->customer?->name  ?? 'Customer';
                    $cwFile  = $latestSub?->file_path ? url(Storage::url($latestSub->file_path)) : '';
                    $cwMsg   = "Hello {$cwName}, your design for \"{$task->title}\" has been submitted for review. We'd love your feedback before we finalize approval.";
                    if ($cwFile) $cwMsg .= "\n\nView design: {$cwFile}";
                @endphp
                <div data-wa-phone="{{ $cwPhone }}" data-wa-msg="{{ $cwMsg }}" data-wa-file="{{ $cwFile }}"
                     x-data="{
                         waOpen: false, waPhone: '', waMsg: '', waFile: '',
                         waTop: 0, waRight: 0, waSending: false, waResult: null,
                         init() {
                             this.waPhone = this.$el.dataset.waPhone;
                             this.waMsg   = this.$el.dataset.waMsg;
                             this.waFile  = this.$el.dataset.waFile;
                         },
                         toggleWa(btn) {
                             if (!this.waOpen) {
                                 const r = btn.getBoundingClientRect();
                                 this.waTop   = r.bottom + 7;
                                 this.waRight = window.innerWidth - r.right;
                             }
                             this.waOpen = !this.waOpen;
                         },
                         async doSend() {
                             const d = this.waPhone.replace(/\D/g,'');
                             if (!d) { this.$refs.cwInput.style.borderColor='#EF4444'; this.$refs.cwInput.focus(); return; }
                             this.waSending = true; this.waResult = null;
                             const hasFile = !!this.waFile;
                             const isImage = hasFile && /\.(jpg|jpeg|png|gif|webp)(\?|$)/i.test(this.waFile);
                             const baseMsg = this.waMsg.split('\n\nView design:')[0];
                             const url = hasFile ? '{{ $waMediaRoute }}' : '{{ $waSendRoute }}';
                             const body = hasFile
                                 ? { phone: this.waPhone, file_url: this.waFile, filename: this.waFile.split('/').pop(), caption: baseMsg }
                                 : { phone: this.waPhone, message: this.waMsg };
                             try {
                                 const res  = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ $waCsrf }}','Accept':'application/json'}, body: JSON.stringify(body) });
                                 const data = await res.json();
                                 this.waResult = { ok: data.ok, message: typeof data.message === 'string' ? data.message : JSON.stringify(data.message) };
                                 if (data.ok) setTimeout(() => { this.waOpen = false; this.waResult = null; }, 1800);
                             } catch(e) {
                                 this.waResult = { ok: false, message: 'Network error. Try again.' };
                             }
                             this.waSending = false;
                         }
                     }" @click.stop>
                    <button type="button" @click.stop="toggleWa($event.currentTarget)"
                            class="w-6 h-6 rounded-lg flex items-center justify-center"
                            :style="waOpen ? 'background:#25D366;' : 'background:#DCFCE7;'"
                            title="Send preview via WhatsApp">
                        <i class="fab fa-whatsapp" :style="waOpen ? 'font-size:12px;color:#fff;' : 'font-size:12px;color:#25D366;'"></i>
                    </button>
                    <div x-show="waOpen" x-cloak x-transition @click.outside="waOpen=false" @click.stop
                         :style="`position:fixed;top:${waTop}px;right:${waRight}px;`"
                         style="z-index:9999;background:#fff;border-radius:14px;width:244px;box-shadow:0 16px 40px rgba(0,0,0,.18);border:1px solid #D1FAE5;overflow:hidden;">
                        <div style="background:linear-gradient(135deg,#25D366,#128C7E);padding:11px 14px;display:flex;align-items:center;gap:9px;">
                            <i class="fab fa-whatsapp" style="color:#fff;font-size:18px;flex-shrink:0;"></i>
                            <div>
                                <p style="font-size:12px;font-weight:700;color:#fff;margin:0;">Send Preview</p>
                                <p style="font-size:10px;color:rgba(255,255,255,.75);margin:0;">Before approval · sends via API</p>
                            </div>
                        </div>
                        <div style="padding:13px 14px;">
                            <p style="font-size:11px;color:#6B7280;margin:0 0 10px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <i class="fas fa-file-image" style="font-size:9px;color:#A78BFA;margin-right:3px;"></i>{{ Str::limit($task->title, 32) }}
                            </p>
                            <label style="display:block;font-size:10px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px;">WhatsApp Number</label>
                            <input type="tel" x-model="waPhone" x-ref="cwInput" placeholder="+971501234567"
                                   style="width:100%;padding:8px 10px;border:1.5px solid #D1FAE5;background:#F0FDF4;border-radius:8px;font-size:12px;color:#111827;outline:none;box-sizing:border-box;margin-bottom:9px;transition:border-color .15s;"
                                   onfocus="this.style.borderColor='#25D366'" onblur="this.style.borderColor='#D1FAE5'">
                            <button type="button" @click="doSend()" :disabled="waSending"
                                    :style="waSending ? 'width:100%;display:flex;align-items:center;justify-content:center;gap:6px;padding:9px;background:#9CA3AF;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:not-allowed;' : 'width:100%;display:flex;align-items:center;justify-content:center;gap:6px;padding:9px;background:linear-gradient(135deg,#25D366,#128C7E);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;box-shadow:0 3px 10px rgba(37,211,102,.3);transition:opacity .15s;'"
                                    onmouseover="if(this.style.cursor!=='not-allowed')this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                                <i class="fab fa-whatsapp" style="font-size:13px;" x-show="!waSending"></i>
                                <i class="fas fa-spinner fa-spin" style="font-size:12px;" x-show="waSending" x-cloak></i>
                                <span x-text="waSending ? 'Sending…' : (waFile ? 'Send File via WhatsApp' : 'Send via WhatsApp')">Send via WhatsApp</span>
                            </button>
                            <div x-show="waResult" x-cloak
                                 :style="waResult?.ok ? 'margin-top:8px;font-size:11px;color:#16A34A;display:flex;align-items:center;gap:4px;' : 'margin-top:8px;font-size:11px;color:#DC2626;display:flex;align-items:center;gap:4px;'">
                                <i :class="waResult?.ok ? 'fas fa-circle-check' : 'fas fa-circle-xmark'" style="font-size:11px;"></i>
                                <span x-text="waResult?.message ?? ''"></span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif {{-- hide_approval_customer_notify --}}
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
        @php
            $qvData = [
                'id'            => $task->id,
                'title'         => $task->title,
                'assignee'      => $task->assignee->name ?? 'Unknown',
                'initial'       => strtoupper(substr($task->assignee->name ?? 'U', 0, 1)),
                'project'       => $task->project->name ?? '—',
                'priority'      => ucfirst($task->priority ?? 'normal'),
                'priority_bg'   => $pbg2,
                'priority_color'=> $pco2,
                'deadline'      => $task->deadline?->format(config('app.date_format', 'M d, Y')),
                'is_overdue'    => $isOverdue2,
                'versions'      => $task->submissions->count(),
                'submission_url'  => $latestSub2?->file_path ? url(Storage::url($latestSub2->file_path)) : null,
                'submission_name' => $latestSub2?->original_filename ?? ($latestSub2?->file_path ? basename($latestSub2->file_path) : null),
                'approve_url'          => route('admin.tasks.approve', $task),
                'pending_customer_url' => route('admin.tasks.pending-customer', $task),
                'reject_url'           => route('admin.tasks.reject', $task),
                'task_url'      => route('admin.tasks.show', $task),
                'customer_name' => $task->customer?->name ?? $task->project?->customer?->name ?? 'Customer',
                'customer_phone'=> $task->customer?->phone ?? $task->project?->customer?->phone ?? null,
            ];
        @endphp
        <tr id="pend-row-{{ $task->id }}"
            @click="openQuickView(@js($qvData))"
            style="cursor:pointer;">
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
                    {{ $isOverdue2 ? '⚠ ' : '' }}{{ $task->deadline->format(config('app.date_format', 'M d, Y')) }}
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
            <td style="text-align:right;white-space:nowrap;" onclick="event.stopPropagation()">
                <div style="display:inline-flex;align-items:center;gap:5px;">
                    {{-- Approve --}}
                    <button type="button"
                            @click="openApprovalModal({
                                id:                   {{ $task->id }},
                                title:                @js($task->title),
                                assignee:             @js($task->assignee->name ?? 'Unknown'),
                                url:                  '{{ route('admin.tasks.approve', $task) }}',
                                pending_customer_url: '{{ route('admin.tasks.pending-customer', $task) }}',
                                customer_name:        @js($task->customer?->name ?? $task->project?->customer?->name ?? null),
                                customer_email:       @js($task->customer?->email ?? $task->project?->customer?->email ?? null),
                                customer_phone:       @js($task->customer?->phone ?? $task->project?->customer?->phone ?? null),
                                submission_url:       @js($latestSub2?->file_path ? url(Storage::url($latestSub2->file_path)) : null),
                                submission_name:      @js($latestSub2?->original_filename ?? ($latestSub2?->file_path ? basename($latestSub2->file_path) : null)),
                            })"
                            style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;background:linear-gradient(135deg,#10B981,#059669);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;box-shadow:0 2px 6px rgba(16,185,129,.25);transition:opacity .15s;white-space:nowrap;"
                            onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'"
                            title="Approve submission">
                        <i class="fas fa-circle-check" style="font-size:11px;"></i> Approve
                    </button>

                    {{-- Separator --}}
                    <div style="width:1px;height:20px;background:#E5E7EB;margin:0 2px;"></div>

                    @if(($appSettings['hide_approval_customer_notify'] ?? '0') !== '1')
                    @php
                        $lwPhone = $task->customer?->phone ?? $task->project?->customer?->phone ?? '';
                        $lwName  = $task->customer?->name  ?? $task->project?->customer?->name  ?? 'Customer';
                        $lwFile  = $latestSub2?->file_path ? url(Storage::url($latestSub2->file_path)) : '';
                        $lwMsg   = "Hello {$lwName}, your design for \"{$task->title}\" has been submitted for review. We'd love your feedback before we finalize approval.";
                        if ($lwFile) $lwMsg .= "\n\nView design: {$lwFile}";
                    @endphp
                    {{-- Quick WhatsApp icon + dropdown --}}
                    <div data-wa-phone="{{ $lwPhone }}" data-wa-msg="{{ $lwMsg }}" data-wa-file="{{ $lwFile }}"
                         x-data="{
                             waOpen: false, waPhone: '', waMsg: '', waFile: '',
                             waTop: 0, waRight: 0, waSending: false, waResult: null,
                             init() {
                                 this.waPhone = this.$el.dataset.waPhone;
                                 this.waMsg   = this.$el.dataset.waMsg;
                                 this.waFile  = this.$el.dataset.waFile;
                             },
                             toggleWa(btn) {
                                 if (!this.waOpen) {
                                     const r = btn.getBoundingClientRect();
                                     this.waTop   = r.bottom + 7;
                                     this.waRight = window.innerWidth - r.right;
                                 }
                                 this.waOpen = !this.waOpen;
                             },
                             async doSend() {
                                 const d = this.waPhone.replace(/\D/g,'');
                                 if (!d) { this.$refs.lwInput.style.borderColor='#EF4444'; this.$refs.lwInput.focus(); return; }
                                 this.waSending = true; this.waResult = null;
                                 const hasFile = !!this.waFile;
                                 const baseMsg = this.waMsg.split('\n\nView design:')[0];
                                 const url = hasFile ? '{{ $waMediaRoute }}' : '{{ $waSendRoute }}';
                                 const body = hasFile
                                     ? { phone: this.waPhone, file_url: this.waFile, filename: this.waFile.split('/').pop(), caption: baseMsg }
                                     : { phone: this.waPhone, message: this.waMsg };
                                 try {
                                     const res  = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ $waCsrf }}','Accept':'application/json'}, body: JSON.stringify(body) });
                                     const data = await res.json();
                                     this.waResult = { ok: data.ok, message: typeof data.message === 'string' ? data.message : JSON.stringify(data.message) };
                                     if (data.ok) setTimeout(() => { this.waOpen = false; this.waResult = null; }, 1800);
                                 } catch(e) {
                                     this.waResult = { ok: false, message: 'Network error. Try again.' };
                                 }
                                 this.waSending = false;
                             }
                         }">
                        <button type="button" @click.stop="toggleWa($event.currentTarget)"
                                :style="waOpen
                                    ? 'width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;background:#25D366;border:none;border-radius:8px;cursor:pointer;transition:all .15s;'
                                    : 'width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;background:#F0FDF4;border:1.5px solid #BBF7D0;border-radius:8px;cursor:pointer;transition:all .15s;'"
                                title="Send preview via WhatsApp">
                            <i class="fab fa-whatsapp" :style="waOpen ? 'font-size:15px;color:#fff;' : 'font-size:15px;color:#25D366;'"></i>
                        </button>
                        <div x-show="waOpen" x-cloak x-transition @click.outside="waOpen=false"
                             :style="`position:fixed;top:${waTop}px;right:${waRight}px;`"
                             style="z-index:9999;background:#fff;border-radius:16px;width:256px;box-shadow:0 20px 50px rgba(0,0,0,.18);border:1px solid #D1FAE5;overflow:hidden;">
                            <div style="background:linear-gradient(135deg,#25D366,#128C7E);padding:12px 16px;display:flex;align-items:center;gap:10px;">
                                <i class="fab fa-whatsapp" style="color:#fff;font-size:20px;flex-shrink:0;"></i>
                                <div>
                                    <p style="font-size:13px;font-weight:700;color:#fff;margin:0;">Send Preview</p>
                                    <p style="font-size:10px;color:rgba(255,255,255,.75);margin:2px 0 0;">Before approval · sends via API</p>
                                </div>
                            </div>
                            <div style="padding:14px 16px;">
                                <p style="font-size:11px;color:#6B7280;margin:0 0 12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <i class="fas fa-file-image" style="font-size:9px;color:#A78BFA;margin-right:3px;"></i>{{ Str::limit($task->title, 34) }}
                                </p>
                                <label style="display:block;font-size:10px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px;">WhatsApp Number</label>
                                <input type="tel" x-model="waPhone" x-ref="lwInput" placeholder="+971501234567"
                                       style="width:100%;padding:9px 11px;border:1.5px solid #D1FAE5;background:#F0FDF4;border-radius:9px;font-size:12px;color:#111827;outline:none;box-sizing:border-box;margin-bottom:10px;transition:border-color .15s;"
                                       onfocus="this.style.borderColor='#25D366'" onblur="this.style.borderColor='#D1FAE5'">
                                <button type="button" @click="doSend()" :disabled="waSending"
                                        :style="waSending ? 'width:100%;display:flex;align-items:center;justify-content:center;gap:7px;padding:10px;background:#9CA3AF;color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:not-allowed;' : 'width:100%;display:flex;align-items:center;justify-content:center;gap:7px;padding:10px;background:linear-gradient(135deg,#25D366,#128C7E);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(37,211,102,.35);transition:opacity .15s;'"
                                        onmouseover="if(this.style.cursor!=='not-allowed')this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                                    <i class="fab fa-whatsapp" style="font-size:14px;" x-show="!waSending"></i>
                                    <i class="fas fa-spinner fa-spin" style="font-size:13px;" x-show="waSending" x-cloak></i>
                                    <span x-text="waSending ? 'Sending…' : (waFile ? 'Send File via WhatsApp' : 'Send via WhatsApp')">Send via WhatsApp</span>
                                </button>
                                <div x-show="waResult" x-cloak
                                     :style="waResult?.ok ? 'margin-top:8px;font-size:11px;color:#16A34A;display:flex;align-items:center;gap:4px;' : 'margin-top:8px;font-size:11px;color:#DC2626;display:flex;align-items:center;gap:4px;'">
                                    <i :class="waResult?.ok ? 'fas fa-circle-check' : 'fas fa-circle-xmark'" style="font-size:11px;"></i>
                                    <span x-text="waResult?.message ?? ''"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif {{-- hide_approval_customer_notify --}}

                    {{-- Request Revision icon button --}}
                    <button type="button"
                            onclick="togglePendReject({{ $task->id }})"
                            id="pend-rej-btn-{{ $task->id }}"
                            style="width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;background:#FEF2F2;border:1.5px solid #FECACA;border-radius:8px;cursor:pointer;transition:all .15s;"
                            onmouseover="this.style.background='#FEE2E2';this.style.borderColor='#FCA5A5';" onmouseout="this.style.background='#FEF2F2';this.style.borderColor='#FECACA';"
                            title="Request Revision">
                        <i class="fas fa-rotate-left" style="font-size:12px;color:#DC2626;"></i>
                    </button>

                    {{-- View --}}
                    <a href="{{ route('admin.tasks.show', $task) }}"
                       style="width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;background:#F3F4F6;border:1px solid #E5E7EB;border-radius:8px;text-decoration:none;transition:all .15s;"
                       onmouseover="this.style.background='#EEF2FF';this.style.borderColor='#C7D2FE';" onmouseout="this.style.background='#F3F4F6';this.style.borderColor='#E5E7EB';"
                       title="View task">
                        <i class="fa fa-arrow-up-right-from-square" style="font-size:11px;color:#6B7280;"></i>
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

{{-- ══════════════════════ AWAITING CUSTOMER TAB ══════════════════════ --}}
@if($tab === 'awaiting')
<div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:14px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
    <i class="fas fa-hourglass-half" style="color:#D97706;font-size:15px;flex-shrink:0;"></i>
    <p style="font-size:13px;color:#92400E;margin:0;">These tasks are waiting for <strong>customer approval</strong> before being finalized. You can approve or request a revision once the customer responds.</p>
</div>

@if($awaitingTasks->isEmpty())
<div style="text-align:center;padding:60px 20px;">
    <div style="width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#FEF3C7,#FDE68A);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
        <i class="fas fa-hourglass-half" style="color:#D97706;font-size:22px;"></i>
    </div>
    <p style="font-size:15px;font-weight:700;color:#111827;margin:0 0 6px;">No tasks awaiting customer approval</p>
    <p style="font-size:13px;color:#9CA3AF;margin:0;">Tasks marked as "Awaiting Customer Approval" will appear here.</p>
</div>
@else
<div style="overflow-x:auto;border-radius:14px;border:1px solid #F3F4F6;">
<table class="pend-table" style="background:#fff;">
    <thead>
        <tr>
            <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;background:#FAFBFF;border-radius:14px 0 0 0;">Task</th>
            <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;background:#FAFBFF;">Assignee</th>
            <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;background:#FAFBFF;">Project</th>
            <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;background:#FAFBFF;">Priority</th>
            <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;background:#FAFBFF;">Deadline</th>
            <th style="padding:12px 16px;text-align:right;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;background:#FAFBFF;border-radius:0 14px 0 0;">Actions</th>
        </tr>
    </thead>
    <tbody>
    @foreach($awaitingTasks as $task)
    @php
        $latestSubAw  = $task->submissions->first();
        $isOverdueAw  = $task->deadline && $task->deadline->isPast();
        $pbgAw = match($task->priority) { 'high' => '#FEE2E2', 'medium' => '#FEF3C7', default => '#D1FAE5' };
        $pcoAw = match($task->priority) { 'high' => '#DC2626', 'medium' => '#D97706', default => '#059669' };
    @endphp
    <tr>
        <td style="max-width:220px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,#FCD34D,#F59E0B);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff;flex-shrink:0;">
                    {{ strtoupper(substr($task->assignee->name ?? 'U', 0, 1)) }}
                </div>
                <a href="{{ route('admin.tasks.show', $task) }}" style="font-size:13px;font-weight:600;color:#111827;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:160px;display:block;" title="{{ $task->title }}">{{ $task->title }}</a>
            </div>
        </td>
        <td><span style="font-size:12px;font-weight:600;color:#D97706;">{{ $task->assignee->name ?? '—' }}</span></td>
        <td><span style="font-size:12px;color:#6B7280;"><i class="fas fa-folder" style="font-size:10px;color:#FCD34D;margin-right:4px;"></i>{{ $task->project->name ?? '—' }}</span></td>
        <td><span style="font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;background:{{ $pbgAw }};color:{{ $pcoAw }};white-space:nowrap;">{{ ucfirst($task->priority) }}</span></td>
        <td>
            @if($task->deadline)
            <span style="font-size:12px;{{ $isOverdueAw ? 'color:#DC2626;font-weight:600;' : 'color:#6B7280;' }}white-space:nowrap;">
                {{ $isOverdueAw ? '⚠ ' : '' }}{{ $task->deadline->format(config('app.date_format', 'M d, Y')) }}
            </span>
            @else
            <span style="font-size:12px;color:#D1D5DB;">—</span>
            @endif
        </td>
        <td style="text-align:right;white-space:nowrap;">
            <div style="display:inline-flex;align-items:center;gap:5px;">
                {{-- Approve --}}
                <button type="button"
                        @click="openApprovalModal({
                            id:                   {{ $task->id }},
                            title:                @js($task->title),
                            assignee:             @js($task->assignee->name ?? 'Unknown'),
                            url:                  '{{ route('admin.tasks.approve', $task) }}',
                            pending_customer_url: '{{ route('admin.tasks.pending-customer', $task) }}',
                            customer_name:        @js($task->customer?->name ?? $task->project?->customer?->name ?? null),
                            customer_email:       @js($task->customer?->email ?? $task->project?->customer?->email ?? null),
                            customer_phone:       @js($task->customer?->phone ?? $task->project?->customer?->phone ?? null),
                            submission_url:       @js($latestSubAw?->file_path ? url(Storage::url($latestSubAw->file_path)) : null),
                            submission_name:      @js($latestSubAw?->original_filename ?? ($latestSubAw?->file_path ? basename($latestSubAw->file_path) : null)),
                        })"
                        style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;background:linear-gradient(135deg,#10B981,#059669);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;box-shadow:0 2px 6px rgba(16,185,129,.25);transition:opacity .15s;white-space:nowrap;"
                        onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                    <i class="fas fa-circle-check" style="font-size:11px;"></i> Approve
                </button>
                <div style="width:1px;height:20px;background:#E5E7EB;margin:0 2px;"></div>
                {{-- Reject --}}
                <button type="button"
                        @click="openRejectModal({
                            id:       {{ $task->id }},
                            title:    @js($task->title),
                            assignee: @js($task->assignee->name ?? 'Unknown'),
                            url:      '{{ route('admin.tasks.reject', $task) }}'
                        })"
                        style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;background:#FEF2F2;color:#DC2626;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:opacity .15s;white-space:nowrap;"
                        onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    <i class="fas fa-rotate-left" style="font-size:11px;"></i> Revision
                </button>
            </div>
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>
@if($awaitingTasks->hasPages())
<div style="margin-top:20px;">{{ $awaitingTasks->appends(['tab' => 'awaiting'])->links() }}</div>
@endif
@endif
@endif {{-- end awaiting tab --}}

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
    <div class="hist-view-toggle">
        <button id="histBtnTable" onclick="setHistView('table')" class="hist-view-btn active" title="Table view">
            <i class="fas fa-table-list" style="font-size:11px;"></i> Table
        </button>
        <button id="histBtnCards" onclick="setHistView('cards')" class="hist-view-btn" title="Card view">
            <i class="fas fa-table-cells-large" style="font-size:11px;"></i> Cards
        </button>
    </div>
    <p style="font-size:12px;color:#9CA3AF;margin:0;">
        {{ $history->total() }} {{ Str::plural('result', $history->total()) }}
        @if($hHasFilters)<span style="color:#4F46E5;font-weight:600;"> (filtered)</span>@endif
    </p>
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
<div style="background:#fff;border-radius:18px;border:1px solid #EBEBEB;box-shadow:0 2px 10px rgba(99,102,241,.06);overflow:clip;">
<div class="tbl-scroll">
    <table class="hist-table" style="table-layout:auto;">
        <thead>
            <tr>
                <th style="width:32px;padding:11px 8px 11px 16px;"></th>
                <th><a href="{{ hSortUrl('task', $hSort, $hDir, $hParams) }}" style="color:inherit;text-decoration:none;display:flex;align-items:center;">Task {!! hSortIcon('task', $hSort, $hDir) !!}</a></th>
                <th><a href="{{ hSortUrl('assignee', $hSort, $hDir, $hParams) }}" style="color:inherit;text-decoration:none;display:flex;align-items:center;">Assignee {!! hSortIcon('assignee', $hSort, $hDir) !!}</a></th>
                <th><a href="{{ hSortUrl('decision', $hSort, $hDir, $hParams) }}" style="color:inherit;text-decoration:none;display:flex;align-items:center;">Decision {!! hSortIcon('decision', $hSort, $hDir) !!}</a></th>
                <th><a href="{{ hSortUrl('date', $hSort, $hDir, $hParams) }}" style="color:inherit;text-decoration:none;display:flex;align-items:center;">Date {!! hSortIcon('date', $hSort, $hDir) !!}</a></th>
                <th></th>
            </tr>
        </thead>
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
        <tbody x-data="{ expanded: false }">
            {{-- Summary row --}}
            <tr>
                {{-- Toggle --}}
                <td style="padding:12px 8px 12px 16px;width:32px;">
                    <button @click="expanded = !expanded"
                            style="width:24px;height:24px;border-radius:6px;background:#F3F4F6;border:1px solid #E5E7EB;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s;flex-shrink:0;"
                            :style="expanded ? 'background:#EEF2FF;border-color:#C7D2FE;' : ''"
                            :title="expanded ? 'Collapse' : 'Expand'">
                        <i class="fas fa-chevron-right" style="font-size:9px;color:#6B7280;transition:transform .2s;"
                           :style="expanded ? 'transform:rotate(90deg);color:#4F46E5;' : ''"></i>
                    </button>
                </td>
                {{-- Task --}}
                <td @click="expanded = !expanded" style="cursor:pointer;">
                    <p style="font-size:13px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:260px;"
                       title="{{ $sub->task->title ?? '' }}">{{ $sub->task->title ?? '—' }}</p>
                    <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">
                        <i class="fas fa-folder" style="font-size:9px;color:#C4B5FD;margin-right:3px;"></i>{{ $sub->task->project->name ?? '—' }}
                        <span style="margin:0 4px;color:#E5E7EB;">·</span>v{{ $sub->version }}
                    </p>
                </td>
                {{-- Assignee --}}
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;">
                            {{ strtoupper(substr($sub->task->assignee->name ?? 'U', 0, 1)) }}
                        </div>
                        <span style="font-size:12px;font-weight:500;color:#374151;white-space:nowrap;">{{ $sub->task->assignee->name ?? '—' }}</span>
                    </div>
                </td>
                {{-- Decision --}}
                <td>
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:4px 11px;border-radius:20px;background:{{ $decisionBg }};color:{{ $decisionCo }};white-space:nowrap;">
                        <i class="fa {{ $decisionIco }}" style="font-size:10px;"></i> {{ $decisionLbl }}
                    </span>
                </td>
                {{-- Date --}}
                <td>
                    <span style="font-size:12px;color:#6B7280;white-space:nowrap;">{{ $sub->reviewed_at?->format(config('app.date_format', 'M d, Y')) }}</span>
                    <p style="font-size:10px;color:#D1D5DB;margin:2px 0 0;white-space:nowrap;">{{ $sub->reviewed_at?->diffForHumans() }}</p>
                </td>
                {{-- Actions dropdown --}}
                <td style="white-space:nowrap;">
                    @if($sub->task_id)
                    <div x-data="{ menuOpen: false, dTop: 0, dRight: 0 }"
                         @click.outside="menuOpen=false"
                         @scroll.window="menuOpen=false"
                         @keydown.escape.window="menuOpen=false">
                        <button x-ref="actBtn"
                                @click.stop="
                                    if (!menuOpen) {
                                        const r = $refs.actBtn.getBoundingClientRect();
                                        dTop   = r.bottom + 5;
                                        dRight = window.innerWidth - r.right;
                                    }
                                    menuOpen = !menuOpen;
                                "
                                style="display:inline-flex;align-items:center;gap:6px;padding:5px 13px;background:#EEF2FF;color:#4F46E5;border:1.5px solid #C7D2FE;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;transition:all .15s;"
                                onmouseover="this.style.background='#E0E7FF';this.style.borderColor='#A5B4FC'" onmouseout="this.style.background='#EEF2FF';this.style.borderColor='#C7D2FE'">
                            Actions
                            <i class="fas fa-chevron-down" style="font-size:9px;transition:transform .15s;" :style="menuOpen ? 'transform:rotate(180deg)' : ''"></i>
                        </button>
                        <div x-show="menuOpen" x-cloak
                             :style="`position:fixed;top:${dTop}px;right:${dRight}px;background:#fff;border:1.5px solid #E5E7EB;border-radius:10px;box-shadow:0 8px 28px rgba(0,0,0,.13);min-width:170px;z-index:9999;overflow:hidden;`">
                            {{-- View Details --}}
                            <button @click="menuOpen=false; openTaskPanel({{ $sub->task_id }})"
                                    style="display:flex;align-items:center;gap:9px;width:100%;padding:10px 15px;background:none;border:none;border-bottom:1px solid #F3F4F6;font-size:12px;font-weight:600;color:#4F46E5;cursor:pointer;text-align:left;"
                                    onmouseover="this.style.background='#F5F3FF'" onmouseout="this.style.background=''">
                                <i class="fas fa-eye" style="font-size:11px;width:14px;text-align:center;color:#6366F1;"></i>
                                View Details
                            </button>
                            {{-- Open Task --}}
                            <a href="{{ route('admin.tasks.show', $sub->task_id) }}"
                               @click="menuOpen=false"
                               style="display:flex;align-items:center;gap:9px;padding:10px 15px;font-size:12px;font-weight:600;color:#374151;text-decoration:none;border-bottom:1px solid #F3F4F6;"
                               onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background=''">
                                <i class="fas fa-arrow-up-right-from-square" style="font-size:11px;width:14px;text-align:center;color:#6B7280;"></i>
                                Open Task
                            </a>
                            @if($isApproved)
                            {{-- Reopen Task --}}
                            <form method="POST" action="{{ route('admin.tasks.reopen', $sub->task_id) }}"
                                  onsubmit="return confirm('Reopen this task and set it back to In Progress?')">
                                @csrf
                                <button type="submit"
                                        style="display:flex;align-items:center;gap:9px;width:100%;padding:10px 15px;background:none;border:none;font-size:12px;font-weight:600;color:#D97706;cursor:pointer;text-align:left;"
                                        onmouseover="this.style.background='#FFFBEB'" onmouseout="this.style.background=''">
                                    <i class="fas fa-rotate-right" style="font-size:11px;width:14px;text-align:center;color:#F59E0B;"></i>
                                    Reopen Task
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                    @endif
                </td>
            </tr>

            {{-- Expanded details row --}}
            <tr x-show="expanded" x-collapse style="background:#F8FAFF;">
                <td></td>
                <td colspan="5" style="padding:0 16px 16px;">
                    <div style="display:flex;flex-wrap:wrap;gap:20px;padding-top:14px;border-top:1px solid #EEF2FF;">

                        {{-- Meta info --}}
                        <div style="display:flex;gap:16px;flex-wrap:wrap;flex:1;min-width:0;">
                            <div>
                                <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Project</p>
                                <span style="font-size:12px;color:#374151;display:flex;align-items:center;gap:4px;">
                                    <i class="fas fa-folder" style="font-size:10px;color:#C4B5FD;"></i>
                                    {{ $sub->task->project->name ?? '—' }}
                                </span>
                            </div>
                            <div>
                                <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Version</p>
                                <span style="font-size:11px;font-weight:700;color:#4F46E5;background:linear-gradient(135deg,#EEF2FF,#E0E7FF);padding:3px 9px;border-radius:20px;border:1px solid #C7D2FE;">v{{ $sub->version }}</span>
                            </div>
                            <div>
                                <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Reviewed By</p>
                                <span style="font-size:12px;color:#374151;">{{ $sub->reviewer->name ?? '—' }}</span>
                            </div>
                            @if($sub->admin_note)
                            <div style="flex:1;min-width:160px;">
                                <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Note</p>
                                <span style="font-size:12px;color:#374151;">{{ $sub->admin_note }}</span>
                            </div>
                            @endif
                        </div>

                        {{-- Social media --}}
                        @if($isApproved && $taskSocialPosts->isNotEmpty())
                        <div>
                            <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;margin:0 0 6px;">Social Media</p>
                            <div style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;background:#D1FAE5;color:#065F46;font-size:11px;font-weight:700;margin-bottom:6px;">
                                <i class="fas fa-circle-check" style="font-size:9px;"></i> Posted
                            </div>
                            <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:4px;">
                                @foreach($taskSocialPosts as $sp)
                                @php [$spIcon,$spColor] = $pIcons[$sp->platform] ?? $pIcons['other']; @endphp
                                @if($sp->post_url)
                                <a href="{{ $sp->post_url }}" target="_blank" rel="noopener" title="{{ $sp->platformLabel() }}"
                                   style="width:26px;height:26px;border-radius:6px;background:#F3F4F6;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;"
                                   onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                                    <i class="fab {{ $spIcon }}" style="font-size:13px;color:{{ $spColor }};"></i>
                                </a>
                                @else
                                <span title="{{ $sp->platformLabel() }}" style="width:26px;height:26px;border-radius:6px;background:#F3F4F6;display:inline-flex;align-items:center;justify-content:center;">
                                    <i class="fab {{ $spIcon }}" style="font-size:13px;color:{{ $spColor }};"></i>
                                </span>
                                @endif
                                @endforeach
                            </div>
                            @if($postedAt)
                            <p style="font-size:10px;color:#9CA3AF;margin:0;">{{ $postedAt->format('M d, Y · H:i') }}{{ $socialAssignee ? ' · '.$socialAssignee->name : '' }}</p>
                            @endif
                        </div>
                        @endif

                    </div>
                </td>
            </tr>
        </tbody>
        @endforeach
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
                    <p style="font-size:12px;color:#6B7280;margin:0;white-space:nowrap;">{{ $sub->reviewed_at?->format(config('app.date_format', 'M d, Y')) }}</p>
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
@php $pubTotalPosts = $publishedSocialTasks->sum(fn($t) => $t->socialPosts->count()); @endphp
<div>

    {{-- View toggle --}}
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
        <div class="hist-view-toggle" id="pendViewToggle">
            <button class="hist-view-btn active" id="pendBtnList" onclick="setPendView('list')" title="Table view">
                <i class="fas fa-table-list" style="font-size:11px;"></i> Table
            </button>
            <button class="hist-view-btn" id="pendBtnCards" onclick="setPendView('cards')" title="Card view">
                <i class="fas fa-th-large" style="font-size:11px;"></i> Cards
            </button>
        </div>
        <span style="font-size:12px;color:#9CA3AF;">{{ $pubTotalPosts }} published {{ Str::plural('post', $pubTotalPosts) }}</span>
    </div>

    {{-- ── CARDS VIEW ── --}}
    <div id="pendingCardsView">
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
                    <form method="POST" action="{{ route('admin.social-posts.destroy', $sp->id) }}" style="margin:0;" id="pub-del-form-{{ $sp->id }}">
                        @csrf @method('DELETE')
                        <button type="button" class="pub-del-btn"
                                @click="openPubDelete($el.closest('form'), '{{ $sp->platformLabel() }} post on task: {{ addslashes($pt->title) }}')">
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
                        {{ $pt->social_posted_at->format(config('app.date_format', 'M d, Y')) }}
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
    </div>{{-- #pendingCardsView --}}

    {{-- ── TABLE VIEW ── --}}
    <div id="pendingListView" style="display:none;">
    <div style="background:#fff;border-radius:18px;border:1px solid #EBEBEB;box-shadow:0 2px 10px rgba(99,102,241,.06);overflow:clip;">
    <div class="tbl-scroll">
    <table class="pend-table" style="table-layout:auto;">
        <thead>
            <tr>
                <th>Platform</th>
                <th>Task</th>
                <th>Project</th>
                <th>Post URL</th>
                <th>Posted</th>
                <th style="text-align:right;">Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach($publishedSocialTasks as $pt)
        @foreach($pt->socialPosts as $sp)
        @php [$pIconT,$pColorT] = $pubIcons[$sp->platform] ?? $pubIcons['other']; @endphp
        <tr x-data="{ editing: false }">
            {{-- Platform --}}
            <td style="white-space:nowrap;">
                <div style="display:flex;align-items:center;gap:7px;">
                    <div style="width:28px;height:28px;border-radius:8px;background:#F9FAFB;border:1px solid #E5E7EB;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fab {{ $pIconT }}" style="font-size:14px;color:{{ $pColorT }};"></i>
                    </div>
                    <span style="font-size:12px;font-weight:600;color:#374151;">{{ $sp->platformLabel() }}</span>
                </div>
            </td>
            {{-- Task --}}
            <td style="max-width:200px;">
                <a href="{{ route('admin.tasks.show', $pt) }}" style="font-size:12px;font-weight:600;color:#4F46E5;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block;max-width:190px;" title="{{ $pt->title }}">{{ $pt->title }}</a>
            </td>
            {{-- Project --}}
            <td style="font-size:12px;color:#6B7280;white-space:nowrap;">{{ $pt->project->name ?? '—' }}</td>
            {{-- Post URL --}}
            <td style="max-width:200px;">
                @if($sp->post_url)
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="font-size:11px;color:#6B7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:140px;" title="{{ $sp->post_url }}">{{ $sp->post_url }}</span>
                    <a href="{{ $sp->post_url }}" target="_blank" rel="noopener"
                       style="display:inline-flex;align-items:center;gap:3px;padding:3px 8px;background:#EEF2FF;color:#4F46E5;border:1px solid #C7D2FE;border-radius:6px;font-size:10px;font-weight:700;text-decoration:none;flex-shrink:0;">
                        <i class="fas fa-arrow-up-right-from-square" style="font-size:8px;"></i> Open
                    </a>
                </div>
                @else
                <span style="font-size:11px;color:#D1D5DB;font-style:italic;">No link</span>
                @endif
            </td>
            {{-- Posted date --}}
            <td style="font-size:12px;color:#6B7280;white-space:nowrap;">{{ $pt->social_posted_at->format(config('app.date_format', 'M d, Y')) }}</td>
            {{-- Actions --}}
            <td style="text-align:right;white-space:nowrap;">
                <div style="display:flex;align-items:center;gap:4px;justify-content:flex-end;">
                    <button type="button" @click="editing=!editing" class="pub-edit-btn">
                        <i class="fas fa-pen" style="font-size:9px;"></i>
                        <span x-text="editing ? 'Cancel' : 'Edit'"></span>
                    </button>
                    <form method="POST" action="{{ route('admin.social-posts.destroy', $sp->id) }}" style="margin:0;" id="pub-del-form-{{ $sp->id }}">
                        @csrf @method('DELETE')
                        <button type="button" class="pub-del-btn"
                                @click="openPubDelete($el.closest('form'), '{{ $sp->platformLabel() }} post on task: {{ addslashes($pt->title) }}')">
                            <i class="fas fa-trash" style="font-size:9px;"></i>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        {{-- Inline edit row --}}
        <tr x-show="editing" x-cloak style="background:#F9FAFB;">
            <td colspan="6" style="padding:10px 14px;">
                <form method="POST" action="{{ route('admin.social-posts.update', $sp->id) }}">
                    @csrf @method('PUT')
                    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
                        <div>
                            <label style="font-size:10px;font-weight:700;color:#374151;display:block;margin-bottom:3px;">Platform</label>
                            <select name="platform" style="font-size:12px;padding:5px 8px;border:1.5px solid #D1D5DB;border-radius:7px;background:#fff;color:#111827;outline:none;">
                                @foreach($pubPlatforms as $val => $label)
                                <option value="{{ $val }}" {{ $sp->platform === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="flex:1;min-width:160px;">
                            <label style="font-size:10px;font-weight:700;color:#374151;display:block;margin-bottom:3px;">Post URL</label>
                            <input type="url" name="post_url" value="{{ $sp->post_url }}" placeholder="https://..."
                                   style="width:100%;font-size:12px;padding:5px 8px;border:1.5px solid #D1D5DB;border-radius:7px;background:#fff;color:#111827;outline:none;box-sizing:border-box;">
                        </div>
                        <div style="flex:1;min-width:120px;">
                            <label style="font-size:10px;font-weight:700;color:#374151;display:block;margin-bottom:3px;">Note</label>
                            <input type="text" name="note" value="{{ $sp->note }}" placeholder="Optional note..."
                                   style="width:100%;font-size:12px;padding:5px 8px;border:1.5px solid #D1D5DB;border-radius:7px;background:#fff;color:#111827;outline:none;box-sizing:border-box;">
                        </div>
                        <div style="display:flex;gap:6px;">
                            <button type="button" @click="editing=false" class="pub-cancel-btn">Cancel</button>
                            <button type="submit" class="pub-save-btn">Save</button>
                        </div>
                    </div>
                </form>
            </td>
        </tr>
        @endforeach
        @endforeach
        </tbody>
    </table>
    </div>
    </div>
    </div>{{-- #pendingListView --}}

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
<div style="background:#fff;border-radius:18px;border:1px solid #EBEBEB;box-shadow:0 2px 10px rgba(99,102,241,.06);overflow:clip;">
<div class="tbl-scroll">
    <table class="hist-table" style="table-layout:auto;">
        <thead>
            <tr>
                <th style="width:32px;padding:11px 8px 11px 16px;"></th>
                <th>Task</th>
                <th>Social Handler</th>
                <th>Status</th>
                <th>Assigned</th>
                <th></th>
            </tr>
        </thead>
        @foreach($socialTasks as $st)
        <tbody x-data="{ expanded: false }">
            <tr>
                <td style="padding:12px 8px 12px 16px;width:32px;">
                    <button @click="expanded = !expanded"
                            :style="expanded ? 'background:#EEF2FF;border-color:#C7D2FE;' : ''"
                            :title="expanded ? 'Collapse' : 'Expand'" title="Expand">
                        <i class="fas fa-chevron-right" :style="expanded ? 'transform:rotate(90deg);color:#4F46E5;' : ''"></i>
                    </button>
                </td>
                <td @click="expanded = !expanded" style="cursor:pointer;">
                    <p style="font-size:13px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:260px;" title="{{ $st->title }}">{{ $st->title }}</p>
                    <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">
                        <i class="fas fa-folder" style="font-size:9px;color:#C4B5FD;margin-right:3px;"></i>{{ $st->project->name ?? '—' }}
                        @if($st->assignee)
                        <span style="margin:0 4px;color:#E5E7EB;">·</span>{{ $st->assignee->name }}
                        @endif
                    </p>
                </td>
                <td>
                    @if($st->socialAssignee)
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;">
                            {{ strtoupper(substr($st->socialAssignee->name, 0, 1)) }}
                        </div>
                        <span style="font-size:12px;font-weight:500;color:#374151;white-space:nowrap;">{{ $st->socialAssignee->name }}</span>
                    </div>
                    @else
                    <span style="color:#D1D5DB;font-size:12px;">—</span>
                    @endif
                </td>
                <td>
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:4px 11px;border-radius:20px;background:#FEF3C7;color:#D97706;white-space:nowrap;">
                        <i class="fas fa-clock" style="font-size:9px;"></i> Pending
                    </span>
                    @if($st->socialAssignee)
                    <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;white-space:nowrap;">
                        <i class="fas fa-hourglass-half" style="font-size:9px;margin-right:3px;color:#FBB040;"></i>{{ $st->updated_at->diffForHumans() }}
                    </p>
                    @endif
                </td>
                <td>
                    <span style="font-size:12px;color:#6B7280;white-space:nowrap;">{{ $st->updated_at->format(config('app.date_format', 'M d, Y')) }}</span>
                    <p style="font-size:10px;color:#D1D5DB;margin:2px 0 0;white-space:nowrap;">{{ $st->updated_at->diffForHumans() }}</p>
                </td>
                <td style="white-space:nowrap;">
                    <div x-data="{ menuOpen: false, dTop: 0, dRight: 0 }" @click.outside="menuOpen=false" @scroll.window="menuOpen=false" @keydown.escape.window="menuOpen=false">
                        <button x-ref="actBtn" @click.stop="
                                    if (!menuOpen) {
                                        const r = $refs.actBtn.getBoundingClientRect();
                                        dTop   = r.bottom + 5;
                                        dRight = window.innerWidth - r.right;
                                    }
                                    menuOpen = !menuOpen;
                                "
                                style="display:inline-flex;align-items:center;gap:6px;padding:5px 13px;background:#EEF2FF;color:#4F46E5;border:1.5px solid #C7D2FE;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;transition:all .15s;"
                                onmouseover="this.style.background='#E0E7FF';this.style.borderColor='#A5B4FC'" onmouseout="this.style.background='#EEF2FF';this.style.borderColor='#C7D2FE'">
                            Actions <i class="fas fa-chevron-down" :style="menuOpen ? 'transform:rotate(180deg)' : ''"></i>
                        </button>
                        <div x-show="menuOpen"
                             :style="`position:fixed;top:${dTop}px;right:${dRight}px;background:#fff;border:1.5px solid #E5E7EB;border-radius:10px;box-shadow:0 8px 28px rgba(0,0,0,.13);min-width:170px;z-index:9999;overflow:hidden;`">
                            <button @click="menuOpen=false; expanded=true"
                                    style="display:flex;align-items:center;gap:9px;width:100%;padding:10px 15px;background:none;border:none;border-bottom:1px solid #F3F4F6;font-size:12px;font-weight:600;color:#4F46E5;cursor:pointer;text-align:left;"
                                    onmouseover="this.style.background='#F5F3FF'" onmouseout="this.style.background=''">
                                <i class="fas fa-user-pen" style="font-size:11px;width:14px;text-align:center;color:#6366F1;"></i> Reassign
                            </button>
                            <a href="{{ route('admin.tasks.show', $st->id) }}" @click="menuOpen=false"
                               style="display:flex;align-items:center;gap:9px;padding:10px 15px;font-size:12px;font-weight:600;color:#374151;text-decoration:none;"
                               onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background=''">
                                <i class="fas fa-arrow-up-right-from-square" style="font-size:11px;width:14px;text-align:center;color:#6B7280;"></i> Open Task
                            </a>
                        </div>
                    </div>
                </td>
            </tr>
            <tr x-show="expanded" x-collapse style="background:rgb(248,250,255);">
                <td></td>
                <td colspan="5" style="padding:0 16px 16px;">
                    <div style="padding-top:14px;border-top:1px solid #EEF2FF;">
                        <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;margin:0 0 10px;">
                            <i class="fas fa-user-pen" style="margin-right:4px;color:#6366F1;"></i>Reassign &amp; Instructions
                        </p>
                        <form method="POST" action="{{ route('admin.tasks.social.assign', $st->id) }}" style="display:flex;flex-direction:column;gap:8px;max-width:480px;">
                            @csrf
                            <div style="display:flex;gap:6px;align-items:center;">
                                <select name="social_user_id" required style="font-size:12px;padding:7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;background:#fff;color:#374151;outline:none;flex:1;min-width:0;"
                                        onfocus="this.style.borderColor='#A5B4FC'" onblur="this.style.borderColor='#E5E7EB'">
                                    <option value="">Select handler…</option>
                                    @foreach($socialUsers as $su)
                                    <option value="{{ $su->id }}" {{ $su->id == $st->social_assigned_to ? 'selected' : '' }}>{{ $su->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit"
                                        style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:linear-gradient(135deg,#6366F1,#8B5CF6);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;flex-shrink:0;">
                                    <i class="fas fa-arrows-rotate" style="font-size:11px;"></i> Save
                                </button>
                            </div>
                            <textarea name="social_description" rows="2"
                                      placeholder="Posting instructions…"
                                      style="font-size:12px;padding:7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;background:#fff;color:#374151;outline:none;resize:vertical;width:100%;box-sizing:border-box;line-height:1.5;"
                                      onfocus="this.style.borderColor='#A5B4FC'" onblur="this.style.borderColor='#E5E7EB'">{{ $st->social_description }}</textarea>
                            <textarea name="social_caption" rows="2"
                                      placeholder="Ad caption…"
                                      style="font-size:12px;padding:7px 10px;border:1.5px solid #DDD6FE;border-radius:8px;background:#fff;color:#374151;outline:none;resize:vertical;width:100%;box-sizing:border-box;line-height:1.5;"
                                      onfocus="this.style.borderColor='#8B5CF6'" onblur="this.style.borderColor='#DDD6FE'">{{ $st->social_caption }}</textarea>
                            <input type="text" name="social_budget"
                                   value="{{ $st->social_budget }}"
                                   placeholder="Ad budget (e.g. $200)…"
                                   style="font-size:12px;padding:7px 10px;border:1.5px solid #FDE68A;border-radius:8px;background:#fff;color:#374151;outline:none;width:100%;box-sizing:border-box;"
                                   onfocus="this.style.borderColor='#F59E0B'" onblur="this.style.borderColor='#FDE68A'">
                        </form>
                    </div>
                </td>
            </tr>
        </tbody>
        @endforeach
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
        approvalModal:          false,
        approvalTask:           null,
        approvalNote:           '',
        approvalSocial:          null,   // 'yes' | 'no' | 'later' | null
        approvalSocialUser:      '',
        approvalSocialPlatforms: [],
        approvalNotifyEmail:    false,
        approvalNotifyWhatsapp: false,
        approvalCustomerMsg:    '',
        approvalManualEmail:    '',
        approvalManualPhone:    '',
        waSendState:            'idle',   // idle | sending | sent | error
        waSendMsg:              '',
        qvWaSending:            false,
        qvWaResult:             null,

        async quickWhatsApp(phone, name, title, submissionUrl) {
            if (!phone) { alert('No WhatsApp number on file for this customer.'); return; }
            this.qvWaSending = true; this.qvWaResult = null;
            const base = `Hello ${name}, your design for "${title}" has been submitted for review. We'd love your feedback before we finalize approval.`;
            const isImage = submissionUrl && /\.(jpg|jpeg|png|gif|webp)(\?|$)/i.test(submissionUrl);
            const hasFile = !!submissionUrl;
            const fetchUrl = hasFile ? '{{ $waMediaRoute }}' : '{{ $waSendRoute }}';
            const bodyData = hasFile
                ? { phone, file_url: submissionUrl, filename: submissionUrl.split('/').pop(), caption: base }
                : { phone, message: base };
            try {
                const res  = await fetch(fetchUrl, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ $waCsrf }}','Accept':'application/json'}, body: JSON.stringify(bodyData) });
                const data = await res.json();
                this.qvWaResult = { ok: data.ok, message: typeof data.message === 'string' ? data.message : JSON.stringify(data.message) };
                if (data.ok) setTimeout(() => { this.qvWaResult = null; }, 2500);
            } catch(e) {
                this.qvWaResult = { ok: false, message: 'Network error. Try again.' };
            }
            this.qvWaSending = false;
        },

        openApprovalModal(task) {
            this.approvalTask           = task;
            this.approvalNote           = '';
            this.approvalSocial          = null;
            this.approvalSocialUser      = '';
            this.approvalSocialPlatforms = [];
            this.approvalNotifyEmail    = false;
            this.approvalNotifyWhatsapp = false;
            this.approvalCustomerMsg    = '';
            this.approvalManualEmail    = '';
            this.approvalManualPhone    = '';
            this.waSendState            = 'idle';
            this.waSendMsg              = '';
            this.approvalModal          = true;
        },

        buildWhatsAppMessage() {
            const task   = this.approvalTask;
            const name   = task?.customer_name ?? 'Customer';
            const base   = `Hello ${name}, your design for "${task?.title ?? ''}" has been submitted for review. We'd love your feedback before we finalize approval.`;
            const custom = this.approvalCustomerMsg ? `\n\n${this.approvalCustomerMsg}` : '';
            return base + custom;
        },

        openCustomerWhatsApp() {
            const task   = this.approvalTask;
            const phone  = task?.customer_phone || this.approvalManualPhone;
            if (!phone) return;
            const digits = phone.replace(/\D/g, '');
            if (!digits) return;
            window.open('https://wa.me/' + digits + '?text=' + encodeURIComponent(this.buildWhatsAppMessage()), '_blank');
        },

        async sendWhatsAppApi() {
            const task    = this.approvalTask;
            const phone   = task?.customer_phone || this.approvalManualPhone;
            if (!phone) { this.waSendState = 'error'; this.waSendMsg = 'No phone number — enter one above.'; return; }
            this.waSendState = 'sending';
            this.waSendMsg   = '';
            const fileUrl = task?.submission_url ?? '';
            const hasFile = !!fileUrl;
            const fetchUrl = hasFile ? '{{ $waMediaRoute }}' : '{{ $waSendRoute }}';
            const body = hasFile
                ? { phone, file_url: fileUrl, filename: fileUrl.split('/').pop(), caption: this.buildWhatsAppMessage() }
                : { phone, message: this.buildWhatsAppMessage() };
            try {
                const res = await fetch(fetchUrl, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ $waCsrf }}', 'Accept': 'application/json' },
                    body:    JSON.stringify(body),
                });
                const data = await res.json();
                if (data.ok) {
                    this.waSendState = 'sent';
                    this.waSendMsg   = 'WhatsApp sent successfully!';
                } else {
                    this.waSendState = 'error';
                    this.waSendMsg   = data.message ?? 'Failed to send.';
                }
            } catch (e) {
                this.waSendState = 'error';
                this.waSendMsg   = 'Network error. Try again.';
            }
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

        // ── Published post delete confirmation ──
        pubDeleteModal: false,
        pubDeleteForm:  null,
        pubDeleteLabel: '',

        openPubDelete(formEl, label) {
            this.pubDeleteForm  = formEl;
            this.pubDeleteLabel = label;
            this.pubDeleteModal = true;
        },
        confirmPubDelete() {
            if (this.pubDeleteForm) this.pubDeleteForm.submit();
            this.pubDeleteModal = false;
        },

        // ── Quick view (list row click) ──
        qvModal: false,
        qvTask:  null,

        openQuickView(task) { this.qvTask = task; this.qvModal = true; this.qvWaSending = false; this.qvWaResult = null; },
        closeQuickView()    { this.qvModal = false; this.qvTask = null; this.qvWaSending = false; this.qvWaResult = null; },

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
