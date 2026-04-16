@extends('layouts.app')

@section('title', 'Create Project')

@section('content')
<div class="max-w-2xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.projects.index') }}" class="text-gray-400 hover:text-gray-600 transition">
            <i class="fa fa-arrow-left"></i>
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Create New Project</h1>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <form method="POST" action="{{ route('admin.projects.store') }}" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Project Name <span class="text-red-400">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full px-3 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }}"
                       placeholder="e.g. Mobile App Design">
                @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="4"
                          class="w-full px-3 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition {{ $errors->has('description') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }}"
                          placeholder="Brief description of the project...">{{ old('description') }}</textarea>
                @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Deadline <span class="text-red-400">*</span></label>
                <input type="date" name="deadline" value="{{ old('deadline') }}" required
                       class="w-full px-3 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition {{ $errors->has('deadline') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }}">
                @error('deadline') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-xl text-sm transition shadow-sm shadow-indigo-200">
                    Create Project
                </button>
                <a href="{{ route('admin.projects.index') }}" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm text-center transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
