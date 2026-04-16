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

<div class="settings-wrap" x-data="{ tab: '{{ session('_fragment') ?? 'general' }}' }">

    {{-- ── Sidebar Nav ── --}}
    <nav class="settings-nav">
        @php
        $navItems = [
            ['id'=>'general',       'icon'=>'fa-sliders',        'label'=>'General'],
            ['id'=>'branding',      'icon'=>'fa-palette',        'label'=>'Branding'],
            ['id'=>'team',          'icon'=>'fa-users',          'label'=>'Team'],
            ['id'=>'notifications', 'icon'=>'fa-bell',           'label'=>'Notifications'],
            ['id'=>'security',      'icon'=>'fa-shield-halved',  'label'=>'Security'],
            ['id'=>'backup',        'icon'=>'fa-database',       'label'=>'Backup & Export'],
        ];
        @endphp
        @foreach($navItems as $nav)
        <button class="snav-item" :class="tab === '{{ $nav['id'] }}' ? 'active' : ''"
                @click="tab = '{{ $nav['id'] }}'">
            <i class="fas {{ $nav['icon'] }}"></i>
            {{ $nav['label'] }}
        </button>
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
                                <select name="timezone" class="sf-input sf-select">
                                    @foreach(['UTC','Asia/Riyadh','Asia/Dubai','Asia/Kuwait','Europe/London','America/New_York','America/Los_Angeles','Asia/Tokyo','Australia/Sydney'] as $tz)
                                    <option value="{{ $tz }}" {{ $settings['timezone'] === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                                    @endforeach
                                </select>
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
                        <div class="sf-toggle-row">
                            <div>
                                <p class="sf-toggle-label">Email Notifications</p>
                                <p class="sf-toggle-hint">Send email alerts for important events</p>
                            </div>
                            <label class="toggle">
                                <input type="checkbox" name="email_notifications" value="1"
                                       {{ $settings['email_notifications'] === '1' ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="sf-toggle-row">
                            <div>
                                <p class="sf-toggle-label">Notify on Task Assignment</p>
                                <p class="sf-toggle-hint">User receives email when a task is assigned to them</p>
                            </div>
                            <label class="toggle">
                                <input type="checkbox" name="notify_on_assign" value="1"
                                       {{ $settings['notify_on_assign'] === '1' ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="sf-toggle-row">
                            <div>
                                <p class="sf-toggle-label">Notify on Task Completion</p>
                                <p class="sf-toggle-hint">Admin receives email when a task is marked complete</p>
                            </div>
                            <label class="toggle">
                                <input type="checkbox" name="notify_on_complete" value="1"
                                       {{ $settings['notify_on_complete'] === '1' ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="sf-group" style="margin-top:16px;margin-bottom:0;">
                            <label class="sf-label">Deadline Reminder (days before)</label>
                            <input type="number" name="task_reminder_days" class="sf-input" style="max-width:120px;"
                                   value="{{ $settings['task_reminder_days'] }}" min="0" max="30">
                            <p class="sf-hint">Send a reminder this many days before a task deadline. Set 0 to disable.</p>
                        </div>
                    </div>
                    <div class="scard-footer">
                        <button type="submit" class="btn-save"><i class="fas fa-check" style="font-size:11px;margin-right:5px;"></i>Save Preferences</button>
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

            {{-- Data overview --}}
            <div class="scard" style="margin-bottom:0;">
                <div class="scard-header">
                    <div class="scard-icon" style="background:#EFF6FF;color:#2563EB;"><i class="fas fa-database"></i></div>
                    <div>
                        <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">Backup & Export</p>
                        <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Download your data as CSV files</p>
                    </div>
                </div>
                <div class="scard-body">

                    {{-- Stats strip --}}
                    <div class="stat-strip" style="margin-bottom:22px;">
                        <div class="stat-pill">
                            <p style="font-size:22px;font-weight:700;color:#4F46E5;margin:0;">{{ $stats['users'] }}</p>
                            <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">Users</p>
                        </div>
                        <div class="stat-pill">
                            <p style="font-size:22px;font-weight:700;color:#10B981;margin:0;">{{ $stats['projects'] }}</p>
                            <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">Projects</p>
                        </div>
                        <div class="stat-pill">
                            <p style="font-size:22px;font-weight:700;color:#F59E0B;margin:0;">{{ $stats['tasks'] }}</p>
                            <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">Tasks</p>
                        </div>
                        <div class="stat-pill">
                            <p style="font-size:22px;font-weight:700;color:#6366F1;margin:0;">{{ $stats['db_size'] }} KB</p>
                            <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">DB Size</p>
                        </div>
                    </div>

                    {{-- Export cards --}}
                    <div class="export-grid">

                        <div class="export-card">
                            <div class="export-icon" style="background:#EEF2FF;color:#4F46E5;">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <p style="font-size:13px;font-weight:700;color:#111827;margin:0 0 3px;">Users Export</p>
                                <p style="font-size:11px;color:#9CA3AF;margin:0;">All users with roles and task counts</p>
                            </div>
                            <a href="{{ route('admin.settings.export.users') }}"
                               class="btn-export" style="color:#4F46E5;border-color:#C7D2FE;background:#EEF2FF;">
                                <i class="fas fa-download" style="font-size:11px;"></i> Download CSV
                            </a>
                        </div>

                        <div class="export-card">
                            <div class="export-icon" style="background:#F0FDF4;color:#16A34A;">
                                <i class="fas fa-square-check"></i>
                            </div>
                            <div>
                                <p style="font-size:13px;font-weight:700;color:#111827;margin:0 0 3px;">Tasks Export</p>
                                <p style="font-size:11px;color:#9CA3AF;margin:0;">All tasks with status, assignee and deadline</p>
                            </div>
                            <a href="{{ route('admin.settings.export.tasks') }}"
                               class="btn-export" style="color:#16A34A;border-color:#BBF7D0;background:#F0FDF4;">
                                <i class="fas fa-download" style="font-size:11px;"></i> Download CSV
                            </a>
                        </div>

                        <div class="export-card">
                            <div class="export-icon" style="background:#FFFBEB;color:#D97706;">
                                <i class="fas fa-diagram-project"></i>
                            </div>
                            <div>
                                <p style="font-size:13px;font-weight:700;color:#111827;margin:0 0 3px;">Projects Export</p>
                                <p style="font-size:11px;color:#9CA3AF;margin:0;">All projects with status and task counts</p>
                            </div>
                            <a href="{{ route('admin.settings.export.projects') }}"
                               class="btn-export" style="color:#D97706;border-color:#FDE68A;background:#FFFBEB;">
                                <i class="fas fa-download" style="font-size:11px;"></i> Download CSV
                            </a>
                        </div>

                    </div>

                    {{-- Info note --}}
                    <div style="margin-top:20px;background:#F8FAFC;border:1px solid #E5E7EB;border-radius:10px;padding:14px 16px;display:flex;align-items:flex-start;gap:10px;">
                        <i class="fas fa-circle-info" style="color:#6366F1;margin-top:1px;flex-shrink:0;"></i>
                        <p style="font-size:12px;color:#6B7280;margin:0;line-height:1.6;">
                            Exports download instantly as <strong>.csv</strong> files. They include all records at the time of download. For a full database backup, copy the <code style="background:#E5E7EB;padding:1px 5px;border-radius:4px;font-size:11px;">database/database.sqlite</code> file directly from the server.
                        </p>
                    </div>

                </div>
            </div>

        </div>

    </div>{{-- end settings-panel --}}
</div>{{-- end settings-wrap --}}

@endsection
