@extends('layouts.app')
@section('title', 'Settings')

@section('content')
<style>
/* ── Settings Layout ── */
.settings-wrap  { display:grid; grid-template-columns:220px 1fr; gap:24px; align-items:start; }
.settings-nav   { background:#fff; border-radius:14px; border:1px solid #F0F0F0; box-shadow:0 1px 4px rgba(0,0,0,0.05); padding:10px; position:sticky; top:24px; }
.settings-panel { display:flex; flex-direction:column; gap:20px; }

/* Sidebar nav items */
.snav-item { display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:9px; font-size:13px; font-weight:500; color:#6B7280; cursor:pointer; transition:all 0.15s; border:none; background:none; width:100%; text-align:left; }
.snav-item:hover  { background:#F9FAFB; color:#111827; }
.snav-item.active { background:#EEF2FF; color:#4F46E5; }
.snav-item i      { width:16px; text-align:center; font-size:13px; }

/* Setting cards */
.scard { background:#fff; border-radius:14px; border:1px solid #F0F0F0; box-shadow:0 1px 4px rgba(0,0,0,0.05); overflow:hidden; }
.scard-header { padding:18px 24px 14px; border-bottom:1px solid #F3F4F6; display:flex; align-items:center; gap:12px; }
.scard-icon   { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:15px; }
.scard-body   { padding:22px 24px; }
.scard-footer { padding:14px 24px; background:#FAFAFA; border-top:1px solid #F3F4F6; display:flex; align-items:center; justify-content:flex-end; gap:10px; }

/* Form controls */
.sf-group  { margin-bottom:18px; }
.sf-group:last-child { margin-bottom:0; }
.sf-label  { display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:6px; }
.sf-hint   { font-size:11px; color:#9CA3AF; margin-top:4px; }
.sf-input  { width:100%; padding:9px 12px; font-size:13px; border:1.5px solid #E5E7EB; border-radius:9px; color:#111827; outline:none; font-family:'Inter',sans-serif; transition:border-color 0.15s; background:#fff; }
.sf-input:focus { border-color:#6366F1; box-shadow:0 0 0 3px rgba(99,102,241,0.1); }
.sf-select { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%239CA3AF' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 12px center; padding-right:32px; }
.sf-row    { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.sf-toggle-row { display:flex; align-items:center; justify-content:space-between; padding:12px 0; border-bottom:1px solid #F3F4F6; }
.sf-toggle-row:last-child { border-bottom:none; padding-bottom:0; }
.sf-toggle-label { font-size:13px; font-weight:500; color:#111827; }
.sf-toggle-hint  { font-size:11px; color:#9CA3AF; margin-top:1px; }

/* Toggle switch */
.toggle { position:relative; display:inline-block; width:40px; height:22px; flex-shrink:0; }
.toggle input { opacity:0; width:0; height:0; }
.toggle-slider { position:absolute; cursor:pointer; inset:0; background:#E5E7EB; border-radius:22px; transition:.2s; }
.toggle-slider:before { content:''; position:absolute; height:16px; width:16px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.2s; box-shadow:0 1px 3px rgba(0,0,0,0.2); }
input:checked + .toggle-slider { background:#4F46E5; }
input:checked + .toggle-slider:before { transform:translateX(18px); }

/* Save btn */
.btn-save { padding:9px 22px; font-size:13px; font-weight:600; background:#4F46E5; color:#fff; border:none; border-radius:9px; cursor:pointer; box-shadow:0 2px 8px rgba(79,70,229,0.3); transition:background 0.15s; }
.btn-save:hover { background:#4338CA; }
.btn-cancel { padding:9px 16px; font-size:13px; font-weight:500; background:#F3F4F6; color:#374151; border:none; border-radius:9px; cursor:pointer; }

/* Stats strip */
.stat-strip { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
.stat-pill  { background:#F9FAFB; border:1px solid #F0F0F0; border-radius:10px; padding:14px 16px; text-align:center; }

/* Export cards */
.export-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
.export-card { border:1.5px solid #E5E7EB; border-radius:12px; padding:18px; display:flex; flex-direction:column; align-items:flex-start; gap:10px; transition:border-color 0.15s; }
.export-card:hover { border-color:#6366F1; }
.export-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:16px; }
.btn-export  { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:12px; font-weight:600; border-radius:8px; text-decoration:none; transition:all 0.15s; border:1.5px solid; }

/* Color swatch preview */
.color-wrap { display:flex; align-items:center; gap:10px; }
.color-swatch { width:36px; height:36px; border-radius:8px; border:2px solid #E5E7EB; cursor:pointer; flex-shrink:0; }

/* Upload zones */
.upload-zone { border:2px dashed #E5E7EB; border-radius:12px; padding:20px; text-align:center; cursor:pointer; transition:all 0.2s; background:#FAFAFA; position:relative; }
.upload-zone:hover, .upload-zone.dragover { border-color:#6366F1; background:#EEF2FF; }
.upload-zone input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
.upload-preview { width:100%; height:80px; object-fit:contain; border-radius:8px; margin-bottom:8px; }
.upload-preview-favicon { width:48px; height:48px; object-fit:contain; border-radius:8px; margin-bottom:8px; }
.remove-btn { display:inline-flex; align-items:center; gap:5px; font-size:11px; color:#EF4444; background:#FEF2F2; border:1px solid #FECACA; border-radius:6px; padding:4px 10px; cursor:pointer; text-decoration:none; margin-top:6px; }

@media(max-width:900px){
    .settings-wrap { grid-template-columns:1fr; }
    .settings-nav  { position:static; display:flex; flex-wrap:wrap; gap:4px; }
    .sf-row, .stat-strip, .export-grid { grid-template-columns:1fr; }
}
@media(max-width:600px){
    .export-grid { grid-template-columns:1fr 1fr; }
}
</style>

{{-- Page Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <div>
        <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Settings</h1>
        <p  style="font-size:13px;color:#9CA3AF;margin:3px 0 0;">Manage branding, team, security and data</p>
    </div>
</div>

<div class="settings-wrap" x-data="{ tab: '{{ session('_fragment') ?? 'general' }}', confirm: null, phrase: '', openClear(type){ this.confirm = type; this.phrase = ''; }, closeClear(){ this.confirm = null; } }">

    {{-- ── Sidebar Nav ── --}}
    <nav class="settings-nav">
        @php
        $navItems = [
            ['id'=>'general',       'icon'=>'fa-sliders',        'label'=>'General'],
            ['id'=>'branding',      'icon'=>'fa-palette',        'label'=>'Branding'],
            ['id'=>'team',          'icon'=>'fa-users',          'label'=>'Team'],
            ['id'=>'notifications', 'icon'=>'fa-bell',           'label'=>'Notifications'],
            ['id'=>'mail',          'icon'=>'fa-envelope',       'label'=>'Mail / SMTP'],
            ['id'=>'whatsapp',      'icon'=>'fa-brands fa-whatsapp', 'label'=>'WhatsApp'],
            ['id'=>'security',      'icon'=>'fa-shield-halved',  'label'=>'Security'],
            ['id'=>'backup',        'icon'=>'fa-database',       'label'=>'Backup & Export'],
            ['id'=>'developer',     'icon'=>'fa-code',           'label'=>'Developer'],
            ['id'=>'danger',        'icon'=>'fa-trash-can',      'label'=>'Clear Data'],
        ];
        @endphp
        @foreach($navItems as $nav)
        @if($nav['id'] === 'danger')
        <button class="snav-item"
                :class="tab === 'danger' ? 'active' : ''"
                :style="tab === 'danger' ? 'background:#FEF2F2;color:#DC2626;' : 'color:#EF4444;'"
                @click="tab = 'danger'">
            <i class="fas fa-trash-can"></i> Clear Data
        </button>
        @else
        <button class="snav-item" :class="tab === '{{ $nav['id'] }}' ? 'active' : ''"
                @click="tab = '{{ $nav['id'] }}'">
            <i class="fas {{ $nav['icon'] }}"></i>
            {{ $nav['label'] }}
        </button>
        @endif
        @endforeach
    </nav>

    {{-- ── Panels ── --}}
    <div class="settings-panel">

        {{-- ════ GENERAL ════ --}}
        <div x-show="tab === 'general'" x-cloak>
            <div class="scard">
                <div class="scard-header">
                    <div class="scard-icon" style="background:#EEF2FF;color:#4F46E5;"><i class="fas fa-sliders"></i></div>
                    <div>
                        <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">General Settings</p>
                        <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Application name, timezone and display preferences</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.settings.general') }}">
                    @csrf
                    <div class="scard-body">
                        <div class="sf-row">
                            <div class="sf-group">
                                <label class="sf-label">Application Name</label>
                                <input type="text" name="app_name" class="sf-input" value="{{ $settings['app_name'] }}" required>
                                <p class="sf-hint">Shown in the browser tab title.</p>
                            </div>
                            <div class="sf-group">
                                <label class="sf-label">Department Name</label>
                                <input type="text" name="department_name" class="sf-input" value="{{ $settings['department_name'] }}">
                            </div>
                        </div>
                        <div class="sf-group">
                            <label class="sf-label">Tagline</label>
                            <input type="text" name="app_tagline" class="sf-input" value="{{ $settings['app_tagline'] }}" placeholder="Short description shown on login page">
                        </div>
                        <div class="sf-row">
                            <div class="sf-group" style="margin-bottom:0;">
                                <label class="sf-label">Timezone</label>
                                @php
                                    $popularTzKeys = ['UTC','Asia/Bahrain','Asia/Riyadh','Asia/Dubai','Asia/Kuwait','Asia/Qatar','Asia/Amman','Africa/Cairo','Asia/Beirut','Europe/London','America/New_York','America/Los_Angeles','Asia/Tokyo','Asia/Singapore','Europe/Paris','Asia/Kolkata'];
                                    $allTimezones  = collect(\DateTimeZone::listIdentifiers())->map(function($tz) use ($popularTzKeys) {
                                        $dt     = new \DateTime('now', new \DateTimeZone($tz));
                                        $offset = $dt->getOffset();
                                        $sign   = $offset >= 0 ? '+' : '-';
                                        $h      = intdiv(abs($offset), 3600);
                                        $m      = (abs($offset) % 3600) / 60;
                                        $parts  = explode('/', $tz);
                                        return [
                                            'value'    => $tz,
                                            'city'     => str_replace('_', ' ', end($parts)),
                                            'region'   => count($parts) > 1 ? str_replace('_', ' ', $parts[0]) : '',
                                            'offset'   => sprintf('UTC%s%02d:%02d', $sign, $h, $m),
                                            'offsetN'  => $offset,
                                            'popular'  => in_array($tz, $popularTzKeys),
                                        ];
                                    })->sortBy([['offsetN','asc'],['value','asc']])->values()->toArray();
                                    $currentTz = collect($allTimezones)->firstWhere('value', $settings['timezone']);
                                @endphp
                                <div x-data="{
                                    query: '',
                                    selected: '{{ $settings['timezone'] }}',
                                    selectedLabel: '{{ $currentTz ? $currentTz['city'].' — '.$currentTz['offset'] : $settings['timezone'] }}',
                                    open: false,
                                    activeIdx: -1,
                                    dropTop: 0,
                                    dropLeft: 0,
                                    dropWidth: 0,
                                    allTz: {{ json_encode($allTimezones) }},
                                    get filtered() {
                                        const q = this.query.trim().toLowerCase();
                                        if (!q) return this.allTz.filter(t => t.popular);
                                        return this.allTz.filter(t =>
                                            t.city.toLowerCase().includes(q) ||
                                            t.value.toLowerCase().includes(q.replace(/ /g,'_')) ||
                                            t.region.toLowerCase().includes(q) ||
                                            t.offset.toLowerCase().includes(q)
                                        ).slice(0, 60);
                                    },
                                    pick(tz) {
                                        this.selected      = tz.value;
                                        this.selectedLabel = tz.city + ' — ' + tz.offset;
                                        this.query         = '';
                                        this.open          = false;
                                        this.activeIdx     = -1;
                                    },
                                    openDrop() {
                                        const rect = this.$refs.tzTrigger.getBoundingClientRect();
                                        this.dropTop   = rect.bottom + window.scrollY + 4;
                                        this.dropLeft  = rect.left  + window.scrollX;
                                        this.dropWidth = rect.width;
                                        this.open      = true;
                                        this.activeIdx = -1;
                                    },
                                    onKey(e) {
                                        if (!this.open) { this.openDrop(); return; }
                                        const len = this.filtered.length;
                                        if (e.key === 'ArrowDown') { e.preventDefault(); this.activeIdx = (this.activeIdx + 1) % len; this.scrollActive(); }
                                        else if (e.key === 'ArrowUp') { e.preventDefault(); this.activeIdx = (this.activeIdx - 1 + len) % len; this.scrollActive(); }
                                        else if (e.key === 'Enter') { e.preventDefault(); if (this.activeIdx >= 0 && this.filtered[this.activeIdx]) this.pick(this.filtered[this.activeIdx]); }
                                        else if (e.key === 'Escape') { this.open = false; this.activeIdx = -1; }
                                    },
                                    scrollActive() {
                                        this.$nextTick(() => { const el = document.getElementById('tz-opt-'+this.activeIdx); if(el) el.scrollIntoView({block:'nearest'}); });
                                    }
                                }" @click.outside="open=false; query=''">

                                    <input type="hidden" name="timezone" :value="selected">

                                    {{-- Trigger --}}
                                    <div x-ref="tzTrigger"
                                         @click="openDrop(); $nextTick(()=>$refs.tzInput.focus())"
                                         style="display:flex;align-items:center;gap:8px;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:9px;cursor:pointer;background:#fff;transition:border-color .15s;"
                                         :style="open ? 'border-color:#6366F1;box-shadow:0 0 0 3px rgba(99,102,241,0.1);' : ''">
                                        <i class="fas fa-globe" style="color:#9CA3AF;font-size:12px;flex-shrink:0;"></i>
                                        <input type="text" x-ref="tzInput" x-model="query"
                                               :placeholder="selectedLabel"
                                               @focus="openDrop()" @input="open=true; activeIdx=-1"
                                               @keydown="onKey($event)"
                                               style="flex:1;border:none;outline:none;font-size:13px;color:#111827;background:transparent;min-width:0;"
                                               autocomplete="off" spellcheck="false">
                                        <i class="fas fa-chevron-down" style="color:#9CA3AF;font-size:10px;flex-shrink:0;transition:transform .15s;"
                                           :style="open ? 'transform:rotate(180deg)' : ''"></i>
                                    </div>

                                    {{-- Dropdown — fixed to escape overflow:hidden --}}
                                    <template x-teleport="body">
                                        <div x-show="open"
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="opacity-0 -translate-y-1"
                                             x-transition:enter-end="opacity-100 translate-y-0"
                                             :style="`position:absolute;top:${dropTop}px;left:${dropLeft}px;width:${dropWidth}px;z-index:99999;background:#fff;border:1.5px solid #E5E7EB;border-radius:10px;box-shadow:0 12px 32px rgba(0,0,0,0.12);overflow:hidden;`">

                                            <div style="padding:7px 12px 4px;font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid #F3F4F6;">
                                                <span x-text="query.trim() ? 'Results' : 'Popular timezones'"></span>
                                            </div>

                                            <div style="max-height:220px;overflow-y:auto;">
                                                <template x-for="(tz, idx) in filtered" :key="tz.value">
                                                    <div :id="'tz-opt-'+idx"
                                                         @click="pick(tz)"
                                                         @mouseenter="activeIdx = idx"
                                                         :style="activeIdx === idx ? 'background:#EEF2FF;' : (selected === tz.value ? 'background:#F5F3FF;' : '')"
                                                         style="padding:8px 12px;cursor:pointer;display:flex;align-items:center;gap:10px;transition:background .1s;">
                                                        <div style="flex:1;min-width:0;">
                                                            <div style="display:flex;align-items:center;gap:6px;">
                                                                <span x-text="tz.city" style="font-size:13px;font-weight:500;color:#111827;"></span>
                                                                <span x-show="tz.region" x-text="tz.region" style="font-size:11px;color:#9CA3AF;"></span>
                                                            </div>
                                                        </div>
                                                        <span x-text="tz.offset"
                                                              style="font-size:11px;font-weight:600;padding:2px 7px;border-radius:6px;flex-shrink:0;"
                                                              :style="selected === tz.value ? 'background:#EDE9FE;color:#6D28D9;' : 'background:#F3F4F6;color:#6B7280;'"></span>
                                                        <i x-show="selected === tz.value" class="fas fa-check" style="color:#6366F1;font-size:10px;flex-shrink:0;"></i>
                                                    </div>
                                                </template>
                                                <div x-show="filtered.length === 0" style="padding:20px;text-align:center;font-size:13px;color:#9CA3AF;">
                                                    No timezones match "<span x-text="query"></span>"
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                </div>
                                <p class="sf-hint" x-data style="margin-top:5px;">
                                    Currently set to <strong>{{ $settings['timezone'] }}</strong>
                                    @if($currentTz) <span style="color:#9CA3AF;">({{ $currentTz['offset'] }})</span>@endif
                                </p>
                            </div>
                            <div class="sf-group" style="margin-bottom:0;">
                                <label class="sf-label">Date Format</label>
                                <select name="date_format" class="sf-input sf-select">
                                    @foreach(['Y-m-d'=>'2025-04-16','d/m/Y'=>'16/04/2025','m/d/Y'=>'04/16/2025','d M Y'=>'16 Apr 2025'] as $fmt => $example)
                                    <option value="{{ $fmt }}" {{ $settings['date_format'] === $fmt ? 'selected' : '' }}>{{ $example }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="scard-footer">
                        <button type="submit" class="btn-save"><i class="fas fa-check" style="font-size:11px;margin-right:5px;"></i>Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ════ BRANDING ════ --}}
        <div x-show="tab === 'branding'" x-cloak>
            <div class="scard" x-data="{
                primary:          '{{ $settings['primary_color'] }}',
                accent:           '{{ $settings['accent_color'] }}',
                companyName:      '{{ addslashes($settings['company_name']) }}',
                logoPreview:      '{{ $settings['logo_path']    ? Storage::url($settings['logo_path'])    : '' }}',
                faviconPreview:   '{{ $settings['favicon_path'] ? Storage::url($settings['favicon_path']) : '' }}',
                removeLogo:       false,
                removeFavicon:    false,
                loginBgType:      '{{ $settings['login_bg_type']  ?? 'gradient' }}',
                loginBgColor:     '{{ $settings['login_bg_color'] ?? '#e8eaf6' }}',
                loginBgPreview:   '{{ isset($settings['login_bg_image']) && $settings['login_bg_image'] ? Storage::url($settings['login_bg_image']) : '' }}',
                removeBgImage:    false,
                setLogo(e)    { const f=e.target.files[0]; if(f){ const r=new FileReader(); r.onload=ev=>{ this.logoPreview=ev.target.result; this.removeLogo=false; }; r.readAsDataURL(f); } },
                setFavicon(e) { const f=e.target.files[0]; if(f){ const r=new FileReader(); r.onload=ev=>{ this.faviconPreview=ev.target.result; this.removeFavicon=false; }; r.readAsDataURL(f); } },
                setBgImage(e) { const f=e.target.files[0]; if(f){ const r=new FileReader(); r.onload=ev=>{ this.loginBgPreview=ev.target.result; this.removeBgImage=false; }; r.readAsDataURL(f); } },
            }">
                <div class="scard-header">
                    <div class="scard-icon" style="background:#FDF2F8;color:#EC4899;"><i class="fas fa-palette"></i></div>
                    <div>
                        <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">Branding</p>
                        <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Logo, favicon, company name and colour theme</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.settings.branding') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="scard-body">

                        {{-- ── Logo + Favicon row ── --}}
                        <div class="sf-row" style="margin-bottom:20px;">

                            {{-- Logo --}}
                            <div>
                                <label class="sf-label">
                                    Company Logo
                                    <span style="font-size:10px;color:#9CA3AF;font-weight:400;margin-left:4px;">PNG, JPG, SVG · max 2 MB</span>
                                </label>
                                <div class="upload-zone" :class="{ dragover: false }"
                                     @dragover.prevent @drop.prevent="setLogo($event.dataTransfer)"
                                     style="min-height:110px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;">
                                    <input type="file" name="logo" accept="image/*" @change="setLogo($event)">
                                    <input type="hidden" name="remove_logo" :value="removeLogo ? '1' : '0'">

                                    <template x-if="logoPreview && !removeLogo">
                                        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                                            <img :src="logoPreview" class="upload-preview" alt="Logo preview">
                                            <button type="button" class="remove-btn" @click.stop="removeLogo=true;logoPreview=''">
                                                <i class="fas fa-trash-can"></i> Remove
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="!logoPreview || removeLogo">
                                        <div style="display:flex;flex-direction:column;align-items:center;gap:4px;pointer-events:none;">
                                            <div style="width:40px;height:40px;background:#EEF2FF;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                                                <i class="fas fa-image" style="color:#6366F1;font-size:16px;"></i>
                                            </div>
                                            <p style="font-size:12px;font-weight:500;color:#374151;margin:0;">Click or drag to upload</p>
                                            <p style="font-size:11px;color:#9CA3AF;margin:0;">Replaces the sidebar icon</p>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Favicon --}}
                            <div>
                                <label class="sf-label">
                                    Favicon
                                    <span style="font-size:10px;color:#9CA3AF;font-weight:400;margin-left:4px;">PNG, ICO · max 512 KB · 32×32px</span>
                                </label>
                                <div class="upload-zone"
                                     @dragover.prevent @drop.prevent="setFavicon($event.dataTransfer)"
                                     style="min-height:110px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;">
                                    <input type="file" name="favicon" accept="image/png,image/x-icon,image/svg+xml" @change="setFavicon($event)">
                                    <input type="hidden" name="remove_favicon" :value="removeFavicon ? '1' : '0'">

                                    <template x-if="faviconPreview && !removeFavicon">
                                        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                                            <img :src="faviconPreview" class="upload-preview-favicon" alt="Favicon preview">
                                            <button type="button" class="remove-btn" @click.stop="removeFavicon=true;faviconPreview=''">
                                                <i class="fas fa-trash-can"></i> Remove
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="!faviconPreview || removeFavicon">
                                        <div style="display:flex;flex-direction:column;align-items:center;gap:4px;pointer-events:none;">
                                            <div style="width:40px;height:40px;background:#F0FDF4;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                                                <i class="fas fa-star" style="color:#16A34A;font-size:16px;"></i>
                                            </div>
                                            <p style="font-size:12px;font-weight:500;color:#374151;margin:0;">Click or drag to upload</p>
                                            <p style="font-size:11px;color:#9CA3AF;margin:0;">Shown in the browser tab</p>
                                        </div>
                                    </template>
                                </div>
                            </div>

                        </div>

                        {{-- ── Company Name ── --}}
                        <div class="sf-group">
                            <label class="sf-label">Company Name</label>
                            <input type="text" name="company_name" class="sf-input"
                                   x-model="companyName" required>
                            <p class="sf-hint">Shown in the sidebar header (when no logo is set).</p>
                        </div>

                        {{-- ── Copyright ── --}}
                        <div class="sf-group">
                            <label class="sf-label">Copyright Text</label>
                            <input type="text" name="copyright" class="sf-input" value="{{ $settings['copyright'] }}" placeholder="© 2025 Your Company. All rights reserved." maxlength="160">
                            <p class="sf-hint">Shown in the application footer.</p>
                        </div>

                        {{-- ── Live brand preview banner ── --}}
                        <div style="border-radius:12px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;transition:background 0.3s;"
                             :style="`background:linear-gradient(135deg,${primary},${accent})`">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <template x-if="logoPreview && !removeLogo">
                                    <img :src="logoPreview" style="height:28px;width:auto;border-radius:5px;object-fit:contain;" alt="Logo">
                                </template>
                                <template x-if="!logoPreview || removeLogo">
                                    <div style="width:28px;height:28px;background:rgba(255,255,255,0.2);border-radius:7px;display:flex;align-items:center;justify-content:center;">
                                        <i class="fas fa-bolt" style="color:#fff;font-size:12px;"></i>
                                    </div>
                                </template>
                                <div>
                                    <p style="font-size:13px;font-weight:700;color:#fff;margin:0;" x-text="companyName || 'Company Name'"></p>
                                    <p style="font-size:10px;color:rgba(255,255,255,0.65);margin:1px 0 0;">Live brand preview</p>
                                </div>
                            </div>
                            <template x-if="faviconPreview && !removeFavicon">
                                <img :src="faviconPreview" style="width:22px;height:22px;border-radius:4px;object-fit:contain;" alt="favicon">
                            </template>
                        </div>

                        {{-- ── Colours ── --}}
                        <div class="sf-row">
                            <div class="sf-group" style="margin-bottom:0;">
                                <label class="sf-label">Primary Colour</label>
                                <div class="color-wrap">
                                    <input type="color" x-model="primary" class="color-swatch" :style="`background:${primary};border-color:${primary}`">
                                    <input type="text" name="primary_color" class="sf-input" x-model="primary" pattern="^#[0-9A-Fa-f]{6}$" required>
                                </div>
                            </div>
                            <div class="sf-group" style="margin-bottom:0;">
                                <label class="sf-label">Accent Colour</label>
                                <div class="color-wrap">
                                    <input type="color" x-model="accent" class="color-swatch" :style="`background:${accent};border-color:${accent}`">
                                    <input type="text" name="accent_color" class="sf-input" x-model="accent" pattern="^#[0-9A-Fa-f]{6}$" required>
                                </div>
                            </div>
                        </div>

                        {{-- ── Login Background ── --}}
                        <div style="margin-top:24px;padding-top:20px;border-top:1px solid #F3F4F6;">
                            <label class="sf-label" style="margin-bottom:10px;">Login Page Background</label>
                            <p class="sf-hint" style="margin-bottom:14px;">Controls the background of the login and register pages.</p>

                            {{-- Type selector --}}
                            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px;">
                                <label style="cursor:pointer;">
                                    <input type="radio" name="login_bg_type" value="gradient" x-model="loginBgType" style="display:none;">
                                    <div :style="loginBgType==='gradient' ? 'border-color:#6366F1;background:#EEF2FF;' : 'border-color:#E5E7EB;'"
                                         style="border:2px solid;border-radius:10px;padding:10px 8px;text-align:center;transition:all 0.15s;cursor:pointer;">
                                        <div style="height:28px;border-radius:6px;background:linear-gradient(135deg,#fce4ec,#f3e5f5,#e8eaf6,#e3f2fd);margin-bottom:6px;"></div>
                                        <p style="font-size:11px;font-weight:600;margin:0;" :style="loginBgType==='gradient' ? 'color:#4F46E5' : 'color:#374151'">Gradient</p>
                                    </div>
                                </label>
                                <label style="cursor:pointer;">
                                    <input type="radio" name="login_bg_type" value="color" x-model="loginBgType" style="display:none;">
                                    <div :style="loginBgType==='color' ? 'border-color:#6366F1;background:#EEF2FF;' : 'border-color:#E5E7EB;'"
                                         style="border:2px solid;border-radius:10px;padding:10px 8px;text-align:center;transition:all 0.15s;cursor:pointer;">
                                        <div :style="`height:28px;border-radius:6px;background:${loginBgColor};margin-bottom:6px;`"></div>
                                        <p style="font-size:11px;font-weight:600;margin:0;" :style="loginBgType==='color' ? 'color:#4F46E5' : 'color:#374151'">Solid Color</p>
                                    </div>
                                </label>
                                <label style="cursor:pointer;">
                                    <input type="radio" name="login_bg_type" value="image" x-model="loginBgType" style="display:none;">
                                    <div :style="loginBgType==='image' ? 'border-color:#6366F1;background:#EEF2FF;' : 'border-color:#E5E7EB;'"
                                         style="border:2px solid;border-radius:10px;padding:10px 8px;text-align:center;transition:all 0.15s;cursor:pointer;">
                                        <div style="height:28px;border-radius:6px;background:#E5E7EB;display:flex;align-items:center;justify-content:center;margin-bottom:6px;">
                                            <i class="fas fa-image" style="color:#9CA3AF;font-size:13px;"></i>
                                        </div>
                                        <p style="font-size:11px;font-weight:600;margin:0;" :style="loginBgType==='image' ? 'color:#4F46E5' : 'color:#374151'">Image</p>
                                    </div>
                                </label>
                            </div>

                            {{-- Solid Color picker --}}
                            <div x-show="loginBgType === 'color'" style="margin-bottom:8px;">
                                <label class="sf-label">Background Color</label>
                                <div class="color-wrap">
                                    <input type="color" x-model="loginBgColor" class="color-swatch"
                                           :style="`background:${loginBgColor};border-color:${loginBgColor}`">
                                    <input type="text" name="login_bg_color" class="sf-input"
                                           x-model="loginBgColor" pattern="^#[0-9A-Fa-f]{6}$">
                                </div>
                            </div>

                            {{-- Image upload --}}
                            <div x-show="loginBgType === 'image'">
                                <label class="sf-label">
                                    Background Image
                                    <span style="font-size:10px;color:#9CA3AF;font-weight:400;margin-left:4px;">PNG, JPG, WEBP · max 5 MB</span>
                                </label>
                                <div class="upload-zone" @dragover.prevent @drop.prevent="setBgImage($event.dataTransfer)"
                                     style="min-height:100px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;">
                                    <input type="file" name="login_bg_image" accept="image/png,image/jpeg,image/webp" @change="setBgImage($event)">
                                    <input type="hidden" name="remove_login_bg_image" :value="removeBgImage ? '1' : '0'">

                                    <template x-if="loginBgPreview && !removeBgImage">
                                        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;width:100%;">
                                            <img :src="loginBgPreview" class="upload-preview" alt="Background preview"
                                                 style="height:80px;object-fit:cover;border-radius:8px;">
                                            <button type="button" class="remove-btn"
                                                    @click.stop="removeBgImage=true;loginBgPreview=''">
                                                <i class="fas fa-trash-can"></i> Remove
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="!loginBgPreview || removeBgImage">
                                        <div style="display:flex;flex-direction:column;align-items:center;gap:4px;pointer-events:none;">
                                            <div style="width:40px;height:40px;background:#F0F9FF;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                                                <i class="fas fa-panorama" style="color:#0EA5E9;font-size:16px;"></i>
                                            </div>
                                            <p style="font-size:12px;font-weight:500;color:#374151;margin:0;">Click or drag to upload</p>
                                            <p style="font-size:11px;color:#9CA3AF;margin:0;">Used as the full-page background</p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="scard-footer">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-check" style="font-size:11px;margin-right:5px;"></i>Save Branding
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ════ TEAM ════ --}}
        <div x-show="tab === 'team'" x-cloak>
            <div class="scard">
                <div class="scard-header">
                    <div class="scard-icon" style="background:#F0FDF4;color:#16A34A;"><i class="fas fa-users"></i></div>
                    <div>
                        <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">Team Settings</p>
                        <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Default roles, registration and task limits</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.settings.team') }}">
                    @csrf
                    <div class="scard-body">
                        <div class="sf-row">
                            <div class="sf-group">
                                <label class="sf-label">Default Role for New Users</label>
                                <select name="default_role" class="sf-input sf-select">
                                    <option value="user"    {{ $settings['default_role'] === 'user'    ? 'selected' : '' }}>User</option>
                                    <option value="manager" {{ $settings['default_role'] === 'manager' ? 'selected' : '' }}>Manager</option>
                                </select>
                            </div>
                            <div class="sf-group">
                                <label class="sf-label">Max Tasks per User</label>
                                <input type="number" name="max_tasks_per_user" class="sf-input" value="{{ $settings['max_tasks_per_user'] }}" min="1" max="500">
                            </div>
                        </div>
                        <div class="sf-row" style="margin-top:2px;">
                            <div class="sf-group" style="margin-bottom:0;">
                                <label class="sf-label">Default Task Priority</label>
                                <select name="default_task_priority" class="sf-input sf-select">
                                    <option value="low"    {{ ($settings['default_task_priority'] ?? 'medium') === 'low'    ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ ($settings['default_task_priority'] ?? 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high"   {{ ($settings['default_task_priority'] ?? 'medium') === 'high'   ? 'selected' : '' }}>High</option>
                                </select>
                                <p class="sf-hint">Applied when creating tasks without an explicit priority.</p>
                            </div>
                            <div class="sf-group" style="margin-bottom:0;">
                                <label class="sf-label">Max File Upload Size (MB)</label>
                                <input type="number" name="max_upload_mb" class="sf-input" value="{{ $settings['max_upload_mb'] ?? 20 }}" min="1" max="100">
                                <p class="sf-hint">Applies to task attachments and message files.</p>
                            </div>
                        </div>
                        <div class="sf-toggle-row">
                            <div>
                                <p class="sf-toggle-label">Allow Self-Registration</p>
                                <p class="sf-toggle-hint">Users can create their own accounts via the register page</p>
                            </div>
                            <label class="toggle">
                                <input type="checkbox" name="allow_registration" value="1"
                                       {{ $settings['allow_registration'] === '1' ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        {{-- Work Hours ──────────────────────────────────────── --}}
                        <div style="margin-top:18px;padding-top:18px;border-top:1px solid #F3F4F6;">
                            <p style="font-size:12px;font-weight:700;color:#374151;margin:0 0 12px;display:flex;align-items:center;gap:6px;">
                                <i class="fa fa-clock" style="color:#6366F1;font-size:11px;"></i>
                                Work Hours &amp; Timer
                            </p>
                            <p style="font-size:11px;color:#9CA3AF;margin:0 0 14px;">
                                The end-of-day command auto-pauses all running employee timers at the configured end time.
                                Add <code style="background:#F3F4F6;padding:1px 5px;border-radius:4px;font-size:10px;">* * * * * php /var/www/Task-Managment-System/artisan schedule:run</code> to your server cron.
                            </p>
                            <div class="sf-row">
                                <div class="sf-group">
                                    <label class="sf-label">Work Start Time</label>
                                    <input type="time" name="work_start_time" class="sf-input"
                                           value="{{ $settings['work_start_time'] ?? '09:00' }}">
                                    <p class="sf-hint">Used for outside-hours warnings when employees start timers early.</p>
                                </div>
                                <div class="sf-group">
                                    <label class="sf-label">Work End Time</label>
                                    <input type="time" name="work_end_time" class="sf-input"
                                           value="{{ $settings['work_end_time'] ?? '18:00' }}">
                                    <p class="sf-hint">All running timers are auto-paused at this time each work day.</p>
                                </div>
                            </div>
                            @php
                                $savedDays = json_decode($settings['work_days'] ?? '[1,2,3,4,5]', true) ?? [1,2,3,4,5];
                                $dayNames  = [1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'];
                            @endphp
                            <div class="sf-group" style="margin-bottom:0;">
                                <label class="sf-label">Work Days</label>
                                <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:4px;">
                                    @foreach($dayNames as $num => $name)
                                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#374151;cursor:pointer;">
                                        <input type="checkbox" name="work_days[]" value="{{ $num }}"
                                               {{ in_array($num, $savedDays) ? 'checked' : '' }}
                                               style="width:15px;height:15px;accent-color:#6366F1;cursor:pointer;">
                                        {{ $name }}
                                    </label>
                                    @endforeach
                                </div>
                                <p class="sf-hint">End-of-day auto-pause only runs on selected days.</p>
                            </div>
                        </div>
                    </div>
                    <div class="scard-footer">
                        <button type="submit" class="btn-save"><i class="fas fa-check" style="font-size:11px;margin-right:5px;"></i>Save Team Settings</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ════ NOTIFICATIONS ════ --}}
        <div x-show="tab === 'notifications'" x-cloak>
            <div class="scard">
                <div class="scard-header">
                    <div class="scard-icon" style="background:#FFFBEB;color:#D97706;"><i class="fas fa-bell"></i></div>
                    <div>
                        <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">Notifications</p>
                        <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Email alerts and task reminders</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.settings.notifications') }}">
                    @csrf
                    <div class="scard-body">

                        @php
                        $notifGroups = [
                            'Task Lifecycle' => [
                                ['key'=>'notify_on_assign',   'label'=>'Task Assigned',         'hint'=>'Notify user when a task is assigned to them'],
                                ['key'=>'notify_on_approve',  'label'=>'Task Approved',          'hint'=>'Notify user when their submission is approved'],
                                ['key'=>'notify_on_reject',   'label'=>'Revision Requested',     'hint'=>'Notify user when a revision is requested'],
                                ['key'=>'notify_on_deliver',  'label'=>'Task Delivered',         'hint'=>'Notify user when admin marks the task as delivered'],
                                ['key'=>'notify_on_complete', 'label'=>'Task Submitted',         'hint'=>'Notify admin when a user submits work for review'],
                            ],
                            'Team Activity' => [
                                ['key'=>'notify_on_reassign', 'label'=>'Task Reassigned',        'hint'=>'Notify users when a task is reassigned or deadline changed'],
                                ['key'=>'notify_on_transfer', 'label'=>'Task Transferred',       'hint'=>'Notify recipient when tasks are bulk-transferred to them'],
                                ['key'=>'notify_on_comment',  'label'=>'Comment Posted',         'hint'=>'Notify the other party when a comment is added to a task'],
                                ['key'=>'notify_on_viewed',   'label'=>'Task Viewed',            'hint'=>'Notify admin when a user first opens an assigned task (off by default)'],
                            ],
                            'Social & Reports' => [
                                ['key'=>'notify_on_social',   'label'=>'Social Media Events',    'hint'=>'Notify on social media assignment and post recording'],
                                ['key'=>'notify_on_report',   'label'=>'User Progress Reports',  'hint'=>'Notify admin when a user submits a progress report'],
                            ],
                        ];
                        @endphp

                        @foreach($notifGroups as $groupLabel => $items)
                        <p style="font-size:11px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 2px;{{ !$loop->first ? 'margin-top:16px;padding-top:16px;border-top:1px solid #F3F4F6;' : '' }}">{{ $groupLabel }}</p>
                        @foreach($items as $item)
                        <div class="sf-toggle-row">
                            <div>
                                <p class="sf-toggle-label">{{ $item['label'] }}</p>
                                <p class="sf-toggle-hint">{{ $item['hint'] }}</p>
                            </div>
                            <label class="toggle">
                                <input type="checkbox" name="{{ $item['key'] }}" value="1"
                                       {{ ($settings[$item['key']] ?? '1') === '1' ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        @endforeach
                        @endforeach

                        <div style="margin-top:16px;padding-top:16px;border-top:1px solid #F3F4F6;">
                            <div class="sf-group" style="margin-bottom:0;">
                                <label class="sf-label">Deadline Reminder (days before)</label>
                                <input type="number" name="task_reminder_days" class="sf-input" style="max-width:120px;"
                                       value="{{ $settings['task_reminder_days'] }}" min="0" max="30">
                                <p class="sf-hint">Send a reminder this many days before a task deadline. Set 0 to disable.</p>
                            </div>
                        </div>

                        {{-- ── External Channels on Assignment ── --}}
                        <div style="margin-top:20px;padding-top:20px;border-top:1px solid #F3F4F6;">
                            <p style="font-size:11px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 4px;">External Notifications on Task Assignment</p>
                            <p style="font-size:11px;color:#9CA3AF;margin:0 0 12px;">Send an email or WhatsApp message to the employee when a task is assigned to them. Requires Mail/SMTP and WhatsApp to be configured.</p>

                            {{-- Email on assign --}}
                            <div class="sf-toggle-row">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:32px;height:32px;border-radius:8px;background:#EFF6FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i class="fas fa-envelope" style="color:#2563EB;font-size:13px;"></i>
                                    </div>
                                    <div>
                                        <p class="sf-toggle-label" style="margin:0;">Send Email on Task Assigned</p>
                                        <p class="sf-toggle-hint" style="margin:0;">Employee receives a formatted email with task details and a link. Requires SMTP configured in Mail tab.</p>
                                    </div>
                                </div>
                                <label class="toggle">
                                    <input type="checkbox" name="email_on_assign" value="1"
                                           {{ ($settings['email_on_assign'] ?? '0') === '1' ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            {{-- WhatsApp on assign --}}
                            <div class="sf-toggle-row" style="margin-top:8px;">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:32px;height:32px;border-radius:8px;background:#DCFCE7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i class="fa-brands fa-whatsapp" style="color:#16A34A;font-size:15px;"></i>
                                    </div>
                                    <div>
                                        <p class="sf-toggle-label" style="margin:0;">Send WhatsApp on Task Assigned</p>
                                        <p class="sf-toggle-hint" style="margin:0;">Employee receives a WhatsApp message using the template in the WhatsApp tab. Requires WhatsApp enabled and a phone number on the user's profile.</p>
                                    </div>
                                </div>
                                <label class="toggle">
                                    <input type="checkbox" name="wa_on_assign" value="1"
                                           {{ ($settings['wa_on_assign'] ?? '0') === '1' ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            @if(($settings['email_on_assign'] ?? '0') === '1' || ($settings['wa_on_assign'] ?? '0') === '1')
                            <div style="margin-top:10px;padding:10px 12px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;font-size:11px;color:#92400E;display:flex;align-items:center;gap:8px;">
                                <i class="fas fa-circle-info" style="flex-shrink:0;"></i>
                                <span>
                                    @if(($settings['email_on_assign'] ?? '0') === '1') Email is active — make sure SMTP is configured in the <strong>Mail</strong> tab. @endif
                                    @if(($settings['wa_on_assign'] ?? '0') === '1') WhatsApp is active — make sure each employee has a phone number set on their profile. @endif
                                </span>
                            </div>
                            @endif
                        </div>

                    </div>
                    <div class="scard-footer">
                        <button type="submit" class="btn-save"><i class="fas fa-check" style="font-size:11px;margin-right:5px;"></i>Save Preferences</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ════ MAIL / SMTP ════ --}}
        <div x-show="tab === 'mail'" x-cloak x-data="{
            host:        '{{ addslashes($mail['host']        ?? '') }}',
            port:        '{{ $mail['port']        ?? 587 }}',
            encryption:  '{{ $mail['encryption']  ?? 'tls' }}',
            username:    '{{ addslashes($mail['username']    ?? '') }}',
            fromAddress: '{{ addslashes($mail['from_address'] ?? '') }}',
            fromName:    '{{ addslashes($mail['from_name']   ?? '') }}',
            showPw:      false,
            testEmail:   '',
            testing:     false,
            testResult:  '',
            testOk:      null,
            preset(p) {
                const presets = {
                    gmail:     { host:'smtp.gmail.com',       port:587, encryption:'tls'  },
                    outlook:   { host:'smtp.office365.com',   port:587, encryption:'starttls' },
                    mailgun:   { host:'smtp.mailgun.org',     port:587, encryption:'tls'  },
                    sendgrid:  { host:'smtp.sendgrid.net',    port:587, encryption:'tls'  },
                    mailtrap:  { host:'sandbox.smtp.mailtrap.io', port:2525, encryption:'tls' },
                };
                if (presets[p]) { this.host = presets[p].host; this.port = presets[p].port; this.encryption = presets[p].encryption; }
            },
            async sendTest() {
                if (!this.testEmail) return;
                this.testing = true; this.testResult = ''; this.testOk = null;
                try {
                    const fd = new FormData(document.getElementById('mail-form'));
                    fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
                    fd.append('to', this.testEmail);
                    const r = await fetch('{{ route('admin.settings.mail.test') }}', { method:'POST', body: fd });
                    const j = await r.json();
                    this.testOk = j.ok; this.testResult = j.message;
                } catch(e) { this.testOk = false; this.testResult = 'Request failed: ' + e.message; }
                this.testing = false;
            }
        }">
            <div class="scard">
                <div class="scard-header">
                    <div class="scard-icon" style="background:#EFF6FF;color:#2563EB;"><i class="fas fa-envelope"></i></div>
                    <div>
                        <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">Mail / SMTP</p>
                        <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Configure outgoing email for notifications and alerts</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.settings.mail') }}" id="mail-form">
                    @csrf
                    <div class="scard-body">

                        {{-- Quick Presets --}}
                        <div class="sf-group">
                            <label class="sf-label">Quick Presets</label>
                            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                @foreach([
                                    ['key'=>'gmail',    'label'=>'Gmail',     'color'=>'#EA4335'],
                                    ['key'=>'outlook',  'label'=>'Outlook',   'color'=>'#0078D4'],
                                    ['key'=>'mailgun',  'label'=>'Mailgun',   'color'=>'#F06B35'],
                                    ['key'=>'sendgrid', 'label'=>'SendGrid',  'color'=>'#1A82E2'],
                                    ['key'=>'mailtrap', 'label'=>'Mailtrap',  'color'=>'#16A34A'],
                                ] as $p)
                                <button type="button" @click="preset('{{ $p['key'] }}')"
                                        style="padding:6px 14px;font-size:12px;font-weight:600;border-radius:8px;border:1.5px solid {{ $p['color'] }}20;background:{{ $p['color'] }}10;color:{{ $p['color'] }};cursor:pointer;transition:all 0.15s;"
                                        onmouseover="this.style.background='{{ $p['color'] }}20'" onmouseout="this.style.background='{{ $p['color'] }}10'">
                                    {{ $p['label'] }}
                                </button>
                                @endforeach
                            </div>
                            <p class="sf-hint">Click a preset to auto-fill the server details below.</p>
                        </div>

                        {{-- Connection --}}
                        <div style="background:#F8FAFC;border:1px solid #E5E7EB;border-radius:10px;padding:16px 18px;margin-bottom:18px;">
                            <p style="font-size:12px;font-weight:700;color:#374151;margin:0 0 14px;text-transform:uppercase;letter-spacing:.05em;">Connection</p>
                            <div style="display:grid;grid-template-columns:1fr 120px 160px;gap:14px;align-items:end;">
                                <div class="sf-group" style="margin-bottom:0;">
                                    <label class="sf-label">SMTP Host</label>
                                    <input type="text" name="mail_host" class="sf-input" x-model="host"
                                           placeholder="smtp.example.com" required>
                                </div>
                                <div class="sf-group" style="margin-bottom:0;">
                                    <label class="sf-label">Port</label>
                                    <select name="mail_port" class="sf-input sf-select" x-model="port">
                                        <option value="25">25</option>
                                        <option value="465">465</option>
                                        <option value="587">587</option>
                                        <option value="2525">2525</option>
                                    </select>
                                </div>
                                <div class="sf-group" style="margin-bottom:0;">
                                    <label class="sf-label">Encryption</label>
                                    <select name="mail_encryption" class="sf-input sf-select" x-model="encryption">
                                        <option value="tls">TLS (STARTTLS)</option>
                                        <option value="ssl">SSL</option>
                                        <option value="starttls">STARTTLS</option>
                                        <option value="">None</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Authentication --}}
                        <div style="background:#F8FAFC;border:1px solid #E5E7EB;border-radius:10px;padding:16px 18px;margin-bottom:18px;">
                            <p style="font-size:12px;font-weight:700;color:#374151;margin:0 0 14px;text-transform:uppercase;letter-spacing:.05em;">Authentication</p>
                            <div class="sf-row">
                                <div class="sf-group" style="margin-bottom:0;">
                                    <label class="sf-label">Username</label>
                                    <input type="text" name="mail_username" class="sf-input" x-model="username"
                                           placeholder="you@example.com" autocomplete="off" required>
                                </div>
                                <div class="sf-group" style="margin-bottom:0;">
                                    <label class="sf-label">Password</label>
                                    <div style="position:relative;">
                                        <input :type="showPw ? 'text' : 'password'" name="mail_password" class="sf-input"
                                               placeholder="Leave blank to keep current" autocomplete="new-password"
                                               style="padding-right:40px;">
                                        <button type="button" @click="showPw=!showPw"
                                                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9CA3AF;padding:0;">
                                            <i :class="showPw ? 'fas fa-eye-slash' : 'fas fa-eye'" style="font-size:13px;"></i>
                                        </button>
                                    </div>
                                    <p class="sf-hint">Leave blank to keep the existing password.</p>
                                </div>
                            </div>
                        </div>

                        {{-- Sender Identity --}}
                        <div style="background:#F8FAFC;border:1px solid #E5E7EB;border-radius:10px;padding:16px 18px;margin-bottom:18px;">
                            <p style="font-size:12px;font-weight:700;color:#374151;margin:0 0 14px;text-transform:uppercase;letter-spacing:.05em;">Sender Identity</p>
                            <div class="sf-row">
                                <div class="sf-group" style="margin-bottom:0;">
                                    <label class="sf-label">From Address</label>
                                    <input type="email" name="mail_from_address" class="sf-input" x-model="fromAddress"
                                           placeholder="noreply@example.com" required>
                                </div>
                                <div class="sf-group" style="margin-bottom:0;">
                                    <label class="sf-label">From Name</label>
                                    <input type="text" name="mail_from_name" class="sf-input" x-model="fromName"
                                           placeholder="Task Manager" required>
                                </div>
                            </div>
                        </div>

                        {{-- Test Email --}}
                        <div style="border:1.5px solid #DBEAFE;border-radius:10px;padding:16px 18px;background:#EFF6FF;">
                            <p style="font-size:12px;font-weight:700;color:#1D4ED8;margin:0 0 12px;"><i class="fas fa-vial" style="margin-right:6px;"></i>Send Test Email</p>
                            <div style="display:flex;gap:10px;align-items:flex-end;">
                                <div style="flex:1;">
                                    <label class="sf-label">Recipient Address</label>
                                    <input type="email" class="sf-input" x-model="testEmail"
                                           placeholder="test@example.com" style="background:#fff;">
                                </div>
                                <button type="button" @click="sendTest()" :disabled="testing || !testEmail"
                                        style="padding:9px 18px;font-size:13px;font-weight:600;background:#2563EB;color:#fff;border:none;border-radius:9px;cursor:pointer;white-space:nowrap;transition:background 0.15s;flex-shrink:0;"
                                        :style="(testing || !testEmail) ? 'opacity:.6;cursor:not-allowed;' : ''"
                                        onmouseover="if(!this.disabled)this.style.background='#1D4ED8'" onmouseout="this.style.background='#2563EB'">
                                    <i class="fas fa-paper-plane" style="font-size:11px;margin-right:5px;"></i>
                                    <span x-text="testing ? 'Sending…' : 'Send Test'"></span>
                                </button>
                            </div>
                            <div x-show="testResult" x-cloak style="margin-top:10px;display:flex;align-items:center;gap:8px;font-size:13px;font-weight:500;padding:9px 13px;border-radius:8px;"
                                 :style="testOk ? 'background:#F0FDF4;color:#15803D;border:1px solid #BBF7D0;' : 'background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;'">
                                <i :class="testOk ? 'fas fa-circle-check' : 'fas fa-circle-xmark'"></i>
                                <span x-text="testResult"></span>
                            </div>
                        </div>

                    </div>
                    <div class="scard-footer">
                        <button type="submit" class="btn-save"><i class="fas fa-check" style="font-size:11px;margin-right:5px;"></i>Save Mail Settings</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ════ SECURITY ════ --}}
        <div x-show="tab === 'security'" x-cloak>
            <div class="scard">
                <div class="scard-header">
                    <div class="scard-icon" style="background:#FEF2F2;color:#DC2626;"><i class="fas fa-shield-halved"></i></div>
                    <div>
                        <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">Security</p>
                        <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Password policy and session management</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.settings.security') }}">
                    @csrf
                    <div class="scard-body">
                        <div class="sf-row">
                            <div class="sf-group">
                                <label class="sf-label">Minimum Password Length</label>
                                <input type="number" name="min_password_length" class="sf-input"
                                       value="{{ $settings['min_password_length'] }}" min="6" max="32">
                            </div>
                            <div class="sf-group">
                                <label class="sf-label">Session Timeout (minutes)</label>
                                <input type="number" name="session_timeout" class="sf-input"
                                       value="{{ $settings['session_timeout'] }}" min="15" max="1440">
                                <p class="sf-hint">Users are logged out after this period of inactivity.</p>
                            </div>
                        </div>
                        <div class="sf-row" style="margin-top:2px;">
                            <div class="sf-group" style="margin-bottom:0;">
                                <label class="sf-label">Max Login Attempts</label>
                                <input type="number" name="max_login_attempts" class="sf-input"
                                       value="{{ $settings['max_login_attempts'] ?? 5 }}" min="3" max="20">
                                <p class="sf-hint">Failed attempts before the account is temporarily locked. Default: 5.</p>
                            </div>
                        </div>
                        <div class="sf-toggle-row">
                            <div>
                                <p class="sf-toggle-label">Require Strong Passwords</p>
                                <p class="sf-toggle-hint">Passwords must contain uppercase, number and special character</p>
                            </div>
                            <label class="toggle">
                                <input type="checkbox" name="require_strong_password" value="1"
                                       {{ $settings['require_strong_password'] === '1' ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="scard-footer">
                        <button type="submit" class="btn-save"><i class="fas fa-check" style="font-size:11px;margin-right:5px;"></i>Save Security</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ════ BACKUP & EXPORT ════ --}}
        <div x-show="tab === 'backup'" x-cloak>

            <div class="scard" x-data="{ showRestore: false }" style="margin-bottom:16px;">

                {{-- Backup banner --}}
                <div style="background:linear-gradient(135deg,#1E1B4B 0%,#312E81 100%);border-radius:12px;padding:14px 18px;margin:20px 20px 0;display:flex;align-items:center;justify-content:space-between;gap:12px;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:36px;height:36px;border-radius:9px;background:rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-server" style="color:#fff;font-size:15px;"></i>
                        </div>
                        <div>
                            <p style="font-size:13px;font-weight:700;color:#fff;margin:0 0 2px;">Full System Backup</p>
                            <p style="font-size:11px;color:rgba(255,255,255,.55);margin:0;">All users, projects, tasks, settings &amp; notifications</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.settings.backup.download') }}"
                       style="display:flex;align-items:center;gap:7px;padding:8px 16px;background:#fff;color:#4F46E5;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;flex-shrink:0;transition:opacity .15s;"
                       onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                        <i class="fas fa-download" style="font-size:10px;"></i> Download Backup
                    </a>
                </div>

                {{-- Stats strip (horizontal) --}}
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;padding:14px 20px;">
                    @foreach([['users','#4F46E5','Users'],['projects','#10B981','Projects'],['tasks','#F59E0B','Tasks']] as [$sk,$sc,$sl])
                    <div style="background:#F9FAFB;border:1.5px solid #F0F0F0;border-radius:10px;padding:10px 14px;text-align:center;">
                        <p style="font-size:20px;font-weight:700;color:{{ $sc }};margin:0;line-height:1.1;">{{ $stats[$sk] }}</p>
                        <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;text-transform:uppercase;letter-spacing:.04em;">{{ $sl }}</p>
                    </div>
                    @endforeach
                    <div style="background:#F9FAFB;border:1.5px solid #F0F0F0;border-radius:10px;padding:10px 14px;text-align:center;">
                        <p style="font-size:18px;font-weight:700;color:#6366F1;margin:0;line-height:1.1;">{{ $stats['db_size'] }}<span style="font-size:10px;font-weight:500;">KB</span></p>
                        <p style="font-size:10px;color:#9CA3AF;margin:3px 0 0;text-transform:uppercase;letter-spacing:.04em;">DB Size</p>
                    </div>
                </div>

                {{-- Full System Restore (collapsible row) --}}
                <div style="border-top:1px solid #F3F4F6;padding:14px 20px 16px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:8px;background:#FEE2E2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas fa-rotate-left" style="color:#DC2626;font-size:13px;"></i>
                            </div>
                            <div>
                                <p style="font-size:13px;font-weight:700;color:#111827;margin:0;">Full System Restore</p>
                                <p style="font-size:11px;color:#9CA3AF;margin:1px 0 0;">Replace entire database with a <code style="background:#F3F4F6;padding:1px 4px;border-radius:3px;">.sqlite</code> backup</p>
                            </div>
                        </div>
                        <button type="button" @click="showRestore = !showRestore"
                                style="display:flex;align-items:center;gap:6px;padding:7px 14px;background:#DC2626;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;flex-shrink:0;"
                                onmouseover="this.style.background='#B91C1C'" onmouseout="this.style.background='#DC2626'">
                            <i class="fas fa-upload" style="font-size:10px;"></i>
                            <span x-text="showRestore ? 'Cancel' : 'Restore'"></span>
                        </button>
                    </div>
                    <div x-show="showRestore" x-cloak style="margin-top:12px;padding-top:12px;border-top:1px solid #FECACA;">
                        <p style="font-size:11px;color:#7F1D1D;background:#FEF2F2;border:1px solid #FECACA;border-radius:7px;padding:8px 12px;margin:0 0 10px;line-height:1.5;">
                            <i class="fas fa-triangle-exclamation" style="margin-right:5px;"></i><strong>Warning:</strong> This permanently replaces ALL current data. Download a backup first.
                        </p>
                        <form method="POST" action="{{ route('admin.settings.backup.restore') }}" enctype="multipart/form-data"
                              onsubmit="return confirm('Are you sure? This will replace ALL system data with the uploaded backup. This cannot be undone.')">
                            @csrf
                            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                <label style="flex:1;min-width:180px;display:flex;align-items:center;gap:8px;padding:8px 12px;border:2px dashed #FECACA;border-radius:8px;cursor:pointer;background:#fff;"
                                       onmouseover="this.style.borderColor='#DC2626'" onmouseout="this.style.borderColor='#FECACA'">
                                    <i class="fas fa-file" style="color:#DC2626;font-size:13px;flex-shrink:0;"></i>
                                    <span style="font-size:12px;color:#9CA3AF;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" id="backup-file-name">Choose .sqlite file…</span>
                                    <input type="file" name="backup_file" accept=".sqlite" required style="display:none;"
                                           onchange="document.getElementById('backup-file-name').textContent = this.files[0]?.name || 'Choose .sqlite file…'">
                                </label>
                                <button type="submit"
                                        style="padding:8px 18px;background:#DC2626;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;white-space:nowrap;flex-shrink:0;"
                                        onmouseover="this.style.background='#B91C1C'" onmouseout="this.style.background='#DC2626'">
                                    <i class="fas fa-rotate-left" style="font-size:10px;"></i> Restore Now
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

            {{-- Export + Restore CSV --}}
            <div class="scard">
                <div class="scard-header" style="padding-bottom:0;">
                    <div class="scard-icon" style="background:#F0FDF4;color:#16A34A;"><i class="fas fa-file-csv"></i></div>
                    <div>
                        <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">Export & Restore CSV</p>
                        <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Download CSVs or import them back — restore is non-destructive</p>
                    </div>
                </div>

                @if($errors->any())
                <div style="margin:0 20px;background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 14px;font-size:12px;color:#DC2626;">
                    <i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>{{ $errors->first() }}
                </div>
                @endif

                {{-- Entity rows --}}
                @php
                    $csvEntities = [
                        ['icon'=>'fa-users',          'label'=>'Users',    'desc'=>'Roles, task counts',              'color'=>'#4F46E5','bg'=>'#EEF2FF','border'=>'#C7D2FE','exportRoute'=>'admin.settings.export.users',    'restoreRoute'=>'admin.settings.restore.users',    'fileId'=>'users-csv',     'columns'=>'name, email, role'],
                        ['icon'=>'fa-square-check',   'label'=>'Tasks',    'desc'=>'Status, assignee, deadline',      'color'=>'#16A34A','bg'=>'#F0FDF4','border'=>'#BBF7D0','exportRoute'=>'admin.settings.export.tasks',    'restoreRoute'=>'admin.settings.restore.tasks',    'fileId'=>'tasks-csv',     'columns'=>'title, project, assigned to, deadline, priority, status'],
                        ['icon'=>'fa-diagram-project','label'=>'Projects', 'desc'=>'Status, task counts',             'color'=>'#D97706','bg'=>'#FFFBEB','border'=>'#FDE68A','exportRoute'=>'admin.settings.export.projects', 'restoreRoute'=>'admin.settings.restore.projects', 'fileId'=>'projects-csv',  'columns'=>'name, deadline, status'],
                    ];
                @endphp

                @foreach($csvEntities as $ent)
                <div style="display:grid;grid-template-columns:auto 1fr auto auto;align-items:center;gap:14px;padding:14px 20px;border-top:1px solid #F3F4F6;">
                    {{-- Icon + label --}}
                    <div style="width:36px;height:36px;border-radius:9px;background:{{ $ent['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas {{ $ent['icon'] }}" style="font-size:14px;color:{{ $ent['color'] }};"></i>
                    </div>
                    <div>
                        <p style="font-size:13px;font-weight:600;color:#111827;margin:0;">{{ $ent['label'] }}</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:1px 0 0;">{{ $ent['desc'] }} · <code style="font-size:10px;background:#F3F4F6;padding:1px 4px;border-radius:3px;">{{ $ent['columns'] }}</code></p>
                    </div>
                    {{-- Export button --}}
                    <a href="{{ route($ent['exportRoute']) }}"
                       style="display:flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;border:1.5px solid {{ $ent['border'] }};background:{{ $ent['bg'] }};color:{{ $ent['color'] }};white-space:nowrap;transition:opacity .15s;"
                       onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">
                        <i class="fas fa-download" style="font-size:10px;"></i> Export CSV
                    </a>
                    {{-- Restore form --}}
                    <form method="POST" action="{{ route($ent['restoreRoute']) }}" enctype="multipart/form-data"
                          style="display:flex;align-items:center;gap:6px;">
                        @csrf
                        <label style="display:flex;align-items:center;gap:6px;padding:7px 12px;border:1.5px dashed {{ $ent['border'] }};border-radius:8px;cursor:pointer;background:#fff;white-space:nowrap;"
                               onmouseover="this.style.borderColor='{{ $ent['color'] }}'" onmouseout="this.style.borderColor='{{ $ent['border'] }}'">
                            <i class="fas fa-file-csv" style="color:{{ $ent['color'] }};font-size:11px;flex-shrink:0;"></i>
                            <span style="font-size:11px;color:#9CA3AF;max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" id="{{ $ent['fileId'] }}-name">Choose CSV…</span>
                            <input type="file" name="file" accept=".csv,.txt" style="display:none;"
                                   onchange="document.getElementById('{{ $ent['fileId'] }}-name').textContent = this.files[0]?.name || 'Choose CSV…'">
                        </label>
                        <button type="submit"
                                style="display:flex;align-items:center;gap:5px;padding:7px 12px;background:{{ $ent['bg'] }};color:{{ $ent['color'] }};border:1.5px solid {{ $ent['border'] }};border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;"
                                onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">
                            <i class="fas fa-rotate-left" style="font-size:10px;"></i> Restore
                        </button>
                    </form>
                </div>
                @endforeach

            </div>

        </div>

        {{-- ════ DEVELOPER ════ --}}
        <div x-show="tab === 'developer'" x-cloak>

            {{-- Row 1: System Controls + Manager Access side by side --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">

                {{-- System Controls --}}
                <div class="scard" style="margin:0;">
                    <div class="scard-header" style="padding-bottom:0;">
                        <div class="scard-icon" style="background:#FEF3C7;color:#D97706;"><i class="fas fa-sliders"></i></div>
                        <div>
                            <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">System Controls</p>
                            <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Global system switches</p>
                        </div>
                    </div>
                    <div style="padding:0 20px 16px;display:flex;flex-direction:column;gap:0;">
                        {{-- Maintenance Mode --}}
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-top:1px solid #F3F4F6;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:28px;height:28px;border-radius:8px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fas fa-triangle-exclamation" style="font-size:11px;color:#D97706;"></i>
                                </div>
                                <div>
                                    <p style="font-size:12px;font-weight:600;color:#111827;margin:0;">Maintenance Mode</p>
                                    <p style="font-size:11px;color:#9CA3AF;margin:1px 0 0;" id="maintenance-status">
                                        {{ ($appSettings['maintenance_mode'] ?? '0') === '1' ? 'Active — admins only' : 'Inactive' }}
                                    </p>
                                </div>
                            </div>
                            <button id="maintenance-toggle" onclick="toggleMaintenance(this)"
                                    style="display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;border:none;cursor:pointer;transition:all .2s;flex-shrink:0;
                                           {{ ($appSettings['maintenance_mode'] ?? '0') === '1' ? 'background:#D97706;color:#fff;' : 'background:#F3F4F6;color:#374151;' }}">
                                <i class="fas {{ ($appSettings['maintenance_mode'] ?? '0') === '1' ? 'fa-toggle-on' : 'fa-toggle-off' }}" id="maintenance-icon"></i>
                                <span id="maintenance-label">{{ ($appSettings['maintenance_mode'] ?? '0') === '1' ? 'On' : 'Off' }}</span>
                            </button>
                        </div>
                        {{-- Developer Mode --}}
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-top:1px solid #F3F4F6;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:28px;height:28px;border-radius:8px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fas fa-code" style="font-size:11px;color:#6366F1;"></i>
                                </div>
                                <div>
                                    <p style="font-size:12px;font-weight:600;color:#111827;margin:0;">Developer Mode</p>
                                    <p style="font-size:11px;color:#9CA3AF;margin:1px 0 0;" id="dev-mode-status">
                                        {{ ($appSettings['developer_mode'] ?? '0') === '1' ? 'Active — click to hide sections' : 'Inactive' }}
                                    </p>
                                </div>
                            </div>
                            <button id="dev-mode-toggle" onclick="toggleDevMode(this)"
                                    style="display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;border:none;cursor:pointer;transition:all .2s;flex-shrink:0;
                                           {{ ($appSettings['developer_mode'] ?? '0') === '1' ? 'background:#6366F1;color:#fff;' : 'background:#F3F4F6;color:#374151;' }}">
                                <i class="fas {{ ($appSettings['developer_mode'] ?? '0') === '1' ? 'fa-toggle-on' : 'fa-toggle-off' }}" id="dev-mode-icon"></i>
                                <span id="dev-mode-label">{{ ($appSettings['developer_mode'] ?? '0') === '1' ? 'On' : 'Off' }}</span>
                            </button>
                        </div>
                        {{-- Hide Approval Customer Notify --}}
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-top:1px solid #F3F4F6;"
                             x-data="{ hideNotify: {{ ($appSettings['hide_approval_customer_notify'] ?? '0') === '1' ? 'true' : 'false' }} }">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:28px;height:28px;border-radius:8px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fas fa-paper-plane" style="font-size:11px;color:#D97706;"></i>
                                </div>
                                <div>
                                    <p style="font-size:12px;font-weight:600;color:#111827;margin:0;">Hide "Send to Customer"</p>
                                    <p style="font-size:11px;color:#9CA3AF;margin:1px 0 0;">Remove the customer notification section from the approval modal</p>
                                </div>
                            </div>
                            <button type="button"
                                    @click="fetch('{{ route('admin.settings.approval-customer-notify') }}', { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'} }).then(r=>r.json()).then(d=>{ hideNotify = d.hide_approval_customer_notify })"
                                    style="display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;border:none;cursor:pointer;transition:all .2s;flex-shrink:0;"
                                    :style="hideNotify ? 'background:#D97706;color:#fff;' : 'background:#F3F4F6;color:#374151;'">
                                <i class="fas" :class="hideNotify ? 'fa-toggle-on' : 'fa-toggle-off'"></i>
                                <span x-text="hideNotify ? 'On' : 'Off'"></span>
                            </button>
                        </div>
                        {{-- Hide Hourly Rate --}}
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-top:1px solid #F3F4F6;"
                             x-data="{ hideHourly: {{ ($appSettings['hide_hourly_rate'] ?? '0') === '1' ? 'true' : 'false' }} }">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:28px;height:28px;border-radius:8px;background:#ECFDF5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fas fa-dollar-sign" style="font-size:11px;color:#059669;"></i>
                                </div>
                                <div>
                                    <p style="font-size:12px;font-weight:600;color:#111827;margin:0;">Hide Hourly Rate</p>
                                    <p style="font-size:11px;color:#9CA3AF;margin:1px 0 0;">Hide hourly rate field in user edit &amp; billing columns in reports</p>
                                </div>
                            </div>
                            <button type="button"
                                    @click="fetch('{{ route('admin.settings.hourly-rate') }}', { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'} }).then(r=>r.json()).then(d=>{ hideHourly = d.hide_hourly_rate })"
                                    style="display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;border:none;cursor:pointer;transition:all .2s;flex-shrink:0;"
                                    :style="hideHourly ? 'background:#059669;color:#fff;' : 'background:#F3F4F6;color:#374151;'">
                                <i class="fas" :class="hideHourly ? 'fa-toggle-on' : 'fa-toggle-off'"></i>
                                <span x-text="hideHourly ? 'On' : 'Off'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Manager Access Controls --}}
                <div class="scard" style="margin:0;position:relative;overflow:hidden;"
                     id="manager-access-card"
                     x-data="{ rolesOn: {{ ($appSettings['manager_can_view_roles'] ?? '0') === '1' ? 'true' : 'false' }}, adminOn: {{ ($appSettings['manager_can_edit_admin'] ?? '0') === '1' ? 'true' : 'false' }} }">
                    <div class="scard-header" style="padding-bottom:0;">
                        <div class="scard-icon" style="background:#FEF2F2;color:#EF4444;"><i class="fas fa-shield-halved"></i></div>
                        <div>
                            <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">Manager Access</p>
                            <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">What managers can access</p>
                        </div>
                    </div>
                    <div style="padding:0 20px 16px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-top:1px solid #F3F4F6;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:28px;height:28px;border-radius:8px;background:#F5F3FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fas fa-id-badge" style="font-size:11px;color:#7C3AED;"></i>
                                </div>
                                <div>
                                    <p style="font-size:12px;font-weight:600;color:#111827;margin:0;">View Roles Page</p>
                                    <p style="font-size:11px;color:#9CA3AF;margin:1px 0 0;">Roles tab in Team page</p>
                                </div>
                            </div>
                            <button type="button"
                                    @click="fetch('{{ route('admin.settings.manager-roles-access') }}', { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'} }).then(r=>r.json()).then(d=>{ rolesOn = d.manager_can_view_roles })"
                                    style="display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;border:none;cursor:pointer;transition:all .2s;flex-shrink:0;"
                                    :style="rolesOn ? 'background:#6366F1;color:#fff;' : 'background:#F3F4F6;color:#374151;'">
                                <i class="fas" :class="rolesOn ? 'fa-toggle-on' : 'fa-toggle-off'"></i>
                                <span x-text="rolesOn ? 'On' : 'Off'"></span>
                            </button>
                        </div>
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-top:1px solid #F3F4F6;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:28px;height:28px;border-radius:8px;background:#FEF2F2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fas fa-user-pen" style="font-size:11px;color:#EF4444;"></i>
                                </div>
                                <div>
                                    <p style="font-size:12px;font-weight:600;color:#111827;margin:0;">Edit Admin Users</p>
                                    <p style="font-size:11px;color:#9CA3AF;margin:1px 0 0;">Edit, reset & deactivate admins</p>
                                </div>
                            </div>
                            <button type="button"
                                    @click="fetch('{{ route('admin.settings.manager-admin-access') }}', { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'} }).then(r=>r.json()).then(d=>{ adminOn = d.manager_can_edit_admin })"
                                    style="display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;border:none;cursor:pointer;transition:all .2s;flex-shrink:0;"
                                    :style="adminOn ? 'background:#EF4444;color:#fff;' : 'background:#F3F4F6;color:#374151;'">
                                <i class="fas" :class="adminOn ? 'fa-toggle-on' : 'fa-toggle-off'"></i>
                                <span x-text="adminOn ? 'On' : 'Off'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Row 2: Dashboard Sections --}}
            @php
                $hiddenKeys  = json_decode($appSettings['hidden_elements'] ?? '[]', true) ?: [];
                $shownExtras = json_decode($appSettings['shown_extras']    ?? '[]', true) ?: [];
                $defaultVisibleLabels = ['dash_stats'=>'Overview Cards','dash_task_analytics'=>'Task Analytics','dash_working_hours'=>'Working Hours','dash_project_stats'=>'Project Stats','dash_workload'=>'Task Workload','dash_customers'=>'Tasks by Customer','dash_recent_tasks'=>'Recent Tasks'];
                $extraLabels = ['dash_priority_chart'=>'Tasks by Priority','dash_team_performance'=>'Team Performance','dash_project_progress'=>'Project Progress'];
            @endphp
            <div class="scard" style="margin-bottom:16px;" id="dashboard-sections-card">
                <div class="scard-header" style="padding-bottom:0;">
                    <div class="scard-icon" style="background:#EEF2FF;color:#6366F1;"><i class="fas fa-table-cells-large"></i></div>
                    <div>
                        <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">Dashboard Sections</p>
                        <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Hide default widgets or enable extra charts</p>
                    </div>
                </div>

                {{-- Default sections as chips --}}
                <div style="border-top:1px solid #F3F4F6;padding:14px 20px 4px;">
                    <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 10px;">Default Sections</p>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;" id="hidden-list">
                        @foreach($defaultVisibleLabels as $hk => $hlabel)
                        @php $isHidden = in_array($hk, $hiddenKeys); @endphp
                        <div id="hidden-row-{{ $hk }}"
                             style="display:inline-flex;align-items:center;gap:7px;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:500;border:1.5px solid {{ $isHidden ? '#FECACA' : '#E0E7FF' }};background:{{ $isHidden ? '#FEF2F2' : '#EEF2FF' }};color:{{ $isHidden ? '#DC2626' : '#4F46E5' }};">
                            <i class="fas {{ $isHidden ? 'fa-eye-slash' : 'fa-eye' }}" style="font-size:10px;" id="chip-icon-{{ $hk }}"></i>
                            <span>{{ $hlabel }}</span>
                            @if($isHidden)
                            <button onclick="restoreElement('{{ $hk }}', this)"
                                    style="display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;border-radius:50%;background:#FECACA;border:none;cursor:pointer;padding:0;margin-left:2px;font-size:9px;color:#DC2626;"
                                    title="Restore">
                                <i class="fas fa-xmark"></i>
                            </button>
                            @else
                            <button onclick="hideElement('{{ $hk }}', this)"
                                    style="display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;border-radius:50%;background:#C7D2FE;border:none;cursor:pointer;padding:0;margin-left:2px;font-size:9px;color:#4F46E5;"
                                    title="Hide">
                                <i class="fas fa-minus"></i>
                            </button>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Extra charts as chips --}}
                <div style="border-top:1px solid #F3F4F6;padding:14px 20px 16px;margin-top:4px;">
                    <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 10px;">Additional Charts</p>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                        @foreach($extraLabels as $ek => $elabel)
                        @php $isAdded = in_array($ek, $shownExtras); @endphp
                        <div id="extra-row-{{ $ek }}"
                             style="display:inline-flex;align-items:center;gap:7px;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:500;border:1.5px solid {{ $isAdded ? '#BBF7D0' : '#E5E7EB' }};background:{{ $isAdded ? '#F0FDF4' : '#F9FAFB' }};color:{{ $isAdded ? '#059669' : '#6B7280' }};">
                            <i class="fas {{ $isAdded ? 'fa-eye' : 'fa-plus-circle' }}" style="font-size:10px;" id="extra-icon-{{ $ek }}"></i>
                            <span>{{ $elabel }}</span>
                            @if($isAdded)
                            <span style="font-size:9px;font-weight:700;background:#D1FAE5;color:#065F46;padding:1px 6px;border-radius:20px;">Active</span>
                            <button onclick="removeExtra('{{ $ek }}', this)"
                                    style="display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;border-radius:50%;background:#BBF7D0;border:none;cursor:pointer;padding:0;margin-left:2px;font-size:9px;color:#059669;"
                                    title="Remove">
                                <i class="fas fa-xmark"></i>
                            </button>
                            @else
                            <button onclick="addExtra('{{ $ek }}', this)"
                                    style="display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;border-radius:50%;background:#E5E7EB;border:none;cursor:pointer;padding:0;margin-left:2px;font-size:9px;color:#6B7280;"
                                    title="Add">
                                <i class="fas fa-plus"></i>
                            </button>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Row 3: Sidebar Navigation + Header Elements side by side --}}
            @php
                $navHiddenKeys = json_decode($appSettings['nav_hidden'] ?? '[]', true) ?: [];
                $navItems = [
                    'all'     => [
                        ['key'=>'nav_activities',  'icon'=>'fa-bolt',            'label'=>'Activities'],
                        ['key'=>'nav_messages',    'icon'=>'fa-comment-dots',    'label'=>'Messages'],
                        ['key'=>'nav_team',        'icon'=>'fa-users',           'label'=>'Team Members'],
                        ['key'=>'nav_calendar',    'icon'=>'fa-calendar-days',   'label'=>'Calendar'],
                    ],
                    'admin'   => [
                        ['key'=>'nav_overview',        'icon'=>'fa-table-cells-large', 'label'=>'Overview'],
                        ['key'=>'nav_projects',        'icon'=>'fa-diagram-project',   'label'=>'Projects'],
                        ['key'=>'nav_tasks',           'icon'=>'fa-list-check',        'label'=>'Tasks'],
                        ['key'=>'nav_approvals',       'icon'=>'fa-clipboard-check',   'label'=>'Approvals'],
                        ['key'=>'nav_customers',       'icon'=>'fa-building',          'label'=>'Customers'],
                        ['key'=>'nav_audit',           'icon'=>'fa-shield-halved',     'label'=>'Audit Log'],
                        ['key'=>'nav_reports',         'icon'=>'fa-chart-bar',         'label'=>'Reports'],
                        ['key'=>'nav_social_budget',   'icon'=>'fa-wallet',            'label'=>'Ad Budget'],
                        ['key'=>'nav_recent_projects', 'icon'=>'fa-clock-rotate-left', 'label'=>'Recent Projects'],
                    ],
                    'user'    => [
                        ['key'=>'nav_my_tasks',      'icon'=>'fa-square-check',    'label'=>'My Tasks'],
                        ['key'=>'nav_my_projects',   'icon'=>'fa-diagram-project', 'label'=>'My Projects'],
                        ['key'=>'nav_user_reports',  'icon'=>'fa-chart-bar',       'label'=>'My Reports'],
                    ],
                    'footer'  => [
                        ['key'=>'nav_settings',    'icon'=>'fa-gear',            'label'=>'Settings Link'],
                    ],
                ];
                $headerItems = [
                    ['key'=>'nav_search',        'icon'=>'fa-magnifying-glass',  'label'=>'Search Bar'],
                    ['key'=>'nav_history',        'icon'=>'fa-clock-rotate-left', 'label'=>'Page History'],
                    ['key'=>'nav_notifications', 'icon'=>'fa-bell',               'label'=>'Notifications Bell'],
                    ['key'=>'nav_online_users',  'icon'=>'fa-circle-dot',         'label'=>"Who's Online"],
                ];
            @endphp
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" id="nav-controls-grid">

                {{-- Sidebar Navigation --}}
                <div class="scard" style="margin:0;position:relative;overflow:hidden;" id="sidebar-nav-card">
                    <div class="scard-header" style="padding-bottom:0;">
                        <div class="scard-icon" style="background:#F5F3FF;color:#7C3AED;"><i class="fas fa-sidebar"></i></div>
                        <div>
                            <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">Sidebar Navigation</p>
                            <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Show or hide sidebar links</p>
                        </div>
                    </div>
                    @foreach(['all'=>'All Roles','admin'=>'Admin / Manager','user'=>'User Only','footer'=>'Footer'] as $grp => $grpLabel)
                    <div style="border-top:1px solid #F3F4F6;padding:10px 20px 6px;">
                        <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.07em;margin:0 0 8px;">{{ $grpLabel }}</p>
                        <div style="display:flex;flex-direction:column;gap:4px;">
                            @foreach($navItems[$grp] as $ni)
                            @php $niHidden = in_array($ni['key'], $navHiddenKeys); @endphp
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:7px 10px;background:{{ $niHidden ? '#FEF2F2' : '#F9FAFB' }};border-radius:8px;border:1px solid {{ $niHidden ? '#FECACA' : '#F0F0F0' }};" id="navrow-{{ $ni['key'] }}" data-active-color="#7C3AED">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <i class="fas {{ $ni['icon'] }}" style="font-size:10px;color:{{ $niHidden ? '#DC2626' : '#7C3AED' }};width:12px;text-align:center;"></i>
                                    <span style="font-size:12px;font-weight:500;color:{{ $niHidden ? '#DC2626' : '#374151' }};">{{ $ni['label'] }}</span>
                                </div>
                                @if($niHidden)
                                <button onclick="toggleNavItem('{{ $ni['key'] }}','show',this)"
                                        style="display:flex;align-items:center;gap:4px;padding:4px 10px;background:#EEF2FF;color:#4F46E5;border:1px solid #C7D2FE;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;">
                                    <i class="fas fa-eye" style="font-size:9px;"></i> Show
                                </button>
                                @else
                                <button onclick="toggleNavItem('{{ $ni['key'] }}','hide',this)"
                                        style="display:flex;align-items:center;gap:4px;padding:4px 10px;background:#F9FAFB;color:#9CA3AF;border:1px solid #E5E7EB;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;"
                                        onmouseover="this.style.background='#FEF2F2';this.style.color='#DC2626';this.style.borderColor='#FECACA';"
                                        onmouseout="this.style.background='#F9FAFB';this.style.color='#9CA3AF';this.style.borderColor='#E5E7EB';">
                                    <i class="fas fa-eye-slash" style="font-size:9px;"></i> Hide
                                </button>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                    <div style="height:6px;"></div>
                </div>

                {{-- Header Elements --}}
                <div class="scard" style="margin:0;position:relative;overflow:hidden;" id="header-elements-card">
                    <div class="scard-header" style="padding-bottom:0;">
                        <div class="scard-icon" style="background:#FFF7ED;color:#EA580C;"><i class="fas fa-bars-staggered"></i></div>
                        <div>
                            <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">Header Elements</p>
                            <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Top navigation bar controls</p>
                        </div>
                    </div>
                    <div style="border-top:1px solid #F3F4F6;padding:10px 20px 16px;">
                        <div style="display:flex;flex-direction:column;gap:4px;">
                            @foreach($headerItems as $hi)
                            @php $hiHidden = in_array($hi['key'], $navHiddenKeys); @endphp
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:7px 10px;background:{{ $hiHidden ? '#FEF2F2' : '#F9FAFB' }};border-radius:8px;border:1px solid {{ $hiHidden ? '#FECACA' : '#F0F0F0' }};" id="navrow-{{ $hi['key'] }}" data-active-color="#EA580C">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <i class="fas {{ $hi['icon'] }}" style="font-size:10px;color:{{ $hiHidden ? '#DC2626' : '#EA580C' }};width:12px;text-align:center;"></i>
                                    <span style="font-size:12px;font-weight:500;color:{{ $hiHidden ? '#DC2626' : '#374151' }};">{{ $hi['label'] }}</span>
                                </div>
                                @if($hiHidden)
                                <button onclick="toggleNavItem('{{ $hi['key'] }}','show',this)"
                                        style="display:flex;align-items:center;gap:4px;padding:4px 10px;background:#EEF2FF;color:#4F46E5;border:1px solid #C7D2FE;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;">
                                    <i class="fas fa-eye" style="font-size:9px;"></i> Show
                                </button>
                                @else
                                <button onclick="toggleNavItem('{{ $hi['key'] }}','hide',this)"
                                        style="display:flex;align-items:center;gap:4px;padding:4px 10px;background:#F9FAFB;color:#9CA3AF;border:1px solid #E5E7EB;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;"
                                        onmouseover="this.style.background='#FEF2F2';this.style.color='#DC2626';this.style.borderColor='#FECACA';"
                                        onmouseout="this.style.background='#F9FAFB';this.style.color='#9CA3AF';this.style.borderColor='#E5E7EB';">
                                    <i class="fas fa-eye-slash" style="font-size:9px;"></i> Hide
                                </button>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ════ WHATSAPP ════ --}}
        <div x-show="tab === 'whatsapp'" x-cloak
             x-data="{
                provider: '{{ $settings['wa_provider'] ?? 'ultramsg' }}',
                waEnabled: {{ ($settings['wa_enabled'] ?? '0') === '1' ? 'true' : 'false' }},
                bcastRecipients: 'all',
                bcastBody: `Dear {customer_name},\n\nWe hope you are doing great.\n\nPlease don't hesitate to contact us for any assistance.\n\nBest regards,\n{company}`,
                get bcastPreview() {
                    return this.bcastBody
                        .replace(/\{customer_name\}/g, 'Ahmed Al-Mansoori')
                        .replace(/\{customer_phone\}/g, '+97312345678')
                        .replace(/\{customer_email\}/g, 'ahmed@example.com')
                        .replace(/\{company\}/g, '{{ addslashes($settings['company_name'] ?? config('app.name')) }}');
                }
             }">

            <form method="POST" action="{{ route('admin.settings.whatsapp') }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start;">

                    {{-- LEFT: API Configuration --}}
                    <div class="scard">
                        <div class="scard-header">
                            <div class="scard-icon" style="background:#dcfce7;color:#16a34a;"><i class="fa-brands fa-whatsapp" style="font-size:16px;"></i></div>
                            <div style="flex:1;">
                                <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">WhatsApp API Configuration</p>
                                <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Connect your WhatsApp Business account to send messages</p>
                            </div>
                            <label class="toggle" style="flex-shrink:0;">
                                <input type="checkbox" name="wa_enabled" value="1" x-model="waEnabled"
                                       {{ ($settings['wa_enabled'] ?? '0') === '1' ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="scard-body">

                            {{-- Provider --}}
                            <div class="sf-group">
                                <label class="sf-label">Provider</label>
                                <select name="wa_provider" class="sf-input sf-select" x-model="provider">
                                    <option value="ultramsg">UltraMsg — Easiest setup</option>
                                    <option value="twilio">Twilio WhatsApp</option>
                                    <option value="meta">Meta Cloud API (Official)</option>
                                </select>
                            </div>

                            {{-- UltraMsg --}}
                            <div x-show="provider === 'ultramsg'">
                                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:12px;color:#166534;line-height:1.6;">
                                    <strong>UltraMsg</strong> is the simplest setup: create a free instance at <strong>ultramsg.com</strong>, scan the QR code with your WhatsApp, then paste the Instance ID and Token below.
                                </div>
                                <div class="sf-group">
                                    <label class="sf-label">Instance ID</label>
                                    <input type="text" name="wa_instance_id" class="sf-input"
                                           value="{{ $settings['wa_instance_id'] ?? '' }}"
                                           placeholder="instance12345">
                                </div>
                            </div>

                            {{-- Twilio --}}
                            <div x-show="provider === 'twilio'">
                                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:12px;color:#1e40af;line-height:1.6;">
                                    <strong>Twilio</strong>: Find your Account SID and Auth Token in the Twilio Console. The From Number should include country code (e.g. <code>+14155238886</code>).
                                </div>
                                <div class="sf-row">
                                    <div class="sf-group">
                                        <label class="sf-label">Account SID</label>
                                        <input type="text" name="wa_account_sid" class="sf-input"
                                               value="{{ $settings['wa_account_sid'] ?? '' }}"
                                               placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                                    </div>
                                    <div class="sf-group">
                                        <label class="sf-label">From Number</label>
                                        <input type="text" name="wa_from_number" class="sf-input"
                                               value="{{ $settings['wa_from_number'] ?? '' }}"
                                               placeholder="+14155238886">
                                    </div>
                                </div>
                            </div>

                            {{-- Meta --}}
                            <div x-show="provider === 'meta'">
                                <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:12px;color:#6b21a8;line-height:1.6;">
                                    <strong>Meta Cloud API</strong>: Get your credentials from <em>developers.facebook.com</em>. The WABA ID lets you send to any number (bypasses the test whitelist).
                                </div>
                                <div class="sf-row">
                                    <div class="sf-group">
                                        <label class="sf-label">Phone Number ID</label>
                                        <input type="text" name="wa_phone_number_id" class="sf-input"
                                               value="{{ $settings['wa_phone_number_id'] ?? '' }}"
                                               placeholder="1234567890">
                                    </div>
                                    <div class="sf-group">
                                        <label class="sf-label">WABA ID <span style="font-size:10px;font-weight:400;color:#9CA3AF;">optional</span></label>
                                        <input type="text" name="wa_waba_id" class="sf-input"
                                               value="{{ $settings['wa_waba_id'] ?? '' }}"
                                               placeholder="123456789012345">
                                        <p class="sf-hint">Found in Meta Business Settings → Accounts → WhatsApp Accounts.</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Token --}}
                            <div class="sf-group" style="margin-bottom:0;">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                                    <label class="sf-label" style="margin:0;">API Token / Auth Token</label>
                                    @if(!empty($settings['wa_token']))
                                        <span style="font-size:11px;color:#16a34a;background:#dcfce7;border:1px solid #bbf7d0;border-radius:6px;padding:2px 8px;"><i class="fas fa-lock" style="font-size:10px;"></i> Token saved</span>
                                    @endif
                                </div>
                                <input type="password" name="wa_token" class="sf-input"
                                       placeholder="{{ !empty($settings['wa_token']) ? 'Leave blank to keep current token' : 'Paste your API token here' }}"
                                       autocomplete="new-password">
                            </div>
                        </div>
                        <div class="scard-footer">
                            <button type="submit" class="btn-save"><i class="fas fa-check" style="margin-right:6px;font-size:11px;"></i>Save WhatsApp Settings</button>
                        </div>
                    </div>

                    {{-- RIGHT: Test + Guides --}}
                    <div style="display:flex;flex-direction:column;gap:16px;">

                        {{-- Test Connection --}}
                        <div class="scard" style="border-color:#bbf7d0 !important;">
                            <div class="scard-header" style="background:#f0fdf4;">
                                <div class="scard-icon" style="background:#dcfce7;color:#16a34a;font-size:13px;"><i class="fa-brands fa-whatsapp"></i></div>
                                <div>
                                    <p style="font-size:13px;font-weight:700;color:#111827;margin:0;">Test Connection</p>
                                    <p style="font-size:11px;color:#9CA3AF;margin:1px 0 0;">Save settings first, then test</p>
                                </div>
                            </div>
                            <div class="scard-body">
                                <div x-show="provider === 'meta'" style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:8px;padding:10px 12px;margin-bottom:12px;font-size:11px;color:#6b21a8;line-height:1.5;">
                                    <i class="fas fa-info-circle" style="margin-right:4px;"></i>
                                    <strong>Meta:</strong> Sends the pre-approved <code>hello_world</code> template. Recipient must be in your Meta test whitelist first.
                                </div>
                                <div class="sf-group" style="margin-bottom:12px;">
                                    <label class="sf-label">Test Phone Number</label>
                                    <input type="text" id="waTestPhone" class="sf-input" placeholder="+97312345678">
                                </div>
                                <div id="waTestResult" style="margin-bottom:10px;font-size:12px;"></div>
                                <button type="button" id="waTestBtn"
                                        style="width:100%;padding:9px;font-size:13px;font-weight:600;background:#25D366;color:#fff;border:none;border-radius:9px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;"
                                        onclick="testWaConnection()">
                                    <i class="fa-brands fa-whatsapp"></i> Send Test Message
                                </button>
                            </div>
                        </div>

                        {{-- Setup Guides --}}
                        <div class="scard">
                            <div class="scard-header">
                                <div class="scard-icon" style="background:#F3F4F6;color:#6B7280;font-size:13px;"><i class="fas fa-book-open"></i></div>
                                <div>
                                    <p style="font-size:13px;font-weight:700;color:#111827;margin:0;">Setup Guides</p>
                                    <p style="font-size:11px;color:#9CA3AF;margin:1px 0 0;">Official documentation links</p>
                                </div>
                            </div>
                            <div class="scard-body" style="display:flex;flex-direction:column;gap:8px;">
                                <a href="https://ultramsg.com" target="_blank" rel="noopener"
                                   style="display:flex;align-items:center;gap:8px;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;font-weight:600;color:#374151;text-decoration:none;transition:border-color .15s;"
                                   onmouseover="this.style.borderColor='#6366F1'" onmouseout="this.style.borderColor='#E5E7EB'">
                                    <i class="fas fa-arrow-up-right-from-square" style="font-size:11px;color:#9CA3AF;"></i>UltraMsg Dashboard
                                </a>
                                <a href="https://console.twilio.com" target="_blank" rel="noopener"
                                   style="display:flex;align-items:center;gap:8px;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;font-weight:600;color:#374151;text-decoration:none;transition:border-color .15s;"
                                   onmouseover="this.style.borderColor='#6366F1'" onmouseout="this.style.borderColor='#E5E7EB'">
                                    <i class="fas fa-arrow-up-right-from-square" style="font-size:11px;color:#9CA3AF;"></i>Twilio Console
                                </a>
                                <a href="https://developers.facebook.com/docs/whatsapp" target="_blank" rel="noopener"
                                   style="display:flex;align-items:center;gap:8px;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;font-weight:600;color:#374151;text-decoration:none;transition:border-color .15s;"
                                   onmouseover="this.style.borderColor='#6366F1'" onmouseout="this.style.borderColor='#E5E7EB'">
                                    <i class="fas fa-arrow-up-right-from-square" style="font-size:11px;color:#9CA3AF;"></i>Meta WhatsApp Docs
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Message Templates (full width) --}}
            <form method="POST" action="{{ route('admin.settings.whatsapp') }}" style="margin-top:20px;">
                @csrf
                <input type="hidden" name="wa_provider" value="{{ $settings['wa_provider'] ?? 'ultramsg' }}">
                <input type="hidden" name="wa_enabled" value="{{ $settings['wa_enabled'] ?? '0' }}">
                <div class="scard">
                    <div class="scard-header">
                        <div class="scard-icon" style="background:#F3F4F6;color:#374151;font-size:13px;"><i class="fas fa-comment-dots"></i></div>
                        <div style="flex:1;">
                            <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">Message Templates</p>
                            <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Customise automated messages — variables are replaced per recipient</p>
                        </div>
                    </div>
                    <div class="scard-body">

                        {{-- Variables reference --}}
                        <div style="background:#f4f7fe;border:1px dashed #c7d2fe;border-radius:10px;padding:12px 16px;margin-bottom:20px;">
                            <p style="font-size:11px;font-weight:700;color:#4338CA;margin:0 0 6px;">Available variables:</p>
                            <div style="display:flex;flex-wrap:wrap;gap:6px;">
                                @foreach(['{user_name}','{task_title}','{project_name}','{customer_name}','{deadline}','{days_left}','{status}','{company}'] as $var)
                                <code style="background:#eef2ff;color:#4338CA;border:1px solid #c7d2fe;border-radius:5px;padding:2px 8px;font-size:11px;cursor:pointer;"
                                      title="Click to copy" onclick="navigator.clipboard.writeText('{{ $var }}')">{{ $var }}</code>
                                @endforeach
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">
                            @php
                            $templates = [
                                ['key'=>'wa_tpl_assigned',  'icon'=>'fa-user-plus',         'color'=>'#4F46E5', 'label'=>'Task Assigned',          'hint'=>'Sent to user when a task is assigned to them'],
                                ['key'=>'wa_tpl_approved',  'icon'=>'fa-circle-check',       'color'=>'#16a34a', 'label'=>'Task Approved',           'hint'=>'Sent when admin approves a submitted task'],
                                ['key'=>'wa_tpl_reminder',  'icon'=>'fa-clock',              'color'=>'#D97706', 'label'=>'Deadline Reminder',       'hint'=>'Sent X days before the task deadline'],
                                ['key'=>'wa_tpl_overdue',   'icon'=>'fa-triangle-exclamation','color'=>'#DC2626', 'label'=>'Overdue Alert',           'hint'=>'Automated daily alert for overdue tasks'],
                                ['key'=>'wa_tpl_social',    'icon'=>'fa-share-nodes',        'color'=>'#7C3AED', 'label'=>'Social Media Assigned',   'hint'=>'Sent when a task is assigned for social posting'],
                            ];
                            @endphp
                            @foreach($templates as $tpl)
                            <div>
                                <label class="sf-label" style="display:flex;align-items:center;gap:6px;">
                                    <i class="fas {{ $tpl['icon'] }}" style="color:{{ $tpl['color'] }};font-size:11px;"></i>
                                    {{ $tpl['label'] }}
                                </label>
                                <p style="font-size:11px;color:#9CA3AF;margin:-4px 0 6px;">{{ $tpl['hint'] }}</p>
                                <textarea name="{{ $tpl['key'] }}" rows="7"
                                          style="width:100%;padding:9px 12px;font-size:12px;font-family:monospace;border:1.5px solid #E5E7EB;border-radius:9px;color:#111827;resize:vertical;outline:none;"
                                          onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">{{ $settings[$tpl['key']] ?? '' }}</textarea>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="scard-footer">
                        <button type="submit" class="btn-save"><i class="fas fa-check" style="margin-right:6px;font-size:11px;"></i>Save Templates</button>
                    </div>
                </div>
            </form>

            {{-- Broadcast to Customers (full width) --}}
            <div class="scard" style="margin-top:20px;border-color:#bbf7d0 !important;">
                <div class="scard-header" style="justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div class="scard-icon" style="background:#dcfce7;color:#16a34a;font-size:13px;"><i class="fas fa-bullhorn"></i></div>
                        <div>
                            <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">Send WhatsApp to Customers</p>
                            <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Broadcast a personalised message to all customers or custom numbers</p>
                        </div>
                    </div>
                    <div style="font-size:12px;color:#6B7280;">
                        <i class="fa-brands fa-whatsapp" style="color:#25D366;margin-right:4px;"></i>
                        {{ \App\Models\Customer::whereNotNull('phone')->where('phone','!=','')->count() }} customers with phone
                    </div>
                </div>
                <div class="scard-body">

                    {{-- Variable reference --}}
                    <div style="background:#f0fdf4;border:1px dashed #86efac;border-radius:10px;padding:12px 16px;margin-bottom:18px;">
                        <p style="font-size:11px;font-weight:700;color:#15803d;margin:0 0 8px;"><i class="fas fa-braces" style="margin-right:4px;"></i>Variables replaced per customer:</p>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;">
                            @foreach(['{customer_name}','{customer_phone}','{customer_email}','{company}'] as $var)
                            <code style="background:#dcfce7;color:#15803d;border:1px solid #86efac;border-radius:5px;padding:2px 8px;font-size:11px;cursor:pointer;"
                                  title="Click to insert" onclick="waInsertVar('{{ $var }}')">{{ $var }}</code>
                            @endforeach
                        </div>
                        <p style="font-size:11px;color:#6B7280;margin:8px 0 0;"><i class="fas fa-cursor" style="margin-right:4px;"></i>Click a variable to insert it at the cursor position.</p>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                        <div>
                            <div class="sf-group">
                                <label class="sf-label">Recipients</label>
                                <select class="sf-input sf-select" x-model="bcastRecipients">
                                    <option value="all">All customers with phone ({{ \App\Models\Customer::whereNotNull('phone')->where('phone','!=','')->count() }})</option>
                                    <option value="custom">Custom phone numbers</option>
                                </select>
                            </div>

                            <div x-show="bcastRecipients === 'custom'" class="sf-group">
                                <label class="sf-label">Phone Numbers <span style="font-weight:400;color:#9CA3AF;">one per line or comma-separated</span></label>
                                <textarea id="waBcastNumbers" rows="4"
                                          style="width:100%;padding:9px 12px;font-size:12px;font-family:monospace;border:1.5px solid #E5E7EB;border-radius:9px;resize:vertical;"
                                          placeholder="+97312345678&#10;+97387654321"></textarea>
                                <p class="sf-hint" id="waBcastNumCount">0 numbers entered.</p>
                            </div>

                            <div class="sf-group" style="margin-bottom:0;">
                                <label class="sf-label">Message</label>
                                <textarea id="waBcastBody" rows="8"
                                          style="width:100%;padding:9px 12px;font-size:12px;font-family:monospace;border:1.5px solid #E5E7EB;border-radius:9px;resize:vertical;"
                                          x-model="bcastBody"
                                          onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'"></textarea>
                                <p class="sf-hint"><span x-text="bcastBody.length"></span> characters</p>
                            </div>
                        </div>

                        <div>
                            <label class="sf-label" style="color:#16a34a;">Live Preview <span style="font-weight:400;color:#9CA3AF;">(sample substitution)</span></label>
                            <div x-text="bcastPreview"
                                 style="background:#e9fbe9;border:1.5px solid #86efac;border-radius:10px;padding:14px 16px;font-size:13px;white-space:pre-wrap;min-height:200px;line-height:1.65;color:#1B2559;"></div>
                        </div>
                    </div>

                    <div style="margin-top:16px;">
                        <div id="waBcastResult" style="margin-bottom:10px;font-size:12px;"></div>
                        <button type="button" id="waBcastBtn"
                                style="padding:9px 22px;font-size:13px;font-weight:600;background:#25D366;color:#fff;border:none;border-radius:9px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"
                                onclick="sendWaBroadcast()">
                            <i class="fa-brands fa-whatsapp"></i> Send WhatsApp Messages
                        </button>
                        @if(($settings['wa_enabled'] ?? '0') !== '1')
                        <span style="font-size:12px;color:#9CA3AF;margin-left:12px;"><i class="fas fa-info-circle" style="margin-right:4px;"></i>Enable and save WhatsApp settings first.</span>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        {{-- ════ CLEAR DATA ════ --}}
        <div x-show="tab === 'danger'" x-cloak>

            {{-- Warning banner --}}
            <div style="background:#FEF2F2;border:1.5px solid #FECACA;border-radius:12px;padding:14px 18px;display:flex;align-items:flex-start;gap:12px;margin-bottom:20px;">
                <i class="fas fa-triangle-exclamation" style="color:#DC2626;margin-top:2px;flex-shrink:0;font-size:15px;"></i>
                <div>
                    <p style="font-size:13px;font-weight:700;color:#DC2626;margin:0 0 3px;">Danger Zone</p>
                    <p style="font-size:12px;color:#B91C1C;margin:0;line-height:1.6;">These actions permanently delete data and <strong>cannot be undone</strong>. Users and system settings are never affected.</p>
                </div>
            </div>

            {{-- Clear options --}}
            <div class="scard">
                <div class="scard-header">
                    <div class="scard-icon" style="background:#FEF2F2;color:#DC2626;"><i class="fas fa-trash-can"></i></div>
                    <div>
                        <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">Clear Data</p>
                        <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Select what to permanently remove from the system</p>
                    </div>
                </div>
                <div class="scard-body" style="padding:0;">

                    @php
                    $clearItems = [
                        ['type'=>'notifications',  'icon'=>'fa-bell',              'bg'=>'#EEF2FF', 'ic'=>'#4F46E5', 'label'=>'Notifications',        'desc'=>'All read and unread notifications for every user'],
                        ['type'=>'messages',        'icon'=>'fa-envelope',          'bg'=>'#F0FDF4', 'ic'=>'#16A34A', 'label'=>'Messages',              'desc'=>'All direct messages between team members'],
                        ['type'=>'audit_logs',      'icon'=>'fa-list-check',        'bg'=>'#FFFBEB', 'ic'=>'#D97706', 'label'=>'Audit Logs',            'desc'=>'Full history of admin actions and system events'],
                        ['type'=>'task_activity',   'icon'=>'fa-clock-rotate-left', 'bg'=>'#F5F3FF', 'ic'=>'#7C3AED', 'label'=>'Task Activity',        'desc'=>'Task logs, comments and submission history'],
                        ['type'=>'tasks_projects',  'icon'=>'fa-diagram-project',   'bg'=>'#FFF7ED', 'ic'=>'#EA580C', 'label'=>'All Tasks & Projects', 'desc'=>'Every task, project and their related files'],
                    ];
                    @endphp

                    @foreach($clearItems as $item)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;border-bottom:1px solid #F3F4F6;">
                        <div style="display:flex;align-items:center;gap:14px;">
                            <div style="width:38px;height:38px;border-radius:10px;background:{{ $item['bg'] }};color:{{ $item['ic'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:15px;">
                                <i class="fas {{ $item['icon'] }}"></i>
                            </div>
                            <div>
                                <p style="font-size:13px;font-weight:600;color:#111827;margin:0 0 2px;">{{ $item['label'] }}</p>
                                <p style="font-size:11px;color:#9CA3AF;margin:0;">{{ $item['desc'] }}</p>
                            </div>
                        </div>
                        <button type="button"
                                @click="openClear('{{ $item['type'] }}')"
                                style="padding:7px 16px;font-size:12px;font-weight:600;background:#FEF2F2;color:#DC2626;border:1.5px solid #FECACA;border-radius:8px;cursor:pointer;white-space:nowrap;flex-shrink:0;"
                                onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'">
                            <i class="fas fa-trash" style="font-size:10px;margin-right:5px;"></i>Clear
                        </button>
                    </div>
                    @endforeach

                    {{-- Full Reset row --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;background:#FEF2F2;border-top:2px dashed #FECACA;">
                        <div style="display:flex;align-items:center;gap:14px;">
                            <div style="width:38px;height:38px;border-radius:10px;background:#DC2626;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:15px;">
                                <i class="fas fa-bomb"></i>
                            </div>
                            <div>
                                <p style="font-size:13px;font-weight:700;color:#DC2626;margin:0 0 2px;">Full Data Reset</p>
                                <p style="font-size:11px;color:#B91C1C;margin:0;">Clears everything above at once — users &amp; settings are kept</p>
                            </div>
                        </div>
                        <button type="button"
                                @click="openClear('full_reset')"
                                style="padding:7px 16px;font-size:12px;font-weight:700;background:#DC2626;color:#fff;border:none;border-radius:8px;cursor:pointer;white-space:nowrap;flex-shrink:0;"
                                onmouseover="this.style.background='#B91C1C'" onmouseout="this.style.background='#DC2626'">
                            <i class="fas fa-bomb" style="font-size:10px;margin-right:5px;"></i>Full Reset
                        </button>
                    </div>

                </div>
            </div>

        </div>
        {{-- ════ END CLEAR DATA ════ --}}

        {{-- ════ CONFIRMATION MODAL (teleported to <body> to escape any overflow/z-index traps) ════ --}}
        <template x-teleport="body">
            <div x-show="confirm !== null"
                 x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 @keydown.escape.window="closeClear()"
                 style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:99999;">
                <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:24px;">
                <div style="background:#fff;border-radius:16px;width:100%;max-width:440px;box-shadow:0 25px 60px rgba(0,0,0,0.3);" @click.stop>

                    {{-- Header --}}
                    <div style="padding:24px 24px 0;">
                        <div style="width:52px;height:52px;background:#FEF2F2;border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
                            <i class="fas fa-triangle-exclamation" style="color:#DC2626;font-size:22px;"></i>
                        </div>
                        <p style="font-size:17px;font-weight:700;color:#111827;margin:0 0 8px;">Are you absolutely sure?</p>
                        <p style="font-size:13px;color:#6B7280;margin:0 0 20px;line-height:1.65;">
                            This will <strong style="color:#DC2626;">permanently delete</strong> the selected data and cannot be undone.<br>
                            Type <strong style="color:#DC2626;letter-spacing:0.05em;">DELETE</strong> below to confirm.
                        </p>
                        <input type="text"
                               x-model="phrase"
                               placeholder="Type DELETE to confirm"
                               autocomplete="off"
                               style="width:100%;padding:10px 14px;font-size:13px;border:1.5px solid #E5E7EB;border-radius:9px;outline:none;font-family:'Inter',sans-serif;box-sizing:border-box;transition:border-color 0.15s;"
                               :style="phrase === 'DELETE' ? 'border-color:#16A34A;background:#F0FDF4;' : ''"
                               @keydown.enter="if(phrase === 'DELETE') $refs.clearBtn.click()">
                    </div>

                    {{-- Footer --}}
                    <div style="padding:16px 24px 24px;display:flex;gap:10px;justify-content:flex-end;">
                        <button type="button"
                                @click="closeClear()"
                                style="padding:10px 20px;font-size:13px;font-weight:500;background:#F3F4F6;color:#374151;border:none;border-radius:9px;cursor:pointer;">
                            Cancel
                        </button>
                        <form method="POST" action="{{ route('admin.settings.clear') }}" style="margin:0;">
                            @csrf
                            <input type="hidden" name="type" :value="confirm">
                            <button type="submit"
                                    x-ref="clearBtn"
                                    :disabled="phrase !== 'DELETE'"
                                    style="padding:10px 20px;font-size:13px;font-weight:500;background:#DC2626;color:#fff;border:none;border-radius:9px;cursor:pointer;"
                                    :style="phrase !== 'DELETE' ? 'opacity:0.5;cursor:not-allowed;' : 'opacity:1;cursor:pointer;'"
                                    onmouseover="if(!this.disabled)this.style.background='#B91C1C'"
                                    onmouseout="this.style.background='#DC2626'">
                                <i class="fas fa-trash" style="font-size:11px;margin-right:6px;"></i>Yes, Delete
                            </button>
                        </form>
                    </div>

                </div>
                </div>
            </div>
        </template>

    </div>{{-- end settings-panel --}}
</div>{{-- end settings-wrap --}}

<script>
const _devToggleUrl         = '{{ route('admin.settings.dev-mode') }}';
const _maintenanceToggleUrl = '{{ route('admin.settings.maintenance') }}';
const _devElementsUrl       = '{{ route('admin.settings.elements.toggle') }}';
const _csrfToken            = '{{ csrf_token() }}';

function _lockCard(id, message) {
    const card = document.getElementById(id);
    if (!card || card.getAttribute('data-locked') === '1') return;
    card.setAttribute('data-locked', '1');

    // Lock badge in header
    const header = card.querySelector('.scard-header');
    if (header && !header.querySelector('.lock-badge')) {
        const badge = document.createElement('span');
        badge.className = 'lock-badge';
        badge.style.cssText = 'display:inline-flex;align-items:center;gap:5px;padding:3px 10px;background:#F3F4F6;border-radius:20px;font-size:11px;font-weight:600;color:#9CA3AF;margin-left:auto;flex-shrink:0;';
        badge.innerHTML = '<i class="fas fa-lock" style="font-size:9px;"></i> Locked';
        badge.title = message;
        header.style.alignItems = 'center';
        header.appendChild(badge);
    }

    // Disable all action buttons inside the card body
    card.querySelectorAll('button').forEach(function (btn) {
        // Skip the scard-header itself (no buttons there, but safety check)
        if (header && header.contains(btn)) return;
        btn.setAttribute('data-was-disabled', btn.disabled ? '1' : '0');
        btn.disabled = true;
        btn.style.opacity = '0.38';
        btn.style.cursor  = 'not-allowed';
        btn.style.pointerEvents = 'none';
    });
}

function _unlockCard(id) {
    const card = document.getElementById(id);
    if (!card || card.getAttribute('data-locked') !== '1') return;
    card.removeAttribute('data-locked');

    // Remove lock badge
    const badge = card.querySelector('.lock-badge');
    if (badge) badge.remove();

    // Re-enable buttons
    card.querySelectorAll('button').forEach(function (btn) {
        if (btn.getAttribute('data-was-disabled') !== '1') {
            btn.disabled = false;
            btn.style.opacity = '';
            btn.style.cursor  = '';
            btn.style.pointerEvents = '';
        }
        btn.removeAttribute('data-was-disabled');
    });
}

function toggleMaintenance(btn) {
    fetch(_maintenanceToggleUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': _csrfToken, 'Content-Type': 'application/json' } })
        .then(r => r.json())
        .then(d => {
            const on = d.maintenance_mode;
            btn.style.background = on ? '#D97706' : '#F3F4F6';
            btn.style.color      = on ? '#fff'    : '#374151';
            document.getElementById('maintenance-icon').className  = 'fas ' + (on ? 'fa-toggle-on' : 'fa-toggle-off');
            document.getElementById('maintenance-label').textContent = on ? 'On' : 'Off';
            document.getElementById('maintenance-status').textContent = on
                ? 'Active — admins only'
                : 'Inactive';
            if (on) _unlockCard('manager-access-card');
            else    _lockCard('manager-access-card', 'Enable Maintenance Mode to edit manager access');
        });
}

function toggleDevMode(btn) {
    fetch(_devToggleUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': _csrfToken, 'Content-Type': 'application/json' } })
        .then(r => r.json())
        .then(d => {
            const on = d.developer_mode;
            btn.style.background = on ? '#6366F1' : '#F3F4F6';
            btn.style.color      = on ? '#fff'    : '#374151';
            document.getElementById('dev-mode-icon').className  = 'fas ' + (on ? 'fa-toggle-on' : 'fa-toggle-off');
            document.getElementById('dev-mode-label').textContent = on ? 'On' : 'Off';
            document.getElementById('dev-mode-status').textContent = on
                ? 'Active — click sections on the dashboard to remove them'
                : 'Inactive — enable to customise the dashboard layout';
            if (on) { _unlockCard('sidebar-nav-card'); _unlockCard('header-elements-card'); _unlockCard('dashboard-sections-card'); }
            else    { _lockCard('sidebar-nav-card', 'Enable Developer Mode to edit navigation'); _lockCard('header-elements-card', 'Enable Developer Mode to edit navigation'); _lockCard('dashboard-sections-card', 'Enable Developer Mode to edit dashboard sections'); }
            if (typeof window._devModeChanged === 'function') window._devModeChanged(on);
        });
}

// Initialise lock state on page load
document.addEventListener('DOMContentLoaded', function () {
    @if(($appSettings['developer_mode'] ?? '0') !== '1')
    _lockCard('sidebar-nav-card',        'Enable Developer Mode to edit navigation');
    _lockCard('header-elements-card',    'Enable Developer Mode to edit navigation');
    _lockCard('dashboard-sections-card', 'Enable Developer Mode to edit dashboard sections');
    @endif
    @if(($appSettings['maintenance_mode'] ?? '0') !== '1')
    _lockCard('manager-access-card', 'Enable Maintenance Mode to edit manager access');
    @endif
});

function restoreElement(key, btn) {
    btn.disabled = true;
    fetch(_devElementsUrl, { method:'POST', headers:{'X-CSRF-TOKEN':_csrfToken,'Content-Type':'application/json'}, body:JSON.stringify({key, action:'restore'}) })
        .then(r => r.json())
        .then(() => { _chipSetVisible(key); })
        .catch(() => { btn.disabled = false; });
}

function hideElement(key, btn) {
    btn.disabled = true;
    fetch(_devElementsUrl, { method:'POST', headers:{'X-CSRF-TOKEN':_csrfToken,'Content-Type':'application/json'}, body:JSON.stringify({key, action:'hide'}) })
        .then(r => r.json())
        .then(() => { _chipSetHidden(key); })
        .catch(() => { btn.disabled = false; });
}

function addExtra(key, btn) {
    btn.disabled = true;
    fetch(_devElementsUrl, { method:'POST', headers:{'X-CSRF-TOKEN':_csrfToken,'Content-Type':'application/json'}, body:JSON.stringify({key, action:'add'}) })
        .then(r => r.json())
        .then(() => { _extraSetActive(key); })
        .catch(() => { btn.disabled = false; });
}

function removeExtra(key, btn) {
    btn.disabled = true;
    fetch(_devElementsUrl, { method:'POST', headers:{'X-CSRF-TOKEN':_csrfToken,'Content-Type':'application/json'}, body:JSON.stringify({key, action:'remove'}) })
        .then(r => r.json())
        .then(() => { _extraSetInactive(key); })
        .catch(() => { btn.disabled = false; });
}

// Update a default-section chip to VISIBLE state
function _chipSetVisible(key) {
    const chip = document.getElementById('hidden-row-' + key);
    if (!chip) return;
    chip.style.background  = '#EEF2FF';
    chip.style.borderColor = '#E0E7FF';
    chip.style.color       = '#4F46E5';
    const icon = chip.querySelector('i');
    if (icon) { icon.className = 'fas fa-eye'; icon.style.fontSize = '10px'; }
    const xBtn = chip.querySelector('button');
    if (xBtn) {
        xBtn.style.background = '#C7D2FE';
        xBtn.style.color      = '#4F46E5';
        xBtn.innerHTML        = '<i class="fas fa-minus"></i>';
        xBtn.title            = 'Hide';
        xBtn.onclick          = function() { hideElement(key, this); };
        xBtn.disabled         = false;
    }
}

// Update a default-section chip to HIDDEN state
function _chipSetHidden(key) {
    const chip = document.getElementById('hidden-row-' + key);
    if (!chip) return;
    chip.style.background  = '#FEF2F2';
    chip.style.borderColor = '#FECACA';
    chip.style.color       = '#DC2626';
    const icon = chip.querySelector('i');
    if (icon) { icon.className = 'fas fa-eye-slash'; icon.style.fontSize = '10px'; }
    const xBtn = chip.querySelector('button');
    if (xBtn) {
        xBtn.style.background = '#FECACA';
        xBtn.style.color      = '#DC2626';
        xBtn.innerHTML        = '<i class="fas fa-xmark"></i>';
        xBtn.title            = 'Restore';
        xBtn.onclick          = function() { restoreElement(key, this); };
        xBtn.disabled         = false;
    }
}

// Update an extra-chart chip to ACTIVE state
function _extraSetActive(key) {
    const chip = document.getElementById('extra-row-' + key);
    if (!chip) return;
    chip.style.background  = '#F0FDF4';
    chip.style.borderColor = '#BBF7D0';
    chip.style.color       = '#059669';
    const icon = document.getElementById('extra-icon-' + key);
    if (icon) { icon.className = 'fas fa-eye'; icon.style.color = '#059669'; }
    const btn = chip.querySelector('button');
    if (btn) {
        btn.style.background = '#BBF7D0';
        btn.style.color      = '#059669';
        btn.innerHTML        = '<i class="fas fa-xmark" style="font-size:9px;"></i>';
        btn.title            = 'Remove';
        btn.onclick          = function() { removeExtra(key, this); };
        btn.disabled         = false;
    }
    // Add Active badge if not present
    if (!chip.querySelector('.extra-active-badge')) {
        const badge = document.createElement('span');
        badge.className   = 'extra-active-badge';
        badge.textContent = 'Active';
        badge.style.cssText = 'font-size:9px;font-weight:700;background:#D1FAE5;color:#065F46;padding:1px 6px;border-radius:20px;';
        btn.insertAdjacentElement('beforebegin', badge);
    }
}

// Update an extra-chart chip to INACTIVE state
function _extraSetInactive(key) {
    const chip = document.getElementById('extra-row-' + key);
    if (!chip) return;
    chip.style.background  = '#F9FAFB';
    chip.style.borderColor = '#E5E7EB';
    chip.style.color       = '#6B7280';
    const icon = document.getElementById('extra-icon-' + key);
    if (icon) { icon.className = 'fas fa-plus-circle'; icon.style.color = '#A5B4FC'; }
    const btn = chip.querySelector('button');
    if (btn) {
        btn.style.background = '#E5E7EB';
        btn.style.color      = '#6B7280';
        btn.innerHTML        = '<i class="fas fa-plus" style="font-size:9px;"></i>';
        btn.title            = 'Add';
        btn.onclick          = function() { addExtra(key, this); };
        btn.disabled         = false;
    }
    const badge = chip.querySelector('.extra-active-badge');
    if (badge) badge.remove();
}

const _navToggleUrl = '{{ route('admin.settings.nav.toggle') }}';

function toggleNavItem(key, action, btn) {
    btn.disabled = true;
    fetch(_navToggleUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': _csrfToken, 'Content-Type': 'application/json' },
        body: JSON.stringify({ key, action })
    })
    .then(r => r.json())
    .then(() => {
        const row        = document.getElementById('navrow-' + key);
        if (!row) return;
        const isNowHidden = action === 'hide';
        const activeColor = row.getAttribute('data-active-color') || '#6366F1';
        const icon  = row.querySelector('i');
        const label = row.querySelector('span');

        row.style.background   = isNowHidden ? '#FEF2F2' : '#F9FAFB';
        row.style.borderColor  = isNowHidden ? '#FECACA' : '#F0F0F0';
        if (icon)  icon.style.color  = isNowHidden ? '#DC2626' : activeColor;
        if (label) label.style.color = isNowHidden ? '#DC2626' : '#374151';

        if (isNowHidden) {
            btn.style.cssText = 'display:flex;align-items:center;gap:4px;padding:4px 10px;background:#EEF2FF;color:#4F46E5;border:1px solid #C7D2FE;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;';
            btn.innerHTML     = '<i class="fas fa-eye" style="font-size:9px;"></i> Show';
            btn.onclick       = function() { toggleNavItem(key, 'show', this); };
            btn.onmouseover   = null;
            btn.onmouseout    = null;
        } else {
            btn.style.cssText = 'display:flex;align-items:center;gap:4px;padding:4px 10px;background:#F9FAFB;color:#9CA3AF;border:1px solid #E5E7EB;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;';
            btn.innerHTML     = '<i class="fas fa-eye-slash" style="font-size:9px;"></i> Hide';
            btn.onclick       = function() { toggleNavItem(key, 'hide', this); };
            btn.onmouseover   = function() { this.style.background='#FEF2F2'; this.style.color='#DC2626'; this.style.borderColor='#FECACA'; };
            btn.onmouseout    = function() { this.style.background='#F9FAFB'; this.style.color='#9CA3AF'; this.style.borderColor='#E5E7EB'; };
        }
        btn.disabled = false;
    })
    .catch(() => { btn.disabled = false; });
}

// ── WhatsApp ──────────────────────────────────────────────────────────────

function testWaConnection() {
    const phone  = document.getElementById('waTestPhone').value.trim();
    const result = document.getElementById('waTestResult');
    const btn    = document.getElementById('waTestBtn');
    if (!phone) { result.innerHTML = '<span style="color:#DC2626;">Please enter a phone number.</span>'; return; }
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-brands fa-whatsapp"></i> Sending…';
    result.innerHTML = '';

    fetch('{{ route('admin.settings.whatsapp.test') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': _csrfToken, 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone })
    })
    .then(r => r.json())
    .then(data => {
        result.innerHTML = data.ok
            ? '<span style="color:#16a34a;"><i class="fas fa-check-circle" style="margin-right:4px;"></i>' + data.message + '</span>'
            : '<span style="color:#DC2626;"><i class="fas fa-times-circle" style="margin-right:4px;"></i>' + data.message + '</span>';
    })
    .catch(() => { result.innerHTML = '<span style="color:#DC2626;">Request failed.</span>'; })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-brands fa-whatsapp"></i> Send Test Message';
    });
}

function waInsertVar(variable) {
    const ta = document.getElementById('waBcastBody');
    if (!ta) return;
    const s  = ta.selectionStart;
    const e  = ta.selectionEnd;
    ta.value = ta.value.substring(0, s) + variable + ta.value.substring(e);
    ta.selectionStart = ta.selectionEnd = s + variable.length;
    ta.focus();
    ta.dispatchEvent(new Event('input'));
}

function sendWaBroadcast() {
    const result = document.getElementById('waBcastResult');
    const btn    = document.getElementById('waBcastBtn');
    const bodyEl = document.getElementById('waBcastBody');
    const body   = bodyEl ? bodyEl.value.trim() : '';

    // Read Alpine-bound select via DOM
    const recipSel = document.querySelector('select[x-model="bcastRecipients"]');
    const recipients = recipSel ? recipSel.value : 'all';
    const numbersEl = document.getElementById('waBcastNumbers');
    const numbers   = numbersEl ? numbersEl.value : '';

    if (!body) { result.innerHTML = '<span style="color:#DC2626;">Please enter a message.</span>'; return; }
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-brands fa-whatsapp"></i> Sending…';
    result.innerHTML = '';

    fetch('{{ route('admin.settings.whatsapp.broadcast') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': _csrfToken, 'Content-Type': 'application/json' },
        body: JSON.stringify({ body, recipients, numbers })
    })
    .then(r => r.json())
    .then(data => {
        let html = data.ok
            ? '<span style="color:#16a34a;"><i class="fas fa-check-circle" style="margin-right:4px;"></i>' + data.message + '</span>'
            : '<span style="color:#DC2626;"><i class="fas fa-times-circle" style="margin-right:4px;"></i>' + data.message + '</span>';
        if (data.errors && data.errors.length) {
            html += '<div style="margin-top:6px;font-size:11px;color:#9CA3AF;">' + data.errors.join('<br>') + '</div>';
        }
        result.innerHTML = html;
    })
    .catch(() => { result.innerHTML = '<span style="color:#DC2626;">Request failed.</span>'; })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-brands fa-whatsapp"></i> Send WhatsApp Messages';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const numTa = document.getElementById('waBcastNumbers');
    const count = document.getElementById('waBcastNumCount');
    if (numTa && count) {
        numTa.addEventListener('input', function() {
            const nums = this.value.split(/[\r\n,]+/).map(s => s.trim()).filter(Boolean);
            count.textContent = nums.length + ' number(s) entered.';
        });
    }
});
</script>

@endsection
