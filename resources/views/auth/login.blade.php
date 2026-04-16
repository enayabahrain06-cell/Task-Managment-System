@extends('layouts.auth')

@section('content')
{{-- Main card --}}
<div style="width:100%;max-width:880px;background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(79,70,229,0.15);overflow:hidden;display:flex;min-height:540px;">

    {{-- ── Left: Form ── --}}
    <div style="flex:1;padding:48px 44px;display:flex;flex-direction:column;justify-content:center;min-width:0;">

        {{-- Logo --}}
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:36px;">
            @if(!empty($appSettings['logo_path']))
                <img src="{{ Storage::url($appSettings['logo_path']) }}"
                     alt="{{ $appSettings['app_name'] ?? 'Logo' }}"
                     style="height:32px;width:auto;max-width:120px;object-fit:contain;border-radius:6px;">
            @else
                <div style="width:34px;height:34px;background:#4F46E5;border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(79,70,229,0.35);">
                    <i class="fa fa-bolt" style="color:#fff;font-size:14px;"></i>
                </div>
                <span style="font-size:15px;font-weight:700;color:#111827;">{{ $appSettings['app_name'] ?? config('app.name','Dash') }}</span>
            @endif
        </div>

        {{-- Heading --}}
        <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0 0 6px;">Login to your account!</h1>
        <p style="font-size:13px;color:#9CA3AF;margin:0 0 28px;">Enter your registered email address and password to login</p>

        {{-- Errors --}}
        @if ($errors->any())
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:10px 14px;margin-bottom:18px;font-size:12px;color:#DC2626;display:flex;align-items:center;gap:8px;">
            <i class="fa fa-exclamation-circle"></i>
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            {{-- Email --}}
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Email</label>
                <div style="position:relative;">
                    <i class="fa fa-envelope" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:12px;"></i>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           placeholder="eg. pixelcor@gmail.com"
                           style="width:100%;padding:10px 12px 10px 36px;border:1.5px solid {{ $errors->has('email') ? '#FCA5A5' : '#E5E7EB' }};border-radius:10px;font-size:13px;font-family:'Inter',sans-serif;background:{{ $errors->has('email') ? '#FEF2F2' : '#F9FAFB' }};color:#111827;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#6366F1';this.style.boxShadow='0 0 0 3px rgba(99,102,241,0.1)'"
                           onblur="this.style.borderColor='#E5E7EB';this.style.boxShadow='none'">
                </div>
            </div>

            {{-- Password --}}
            <div style="margin-bottom:16px;" x-data="{show:false}">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Password</label>
                <div style="position:relative;">
                    <i class="fa fa-lock" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:12px;"></i>
                    <input :type="show?'text':'password'" name="password" required
                           placeholder="••••••••••••"
                           style="width:100%;padding:10px 40px 10px 36px;border:1.5px solid {{ $errors->has('password') ? '#FCA5A5' : '#E5E7EB' }};border-radius:10px;font-size:13px;font-family:'Inter',sans-serif;background:{{ $errors->has('password') ? '#FEF2F2' : '#F9FAFB' }};color:#111827;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#6366F1';this.style.boxShadow='0 0 0 3px rgba(99,102,241,0.1)'"
                           onblur="this.style.borderColor='#E5E7EB';this.style.boxShadow='none'">
                    <button type="button" @click="show=!show"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9CA3AF;padding:0;">
                        <i :class="show?'fa fa-eye-slash':'fa fa-eye'" style="font-size:13px;"></i>
                    </button>
                </div>
            </div>

            {{-- Remember + Forgot --}}
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
                <label style="display:flex;align-items:center;gap:7px;font-size:12px;color:#6B7280;cursor:pointer;">
                    <input type="checkbox" name="remember"
                           style="width:14px;height:14px;accent-color:#4F46E5;border-radius:4px;">
                    Remember me
                </label>
                <a href="#" style="font-size:12px;color:#4F46E5;font-weight:500;text-decoration:none;">Forgot Password ?</a>
            </div>

            {{-- Submit --}}
            <button type="submit"
                    style="width:100%;background:linear-gradient(135deg,#4F46E5,#6366F1);color:#fff;font-size:14px;font-weight:600;padding:12px;border:none;border-radius:12px;cursor:pointer;box-shadow:0 6px 20px rgba(79,70,229,0.35);transition:opacity 0.15s;font-family:'Inter',sans-serif;"
                    onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                Login
            </button>
        </form>

        {{-- Divider --}}
        <div style="display:flex;align-items:center;gap:12px;margin:22px 0;">
            <div style="flex:1;height:1px;background:#F3F4F6;"></div>
            <span style="font-size:11px;color:#9CA3AF;white-space:nowrap;">Or login with</span>
            <div style="flex:1;height:1px;background:#F3F4F6;"></div>
        </div>

        {{-- Social buttons --}}
        <div style="display:flex;gap:10px;justify-content:center;">
            @foreach([['fa-google','#EA4335'],['fa-apple','#111827'],['fa-windows','#00A4EF']] as [$icon,$color])
            <button type="button"
                    style="flex:1;padding:10px;background:#F9FAFB;border:1.5px solid #E5E7EB;border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:border-color 0.15s;"
                    onmouseover="this.style.borderColor='#6366F1'" onmouseout="this.style.borderColor='#E5E7EB'">
                <i class="fab {{ $icon }}" style="font-size:16px;color:{{ $color }};"></i>
            </button>
            @endforeach
        </div>

        {{-- Register link --}}
        <p style="text-align:center;font-size:12px;color:#9CA3AF;margin-top:20px;">
            Don't have an account?
            <a href="{{ route('register') }}" style="color:#4F46E5;font-weight:600;text-decoration:none;">Create account</a>
        </p>
    </div>

    {{-- ── Right: Illustration ── --}}
    <div style="width:380px;flex-shrink:0;background:linear-gradient(145deg,#6EE7F7 0%,#818CF8 40%,#4F46E5 100%);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 32px;position:relative;overflow:hidden;">

        {{-- Background blobs --}}
        <div style="position:absolute;width:280px;height:280px;border-radius:50%;background:rgba(255,255,255,0.06);top:-80px;right:-80px;"></div>
        <div style="position:absolute;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,0.06);bottom:-60px;left:-60px;"></div>

        <h2 style="color:#fff;font-size:22px;font-weight:800;text-align:center;margin:0 0 8px;position:relative;z-index:1;line-height:1.3;">
            Manage Tasks<br><span style="color:#BAE6FD;">Everywhere</span>
        </h2>
        <p style="color:rgba(255,255,255,0.65);font-size:12px;text-align:center;margin:0 0 48px;position:relative;z-index:1;">All your projects, tasks and team<br>in one place.</p>

        {{-- Orbit illustration --}}
        <div style="position:relative;width:220px;height:220px;flex-shrink:0;">

            {{-- Orbit rings --}}
            <div style="position:absolute;inset:0;border-radius:50%;border:1px solid rgba(255,255,255,0.15);"></div>
            <div style="position:absolute;inset:20px;border-radius:50%;border:1px solid rgba(255,255,255,0.10);"></div>

            {{-- Pulse rings --}}
            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;">
                <div class="pulse-ring"></div>
                <div class="pulse-ring pulse-ring2"></div>
                <div class="pulse-ring pulse-ring3"></div>
            </div>

            {{-- Center icon --}}
            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:54px;height:54px;background:linear-gradient(135deg,#818CF8,#4F46E5);border-radius:16px;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 30px rgba(0,0,0,0.25);z-index:2;">
                <i class="fa fa-diagram-project" style="color:#fff;font-size:22px;"></i>
            </div>

            {{-- Orbiting icons --}}
            <div class="orbit-icon o1" style="background:#fff;">
                <i class="fa fa-check-circle" style="color:#10B981;font-size:16px;"></i>
            </div>
            <div class="orbit-icon o2" style="background:#EFF6FF;">
                <i class="fa fa-calendar" style="color:#3B82F6;font-size:15px;"></i>
            </div>
            <div class="orbit-icon o3" style="background:#FFF7ED;">
                <i class="fa fa-comment-dots" style="color:#F59E0B;font-size:15px;"></i>
            </div>
            <div class="orbit-icon o4" style="background:#F0FDF4;">
                <i class="fa fa-users" style="color:#10B981;font-size:14px;"></i>
            </div>
            <div class="orbit-icon o5" style="background:#FDF4FF;">
                <i class="fa fa-chart-bar" style="color:#8B5CF6;font-size:15px;"></i>
            </div>
            <div class="orbit-icon o6" style="background:#FEF2F2;">
                <i class="fa fa-bell" style="color:#EF4444;font-size:14px;"></i>
            </div>
        </div>

        <p style="color:rgba(255,255,255,0.6);font-size:11px;text-align:center;margin-top:36px;line-height:1.6;position:relative;z-index:1;">
            Compatible with <strong style="color:rgba(255,255,255,0.9);">Tasks, Projects, Calendar</strong><br>and your entire team workflow.
        </p>

        {{-- Copyright inside right panel --}}
        <p style="position:absolute;bottom:16px;right:20px;font-size:10px;color:rgba(255,255,255,0.4);margin:0;z-index:1;">
            &copy; {{ date('Y') }} {{ $appSettings['app_name'] ?? config('app.name','Dash') }}. All rights reserved.
        </p>
    </div>

</div>
@endsection
