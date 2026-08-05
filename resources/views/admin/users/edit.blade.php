@extends('layouts.app')
@section('title', 'Edit User')

@push('styles')
<style>
@media (max-width: 768px) {
    /* Back button: larger tap target */
    .eu-back-btn { width: 44px !important; height: 44px !important; }

    /* Single-column, full-width fields with touch-friendly sizing */
    .eu-form-grid { grid-template-columns: 1fr !important; }
    .eu-form-grid input, .eu-form-grid select {
        min-height: 46px !important; font-size: 15px !important; box-sizing: border-box;
    }

    /* Avatar upload: larger tap target (JS/behavior untouched) */
    .eu-avatar-camera-btn { width: 38px !important; height: 38px !important; }

    /* Access permissions: single column, comfortable tap spacing */
    .eu-perm-grid { grid-template-columns: 1fr !important; gap: 10px !important; }
    .eu-perm-grid label { padding: 14px !important; min-height: 46px; box-sizing: border-box; }
    .eu-allaccess-btn { min-height: 40px; padding-left: 14px !important; padding-right: 14px !important; }

    /* MFA disable button: full-width, comfortable height */
    .eu-mfa-btn { min-height: 44px; width: 100%; justify-content: center; }

    /* Actions: full-width, stacked, sticky above the bottom nav */
    .eu-actions { flex-direction: column !important; gap: 10px !important; }
    .eu-actions > * { width: 100% !important; flex: none !important; justify-content: center; min-height: 46px; }

    /* Transfer tasks: stack select + button, full width */
    .eu-transfer-row { flex-direction: column !important; align-items: stretch !important; }
    .eu-transfer-row select, .eu-transfer-row button { width: 100% !important; min-height: 46px !important; }

    /* Warning card keeps its amber border but adopts shared radius/shadow tokens */
    .eu-warn-card { border-radius: var(--mob-r-lg) !important; box-shadow: var(--mob-shadow-1) !important; }
}
</style>
@endpush

