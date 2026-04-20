@extends('layouts.app')

@section('title', 'Calendar')

@section('content')

@php
$isAdmin = auth()->user()->role === 'admin';
$allMeetingsJson = $allMeetings->map(fn($m) => [
    'id'               => $m->id,
    'title'            => $m->title,
    'description'      => $m->description ?? '',
    'meeting_date'     => $m->meeting_date->format('Y-m-d'),
    'start_time'       => substr($m->start_time, 0, 5),
    'duration_minutes' => (int) $m->duration_minutes,
    'location'         => $m->location ?? '',
    'color'            => $m->color,
    'attendees'        => $m->attendees->pluck('id')->values(),
    'attendee_details' => $m->attendees->map(fn($a) => ['id' => $a->id, 'name' => $a->name])->values(),
])->values();
@endphp

{{-- ── Meeting Modal (admin only) ── --}}
@if($isAdmin)
<div x-data="meetingModal()"
     @open-meeting-modal.window="openCreate()"
     @open-edit-meeting.window="openEdit($event.detail)">

    <template x-if="open">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="close()"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[92vh] overflow-y-auto" @click.stop>

                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-100 flex items-center justify-center">
                            <i class="fas fa-calendar-plus text-indigo-600 text-sm"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-base" x-text="editId ? 'Edit Meeting' : 'New Meeting'"></h3>
                            <p class="text-xs text-gray-400 mt-0.5">Schedule a meeting with your team</p>
                        </div>
                    </div>
                    <button @click="close()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 transition text-gray-400 hover:text-gray-600">
                        <i class="fas fa-xmark text-sm"></i>
                    </button>
                </div>

                {{-- Form --}}
                <form :action="editId ? '/admin/meetings/' + editId : '{{ route('admin.meetings.store') }}'"
                      method="POST" class="p-5 space-y-4">
                    @csrf
                    <input type="hidden" name="_method" :value="editId ? 'PUT' : 'POST'">

                    {{-- Title --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                            Meeting Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" x-model="form.title" required
                               placeholder="e.g. Weekly Sprint Review"
                               class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Description</label>
                        <textarea name="description" x-model="form.description" rows="2"
                                  placeholder="Meeting agenda or notes..."
                                  class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition resize-none"></textarea>
                    </div>

                    {{-- Date + Time --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                            <input type="date" name="meeting_date" x-model="form.meeting_date" required
                                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Start Time <span class="text-red-500">*</span></label>
                            <input type="time" name="start_time" x-model="form.start_time" required
                                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                        </div>
                    </div>

                    {{-- Duration + Location --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Duration</label>
                            <select name="duration_minutes" x-model="form.duration_minutes"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition bg-white">
                                <option value="15">15 min</option>
                                <option value="30">30 min</option>
                                <option value="45">45 min</option>
                                <option value="60">1 hour</option>
                                <option value="90">1.5 hours</option>
                                <option value="120">2 hours</option>
                                <option value="180">3 hours</option>
                                <option value="240">4 hours</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Location</label>
                            <input type="text" name="location" x-model="form.location"
                                   placeholder="Room / Zoom / Online"
                                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                        </div>
                    </div>

                    {{-- Color --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Color</label>
                        <div class="flex items-center gap-2">
                            <input type="color" x-model="form.color"
                                   class="w-9 h-9 rounded-lg border-2 cursor-pointer p-0.5 flex-shrink-0"
                                   :style="`border-color:${form.color}`">
                            <input type="text" name="color" x-model="form.color"
                                   class="w-28 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 transition">
                            <div class="flex items-center gap-1.5 flex-1">
                                @foreach(['#4F46E5','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#0EA5E9','#14B8A6'] as $c)
                                <button type="button" @click="form.color = '{{ $c }}'"
                                        class="w-6 h-6 rounded-full border-2 border-white shadow hover:scale-110 transition flex-shrink-0"
                                        style="background:{{ $c }}"
                                        :class="form.color === '{{ $c }}' ? 'ring-2 ring-offset-1 ring-gray-400' : ''"></button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Attendees --}}
                    @if($teamMembers->count())
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                            Attendees <span class="text-gray-400 font-normal">(you are always included)</span>
                        </label>
                        <div class="border border-gray-200 rounded-lg overflow-hidden max-h-44 overflow-y-auto divide-y divide-gray-100">
                            @foreach($teamMembers as $member)
                            <label class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" name="attendees[]" value="{{ $member->id }}"
                                       :checked="form.attendees.includes({{ $member->id }})"
                                       @change="toggleAttendee({{ $member->id }})"
                                       class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                @if($member->avatarUrl())
                                <img src="{{ $member->avatarUrl() }}" class="w-7 h-7 rounded-full object-cover flex-shrink-0" alt="">
                                @else
                                <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0 text-xs font-bold text-indigo-600">
                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $member->name }}</p>
                                    <p class="text-xs text-gray-400">{{ ucfirst($member->role) }}</p>
                                </div>
                                <i class="fas fa-check text-indigo-500 text-xs flex-shrink-0 transition"
                                   :class="form.attendees.includes({{ $member->id }}) ? 'opacity-100' : 'opacity-0'"></i>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Footer --}}
                    <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                        <button type="button" @click="close()"
                                class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                            Cancel
                        </button>
                        <button type="submit"
                                class="flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition shadow-sm">
                            <i class="fas fa-check text-xs"></i>
                            <span x-text="editId ? 'Update Meeting' : 'Schedule Meeting'"></span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </template>
</div>
@endif

{{-- ── Meeting Detail Modal (view / edit / reschedule) ── --}}
<div x-data="meetingDetailModal()"
     x-on:show-meeting-detail.window="openView($event.detail)"
     x-on:confirm-reschedule.window="openReschedule($event.detail)"
     x-cloak>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"  x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="backdrop-filter:blur(6px);background:rgba(0,0,0,0.5);"
         @click.self="close()">

        <div x-show="open"
             x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"  x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.stop>

            {{-- Color accent top bar --}}
            <div class="h-1.5 w-full" :style="`background:${meeting?.color || '#4F46E5'}`"></div>

            {{-- ── VIEW MODE ── --}}
            <div x-show="mode === 'view'">
                <div class="flex items-start justify-between px-6 pt-5 pb-3">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                             :style="`background:${meeting?.color || '#4F46E5'}22`">
                            <i class="fas fa-calendar-days text-sm" :style="`color:${meeting?.color || '#4F46E5'}`"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-gray-900 text-lg leading-tight" x-text="meeting?.title"></h2>
                            <p class="text-xs text-gray-400 mt-0.5">Scheduled Meeting</p>
                        </div>
                    </div>
                    <button @click="close()" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition flex-shrink-0 mt-0.5">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>

                <div class="px-6 pb-4 space-y-2.5">
                    {{-- Date & Time --}}
                    <div class="flex items-center gap-3 p-3 rounded-xl" style="background:#F8F7FF;">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                             :style="`background:${meeting?.color || '#4F46E5'}20`">
                            <i class="fas fa-clock text-xs" :style="`color:${meeting?.color || '#4F46E5'}`"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-800" x-text="formatDate(meeting?.meeting_date)"></p>
                            <p class="text-xs text-gray-500 mt-0.5" x-text="formatTimeRange(meeting?.start_time, meeting?.duration_minutes)"></p>
                        </div>
                    </div>
                    {{-- Location --}}
                    <div x-show="meeting?.location" class="flex items-center gap-3 p-3 bg-amber-50 rounded-xl">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-location-dot text-amber-500 text-xs"></i>
                        </div>
                        <p class="text-sm text-gray-700" x-text="meeting?.location"></p>
                    </div>
                    {{-- Description --}}
                    <div x-show="meeting?.description" class="p-3 bg-gray-50 rounded-xl">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Notes</p>
                        <p class="text-sm text-gray-600 leading-relaxed" x-text="meeting?.description"></p>
                    </div>
                    {{-- Attendees --}}
                    <div x-show="meeting?.attendee_details?.length" class="p-3 bg-gray-50 rounded-xl">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2.5">Attendees</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="att in (meeting?.attendee_details || [])" :key="att.id">
                                <div class="flex items-center gap-1.5 px-2.5 py-1.5 bg-white rounded-lg border border-gray-100 shadow-sm">
                                    <div class="w-5 h-5 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0"
                                         :style="`background:${meeting?.color || '#4F46E5'};font-size:9px`"
                                         x-text="att.name.charAt(0).toUpperCase()"></div>
                                    <span class="text-xs font-medium text-gray-700" x-text="att.name"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                @if($isAdmin)
                <div class="flex items-center gap-2 px-6 py-4 border-t border-gray-100">
                    <button @click="deleteMeeting()"
                            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition">
                        <i class="fas fa-trash-can text-xs"></i> Delete
                    </button>
                    <div class="flex-1"></div>
                    <button @click="openEdit()"
                            class="flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white rounded-lg transition shadow-sm"
                            :style="`background:${meeting?.color || '#4F46E5'}`">
                        <i class="fas fa-pen text-xs"></i> Edit Meeting
                    </button>
                </div>
                @endif
            </div>

            {{-- ── EDIT MODE ── --}}
            <div x-show="mode === 'edit'">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <button @click="mode='view'" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-700 transition">
                            <i class="fas fa-arrow-left text-sm"></i>
                        </button>
                        <h3 class="font-bold text-gray-900">Edit Meeting</h3>
                    </div>
                    <button @click="close()" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>

                <div class="p-5 space-y-4 max-h-[65vh] overflow-y-auto">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Title <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.title" placeholder="Meeting title"
                               class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Description</label>
                        <textarea x-model="form.description" rows="2" placeholder="Agenda or notes..."
                                  class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition resize-none"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                            <input type="date" x-model="form.meeting_date"
                                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Start Time <span class="text-red-500">*</span></label>
                            <input type="time" x-model="form.start_time"
                                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Duration</label>
                            <select x-model="form.duration_minutes"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 bg-white transition">
                                <option value="15">15 min</option>
                                <option value="30">30 min</option>
                                <option value="45">45 min</option>
                                <option value="60">1 hour</option>
                                <option value="90">1.5 hours</option>
                                <option value="120">2 hours</option>
                                <option value="180">3 hours</option>
                                <option value="240">4 hours</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Location</label>
                            <input type="text" x-model="form.location" placeholder="Room / Zoom..."
                                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Color</label>
                        <div class="flex items-center gap-2">
                            <input type="color" x-model="form.color"
                                   class="w-9 h-9 rounded-lg border-2 cursor-pointer p-0.5 flex-shrink-0"
                                   :style="`border-color:${form.color}`">
                            <div class="flex items-center gap-1.5 flex-1">
                                <template x-for="c in ['#4F46E5','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#0EA5E9','#14B8A6']" :key="c">
                                    <button type="button" @click="form.color = c"
                                            class="w-6 h-6 rounded-full border-2 border-white shadow hover:scale-110 transition"
                                            :style="`background:${c};` + (form.color === c ? 'outline:2px solid #6B7280;outline-offset:2px;' : '')"></button>
                                </template>
                            </div>
                        </div>
                    </div>
                    @if($teamMembers->count())
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Attendees</label>
                        <div class="border border-gray-200 rounded-lg overflow-hidden max-h-36 overflow-y-auto divide-y divide-gray-100">
                            @foreach($teamMembers as $member)
                            <label class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" :checked="form.attendees.includes({{ $member->id }})"
                                       @change="toggleAttendee({{ $member->id }})"
                                       class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-bold text-indigo-600 flex-shrink-0">
                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                </div>
                                <span class="text-sm text-gray-700 flex-1">{{ $member->name }}</span>
                                <i class="fas fa-check text-indigo-500 text-xs transition" :class="form.attendees.includes({{ $member->id }}) ? 'opacity-100' : 'opacity-0'"></i>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                <div class="flex items-center gap-2 px-5 py-4 border-t border-gray-100">
                    <button @click="mode='view'" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                        Cancel
                    </button>
                    <div class="flex-1"></div>
                    <button @click="saveEdit()" :disabled="saving"
                            class="flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition shadow-sm disabled:opacity-60">
                        <i class="fas fa-check text-xs"></i>
                        <span x-text="saving ? 'Saving...' : 'Save Changes'"></span>
                    </button>
                </div>
            </div>

            {{-- ── RESCHEDULE MODE ── --}}
            <div x-show="mode === 'reschedule'">
                <div class="px-6 py-5">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-calendar-plus text-indigo-600"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-bold text-gray-900">Move Meeting</h3>
                            <p class="text-xs text-gray-400 mt-0.5 truncate" x-text="`Moving to ${formatDate(reschedule.new_date)}`"></p>
                        </div>
                    </div>
                    <div class="p-3 bg-indigo-50 rounded-xl mb-4">
                        <p class="text-xs font-semibold text-indigo-600 truncate" x-text="reschedule.title"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Start time on the new date</label>
                        <input type="time" x-model="reschedule.start_time"
                               class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                        <p class="text-xs text-gray-400 mt-1.5">Keep it the same or pick a new time.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 px-6 py-4 border-t border-gray-100">
                    <button @click="cancelReschedule()" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                        Revert
                    </button>
                    <div class="flex-1"></div>
                    <button @click="confirmReschedule()" :disabled="saving"
                            class="flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition shadow-sm disabled:opacity-60">
                        <i class="fas fa-arrows-up-down text-xs"></i>
                        <span x-text="saving ? 'Moving...' : 'Confirm Move'"></span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ── Page Header ── --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Calendar & Meetings</h1>
        <p class="text-sm text-gray-500 mt-0.5">Task deadlines and scheduled events</p>
    </div>
    <div class="flex items-center gap-3">
        <div class="hidden md:flex items-center gap-2 text-xs font-medium text-gray-500">
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>Completed</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block"></span>In Progress</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>High Priority</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-indigo-500 inline-block"></span>Meeting</span>
        </div>
        @if($isAdmin)
        <button x-data="{}" @click="$dispatch('open-meeting-modal')"
                class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition shadow-sm">
            <i class="fas fa-plus text-xs"></i> New Meeting
        </button>
        @endif
    </div>
</div>

{{-- ── Main Grid ── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- Calendar --}}
    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <div id="calendar" data-events="{{ $events->toJson() }}"></div>
    </div>

    {{-- Sidebar --}}
    <div class="space-y-4">

        {{-- Today's Tasks --}}
        <div class="bg-gray-900 rounded-xl p-5 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-list-check text-gray-400 text-sm"></i>
                    <h3 class="font-semibold text-sm">Today's Tasks</h3>
                </div>
                <span class="text-xs bg-white/10 rounded-full px-2.5 py-0.5">{{ now()->format('M d') }}</span>
            </div>
            <div class="space-y-2.5">
                @forelse($todayTasks as $task)
                @php $routeName = auth()->user()->role === 'user' ? 'user.tasks.show' : null; @endphp
                <div class="flex items-start gap-2.5 p-3 bg-white/8 hover:bg-white/12 rounded-lg transition" style="background:rgba(255,255,255,0.07)">
                    <div class="w-1 rounded-full flex-shrink-0 mt-0.5 self-stretch
                        {{ $task->status === 'completed' ? 'bg-emerald-400' : ($task->priority === 'high' ? 'bg-red-400' : 'bg-indigo-400') }}">
                    </div>
                    <div class="flex-1 min-w-0">
                        @if($routeName)
                        <a href="{{ route($routeName, $task) }}" class="text-sm font-medium text-white hover:text-indigo-200 transition truncate block">{{ $task->title }}</a>
                        @else
                        <p class="text-sm font-medium text-white truncate">{{ $task->title }}</p>
                        @endif
                        <p class="text-xs text-white/50 mt-0.5">{{ $task->project->name }}</p>
                    </div>
                    <span class="text-xs px-1.5 py-0.5 rounded bg-white/10 flex-shrink-0
                        {{ $task->status === 'completed' ? 'text-emerald-300' : ($task->status === 'in_progress' ? 'text-amber-300' : 'text-gray-300') }}">
                        {{ str_replace('_', ' ', ucfirst($task->status)) }}
                    </span>
                </div>
                @empty
                <div class="text-center py-5">
                    <i class="fas fa-calendar-check text-3xl text-white/15 mb-2 block"></i>
                    <p class="text-xs text-white/40">No tasks due today</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Today's Meetings --}}
        <div class="bg-indigo-950 rounded-xl p-5 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-video text-indigo-400 text-sm"></i>
                    <h3 class="font-semibold text-sm">Today's Meetings</h3>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-xs bg-white/10 rounded-full px-2.5 py-0.5">{{ $todayMeetings->count() }}</span>
                    @if($isAdmin)
                    <button x-data="{}" @click="$dispatch('open-meeting-modal')"
                            class="w-6 h-6 rounded-full bg-indigo-500 hover:bg-indigo-400 flex items-center justify-center transition"
                            title="New Meeting">
                        <i class="fas fa-plus" style="font-size:9px;"></i>
                    </button>
                    @endif
                </div>
            </div>

            <div class="space-y-2.5">
                @forelse($todayMeetings->sortBy('start_time') as $m)
                <div class="p-3 rounded-lg transition" style="background:rgba(255,255,255,0.07)">
                    <div class="flex items-start gap-2">
                        <div class="w-1 self-stretch rounded-full flex-shrink-0" style="background:{{ $m->color }}"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-white truncate">{{ $m->title }}</p>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 mt-1">
                                <span class="text-xs text-indigo-300">
                                    <i class="fas fa-clock text-xs mr-1 opacity-70"></i>
                                    {{ \Carbon\Carbon::createFromTimeString($m->start_time)->format('g:i A') }}
                                    – {{ \Carbon\Carbon::createFromTimeString($m->start_time)->addMinutes($m->duration_minutes)->format('g:i A') }}
                                </span>
                                @if($m->location)
                                <span class="text-xs text-white/50">
                                    <i class="fas fa-location-dot text-xs mr-1 opacity-70"></i>{{ $m->location }}
                                </span>
                                @endif
                            </div>
                            @if($m->description)
                            <p class="text-xs text-white/40 mt-1 truncate">{{ $m->description }}</p>
                            @endif
                            {{-- Attendee avatars --}}
                            <div class="flex items-center gap-1 mt-2">
                                @foreach($m->attendees->take(6) as $att)
                                @if($att->avatarUrl())
                                <img src="{{ $att->avatarUrl() }}" title="{{ $att->name }}"
                                     class="w-5 h-5 rounded-full object-cover border border-white/20 flex-shrink-0" alt="">
                                @else
                                <div class="w-5 h-5 rounded-full bg-indigo-500/60 border border-white/20 flex items-center justify-center flex-shrink-0"
                                     title="{{ $att->name }}" style="font-size:9px;font-weight:700;">
                                    {{ strtoupper(substr($att->name, 0, 1)) }}
                                </div>
                                @endif
                                @endforeach
                                @if($m->attendees->count() > 6)
                                <div class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0"
                                     style="font-size:9px;color:rgba(255,255,255,0.6);">
                                    +{{ $m->attendees->count() - 6 }}
                                </div>
                                @endif
                                <span class="text-xs text-white/40 ml-1">{{ $m->attendees->count() }} attendee{{ $m->attendees->count() !== 1 ? 's' : '' }}</span>
                            </div>
                        </div>
                        @if($isAdmin)
                        <div class="flex flex-col gap-1 flex-shrink-0 ml-1">
                            <button x-data="{}"
                                    @click="$dispatch('open-edit-meeting', {{ json_encode(['id'=>$m->id,'title'=>$m->title,'description'=>$m->description ?? '','meeting_date'=>$m->meeting_date->format('Y-m-d'),'start_time'=>substr($m->start_time,0,5),'duration_minutes'=>(int)$m->duration_minutes,'location'=>$m->location ?? '','color'=>$m->color,'attendees'=>$m->attendees->pluck('id')->values()]) }})"
                                    class="w-6 h-6 rounded-md bg-white/10 hover:bg-white/20 flex items-center justify-center transition"
                                    title="Edit meeting">
                                <i class="fas fa-pen" style="font-size:9px;"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.meetings.destroy', $m) }}"
                                  onsubmit="return confirm('Delete this meeting?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="w-6 h-6 rounded-md bg-white/10 hover:bg-red-500/40 flex items-center justify-center transition"
                                        title="Delete meeting">
                                    <i class="fas fa-trash-can text-red-300" style="font-size:9px;"></i>
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center py-5">
                    <i class="fas fa-calendar-xmark text-3xl text-white/15 mb-2 block"></i>
                    <p class="text-xs text-white/40">No meetings today</p>
                    @if($isAdmin)
                    <button x-data="{}" @click="$dispatch('open-meeting-modal')"
                            class="mt-2 text-xs text-indigo-400 hover:text-indigo-300 underline underline-offset-2 transition">
                        Schedule one
                    </button>
                    @endif
                </div>
                @endforelse
            </div>
        </div>

        {{-- Upcoming Tasks --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 text-sm">Upcoming Tasks</h3>
                <span class="text-xs text-indigo-500 font-medium">Next deadlines</span>
            </div>
            <div class="space-y-2.5">
                @forelse($upcomingTasks as $task)
                @php $daysLeft = (int) now()->diffInDays($task->deadline, false); @endphp
                <div class="flex items-center gap-3 p-2.5 bg-gray-50 hover:bg-indigo-50 rounded-lg transition">
                    <div class="w-9 h-9 rounded-lg flex flex-col items-center justify-center flex-shrink-0
                        {{ $daysLeft <= 2 ? 'bg-red-100' : ($daysLeft <= 7 ? 'bg-amber-100' : 'bg-indigo-100') }}">
                        <span class="text-xs font-bold leading-none {{ $daysLeft <= 2 ? 'text-red-600' : ($daysLeft <= 7 ? 'text-amber-600' : 'text-indigo-600') }}">
                            {{ $task->deadline->format('d') }}
                        </span>
                        <span class="text-xs leading-none {{ $daysLeft <= 2 ? 'text-red-400' : ($daysLeft <= 7 ? 'text-amber-400' : 'text-indigo-400') }}">
                            {{ $task->deadline->format('M') }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $task->title }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $task->project->name }}</p>
                    </div>
                    <span class="text-xs text-gray-400 flex-shrink-0">
                        {{ $daysLeft === 1 ? 'Tomorrow' : "in {$daysLeft}d" }}
                    </span>
                </div>
                @empty
                <div class="text-center py-5">
                    <i class="fas fa-calendar text-3xl text-gray-200 mb-2 block"></i>
                    <p class="text-xs text-gray-400">No upcoming tasks</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Upcoming Meetings --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 text-sm">Upcoming Meetings</h3>
                <span class="text-xs text-indigo-500 font-medium">Next scheduled</span>
            </div>
            <div class="space-y-2.5">
                @forelse($upcomingMeetings as $m)
                <div class="flex items-center gap-3 p-2.5 bg-gray-50 hover:bg-indigo-50 rounded-lg transition cursor-default">
                    <div class="w-9 h-9 rounded-lg flex flex-col items-center justify-center flex-shrink-0"
                         style="background:{{ $m->color }}18;border:1.5px solid {{ $m->color }}30">
                        <span class="text-xs font-bold leading-none" style="color:{{ $m->color }}">
                            {{ $m->meeting_date->format('d') }}
                        </span>
                        <span class="text-xs leading-none" style="color:{{ $m->color }}99">
                            {{ $m->meeting_date->format('M') }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $m->title }}</p>
                        <p class="text-xs text-gray-400 truncate">
                            {{ \Carbon\Carbon::createFromTimeString($m->start_time)->format('g:i A') }}
                            @if($m->location) · {{ $m->location }} @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0 text-xs text-gray-400">
                        <i class="fas fa-users text-xs"></i>
                        <span>{{ $m->attendees->count() }}</span>
                    </div>
                </div>
                @empty
                <div class="text-center py-5">
                    <i class="fas fa-calendar text-3xl text-gray-200 mb-2 block"></i>
                    <p class="text-xs text-gray-400">No upcoming meetings</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>{{-- /sidebar --}}
</div>{{-- /grid --}}

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script>
const csrfToken = '{{ csrf_token() }}';

document.addEventListener('DOMContentLoaded', function () {
    const calendarEl  = document.getElementById('calendar');
    const events      = JSON.parse(calendarEl.dataset.events || '[]');
    const allMeetings = @json($allMeetingsJson);

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 530,
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,listWeek'
        },
        buttonText: { today: 'Today', month: 'Month', list: 'List' },
        events:   events,
        editable: true,

        eventClick: function (info) {
            const props = info.event.extendedProps;
            if (props.type === 'task') {
                @if(auth()->user()->role === 'user')
                window.location.href = '/user/tasks/' + props.id;
                @endif
                return;
            }
            // Meeting click → open detail modal
            const mid = parseInt(info.event.id.replace('meeting-', ''));
            const m   = allMeetings.find(x => x.id === mid);
            if (m) window.dispatchEvent(new CustomEvent('show-meeting-detail', { detail: m }));
        },

        eventDrop: function (info) {
            const props = info.event.extendedProps;
            if (props.type !== 'meeting') { info.revert(); return; }

            const mid     = parseInt(info.event.id.replace('meeting-', ''));
            const m       = allMeetings.find(x => x.id === mid);
            const newDate = info.event.start.toISOString().split('T')[0];

            // Show reschedule time-picker modal, pass a revert callback
            window.dispatchEvent(new CustomEvent('confirm-reschedule', {
                detail: {
                    id:         mid,
                    title:      m?.title || '',
                    new_date:   newDate,
                    start_time: m?.start_time || '',
                    revert:     () => info.revert()
                }
            }));
        },

        eventDidMount: function (info) {
            const props = info.event.extendedProps;
            if (props.type === 'meeting') {
                info.el.style.cursor = 'grab';
            } else {
                info.el.title = info.event.title
                    + '\nProject: ' + (props.project || '')
                    + '\nStatus: '  + (props.status  || '');
            }
        },
        dayMaxEvents: 3,
    });

    calendar.render();
});

