@extends('layouts.app')
@section('title', 'Task Approvals')

@section('content')
<style>
/* ── Card ── */
.apv-card {
    background: #fff;
    border-radius: 18px;
    border: 1px solid #EBEBEB;
    box-shadow: 0 2px 10px rgba(99,102,241,.07), 0 1px 3px rgba(0,0,0,.04);
    margin-bottom: 20px;
    overflow: hidden;
    transition: box-shadow .22s, transform .22s;
    position: relative;
    max-width: 860px;
}
.apv-card:hover {
    box-shadow: 0 8px 28px rgba(99,102,241,.13), 0 2px 8px rgba(0,0,0,.06);
    transform: translateY(-2px);
}
/* priority left bar */
.apv-card.pri-high   { border-left: 4px solid #EF4444; }
.apv-card.pri-medium { border-left: 4px solid #F59E0B; }
.apv-card.pri-low    { border-left: 4px solid #10B981; }

/* ── Sections ── */
.apv-header     { padding: 18px 22px 14px; border-bottom: 1px solid #F3F4F6; }
.apv-submission { padding: 16px 22px; background: #F8FAFF; border-bottom: 1px solid #EEF0F6; }
.apv-actions    { display: grid; grid-template-columns: 1fr 1fr; }
.apv-approve    { padding: 16px 18px 18px; border-right: 1px solid #F0F4F8; background: #F6FEF9; }
.apv-reject     { padding: 16px 18px 18px; background: #FFF8F8; }
.apv-footer     { padding: 12px 22px 14px; border-top: 1px solid #F3F4F6; background: #FAFBFF; display: flex; gap: 10px; align-items: flex-start; }

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

/* ── Version history items ── */
.version-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 14px; border-radius: 10px; gap: 10px;
    background: #F5F7FF; border: 1px solid #EEF0FA;
    transition: background .12s;
}
.version-row:hover { background: #EEF2FF; }
</style>

<div x-data="approvalPage()" @keydown.escape.window="if(viewer) closeViewer(); else closeModal()"
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
         @click.self="closeModal()"
         style="position:fixed;inset:0;background:rgba(15,18,40,.55);z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(3px);">
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
</template>

{{-- ═══════════ PAGE HEADER ═══════════ --}}
<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:14px;">
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
<div style="display:flex;gap:3px;background:#F1F2F6;border-radius:13px;padding:4px;margin-bottom:24px;width:fit-content;">
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
</div>

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

@forelse($tasks as $task)
@php
    $latestSub = $task->submissions->first();
    $isOverdue  = $task->deadline->isPast();
    $priClass   = match($task->priority) { 'high' => 'pri-high', 'medium' => 'pri-medium', default => 'pri-low' };
    $priColors  = ['high'=>['#FEE2E2','#DC2626','#FCA5A5'],'medium'=>['#FEF3C7','#D97706','#FCD34D'],'low'=>['#D1FAE5','#059669','#6EE7B7']];
    [$pbg,$pco,$pbo] = $priColors[$task->priority] ?? ['#F3F4F6','#6B7280','#D1D5DB'];
@endphp

<div class="apv-card {{ $priClass }}">

    {{-- ── Card Header ── --}}
    <div class="apv-header">
        <div style="display:flex;align-items:flex-start;gap:14px;flex-wrap:wrap;">

            {{-- Avatar --}}
            <div style="width:46px;height:46px;border-radius:14px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:17px;font-weight:800;color:#fff;flex-shrink:0;box-shadow:0 4px 12px rgba(99,102,241,.3);">
                {{ strtoupper(substr($task->assignee->name ?? 'U', 0, 1)) }}
            </div>

            {{-- Task info --}}
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                    <h3 style="font-size:15px;font-weight:700;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:400px;">{{ $task->title }}</h3>
                    <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;background:{{ $pbg }};color:{{ $pco }};border:1px solid {{ $pbo }};flex-shrink:0;">
                        {{ ucfirst($task->priority) }}
                    </span>
                    <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;background:linear-gradient(135deg,#EDE9FE,#DDD6FE);color:#7C3AED;flex-shrink:0;">
                        {{ $task->submissions->count() }} {{ Str::plural('ver', $task->submissions->count()) }}
                    </span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                    <div style="display:flex;align-items:center;gap:5px;">
                        <div style="width:18px;height:18px;border-radius:6px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;color:#fff;">
                            {{ strtoupper(substr($task->assignee->name ?? 'U', 0, 1)) }}
                        </div>
                        <span style="font-size:12px;font-weight:600;color:#4F46E5;">{{ $task->assignee->name ?? 'Unknown' }}</span>
                    </div>
                    <span style="color:#D1D5DB;font-size:11px;">·</span>
                    <span style="font-size:12px;color:#6B7280;">
                        <i class="fas fa-folder" style="font-size:10px;color:#A5B4FC;margin-right:3px;"></i>{{ $task->project->name }}
                    </span>
                    <span style="color:#D1D5DB;font-size:11px;">·</span>
                    <span style="font-size:12px;color:{{ $isOverdue ? '#DC2626' : '#6B7280' }};font-weight:{{ $isOverdue ? '600' : '400' }};">
                        <i class="fas fa-calendar-day" style="font-size:10px;margin-right:3px;color:{{ $isOverdue ? '#FCA5A5' : '#D1D5DB' }};"></i>
                        {{ $isOverdue ? 'Overdue · ' : '' }}{{ $task->deadline->format('M d, Y') }}
                    </span>
                </div>
            </div>

            {{-- Full view link --}}
            <a href="{{ route('admin.tasks.show', $task) }}"
               style="display:flex;align-items:center;gap:5px;padding:7px 14px;background:#F3F4F6;color:#374151;border-radius:9px;font-size:12px;font-weight:600;text-decoration:none;flex-shrink:0;border:1px solid #E5E7EB;transition:background .15s;"
               onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                <i class="fa fa-arrow-up-right-from-square" style="font-size:10px;"></i> Full View
            </a>
        </div>
    </div>

    {{-- ── Latest Submission ── --}}
    @if($latestSub)
    @php
        $fname  = $latestSub->original_filename ?? '';
        $fext   = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
        $furl   = $latestSub->fileUrl();
        $fIsImg = in_array($fext, ['jpg','jpeg','png','gif','webp','svg']);
        $fIsVid = in_array($fext, ['mp4','mov','avi','mkv','webm']);
        $fIsPdf = $fext === 'pdf';
        $fIconClass = match(true) {
            $fIsPdf                            => 'fa-file-pdf',
            in_array($fext,['doc','docx'])     => 'fa-file-word',
            in_array($fext,['xls','xlsx'])     => 'fa-file-excel',
            in_array($fext,['ppt','pptx'])     => 'fa-file-powerpoint',
            in_array($fext,['zip','rar','7z']) => 'fa-file-zipper',
            default                            => 'fa-file',
        };
        $fIconColor = match(true) {
            $fIsPdf                            => '#DC2626',
            in_array($fext,['doc','docx'])     => '#2563EB',
            in_array($fext,['xls','xlsx'])     => '#16A34A',
            in_array($fext,['ppt','pptx'])     => '#EA580C',
            in_array($fext,['zip','rar','7z']) => '#CA8A04',
            default                            => '#6B7280',
        };
    @endphp
    <div class="apv-submission">
        {{-- Submission meta bar --}}
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;gap:10px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="display:flex;align-items:center;gap:5px;background:linear-gradient(135deg,#EEF2FF,#E0E7FF);padding:4px 11px;border-radius:20px;border:1px solid #C7D2FE;">
                    <i class="fas fa-code-branch" style="font-size:9px;color:#6366F1;"></i>
                    <span style="font-size:11px;font-weight:700;color:#4F46E5;">Latest · v{{ $latestSub->version }}</span>
                </div>
                <span style="font-size:11px;color:#9CA3AF;">
                    <i class="fas fa-clock" style="font-size:9px;"></i> {{ $latestSub->created_at->diffForHumans() }}
                </span>
            </div>
            <button @click="openModal({
                        version: {{ $latestSub->version }},
                        task: @js($task->title),
                        status: @js($latestSub->status),
                        date: @js($latestSub->created_at->format('M d, Y H:i')),
                        user: @js($task->assignee->name ?? 'Unknown'),
                        note: @js($latestSub->note),
                        file: @js($latestSub->file_path ? $latestSub->fileUrl() : null),
                        filename: @js($latestSub->original_filename),
                        adminNote: @js($latestSub->admin_note)
                    })"
                    style="display:flex;align-items:center;gap:6px;padding:6px 14px;background:#fff;color:#4F46E5;border:1.5px solid #C7D2FE;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all .15s;"
                    onmouseover="this.style.background='#EEF2FF';this.style.borderColor='#A5B4FC'" onmouseout="this.style.background='#fff';this.style.borderColor='#C7D2FE'">
                <i class="fas fa-eye" style="font-size:10px;"></i> View Details
            </button>
        </div>

        {{-- Submission body: thumbnail + note --}}
        <div style="display:flex;gap:14px;align-items:flex-start;">

            {{-- Thumbnail --}}
            @if($latestSub->file_path)
            <div class="apv-thumb" @click="openViewer(@js($furl), @js($fname))" style="width:130px;">
                @if($fIsImg)
                    <img src="{{ $furl }}" alt="{{ $fname }}" style="width:130px;height:88px;object-fit:cover;display:block;">
                    <div style="position:absolute;inset:0;background:rgba(79,70,229,0);display:flex;align-items:center;justify-content:center;transition:background .18s;" onmouseover="this.style.background='rgba(79,70,229,.22)'" onmouseout="this.style.background='rgba(79,70,229,0)'">
                        <i class="fas fa-expand" style="color:#fff;font-size:14px;opacity:0;transition:opacity .18s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'"></i>
                    </div>
                @elseif($fIsVid)
                    <div style="width:130px;height:88px;background:#0D0D0D;position:relative;">
                        <video src="{{ $furl }}" style="width:100%;height:100%;object-fit:cover;display:block;" preload="metadata" muted></video>
                        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.3);">
                            <div style="width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.92);display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-play" style="color:#4F46E5;font-size:12px;margin-left:2px;"></i>
                            </div>
                        </div>
                    </div>
                @else
                    <div style="width:130px;height:88px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;background:#F8FAFF;">
                        <i class="fas {{ $fIconClass }}" style="font-size:32px;color:{{ $fIconColor }};"></i>
                        <span style="font-size:10px;color:#6B7280;text-align:center;padding:0 8px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;width:100%;">{{ strtoupper($fext) }}</span>
                    </div>
                @endif
                {{-- filename label --}}
                <div style="padding:5px 8px;background:#fff;border-top:1px solid #E8ECFC;">
                    <span style="font-size:10px;color:#6B7280;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $fname }}</span>
                </div>
            </div>
            @endif

            {{-- Note text --}}
            <div style="flex:1;min-width:0;">
                @if($latestSub->note)
                <p style="font-size:13px;color:#374151;margin:0 0 8px;line-height:1.65;">"{{ $latestSub->note }}"</p>
                @else
                <p style="font-size:12px;color:#D1D5DB;margin:0 0 8px;font-style:italic;">No submission note provided.</p>
                @endif
                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                    <span style="font-size:11px;color:#9CA3AF;">
                        <i class="fas fa-user" style="font-size:9px;"></i> {{ $task->assignee->name ?? 'Unknown' }}
                    </span>
                    <span style="color:#E5E7EB;font-size:10px;">·</span>
                    <span style="font-size:11px;color:#9CA3AF;">
                        <i class="fas fa-calendar" style="font-size:9px;"></i> {{ $latestSub->created_at->format('M d, Y · H:i') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Action Zone: Approve / Reject ── --}}
    <div class="apv-actions">

        {{-- Approve --}}
        <div class="apv-approve">
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:10px;">
                <div style="width:22px;height:22px;border-radius:7px;background:#10B981;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-check" style="color:#fff;font-size:9px;"></i>
                </div>
                <span style="font-size:11px;font-weight:700;color:#059669;text-transform:uppercase;letter-spacing:.05em;">Approve</span>
            </div>
            <form method="POST" action="{{ route('admin.tasks.approve', $task) }}">
                @csrf
                <input type="text" name="note" placeholder="Optional note for the team member..."
                       class="apv-input apv-input-green">
                <button type="submit" class="btn-approve">
                    <i class="fas fa-circle-check"></i> Approve Submission
                </button>
            </form>
        </div>

        {{-- Reject --}}
        <div class="apv-reject">
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:10px;">
                <div style="width:22px;height:22px;border-radius:7px;background:#EF4444;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-rotate-left" style="color:#fff;font-size:9px;"></i>
                </div>
                <span style="font-size:11px;font-weight:700;color:#DC2626;text-transform:uppercase;letter-spacing:.05em;">Request Revision</span>
            </div>
            <form method="POST" action="{{ route('admin.tasks.reject', $task) }}">
                @csrf
                <input type="text" name="note" required placeholder="Reason for revision (required)..."
                       class="apv-input apv-input-red">
                <button type="submit" class="btn-reject">
                    <i class="fas fa-rotate-left"></i> Request Revision
                </button>
            </form>
        </div>
    </div>

    {{-- ── Footer: Comment ── --}}
    <div class="apv-footer">
        <form method="POST" action="{{ route('admin.tasks.comment', $task) }}" style="flex:1;display:flex;gap:8px;">
            @csrf
            <div style="flex:1;position:relative;">
                <i class="fas fa-comment" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#C4C9D4;font-size:11px;pointer-events:none;"></i>
                <input type="text" name="body" required placeholder="Add a comment for {{ $task->assignee->name ?? 'assignee' }}..."
                       style="width:100%;padding:9px 12px 9px 30px;border:1.5px solid #E5E7EB;border-radius:9px;font-size:12px;color:#111827;outline:none;background:#fff;box-sizing:border-box;transition:border-color .15s,box-shadow .15s;"
                       onfocus="this.style.borderColor='#A5B4FC';this.style.boxShadow='0 0 0 3px rgba(165,180,252,.15)'" onblur="this.style.borderColor='#E5E7EB';this.style.boxShadow='none'">
            </div>
            <button type="submit"
                    style="padding:9px 16px;background:linear-gradient(135deg,#EEF2FF,#E0E7FF);color:#4F46E5;border:1.5px solid #C7D2FE;border-radius:9px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:5px;transition:all .15s;"
                    onmouseover="this.style.background='#E0E7FF'" onmouseout="this.style.background='linear-gradient(135deg,#EEF2FF,#E0E7FF)'">
                <i class="fas fa-paper-plane" style="font-size:10px;"></i> Send
            </button>
        </form>
    </div>

    {{-- ── Previous Versions (collapsible) ── --}}
    @if($task->submissions->count() > 1)
    <div x-data="{ open: false }" style="border-top:1px solid #F0F2F8;">
        <button @click="open = !open"
                style="width:100%;padding:10px 22px;background:none;border:none;cursor:pointer;font-size:12px;color:#9CA3AF;display:flex;align-items:center;gap:6px;justify-content:center;transition:background .15s;"
                onmouseover="this.style.background='#F8FAFF'" onmouseout="this.style.background='transparent'">
            <i class="fa fa-clock-rotate-left" style="font-size:10px;"></i>
            @php $prevCount = $task->submissions->count() - 1; $prevLabel = $prevCount === 1 ? 'version' : 'versions'; @endphp
            <span x-text="open ? 'Hide previous versions' : 'Show {{ $prevCount }} previous {{ $prevLabel }}'"></span>
            <i class="fa fa-chevron-down" :style="open ? 'transform:rotate(180deg)' : ''" style="transition:transform .2s;font-size:10px;"></i>
        </button>
        <div x-show="open" x-cloak style="padding:0 22px 14px;display:flex;flex-direction:column;gap:7px;">
            @foreach($task->submissions->skip(1) as $sub)
            @php
                $sColors = ['submitted'=>['#EEF2FF','#4F46E5'],'approved'=>['#D1FAE5','#059669'],'rejected'=>['#FEE2E2','#DC2626']];
                [$scbg,$scco] = $sColors[$sub->status] ?? ['#F3F4F6','#6B7280'];
            @endphp
            <div class="version-row">
                <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                    <span style="font-size:10px;font-weight:700;color:{{ $scco }};background:{{ $scbg }};padding:3px 9px;border-radius:20px;flex-shrink:0;">v{{ $sub->version }}</span>
                    <span style="font-size:11px;font-weight:600;color:#374151;flex-shrink:0;">{{ ucfirst($sub->status) }}</span>
                    <span style="font-size:11px;color:#9CA3AF;flex-shrink:0;"><i class="fas fa-clock" style="font-size:9px;"></i> {{ $sub->created_at->format('M d, H:i') }}</span>
                    @if($sub->note)
                    <span style="font-size:11px;color:#6B7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">· {{ $sub->note }}</span>
                    @endif
                </div>
                <button @click="openModal({
                            version: {{ $sub->version }},
                            task: @js($task->title),
                            status: @js($sub->status),
                            date: @js($sub->created_at->format('M d, Y H:i')),
                            user: @js($task->assignee->name ?? 'Unknown'),
                            note: @js($sub->note),
                            file: @js($sub->file_path ? $sub->fileUrl() : null),
                            filename: @js($sub->original_filename),
                            adminNote: @js($sub->admin_note)
                        })"
                        style="display:flex;align-items:center;gap:4px;padding:5px 12px;background:#fff;color:#6B7280;border:1.5px solid #E5E7EB;border-radius:7px;font-size:11px;font-weight:600;cursor:pointer;flex-shrink:0;transition:all .15s;"
                        onmouseover="this.style.background='#EEF2FF';this.style.color='#4F46E5';this.style.borderColor='#C7D2FE'" onmouseout="this.style.background='#fff';this.style.color='#6B7280';this.style.borderColor='#E5E7EB'">
                    <i class="fas fa-eye" style="font-size:10px;"></i> View
                </button>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@empty
<div style="background:#fff;border-radius:18px;border:1px solid #EBEBEB;padding:72px 40px;text-align:center;box-shadow:0 2px 10px rgba(99,102,241,.06);">
    <div style="width:64px;height:64px;border-radius:20px;background:linear-gradient(135deg,#D1FAE5,#A7F3D0);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
        <i class="fa fa-circle-check" style="color:#10B981;font-size:28px;"></i>
    </div>
    <p style="font-size:16px;font-weight:700;color:#111827;margin:0 0 6px;">All caught up!</p>
    <p style="font-size:13px;color:#9CA3AF;margin:0;">No tasks are pending review right now. Check back later.</p>
</div>
@endforelse

@if($tasks->hasPages())
<div style="margin-top:20px;">{{ $tasks->links() }}</div>
@endif

@endif {{-- end pending tab --}}

{{-- ══════════════════════ HISTORY TAB ══════════════════════ --}}
@if($tab === 'history')

@if($history->total() === 0)
<div style="background:#fff;border-radius:18px;border:1px solid #EBEBEB;padding:72px 40px;text-align:center;box-shadow:0 2px 10px rgba(99,102,241,.06);">
    <div style="width:64px;height:64px;border-radius:20px;background:linear-gradient(135deg,#EDE9FE,#DDD6FE);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
        <i class="fa fa-clock-rotate-left" style="color:#7C3AED;font-size:26px;"></i>
    </div>
    <p style="font-size:16px;font-weight:700;color:#111827;margin:0 0 6px;">No history yet</p>
    <p style="font-size:13px;color:#9CA3AF;margin:0;">Approved and rejected submissions will appear here.</p>
</div>
@else

<div style="background:#fff;border-radius:18px;border:1px solid #EBEBEB;box-shadow:0 2px 10px rgba(99,102,241,.06);overflow:hidden;">
    <table class="hist-table">
        <thead>
            <tr>
                <th>Task</th>
                <th>Assignee</th>
                <th>Project</th>
                <th>Ver.</th>
                <th>Decision</th>
                <th>Reviewed By</th>
                <th>Date</th>
                <th>Feedback</th>
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
                <td style="max-width:180px;">
                    @if($sub->admin_note)
                    <p style="font-size:12px;color:#6B7280;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $sub->admin_note }}</p>
                    @else
                    <span style="font-size:12px;color:#E5E7EB;">—</span>
                    @endif
                </td>
                <td>
                    <button @click="openModal({
                                version: {{ $sub->version }},
                                task: @js($sub->task->title ?? ''),
                                status: @js($sub->status),
                                date: @js($sub->reviewed_at?->format('M d, Y H:i')),
                                user: @js($sub->task->assignee->name ?? 'Unknown'),
                                note: @js($sub->note),
                                file: @js($sub->file_path ? $sub->fileUrl() : null),
                                filename: @js($sub->original_filename),
                                adminNote: @js($sub->admin_note)
                            })"
                            style="display:flex;align-items:center;gap:5px;padding:6px 13px;background:#F3F4F6;color:#6B7280;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;transition:all .15s;"
                            onmouseover="this.style.background='#EEF2FF';this.style.color='#4F46E5';this.style.borderColor='#C7D2FE'" onmouseout="this.style.background='#F3F4F6';this.style.color='#6B7280';this.style.borderColor='#E5E7EB'">
                        <i class="fas fa-eye" style="font-size:10px;"></i> View
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($history->hasPages())
<div style="margin-top:20px;">{{ $history->appends(['tab' => 'history'])->links() }}</div>
@endif

@endif
@endif {{-- end history tab --}}

</div>

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
</script>

@endsection