@section('content')
<div class="max-w-2xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.users.index') }}" class="eu-back-btn w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500 transition">
            <i class="fa fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit User</h1>
            <p class="text-sm text-gray-400 mt-0.5">{{ $user->name }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        {{-- Avatar --}}
        <div class="mob-card bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-4 max-md:p-4"
             x-data="{ preview: '{{ $user->avatarUrl() }}' }">
            <p class="text-sm font-semibold text-gray-700 mb-4 max-md:pb-3 max-md:mb-3 max-md:border-b max-md:border-gray-100">Profile Photo</p>
            <div class="flex items-center gap-5">
                <div class="relative flex-shrink-0">
                    <div class="w-20 h-20 rounded-full overflow-hidden bg-indigo-100 flex items-center justify-center border-2 border-white shadow-md">
                        <template x-if="preview">
                            <img :src="preview" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!preview">
                            <div class="w-full h-full flex items-center justify-center bg-indigo-500">
                                <span class="text-white text-2xl font-bold">{{ strtoupper(substr($user->name,0,1)) }}</span>
                            </div>
                        </template>
                    </div>
                    <label class="eu-avatar-camera-btn absolute -bottom-1 -right-1 w-7 h-7 bg-indigo-600 hover:bg-indigo-700 rounded-full flex items-center justify-center cursor-pointer shadow transition">
                        <i class="fa fa-camera text-white text-xs"></i>
                        <input type="file" name="avatar" accept="image/*" class="hidden"
                               @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
                    </label>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-700">Change photo</p>
                    <p class="text-xs text-gray-400 mt-0.5">JPG, PNG or WebP · Max 2MB</p>
                    @error('avatar') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Basic Info --}}
        <div class="mob-card bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-4 max-md:p-4">
            <p class="text-sm font-semibold text-gray-700 mb-4 max-md:pb-3 max-md:mb-3 max-md:border-b max-md:border-gray-100">Basic Information</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 eu-form-grid">

                <div class="sm:col-span-2 mob-field">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Full Name <span class="text-red-400">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full px-3 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }}">
                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="mob-field">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email Address <span class="text-red-400">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full px-3 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }}">
                    @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="mob-field">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Phone Number</label>
                    <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+1 555 000 0000"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition">
                </div>

                <div class="mob-field">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Job Title</label>
                    <input type="text" name="job_title" value="{{ old('job_title', $user->job_title) }}" placeholder="e.g. Frontend Developer"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition">
                </div>

                @if(($appSettings['hide_hourly_rate'] ?? '0') !== '1')
                <div class="mob-field">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Hourly Rate <span class="text-gray-400 font-normal">(for billing reports)</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-semibold">$</span>
                        <input type="number" name="hourly_rate" step="0.01" min="0" value="{{ old('hourly_rate', $user->hourly_rate) }}" placeholder="0.00"
                               class="w-full pl-7 pr-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Used to compute estimated cost per customer in time tracking reports.</p>
                </div>
                @endif

                <div class="mob-field">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Role <span class="text-red-400">*</span></label>
                    <select name="role" required
                            class="w-full px-3 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition bg-gray-50 {{ $errors->has('role') ? 'border-red-400' : 'border-gray-200' }}">
                        <option value="admin"   {{ old('role',$user->role)==='admin'   ? 'selected':'' }}>Admin</option>
                        <option value="manager" {{ old('role',$user->role)==='manager' ? 'selected':'' }}>Manager</option>
                        <option value="user"    {{ old('role',$user->role)==='user'    ? 'selected':'' }}>User</option>
                    </select>
                </div>

                <div class="mob-field">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Status</label>
                    <select name="status"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition">
                        <option value="active"   {{ old('status',$user->status)==='active'   ? 'selected':'' }}>Active</option>
                        <option value="inactive" {{ old('status',$user->status)==='inactive' ? 'selected':'' }}>Inactive</option>
                    </select>
                </div>

            </div>
        </div>

        {{-- Password --}}
        <div class="mob-card bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6 max-md:p-4">
            <p class="text-sm font-semibold text-gray-700 mb-1">Change Password</p>
            <p class="text-xs text-gray-400 mb-4 max-md:pb-3 max-md:border-b max-md:border-gray-100">Leave blank to keep the current password</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 eu-form-grid">
                <div class="mob-field">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">New Password</label>
                    <div class="relative" x-data="{show:false}">
                        <input :type="show?'text':'password'" name="password" placeholder="New password…"
                               class="w-full px-3 py-2.5 pr-10 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }}">
                        <button type="button" @click="show=!show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i :class="show?'fa fa-eye-slash':'fa fa-eye'" class="text-sm"></i>
                        </button>
                    </div>
                    @error('password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="mob-field">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Confirm New Password</label>
                    <div class="relative" x-data="{show:false}">
                        <input :type="show?'text':'password'" name="password_confirmation" placeholder="Re-enter new password"
                               class="w-full px-3 py-2.5 pr-10 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition">
                        <button type="button" @click="show=!show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i :class="show?'fa fa-eye-slash':'fa fa-eye'" class="text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Shift PIN --}}
        <div class="mob-card bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6 max-md:p-4" x-data="{ clear: false }">
            <div class="flex items-center justify-between mb-1 max-md:pb-3 max-md:border-b max-md:border-gray-100 max-md:mb-3">
                <p class="text-sm font-semibold text-gray-700">Shift PIN</p>
                @if($user->hasPin())
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200">
                        <i class="fa fa-circle-check text-xs"></i> Set
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full bg-gray-100 text-gray-400 border border-gray-200">
                        Not set
                    </span>
                @endif
            </div>
            <p class="text-xs text-gray-400 mb-4">4-digit code for fast handover sign-in on shared counter devices. Leave blank to keep unchanged.</p>

            @if($user->hasPin())
                <label class="flex items-center gap-2 text-xs text-gray-500 mb-3 cursor-pointer">
                    <input type="checkbox" name="clear_pin" value="1" x-model="clear" class="w-3.5 h-3.5 accent-red-600 rounded">
                    Remove this user's Shift PIN
                </label>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 eu-form-grid" :class="clear && 'opacity-40 pointer-events-none'">
                <div class="mob-field">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">New PIN</label>
                    <input type="text" name="pin" placeholder="4-digit PIN" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="off"
                           oninput="this.value=this.value.replace(/\D/g,'').slice(0,4)"
                           class="w-full px-3 py-2.5 border rounded-xl text-sm text-center tracking-[6px] focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition {{ $errors->has('pin') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }}">
                    @error('pin') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="mob-field">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Confirm New PIN</label>
                    <input type="text" name="pin_confirmation" placeholder="Confirm PIN" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="off"
                           oninput="this.value=this.value.replace(/\D/g,'').slice(0,4)"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-center tracking-[6px] bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition">
                </div>
            </div>
        </div>

        {{-- MFA Security --}}
        <div class="mob-card bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6 max-md:p-4">
            <div class="flex items-center justify-between mb-4 max-md:pb-3 max-md:border-b max-md:border-gray-100">
                <div>
                    <p class="text-sm font-semibold text-gray-700">Two-Factor Authentication</p>
                    <p class="text-xs text-gray-400 mt-0.5">Manage MFA for this user's account</p>
                </div>
                @if($user->mfa_enabled)
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-violet-100 text-violet-700 border border-violet-200 max-md:text-[11px] max-md:font-medium max-md:px-2 max-md:py-0.5 max-md:rounded-full">
                        <i class="fas fa-shield-halved text-xs"></i> Enabled
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full bg-gray-100 text-gray-400 border border-gray-200 max-md:text-[11px] max-md:font-medium max-md:px-2 max-md:py-0.5 max-md:rounded-full">
                        <i class="fas fa-shield-halved text-xs"></i> Not enabled
                    </span>
                @endif
            </div>

            @if($user->mfa_enabled)
                <div class="bg-violet-50 border border-violet-100 rounded-xl p-4 mb-4 max-md:rounded-[18px] max-md:p-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center flex-shrink-0 mt-0.5 max-md:w-9 max-md:h-9 max-md:rounded-xl">
                            <i class="fas fa-lock text-violet-600 text-xs"></i>
                        </div>
                        <div class="text-xs text-violet-700 leading-relaxed">
                            <p class="font-semibold mb-0.5">MFA is active on this account</p>
                            <p class="text-violet-500">
                                Recovery codes remaining: <strong>{{ count($user->mfa_recovery_codes ?? []) }}</strong> of 8
                            </p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('admin.users.disable-mfa', $user) }}" method="POST"
                      onsubmit="return confirm('Disable MFA for {{ addslashes($user->name) }}? They will be able to log in with password only.')">
                    @csrf
                    <button type="submit"
                            class="eu-mfa-btn inline-flex items-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-xl text-sm font-semibold transition">
                        <i class="fas fa-shield-halved text-xs"></i> Disable MFA for this user
                    </button>
                </form>
            @else
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 mb-4 max-md:rounded-[18px] max-md:p-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0 mt-0.5 max-md:w-9 max-md:h-9 max-md:rounded-xl">
                            <i class="fas fa-circle-info text-gray-400 text-xs"></i>
                        </div>
                        <div class="text-xs text-gray-500 leading-relaxed">
                            <p class="font-semibold text-gray-600 mb-0.5">MFA is not set up</p>
                            <p>This user has not enabled two-factor authentication. They must set it up themselves by going to their profile.</p>
                            <p class="mt-1">To require MFA for <strong>all users</strong>, enable the global setting in
                                <a href="{{ route('admin.settings.index') }}#security" class="text-indigo-500 hover:underline font-semibold">Settings → Security</a>.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Permissions --}}
        @if($user->role === 'user')
        <div class="mob-card bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-4 max-md:p-4"
             x-data="{ allOn: {{ is_null($user->permissions) ? 'true' : 'false' }} }">

            <div class="flex items-center justify-between mb-1 max-md:pb-3 max-md:mb-3 max-md:border-b max-md:border-gray-100">
                <div>
                    <p class="text-sm font-semibold text-gray-700">Access Permissions</p>
                    <p class="text-xs text-gray-400 mt-0.5">Control what this user can see and access</p>
                </div>
                <button type="button" @click="allOn = !allOn"
                        :class="allOn ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200'"
                        class="eu-allaccess-btn flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-semibold transition">
                    <i class="fa fa-shield-halved text-xs"></i>
                    <span x-text="allOn ? 'All Access (unrestricted)' : 'Custom restrictions'"></span>
                </button>
            </div>

            {{-- Hidden: when allOn, send no permissions[] so null is stored --}}
            <input type="hidden" name="_perms_sent" value="1">

            <div x-show="!allOn" x-cloak class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 eu-perm-grid">
                @foreach(\App\Models\User::ALL_PERMISSIONS as $key => $label)
                @php
                    $checked = is_null($user->permissions) || in_array($key, $user->permissions ?? []);
                @endphp
                <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 bg-gray-50 cursor-pointer hover:bg-indigo-50 hover:border-indigo-200 transition group max-md:rounded-[18px] max-md:px-4 max-md:py-3">
                    <input type="checkbox" name="permissions[]" value="{{ $key }}"
                           {{ $checked ? 'checked' : '' }}
                           class="w-4 h-4 rounded text-indigo-600 border-gray-300 focus:ring-indigo-400">
                    <div>
                        <p class="text-sm font-medium text-gray-700 group-hover:text-indigo-700 leading-none">{{ $label }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            @switch($key)
                                @case('view_activity_log') Shows task event history @break
                                @case('view_version_history') Shows submission versions @break
                                @case('view_comments') Shows & allows posting comments @break
                                @case('view_team_tasks') Shows colleagues' tasks tab @break
                                @case('view_projects') Access to projects section @break
                                @case('view_messages') Access to messages/chat @break
                                @case('view_team') View the team members page @break
                                @case('view_calendar') View the calendar @break
                                @case('submit_work') Can submit work for review @break
                            @endswitch
                        </p>
                    </div>
                </label>
                @endforeach
            </div>

            {{-- When allOn, send no checkboxes — controller sees no permissions[] and stores null --}}
            <p x-show="allOn" class="text-xs text-gray-400 mt-3">
                <i class="fa fa-circle-check text-indigo-400 mr-1"></i>
                This user has unrestricted access to all sections.
            </p>
        </div>
        @endif

        {{-- Actions --}}
        <div class="flex gap-3 eu-actions mob-sticky-action-bar">
            <button type="submit"
                    class="mob-btn-primary flex-1 sm:flex-none sm:px-8 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-xl text-sm transition shadow-sm">
                <i class="fa fa-check mr-2"></i>Save Changes
            </button>
            <a href="{{ route('admin.users.index') }}"
               class="flex-1 sm:flex-none sm:px-6 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm text-center transition">
                Cancel
            </a>
        </div>

    </form>

    {{-- Transfer Tasks --}}
    @php
        $unfinishedCount = \App\Models\Task::where('assigned_to', $user->id)
            ->whereNotIn('status', ['completed', 'delivered'])->count();
        $otherUsers = \App\Models\User::where('id', '!=', $user->id)
            ->whereIn('role', ['user', 'manager'])->orderBy('name')->get();
    @endphp
    @if($unfinishedCount > 0 && $otherUsers->count() > 0)
    <div class="eu-warn-card bg-white rounded-xl border border-amber-200 shadow-sm p-6 mt-4 max-md:p-4">
        <div class="flex items-center gap-3 mb-4 max-md:pb-3 max-md:mb-3 max-md:border-b max-md:border-gray-100">
            <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
                <i class="fa fa-right-left text-amber-500 text-sm"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-800">Transfer Unfinished Tasks</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $user->name }} has <strong>{{ $unfinishedCount }}</strong> unfinished {{ Str::plural('task', $unfinishedCount) }}. Transfer them to another team member.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.users.transfer-tasks', $user) }}"
              onsubmit="return confirm('Transfer all {{ $unfinishedCount }} unfinished task(s) from {{ $user->name }}?')">
            @csrf
            <div class="flex gap-3 items-center eu-transfer-row">
                <select name="to_user_id" required
                        class="flex-1 px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition">
                    <option value="">— Select recipient —</option>
                    @foreach($otherUsers as $ou)
                    <option value="{{ $ou->id }}">{{ $ou->name }} ({{ ucfirst($ou->role) }})</option>
                    @endforeach
                </select>
                <button type="submit"
                        class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-xl text-sm transition shadow-sm whitespace-nowrap">
                    <i class="fa fa-right-left mr-1.5"></i> Transfer
                </button>
            </div>
        </form>
    </div>
    @endif

</div>
@endsection
