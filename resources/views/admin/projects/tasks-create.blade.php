@extends('layouts.app')
@section('title', 'Add Task — ' . $project->name)

@section('content')
<div style="max-width:600px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
        <a href="{{ route('admin.projects.show', $project) }}"
           style="width:34px;height:34px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;color:#6B7280;text-decoration:none;">
            <i class="fa fa-arrow-left" style="font-size:13px;"></i>
        </a>
        <div>
            <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0;">Add Task</h1>
            <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">{{ $project->name }}</p>
        </div>
    </div>

    <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:28px;">
        <form method="POST" action="{{ route('admin.projects.tasks.store', $project) }}">
            @csrf

            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Task Title <span style="color:#EF4444;">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" required placeholder="e.g. Design landing page"
                       style="width:100%;padding:10px 14px;border:1.5px solid {{ $errors->has('title') ? '#EF4444' : '#E5E7EB' }};border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;">
                @error('title')<p style="font-size:11px;color:#DC2626;margin-top:4px;">{{ $message }}</p>@enderror
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Description</label>
                <textarea name="description" rows="3" placeholder="What needs to be done..."
                          style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;resize:vertical;font-family:'Inter',sans-serif;">{{ old('description') }}</textarea>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Assign To <span style="color:#EF4444;">*</span></label>
                    <select name="assigned_to" required
                            style="width:100%;padding:10px 14px;border:1.5px solid {{ $errors->has('assigned_to') ? '#EF4444' : '#E5E7EB' }};border-radius:10px;font-size:14px;color:#111827;background:#fff;outline:none;">
                        <option value="">— Select member —</option>
                        @foreach($members as $m)
                        <option value="{{ $m->id }}" {{ old('assigned_to') == $m->id ? 'selected' : '' }}>
                            {{ $m->name }} ({{ ucfirst($m->role) }})
                        </option>
                        @endforeach
                    </select>
                    @error('assigned_to')<p style="font-size:11px;color:#DC2626;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Priority <span style="color:#EF4444;">*</span></label>
                    <select name="priority" required
                            style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;background:#fff;outline:none;">
                        <option value="low"    {{ old('priority','medium')==='low'    ? 'selected':'' }}>Low</option>
                        <option value="medium" {{ old('priority','medium')==='medium' ? 'selected':'' }}>Medium</option>
                        <option value="high"   {{ old('priority','medium')==='high'   ? 'selected':'' }}>High</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom:24px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Deadline <span style="color:#EF4444;">*</span></label>
                <input type="date" name="deadline" value="{{ old('deadline') }}" required
                       style="width:100%;padding:10px 14px;border:1.5px solid {{ $errors->has('deadline') ? '#EF4444' : '#E5E7EB' }};border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;">
                @error('deadline')<p style="font-size:11px;color:#DC2626;margin-top:4px;">{{ $message }}</p>@enderror
            </div>

            <div style="display:flex;gap:10px;">
                <button type="submit"
                        style="flex:1;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;padding:11px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                    <i class="fa fa-plus" style="margin-right:6px;"></i>Add Task
                </button>
                <a href="{{ route('admin.projects.show', $project) }}"
                   style="flex:1;background:#F3F4F6;color:#374151;padding:11px;border-radius:10px;font-size:14px;font-weight:600;text-align:center;text-decoration:none;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
