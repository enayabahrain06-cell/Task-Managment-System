@extends('layouts.app')

@section('title', $project->name)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-start mb-6">
        <div>
            <h1 class="text-3xl font-bold">{{ $project->name }}</h1>
            <p class="text-xl text-gray-600 mt-1">{{ $project->description }}</p>
        </div>
        <div class="text-right">
            <span class="px-3 py-1 text-sm rounded-full bg-{{ $project->status == 'active' ? 'green' : ($project->status == 'completed' ? 'gray' : 'red') }}-100 text-{{ $project->status == 'active' ? 'green' : ($project->status == 'completed' ? 'gray' : 'red') }}-800">
                {{ ucfirst($project->status) }}
            </span>
            <p class="text-gray-500 mt-1">Deadline: {{ $project->deadline->format('M d, Y') }}</p>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold">Tasks ({{ $project->tasks->count() }})</h2>
            <a href="{{ route('admin.projects.tasks.create', $project) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Add Task
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($project->tasks as $task)
                <div class="border rounded-lg p-4 hover:shadow-md">
                    <h3 class="font-bold text-lg mb-2">{{ $task->title }}</h3>
                    <p class="text-gray-600 mb-2">{{ Str::limit($task->description, 100) }}</p>
                    <div class="flex justify-between items-center">
                        <span class="px-2 py-1 text-xs rounded-full bg-{{ $task->status == 'pending' ? 'gray' : ($task->status == 'in_progress' ? 'yellow' : 'green') }}-100 text-{{ $task->status == 'pending' ? 'gray' : ($task->status == 'in_progress' ? 'yellow' : 'green') }}-800">
                            {{ ucfirst($task->status) }}
                        </span>
                        <span class="text-sm text-gray-500">{{ $task->deadline->format('M d') }}</span>
                    </div>
                    <div class="mt-2 text-sm text-gray-500">
                        Assigned to: {{ $task->assignee->name }}
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-8 text-gray-500">
                    No tasks yet. Add one above!
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

