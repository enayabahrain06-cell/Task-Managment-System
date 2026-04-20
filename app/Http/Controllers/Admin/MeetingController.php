<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'title'            => 'required|string|max:120',
            'description'      => 'nullable|string|max:500',
            'meeting_date'     => 'required|date',
            'start_time'       => 'required',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'location'         => 'nullable|string|max:120',
            'color'            => 'nullable|string|max:20',
            'attendees'        => 'nullable|array',
            'attendees.*'      => 'exists:users,id',
        ]);

        $meeting = Meeting::create([
            'title'            => $request->title,
            'description'      => $request->description,
            'meeting_date'     => $request->meeting_date,
            'start_time'       => $request->start_time,
            'duration_minutes' => $request->duration_minutes,
            'location'         => $request->location,
            'color'            => $request->color ?? '#4F46E5',
            'created_by'       => auth()->id(),
        ]);

        if ($request->filled('attendees')) {
            $meeting->attendees()->sync($request->attendees);
        }

        // Always add the creator as attendee
        $meeting->attendees()->syncWithoutDetaching([auth()->id()]);

        return back()->with('success', 'Meeting created successfully.');
    }

    public function update(Request $request, Meeting $meeting)
    {
        $request->validate([
            'title'            => 'required|string|max:120',
            'description'      => 'nullable|string|max:500',
            'meeting_date'     => 'required|date',
            'start_time'       => 'required',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'location'         => 'nullable|string|max:120',
            'color'            => 'nullable|string|max:20',
            'attendees'        => 'nullable|array',
            'attendees.*'      => 'exists:users,id',
        ]);

        $meeting->update($request->only('title','description','meeting_date','start_time','duration_minutes','location','color'));
        $attendees = $request->attendees ?? [];
        if (!in_array(auth()->id(), $attendees)) $attendees[] = auth()->id();
        $meeting->attendees()->sync($attendees);

        return back()->with('success', 'Meeting updated.');
    }

    public function reschedule(Request $request, Meeting $meeting)
    {
        $request->validate([
            'meeting_date' => 'required|date',
            'start_time'   => 'nullable|date_format:H:i',
        ]);
        $data = ['meeting_date' => $request->meeting_date];
        if ($request->filled('start_time')) $data['start_time'] = $request->start_time;
        $meeting->update($data);
        return response()->json(['ok' => true]);
    }

    public function destroy(Meeting $meeting)
    {
        $meeting->delete();
        return back()->with('success', 'Meeting deleted.');
    }
}
