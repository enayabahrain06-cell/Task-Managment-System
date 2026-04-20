@extends('layouts.app')
@section('title', 'Create User')

@section('content')
<div class="max-w-2xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.users.index') }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500 transition">
            <i class="fa fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Create User</h1>
            <p class="text-sm text-gray-400 mt-0.5">Add a new member to the team</p>
        </div>
    </div>

    @php
        $allPermKeys  = array_keys(\App\Models\User::ALL_PERMISSIONS);
        $hasOld       = old('_perms_sent') !== null;
        $oldPerms     = old('permissions', []);
        $permsAllOn   = (!$hasOld || empty($oldPerms)) ? 'true' : 'false';
        $permsInit    = $hasOld && !empty($oldPerms) ? $oldPerms : $allPermKeys;
        $createPermGroups = [
            'Content & Tasks' => [
                'icon' => 'fa-file-lines', 'color' => '#6366F1', 'bg' => '#EEF2FF',
                'perms' => [
                    'view_activity_log'    => ['icon' => 'fa-bolt',              'label' => 'Activity Log',       'desc' => 'Task history and change log'],
                    'view_version_history' => ['icon' => 'fa-clock-rotate-left', 'label' => 'Version History',    'desc' => 'Submitted versions and review history'],
                    'view_comments'        => ['icon' => 'fa-comments',          'label' => 'Comments & Updates', 'desc' => 'Read and write task comments'],
                    'submit_work'          => ['icon' => 'fa-paper-plane',       'label' => 'Submit Work',        'desc' => 'Submit deliverables for manager review'],
                ],
            ],
            'Navigation & Pages' => [
                'icon' => 'fa-compass', 'color' => '#8B5CF6', 'bg' => '#F5F3FF',
                'perms' => [
                    'view_messages'   => ['icon' => 'fa-comment-dots',    'label' => 'Messages',       'desc' => 'Direct messaging with teammates'],
                    'view_team'       => ['icon' => 'fa-users',           'label' => 'Team Page',      'desc' => 'Browse team member profiles'],
                    'view_calendar'   => ['icon' => 'fa-calendar-days',   'label' => 'Calendar',       'desc' => 'View task deadlines and schedule'],
                    'view_projects'   => ['icon' => 'fa-diagram-project', 'label' => 'Projects',       'desc' => 'Personal projects section'],
                    'view_team_tasks' => ['icon' => 'fa-list-check',      'label' => 'Team Tasks Tab', 'desc' => 'See tasks assigned to teammates'],
                ],
            ],
        ];
    @endphp

    <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data"
          x-data="{ role: '{{ old('role', '') }}' }">
        @csrf

        {{-- Avatar Upload --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-4"
             x-data="{ preview: null }">
            <p class="text-sm font-semibold text-gray-700 mb-4">Profile Photo</p>
            <div class="flex items-center gap-5">
                {{-- Preview circle --}}
                <div class="relative flex-shrink-0">
                    <div class="w-20 h-20 rounded-full overflow-hidden bg-indigo-100 flex items-center justify-center border-2 border-white shadow-md">
                        <template x-if="preview">
                            <img :src="preview" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!preview">
                            <i class="fa fa-user text-2xl text-indigo-300"></i>
                        </template>
                    </div>
                    <label class="absolute -bottom-1 -right-1 w-7 h-7 bg-indigo-600 hover:bg-indigo-700 rounded-full flex items-center justify-center cursor-pointer shadow transition">
                        <i class="fa fa-camera text-white text-xs"></i>
                        <input type="file" name="avatar" accept="image/*" class="hidden"
                               @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
                    </label>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-700">Upload photo</p>
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
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="John Doe"
                           class="w-full px-3 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }}">
                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email Address <span class="text-red-400">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="user@company.com"
                           class="w-full px-3 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }}">
                    @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Phone Number</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="+1 555 000 0000"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition">
                    @error('phone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Job Title</label>
                    <input type="text" name="job_title" value="{{ old('job_title') }}" placeholder="e.g. Frontend Developer"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition">
                    @error('job_title') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Role <span class="text-red-400">*</span></label>
                    <select name="role" required x-model="role"
                            class="w-full px-3 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition bg-gray-50 {{ $errors->has('role') ? 'border-red-400' : 'border-gray-200' }}">
                        <option value="">Select role</option>
                        <option value="admin"   {{ old('role')==='admin'   ? 'selected':'' }}>Admin</option>
                        <option value="manager" {{ old('role')==='manager' ? 'selected':'' }}>Manager</option>
                        <option value="user"    {{ old('role')==='user'    ? 'selected':'' }}>User</option>
                    </select>
                    @error('role') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Status</label>
                    <select name="status"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition">
                        <option value="active"   {{ old('status','active')==='active'   ? 'selected':'' }}>Active</option>
                        <option value="inactive" {{ old('status')==='inactive' ? 'selected':'' }}>Inactive</option>
                    </select>
                </div>

            </div>
        </div>

        {{-- Password --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6">
            <p class="text-sm font-semibold text-gray-700 mb-4">Password</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Password <span class="text-red-400">*</span></label>
                    <div class="relative" x-data="{show:false}">
                        <input :type="show?'text':'password'" name="password" required placeholder="Min. 8 characters"
                               class="w-full px-3 py-2.5 pr-10 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }}">
                        <button type="button" @click="show=!show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i :class="show?'fa fa-eye-slash':'fa fa-eye'" class="text-sm"></i>
                        </button>
                    </div>
                    @error('password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Confirm Password <span class="text-red-400">*</span></label>
                    <div class="relative" x-data="{show:false}">
                        <input :type="show?'text':'password'" name="password_confirmation" required placeholder="Re-enter password"
                               class="w-full px-3 py-2.5 pr-10 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition">
                        <button type="button" @click="show=!show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i :class="show?'fa fa-eye-slash':'fa fa-eye'" class="text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        function createPermsData() {
            return {
                allOn:   {{ $permsAllOn }},
                perms:   @json($permsInit),
                allKeys: @json($allPermKeys),
                hasPermission(key) { return this.allOn || this.perms.includes(key); },
                toggle(key) {
                    if (this.allOn) return;
                    const idx = this.perms.indexOf(key);
                    if (idx >= 0) this.perms.splice(idx, 1);
                    else this.perms.push(key);
                },
                setAll(val) {
                    this.allOn = val;
                    if (!val && this.perms.length === 0) this.perms = [...this.allKeys];
                },
            };
        }
        </script>

        {{-- Permissions (only for user role) --}}
        <div x-show="role === 'user'" x-cloak class="mb-4" x-data="createPermsData()">

            {{-- Hidden flag + per-permission checkboxes (disabled when allOn so nothing submits → null stored) --}}
            <input type="hidden" name="_perms_sent" value="1">
            @foreach($allPermKeys as $pk)
            <input type="checkbox" name="permissions[]" value="{{ $pk }}"
                   :checked="perms.includes('{{ $pk }}')"
                   :disabled="allOn"
                   class="sr-only">
            @endforeach

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">

                {{-- Header + Full Access toggle --}}
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-700">Permissions</p>
                        <p class="text-xs text-gray-400 mt-0.5">Control what this user can see and access</p>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <span class="text-xs font-medium" :class="allOn ? 'text-emerald-600' : 'text-gray-400'"
                              x-text="allOn ? 'Full Access' : 'Custom'"></span>
                        <button type="button" @click="setAll(!allOn)"
                            :class="allOn ? 'bg-emerald-500 border-emerald-500' : 'bg-white border-gray-300'"
                            style="position:relative;width:44px;height:24px;border-radius:12px;border:2px solid;transition:background .2s,border-color .2s;cursor:pointer;flex-shrink:0;outline:none;">
                            <span :style="allOn ? 'transform:translateX(20px)' : 'transform:translateX(0)'"
                                  style="position:absolute;top:2px;left:2px;width:16px;height:16px;background:#fff;border-radius:50%;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:transform .2s;display:block;"></span>
                        </button>
                    </div>
                </div>

                {{-- Full Access notice --}}
                <div x-show="allOn" style="padding:12px 24px;background:#ECFDF5;border-bottom:1px solid #A7F3D0;">
                    <p style="font-size:12px;color:#059669;">
                        <i class="fa fa-circle-check" style="margin-right:5px;"></i>
                        User will have unrestricted access to all features and pages.
                    </p>
                </div>

                {{-- Permission groups --}}
                <div x-show="!allOn" x-cloak>
                    @foreach($createPermGroups as $groupName => $group)
                    <div class="{{ !$loop->first ? 'border-t border-gray-100' : '' }}">
                        <div style="padding:10px 24px;background:#FAFAFA;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;gap:8px;">
                            <div style="width:24px;height:24px;border-radius:6px;background:{{ $group['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fa {{ $group['icon'] }}" style="font-size:10px;color:{{ $group['color'] }};"></i>
                            </div>
                            <span style="font-size:12px;font-weight:600;color:#374151;text-transform:uppercase;letter-spacing:.04em;">{{ $groupName }}</span>
                        </div>
                        @foreach($group['perms'] as $key => $perm)
                        <div style="padding:12px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;{{ !$loop->last ? 'border-bottom:1px solid #F9FAFB;' : '' }}"
                             style2="cursor:pointer;" @click="toggle('{{ $key }}')"
                             class="hover:bg-gray-50 transition-colors cursor-pointer">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:8px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fa {{ $perm['icon'] }}" style="font-size:12px;color:#6B7280;"></i>
                                </div>
                                <div>
                                    <p style="font-size:13px;font-weight:600;color:#374151;">{{ $perm['label'] }}</p>
                                    <p style="font-size:11px;color:#9CA3AF;margin-top:1px;">{{ $perm['desc'] }}</p>
                                </div>
                            </div>
                            <button type="button" @click.stop="toggle('{{ $key }}')"
                                :class="hasPermission('{{ $key }}') ? 'bg-indigo-500 border-indigo-500' : 'bg-white border-gray-300'"
                                style="position:relative;width:40px;height:22px;border-radius:11px;border:2px solid;transition:background .2s,border-color .2s;cursor:pointer;flex-shrink:0;outline:none;">
                                <span :style="hasPermission('{{ $key }}') ? 'transform:translateX(18px)' : 'transform:translateX(0)'"
                                      style="position:absolute;top:2px;left:2px;width:14px;height:14px;background:#fff;border-radius:50%;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:transform .2s;display:block;"></span>
                            </button>
                        </div>
                        @endforeach
                    </div>
                    @endforeach
                </div>

            </div>
        </div>

        {{-- Actions --}}
        <div class="flex gap-3">
            <button type="submit"
                    class="flex-1 sm:flex-none sm:px-8 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-xl text-sm transition shadow-sm">
                <i class="fa fa-user-plus mr-2"></i>Create User
            </button>
            <a href="{{ route('admin.users.index') }}"
               class="flex-1 sm:flex-none sm:px-6 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm text-center transition">
                Cancel
            </a>
        </div>

    </form>
</div>
@endsection
