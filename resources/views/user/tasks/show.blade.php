@extends('layouts.app')

@section('title', $task->title)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow rounded-lg p-8 mb-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $task->title }}</h1>
                <p class="text-xl text-gray-600 mt-2">{{ $task->description }}</p>
            </div>
            <div class="text-right">
                <span class="px-4 py-2 text-sm rounded-full bg-{{ $task->status == 'pending' ? 'gray' : ($task->status == 'in_progress' ? 'yellow' : 'green') }}-100 text-{{ $task->status == 'pending' ? 'gray' : ($task->status == 'in_progress' ? 'yellow' : 'green') }}-800 font-semibold">
                    {{ ucfirst($task->status) }}
                </span>
                <p class="text-2xl font-bold text-gray-900 mt-2">{{ $task->deadline->format('M d, Y') }}</p>
                @if($task->deadline < now() && $task->status != 'completed')
                    <p class="text-red-600 font-semibold">OVERDUE</p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <h3 class="text-lg font-semibold mb-4">Project</h3>
                <p class="text-gray-700">{{ $task->project->name }}</p>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-4">Priority</h3>
                <span class="px-3 py-1 text-sm rounded-full bg-{{ $task->priority == 'low' ? 'green' : ($task->priority == 'medium' ? 'yellow' : 'red') }}-100 text-{{ $task->priority == 'low' ? 'green' : ($task->priority == 'medium' ? 'yellow' : 'red') }}-800 font-semibold">
                    {{ ucfirst($task->priority) }}
                </span>
            </div>
        </div>

        <div class="mt-8">
            <form method="POST" action="{{ route('user.tasks.updateStatus', $task) }}" class="bg-gray-50 p-6 rounded-lg">
                @csrf @method('PATCH')
                <h3 class="text-lg font-semibold mb-4">Update Status</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <select name="status" class="p-2 border rounded-lg @error('status') border-red-500 @enderror" required>
                        <option value="pending" {{ old('status', $task->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ old('status', $task->status) == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ old('status', $task->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                    <input type="text" name="note" placeholder="Optional note..." value="{{ old('note') }}" class="p-2 border rounded-lg @error('note') border-red-500 @enderror">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-semibold">
                        Update
                    </button>
                </div>
                @error('status')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </form>
        </div>

        <div class="mt-12">
            <h3 class="text-xl font-bold mb-6">Activity Log</h3>
            <div class="bg-gray-50 rounded-lg p-6">
                @forelse($task->logs as $log)
                    <div class="flex items-start space-x-4 mb-4 pb-4 border-b last:border-b-0 last:mb-0 last:pb-0">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-semibold text-sm">{{ substr($log->user->name, 0, 1) }}</span>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-1">
                                <span class="font-semibold text-gray-900">{{ $log->user->name }}</span>
                                <span class="text-sm text-gray-500">{{ $log->created_at->format('M d, H:i') }}</span>
                            </div>
                            <p class="text-gray-700">{{ $log->action }}</p>
                            @if($log->note)
                                <p class="text-sm text-gray-500 mt-1">"{{ $log->note }}"</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-8">No activity yet</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

