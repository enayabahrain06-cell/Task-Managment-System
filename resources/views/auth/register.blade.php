@extends('layouts.auth')

@section('content')
<div style="width:100%;max-width:880px;background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(79,70,229,0.15);overflow:hidden;display:flex;min-height:540px;">

    {{-- ── Left: Form ── --}}
    <div style="flex:1;padding:40px 44px;display:flex;flex-direction:column;justify-content:center;min-width:0;">

        {{-- Logo --}}
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:28px;">
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

        <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0 0 6px;">Create your account!</h1>
        <p style="font-size:13px;color:#9CA3AF;margin:0 0 24px;">Fill in your details to get started</p>

        @if ($errors->any())
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:10px 14px;margin-bottom:16px;font-size:12px;color:#DC2626;display:flex;align-items:center;gap:8px;">
            <i class="fa fa-exclamation-circle"></i>{{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf

            {{-- Name + Role row --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Full name</label>
                    <div style="position:relative;">
                        <i class="fa fa-user" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:12px;"></i>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="John Doe"
                               style="width:100%;padding:10px 10px 10px 32px;border:1.5px solid {{ $errors->has('name') ? '#FCA5A5':'#E5E7EB' }};border-radius:10px;font-size:13px;font-family:'Inter',sans-serif;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                    </div>
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Role</label>
                    <div style="position:relative;">
                        <i class="fa fa-id-badge" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:12px;"></i>
                        <select name="role" required
                                style="width:100%;padding:10px 10px 10px 32px;border:1.5px solid {{ $errors->has('role') ? '#FCA5A5':'#E5E7EB' }};border-radius:10px;font-size:13px;font-family:'Inter',sans-serif;background:#F9FAFB;color:#111827;outline:none;appearance:none;box-sizing:border-box;"
                                onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                            <option value="">Select role</option>
                            <option value="user"    {{ old('role')==='user'    ? 'selected':'' }}>User</option>
                            <option value="manager" {{ old('role')==='manager' ? 'selected':'' }}>Manager</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Email --}}
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Email</label>
                <div style="position:relative;">
                    <i class="fa fa-envelope" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:12px;"></i>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="you@company.com"
                           style="width:100%;padding:10px 10px 10px 32px;border:1.5px solid {{ $errors->has('email') ? '#FCA5A5':'#E5E7EB' }};border-radius:10px;font-size:13px;font-family:'Inter',sans-serif;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                </div>
            </div>

            {{-- Password row --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:22px;">
                <div x-data="{show:false}">
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Password</label>
                    <div style="position:relative;">
                        <i class="fa fa-lock" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:12px;"></i>
                        <input :type="show?'text':'password'" name="password" required placeholder="Min. 8 chars"
                               style="width:100%;padding:10px 32px 10px 32px;border:1.5px solid {{ $errors->has('password') ? '#FCA5A5':'#E5E7EB' }};border-radius:10px;font-size:13px;font-family:'Inter',sans-serif;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                        <button type="button" @click="show=!show" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9CA3AF;padding:0;">
                            <i :class="show?'fa fa-eye-slash':'fa fa-eye'" style="font-size:12px;"></i>
                        </button>
                    </div>
                </div>
                <div x-data="{show:false}">
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Confirm</label>
                    <div style="position:relative;">
                        <i class="fa fa-lock" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:12px;"></i>
                        <input :type="show?'text':'password'" name="password_confirmation" required placeholder="Re-enter"
                               style="width:100%;padding:10px 32px 10px 32px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;font-family:'Inter',sans-serif;background:#F9FAFB;color:#111827;outline:none;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
                        <button type="button" @click="show=!show" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9CA3AF;padding:0;">
                            <i :class="show?'fa fa-eye-slash':'fa fa-eye'" style="font-size:12px;"></i>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit"
                    style="width:100%;background:linear-gradient(135deg,#4F46E5,#6366F1);color:#fff;font-size:14px;font-weight:600;padding:12px;border:none;border-radius:12px;cursor:pointer;box-shadow:0 6px 20px rgba(79,70,229,0.35);font-family:'Inter',sans-serif;"
                    onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                Create Account
            </button>
        </form>

        <p style="text-align:center;font-size:12px;color:#9CA3AF;margin-top:18px;">
            Already have an account?
            <a href="{{ route('login') }}" style="color:#4F46E5;font-weight:600;text-decoration:none;">Sign in</a>
        </p>
    </div>

    {{-- ── Right: Illustration (same as login) ── --}}
    <div style="width:340px;flex-shrink:0;background:linear-gradient(145deg,#6EE7F7 0%,#818CF8 40%,#4F46E5 100%);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 28px;position:relative;overflow:hidden;">
        <div style="position:absolute;width:280px;height:280px;border-radius:50%;background:rgba(255,255,255,0.06);top:-80px;right:-80px;"></div>
        <div style="position:absolute;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,0.06);bottom:-60px;left:-60px;"></div>

        <h2 style="color:#fff;font-size:20px;font-weight:800;text-align:center;margin:0 0 8px;position:relative;z-index:1;line-height:1.3;">
            Join the Team<br><span style="color:#BAE6FD;">Today</span>
        </h2>
        <p style="color:rgba(255,255,255,0.65);font-size:12px;text-align:center;margin:0 0 44px;position:relative;z-index:1;">Start managing tasks and projects<br>with your team instantly.</p>

        <div style="position:relative;width:200px;height:200px;flex-shrink:0;">
            <div style="position:absolute;inset:0;border-radius:50%;border:1px solid rgba(255,255,255,0.15);"></div>
            <div style="position:absolute;inset:20px;border-radius:50%;border:1px solid rgba(255,255,255,0.10);"></div>
            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;">
                <div class="pulse-ring"></div><div class="pulse-ring pulse-ring2"></div><div class="pulse-ring pulse-ring3"></div>
            </div>
            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:50px;height:50px;background:linear-gradient(135deg,#818CF8,#4F46E5);border-radius:14px;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 30px rgba(0,0,0,0.25);z-index:2;">
                <i class="fa fa-user-plus" style="color:#fff;font-size:18px;"></i>
            </div>
            <div class="orbit-icon o1" style="background:#fff;"><i class="fa fa-check-circle" style="color:#10B981;font-size:15px;"></i></div>
            <div class="orbit-icon o2" style="background:#EFF6FF;"><i class="fa fa-tasks" style="color:#3B82F6;font-size:14px;"></i></div>
            <div class="orbit-icon o3" style="background:#FFF7ED;"><i class="fa fa-comments" style="color:#F59E0B;font-size:14px;"></i></div>
            <div class="orbit-icon o4" style="background:#F0FDF4;"><i class="fa fa-calendar" style="color:#10B981;font-size:13px;"></i></div>
            <div class="orbit-icon o5" style="background:#FDF4FF;"><i class="fa fa-chart-line" style="color:#8B5CF6;font-size:14px;"></i></div>
        </div>

        <p style="color:rgba(255,255,255,0.6);font-size:11px;text-align:center;margin-top:32px;line-height:1.6;position:relative;z-index:1;">
            <strong style="color:rgba(255,255,255,0.9);">Tasks, Messages, Calendar</strong><br>everything your team needs.
        </p>

        {{-- Copyright --}}
        @if(!empty($appSettings['copyright']))
        <p style="position:absolute;bottom:16px;right:20px;font-size:10px;color:rgba(255,255,255,0.4);margin:0;z-index:1;">
            {{ $appSettings['copyright'] }}
        </p>
        @endif
    </div>

</div>
@endsection
