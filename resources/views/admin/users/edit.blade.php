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
</div>
@endsection
