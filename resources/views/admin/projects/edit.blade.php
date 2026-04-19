@extends('layouts.app')
@section('title', 'Edit Project')

@section('content')
<div style="max-width:680px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
        <a href="{{ route('admin.projects.index') }}"
           style="width:34px;height:34px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;color:#6B7280;text-decoration:none;">
            <i class="fa fa-arrow-left" style="font-size:13px;"></i>
        </a>
        <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0;">Edit Project</h1>
    </div>

    <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:28px;">
        <form method="POST" action="{{ route('admin.projects.update', $project) }}">
            @csrf @method('PUT')

            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Project Name <span style="color:#EF4444;">*</span></label>
                <input type="text" name="name" value="{{ old('name', $project->name) }}" required
                       style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;">
                @error('name')<p style="font-size:11px;color:#DC2626;margin-top:4px;">{{ $message }}</p>@enderror
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Description</label>
                <textarea name="description" rows="3"
                          style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;resize:vertical;font-family:'Inter',sans-serif;">{{ old('description', $project->description) }}</textarea>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Deadline <span style="color:#EF4444;">*</span></label>
                    <input type="date" name="deadline" value="{{ old('deadline', $project->deadline->format('Y-m-d')) }}" required
                           style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Status <span style="color:#EF4444;">*</span></label>
                    <select name="status" required
                            style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;background:#fff;outline:none;">
                        <option value="active"    {{ old('status',$project->status)==='active'    ? 'selected':'' }}>Active</option>
                        <option value="completed" {{ old('status',$project->status)==='completed' ? 'selected':'' }}>Completed</option>
                        <option value="overdue"   {{ old('status',$project->status)==='overdue'   ? 'selected':'' }}>Overdue</option>
                    </select>
                </div>
            </div>

            {{-- Team Members --}}
            <div style="margin-bottom:24px;" x-data="{ search: '' }">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                    Team Members
                    <span style="font-weight:400;color:#9CA3AF;">({{ count($memberIds) }} currently assigned)</span>
                </label>

                <input type="text" x-model="search" placeholder="Search members..."
                       style="width:100%;padding:8px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;margin-bottom:10px;">

                <div style="border:1.5px solid #E5E7EB;border-radius:10px;max-height:220px;overflow-y:auto;">
                    @foreach($users as $u)
                    <div x-show="search === '' || '{{ strtolower($u->name) }}'.includes(search.toLowerCase())">
                        <label style="display:flex;align-items:center;gap:10px;padding:10px 14px;cursor:pointer;border-bottom:1px solid #F9FAFB;transition:background .1s;"
                               onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background=''">
                            <input type="checkbox" name="members[]" value="{{ $u->id }}"
                                   {{ in_array($u->id, old('members', $memberIds)) ? 'checked' : '' }}
                                   style="width:15px;height:15px;accent-color:#6366F1;flex-shrink:0;">
                            <div style="width:32px;height:32px;border-radius:50%;background:{{ in_array($u->id, $memberIds) ? 'linear-gradient(135deg,#6366F1,#8B5CF6)' : '#E5E7EB' }};display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:{{ in_array($u->id, $memberIds) ? '#fff' : '#9CA3AF' }};flex-shrink:0;">
                                {{ strtoupper(substr($u->name,0,1)) }}
                            </div>
                            <div style="flex:1;min-width:0;">
                                <p style="font-size:13px;font-weight:600;color:#111827;margin:0;">{{ $u->name }}</p>
                                <p style="font-size:11px;color:#9CA3AF;margin:0;">{{ ucfirst($u->role) }}{{ $u->job_title ? ' · '.$u->job_title : '' }}</p>
                            </div>
                            @if(in_array($u->id, $memberIds))
                            <span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:10px;background:#EEF2FF;color:#4F46E5;">Member</span>
                            @endif
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="submit"
                        style="flex:1;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;padding:11px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                    <i class="fa fa-floppy-disk" style="margin-right:6px;"></i>Save Changes
                </button>
                <a href="{{ route('admin.projects.index') }}"
                   style="flex:1;background:#F3F4F6;color:#374151;border:none;padding:11px;border-radius:10px;font-size:14px;font-weight:600;text-align:center;text-decoration:none;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
