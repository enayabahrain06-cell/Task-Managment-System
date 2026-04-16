@extends('layouts.app')

@section('title', 'Add Task to ' . $project->name)

@section('content')
<div class="max-w-2xl mx-auto">
    <form method="POST" action="{{ route('admin.projects.tasks.store', $project) }}">
        @csrf
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-bold mb-6">Add Task to {{ $project->name }}</h2>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Title</label>
                <input type="text" name="title" value="{{ old('title') }}" class="w-full px-3 py-2 border rounded-lg @error('title') border-red-500 @enderror" required>
                @error('title')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded-lg @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Assigned To</label>
                    <select name="assigned_to" class="w-full px-3 py-2 border rounded-lg @error('assigned_to') border-red-500 @enderror" required>
                        <option value="">Select User</option>
                        @foreach(\App\Models\User::where('role', '!=', 'admin')->get() as $user)
                            <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ ucfirst($user->role) }})</option>
                        @endforeach
                    </select>
                    @error('assigned_to')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Priority</label>
                    <select name="priority" class="w-full px-3 py-2 border rounded-lg @error('priority') border-red-500 @enderror" required>
                        <option value="low" {{ old('priority', 'medium') == 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('priority', 'medium') == 'high' ? 'selected' : '' }}>High</option>
                    </select>
                    @error('priority')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Deadline</label>
                <input type="date" name="deadline" value="{{ old('deadline') }}" class="w-full px-3 py-2 border rounded-lg @error('deadline') border-red-500 @enderror" required>
                @error('deadline')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex space-x-3">
                <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    Add Task
                </button>
                <a href="{{ route('admin.projects.show', $project) }}" class="flex-1 bg-gray-500 text-white px-4 py-2 rounded-lg text-center hover:bg-gray-600">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</div>
@endsection

