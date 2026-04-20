@extends('layouts.app')
@section('title', 'Edit User')

@section('content')
<div class="max-w-2xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.users.index') }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500 transition">
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
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-4"
             x-data="{ preview: '{{ $user->avatarUrl() }}' }">
            <p class="text-sm font-semibold text-gray-700 mb-4">Profile Photo</p>
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
                    <label class="absolute -bottom-1 -right-1 w-7 h-7 bg-indigo-600 hover:bg-indigo-700 rounded-full flex items-center justify-center cursor-pointer shadow transition">
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
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-4">
            <p class="text-sm font-semibold text-gray-700 mb-4">Basic Information</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Full Name <span class="text-red-400">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full px-3 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }}">
                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email Address <span class="text-red-400">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full px-3 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }}">
                    @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Phone Number</label>
                    <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+1 555 000 0000"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Job Title</label>
                    <input type="text" name="job_title" value="{{ old('job_title', $user->job_title) }}" placeholder="e.g. Frontend Developer"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Role <span class="text-red-400">*</span></label>
                    <select name="role" required
                            class="w-full px-3 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition bg-gray-50 {{ $errors->has('role') ? 'border-red-400' : 'border-gray-200' }}">
                        <option value="admin"   {{ old('role',$user->role)==='admin'   ? 'selected':'' }}>Admin</option>
                        <option value="manager" {{ old('role',$user->role)==='manager' ? 'selected':'' }}>Manager</option>
                        <option value="user"    {{ old('role',$user->role)==='user'    ? 'selected':'' }}>User</option>
                    </select>
                </div>

                <div>
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
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6">
            <p class="text-sm font-semibold text-gray-700 mb-1">Change Password</p>
            <p class="text-xs text-gray-400 mb-4">Leave blank to keep the current password</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
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
                <div>
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

        {{-- Permissions --}}
        @if($user->role === 'user')
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-4"
             x-data="{ allOn: {{ is_null($user->permissions) ? 'true' : 'false' }} }">

            <div class="flex items-center justify-between mb-1">
                <div>
                    <p class="text-sm font-semibold text-gray-700">Access Permissions</p>
                    <p class="text-xs text-gray-400 mt-0.5">Control what this user can see and access</p>
                </div>
                <button type="button" @click="allOn = !allOn"
                        :class="allOn ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200'"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-semibold transition">
                    <i class="fa fa-shield-halved text-xs"></i>
                    <span x-text="allOn ? 'All Access (unrestricted)' : 'Custom restrictions'"></span>
                </button>
            </div>

            {{-- Hidden: when allOn, send no permissions[] so null is stored --}}
            <input type="hidden" name="_perms_sent" value="1">

            <div x-show="!allOn" x-cloak class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach(\App\Models\User::ALL_PERMISSIONS as $key => $label)
                @php
                    $checked = is_null($user->permissions) || in_array($key, $user->permissions ?? []);
                @endphp
                <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 bg-gray-50 cursor-pointer hover:bg-indigo-50 hover:border-indigo-200 transition group">
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
        <div class="flex gap-3">
            <button type="submit"
                    class="flex-1 sm:flex-none sm:px-8 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-xl text-sm transition shadow-sm">
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
    <div class="bg-white rounded-xl border border-amber-200 shadow-sm p-6 mt-4">
        <div class="flex items-center gap-3 mb-4">
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
            <div class="flex gap-3 items-center">
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
