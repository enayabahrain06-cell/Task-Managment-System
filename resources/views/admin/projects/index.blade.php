@extends('layouts.app')

@section('title', 'Projects')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $projects->total() }} total projects</p>
    </div>
    <a href="{{ route('admin.projects.create') }}" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm shadow-indigo-200">
        <i class="fa fa-plus"></i> New Project
    </a>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="w-full">
        <thead>
            <tr class="border-b border-gray-100 bg-gray-50/50">
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tasks</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Deadline</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($projects as $project)
            @php
                $statusBg = ['active' => 'bg-emerald-100 text-emerald-700', 'completed' => 'bg-gray-100 text-gray-600', 'overdue' => 'bg-red-100 text-red-600'];
            @endphp
            <tr class="hover:bg-gray-50/70 transition">
                <td class="px-5 py-3.5">
                    <a href="{{ route('admin.projects.show', $project) }}" class="text-sm font-semibold text-gray-900 hover:text-indigo-600 transition">
                        {{ $project->name }}
                    </a>
                    @if($project->description)
                    <p class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ $project->description }}</p>
                    @endif
                </td>
                <td class="px-5 py-3.5">
                    <span class="inline-flex items-center gap-1 text-sm text-gray-700">
                        <i class="fa fa-tasks text-gray-300 text-xs"></i> {{ $project->tasks_count }}
                    </span>
                </td>
                <td class="px-5 py-3.5">
                    <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $statusBg[$project->status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ ucfirst($project->status) }}
                    </span>
                </td>
                <td class="px-5 py-3.5 text-sm {{ $project->deadline < now() && $project->status !== 'completed' ? 'text-red-500 font-semibold' : 'text-gray-500' }}">
                    {{ $project->deadline->format('M d, Y') }}
                </td>
                <td class="px-5 py-3.5 text-sm text-gray-400">{{ $project->created_at->format('M d') }}</td>
                <td class="px-5 py-3.5">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.projects.show', $project) }}" class="text-xs font-medium text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-2.5 py-1.5 rounded-lg transition">View</a>
                        <a href="{{ route('admin.projects.edit', $project) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1.5 rounded-lg transition">Edit</a>
                        <form action="{{ route('admin.projects.destroy', $project) }}" method="POST" class="inline"
                              onsubmit="return confirm('Delete {{ addslashes($project->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-2.5 py-1.5 rounded-lg transition">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-5 py-12 text-center">
                    <i class="fa fa-project-diagram text-4xl text-gray-200 mb-3"></i>
                    <p class="text-sm text-gray-400">No projects found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if($projects->hasPages())
    <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50">
        {{ $projects->links() }}
    </div>
    @endif
</div>
@endsection