@if($isAdmin)
// Create-new-meeting modal (for "New Meeting" button + sidebar edit buttons)
function meetingModal() {
    return {
        open:   false,
        editId: null,
        form: { title:'', description:'', meeting_date:'', start_time:'', duration_minutes:'60', location:'', color:'#4F46E5', attendees:[] },
        openCreate() {
            this.editId = null;
            this.form   = { title:'', description:'', meeting_date:'', start_time:'', duration_minutes:'60', location:'', color:'#4F46E5', attendees:[] };
            this.open   = true;
        },
        openEdit(data) {
            this.editId = data.id;
            this.form   = {
                title: data.title, description: data.description||'',
                meeting_date: data.meeting_date, start_time: data.start_time,
                duration_minutes: String(data.duration_minutes), location: data.location||'',
                color: data.color, attendees: (data.attendees||[]).map(Number)
            };
            this.open = true;
        },
        close() { this.open = false; },
        toggleAttendee(id) {
            const idx = this.form.attendees.indexOf(id);
            if (idx === -1) this.form.attendees.push(id); else this.form.attendees.splice(idx,1);
        }
    };
}

// Detail / Edit / Reschedule modal (for calendar event clicks & drag-drop)
function meetingDetailModal() {
    return {
        open:  false,
        mode:  'view', // 'view' | 'edit' | 'reschedule'
        meeting: null,
        form: { title:'', description:'', meeting_date:'', start_time:'', duration_minutes:'60', location:'', color:'#4F46E5', attendees:[] },
        reschedule: { id:null, title:'', new_date:'', start_time:'', revert:null },
        saving: false,

        openView(m) {
            this.meeting = m;
            this.mode    = 'view';
            this.open    = true;
        },

        openEdit() {
            this.form = {
                title:            this.meeting.title,
                description:      this.meeting.description || '',
                meeting_date:     this.meeting.meeting_date,
                start_time:       this.meeting.start_time,
                duration_minutes: String(this.meeting.duration_minutes),
                location:         this.meeting.location || '',
                color:            this.meeting.color,
                attendees:        (this.meeting.attendees || []).map(Number)
            };
            this.mode = 'edit';
        },

        openReschedule(data) {
            this.reschedule = data;
            this.mode       = 'reschedule';
            this.open       = true;
        },

        close() { this.open = false; },

        cancelReschedule() {
            if (this.reschedule.revert) this.reschedule.revert();
            this.open = false;
        },

        async saveEdit() {
            if (!this.form.title.trim()) return;
            this.saving = true;
            const fd = new FormData();
            fd.append('_method', 'PUT');
            fd.append('_token', csrfToken);
            ['title','description','meeting_date','start_time','duration_minutes','location','color'].forEach(k => fd.append(k, this.form[k]));
            this.form.attendees.forEach(id => fd.append('attendees[]', id));
            await fetch('/admin/meetings/' + this.meeting.id, { method:'POST', body:fd });
            window.location.reload();
        },

        async deleteMeeting() {
            if (!confirm('Delete "' + this.meeting.title + '"? This cannot be undone.')) return;
            const fd = new FormData();
            fd.append('_method', 'DELETE');
            fd.append('_token', csrfToken);
            await fetch('/admin/meetings/' + this.meeting.id, { method:'POST', body:fd });
            window.location.reload();
        },

        async confirmReschedule() {
            this.saving = true;
            const r = await fetch('/admin/meetings/' + this.reschedule.id + '/reschedule', {
                method:  'PATCH',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrfToken },
                body:    JSON.stringify({ meeting_date: this.reschedule.new_date, start_time: this.reschedule.start_time })
            });
            this.saving = false;
            if (r.ok) { this.open = false; window.location.reload(); }
            else      { if (this.reschedule.revert) this.reschedule.revert(); this.open = false; }
        },

        toggleAttendee(id) {
            const idx = this.form.attendees.indexOf(id);
            if (idx === -1) this.form.attendees.push(id); else this.form.attendees.splice(idx,1);
        },

        formatDate(d) {
            if (!d) return '';
            return new Date(d + 'T00:00:00').toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
        },

        formatTimeRange(t, dur) {
            if (!t || !dur) return '';
            const [h, m] = t.split(':').map(Number);
            const s = new Date(1970,0,1,h,m);
            const e = new Date(s.getTime() + dur*60000);
            const f = d => d.toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit', hour12:true });
            const label = dur >= 60 ? (dur%60===0 ? dur/60+'h' : Math.floor(dur/60)+'h '+(dur%60)+'m') : dur+'min';
            return f(s) + ' – ' + f(e) + ' · ' + label;
        }
    };
}
@endif
</script>
@endpush
