@extends('layouts.app')

@section('title', 'Messages')

@section('content')

<style>
.msg-bubble-mine  { background:#4F46E5; color:#fff; border-radius:18px 18px 4px 18px; }
.msg-bubble-their { background:#F3F4F6; color:#111827; border-radius:18px 18px 18px 4px; }
.contact-active   { background:#EEF2FF; border:1px solid #C7D2FE; }
.contact-item:hover { background:#F9FAFB; }
#chat-messages::-webkit-scrollbar { width:4px; }
#chat-messages::-webkit-scrollbar-track { background:transparent; }
#chat-messages::-webkit-scrollbar-thumb { background:#E5E7EB; border-radius:4px; }
.reply-quote-mine  { background:rgba(255,255,255,0.18); border-left:3px solid rgba(255,255,255,0.7); border-radius:6px; padding:4px 8px; margin-bottom:5px; cursor:pointer; }
.reply-quote-their { background:rgba(79,70,229,0.07); border-left:3px solid #6366F1; border-radius:6px; padding:4px 8px; margin-bottom:5px; cursor:pointer; }
.mention-item:hover { background:#EEF2FF; }
.msg-actions { opacity:0; transition:opacity 0.15s; pointer-events:none; }
.msg-row:hover .msg-actions { opacity:1; pointer-events:auto; }
.modal-backdrop { position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:100;display:flex;align-items:center;justify-content:center;padding:16px; }

@keyframes rec-pulse { 0%,100%{opacity:1} 50%{opacity:0.3} }
.rec-dot { animation: rec-pulse 1s ease-in-out infinite; }

.vp-bar { width:3px; border-radius:3px; transition:background 0.1s; cursor:pointer; }
.vp-wrap { display:flex; align-items:center; gap:8px; min-width:220px; max-width:280px; }
</style>

@php
$teamMembersJson = $teamMembers->map(fn($m) => ['id'=>$m->id,'name'=>$m->name,'role'=>ucfirst($m->role)])->values()->toJson();
$groupsJson      = $groups->toJson();
$colors = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6'];
@endphp

{{-- ══ New Direct Message Modal ══ --}}
<div x-data="newMsgModal()" x-cloak>
    <div x-show="open" class="modal-backdrop" @click.self="open=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" @click.stop>
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-900 text-lg">New Message</h3>
                <button @click="open=false" class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center hover:bg-gray-200"><i class="fa fa-times text-gray-500 text-sm"></i></button>
            </div>
            <div class="relative mb-4">
                <i class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" placeholder="Search team members..." x-model="search" x-ref="searchInput"
                       class="w-full pl-8 pr-3 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>
            <div class="space-y-1 max-h-64 overflow-y-auto">
                <template x-for="m in filtered" :key="m.id">
                    <button @click="startChat(m)" class="w-full flex items-center gap-3 p-3 rounded-xl hover:bg-indigo-50 text-left transition">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0" :style="'background:'+colorFor(m.id)">
                            <span x-text="m.name.charAt(0).toUpperCase()"></span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900" x-text="m.name"></p>
                            <p class="text-xs text-gray-400" x-text="m.role"></p>
                        </div>
                    </button>
                </template>
                <template x-if="filtered.length===0"><p class="text-center text-sm text-gray-400 py-4">No members found</p></template>
            </div>
        </div>
    </div>
</div>

{{-- ══ Create Group Modal ══ --}}
<div x-data="createGroupModal()" x-cloak>
    <div x-show="open" class="modal-backdrop" @click.self="close()" @keydown.escape.window="close()">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.stop>
            <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-indigo-100 rounded-xl flex items-center justify-center">
                        <i class="fa fa-users text-indigo-600 text-sm"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 text-base">New Group</h3>
                </div>
                <button @click="close()" class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center hover:bg-gray-200"><i class="fa fa-times text-gray-500 text-sm"></i></button>
            </div>
            <div class="p-6">
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Group Name <span class="text-red-500">*</span></label>
                    <input type="text" x-model="name" placeholder="e.g. Design Team, Project Alpha…"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-gray-50">
                </div>
                <div class="mb-5">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Add Members <span class="text-red-500">*</span></label>
                    <div class="relative mb-2">
                        <i class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" x-model="memberSearch" placeholder="Search team members…"
                               class="w-full pl-8 pr-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    </div>
                    {{-- Selected members chips --}}
                    <template x-if="selected.length > 0">
                        <div class="flex flex-wrap gap-1.5 mb-2">
                            <template x-for="m in selected" :key="m.id">
                                <span class="flex items-center gap-1 px-2 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-medium">
                                    <span x-text="m.name"></span>
                                    <button @click="deselect(m)" class="ml-0.5 hover:text-indigo-900"><i class="fa fa-times" style="font-size:9px;"></i></button>
                                </span>
                            </template>
                        </div>
                    </template>
                    <div class="max-h-44 overflow-y-auto space-y-0.5 border border-gray-100 rounded-xl overflow-hidden">
                        <template x-for="m in filteredMembers" :key="m.id">
                            <button @click="toggle(m)"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 text-left transition"
                                    :class="isSelected(m.id) ? 'bg-indigo-50' : 'hover:bg-gray-50'">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" :style="'background:'+colorFor(m.id)">
                                    <span x-text="m.name.charAt(0).toUpperCase()"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate" x-text="m.name"></p>
                                    <p class="text-xs text-gray-400" x-text="m.role"></p>
                                </div>
                                <template x-if="isSelected(m.id)">
                                    <i class="fa fa-check-circle text-indigo-500 text-sm flex-shrink-0"></i>
                                </template>
                            </button>
                        </template>
                        <template x-if="filteredMembers.length===0">
                            <p class="text-center text-xs text-gray-400 py-4">No members found</p>
                        </template>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button @click="close()" class="flex-1 py-2.5 text-sm font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition">Cancel</button>
                    <button @click="submit()"
                            :disabled="!name.trim() || selected.length===0 || creating"
                            class="flex-1 py-2.5 text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 text-white rounded-xl transition flex items-center justify-center gap-2">
                        <i :class="creating ? 'fa fa-spinner fa-spin' : 'fa fa-users'" class="text-xs"></i>
                        <span x-text="creating ? 'Creating…' : 'Create Group'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Messages</h1>
    <div class="flex items-center gap-2">
        @if(in_array(auth()->user()->role, ['admin', 'manager']))
        <button @click="$dispatch('open-new-group')"
                class="flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm border border-gray-200">
            <i class="fa fa-users text-indigo-500"></i> New Group
        </button>
        @endif
        <button @click="$dispatch('open-new-msg')"
                class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
            <i class="fa fa-edit"></i> New Message
        </button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-4" style="height:calc(100vh - 14rem);"
     x-data="messageApp()"
     x-init="init()"
     @open-new-msg.window="$dispatch('open-new-msg-modal')"
     @group-created.window="onGroupCreated($event.detail)">

    {{-- ── Contacts + Groups Sidebar ── --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm flex flex-col overflow-hidden">
        <div class="p-3 border-b border-gray-100">
            <div class="relative">
                <i class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" placeholder="Search…" x-model="search"
                       class="w-full pl-8 pr-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>
        </div>
        <div class="flex-1 overflow-y-auto pb-3">

            {{-- Direct messages --}}
            <div class="px-3 pt-3 pb-1">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Direct</p>
            </div>
            <div class="px-2 space-y-0.5">
                @forelse($teamMembers as $member)
                <button data-user-id="{{ $member->id }}"
                        @click="selectUser({{ $member->id }},'{{ addslashes($member->name) }}','{{ $colors[$loop->index % count($colors)] }}')"
                        :class="activeUserId==={{ $member->id }} && !isGroup ? 'contact-active' : 'contact-item'"
                        class="w-full flex items-center gap-2.5 p-2 rounded-lg text-left transition"
                        x-show="search===''||'{{ strtolower($member->name) }}'.includes(search.toLowerCase())">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                         style="background:{{ $colors[$loop->index % count($colors)] }}">{{ strtoupper(substr($member->name,0,1)) }}</div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $member->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ ucfirst($member->role) }}</p>
                    </div>
                    <template x-if="unreadCounts.direct && unreadCounts.direct[{{ $member->id }}]>0">
                        <span class="text-xs font-bold bg-indigo-600 text-white rounded-full px-1.5 py-0.5 flex-shrink-0"
                              x-text="unreadCounts.direct[{{ $member->id }}]"></span>
                    </template>
                </button>
                @empty
                <div class="text-center py-4 text-gray-400 text-xs">No team members</div>
                @endforelse
            </div>

            {{-- Groups --}}
            <div class="px-3 pt-4 pb-1 flex items-center justify-between">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Groups</p>
                @if(in_array(auth()->user()->role, ['admin', 'manager']))
                <button @click="$dispatch('open-new-group')"
                        class="w-5 h-5 rounded flex items-center justify-center hover:bg-gray-100 text-gray-400 hover:text-indigo-500 transition" title="New Group">
                    <i class="fa fa-plus" style="font-size:9px;"></i>
                </button>
                @endif
            </div>
            <div class="px-2 space-y-0.5">
                <template x-for="grp in groups" :key="grp.id">
                    <button :data-group-id="grp.id"
                            @click="selectGroup(grp)"
                            :class="activeGroupId===grp.id && isGroup ? 'contact-active' : 'contact-item'"
                            class="w-full flex items-center gap-2.5 p-2 rounded-lg text-left transition"
                            x-show="search===''||grp.name.toLowerCase().includes(search.toLowerCase())">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                             :style="'background:'+colorFor(grp.id * 3)">
                            <i class="fa fa-users" style="font-size:10px;"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate" x-text="grp.name"></p>
                            <p class="text-xs text-gray-400 truncate" x-text="grp.members.length + ' members'"></p>
                        </div>
                        <template x-if="grp.unread > 0">
                            <span class="text-xs font-bold bg-indigo-600 text-white rounded-full px-1.5 py-0.5 flex-shrink-0"
                                  x-text="grp.unread"></span>
                        </template>
                    </button>
                </template>
                <template x-if="groups.length===0">
                    <p class="text-center text-xs text-gray-400 py-3">No groups yet</p>
                </template>
            </div>

        </div>
    </div>

    {{-- ── Chat Window ── --}}
    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm flex flex-col overflow-hidden">

        <template x-if="activeUserId===null && !isGroup">
            <div class="flex flex-col items-center justify-center h-full text-gray-400 gap-3">
                <i class="fa fa-comments text-5xl text-gray-200"></i>
                <p class="text-sm">Select a conversation to start messaging</p>
            </div>
        </template>

        <template x-if="activeUserId!==null || isGroup">
            <div class="flex flex-col h-full overflow-hidden">

                {{-- Header --}}
                <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <template x-if="!isGroup">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-sm" :style="'background:'+activeUserColor">
                                <span x-text="activeUserName.charAt(0).toUpperCase()"></span>
                            </div>
                        </template>
                        <template x-if="isGroup">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-bold text-sm" :style="'background:'+activeUserColor">
                                <i class="fa fa-users text-xs"></i>
                            </div>
                        </template>
                        <div>
                            <p class="text-sm font-semibold text-gray-900" x-text="isGroup ? activeGroupName : activeUserName"></p>
                            <p class="text-xs text-gray-400" x-text="isGroup ? (groupMembers.length + ' members') : '● Online'" :class="isGroup ? '' : 'text-emerald-500'"></p>
                        </div>
                    </div>
                    <template x-if="isGroup">
                        <button @click="showLeaveConfirm=true"
                                class="text-xs text-red-400 hover:text-red-600 px-3 py-1.5 rounded-lg hover:bg-red-50 transition flex items-center gap-1.5">
                            <i class="fa fa-sign-out-alt"></i> Leave
                        </button>
                    </template>
                </div>

                {{-- Leave confirmation --}}
                <template x-if="showLeaveConfirm">
                    <div class="px-4 py-2.5 bg-red-50 border-b border-red-100 flex items-center gap-3 flex-shrink-0">
                        <p class="text-xs text-red-700 flex-1">Leave this group? You won't receive new messages.</p>
                        <button @click="leaveGroup()"
                                class="text-xs font-semibold text-white bg-red-500 hover:bg-red-600 px-3 py-1 rounded-lg transition">Leave</button>
                        <button @click="showLeaveConfirm=false"
                                class="text-xs text-gray-500 hover:text-gray-700">Cancel</button>
                    </div>
                </template>

                {{-- Messages list --}}
                <div id="chat-messages" class="flex-1 overflow-y-auto p-5 space-y-1">
                    <template x-if="loading">
                        <div class="flex justify-center py-8"><i class="fa fa-spinner fa-spin text-indigo-400 text-xl"></i></div>
                    </template>
                    <template x-if="!loading && messages.length===0">
                        <div class="flex flex-col items-center justify-center h-32 text-gray-400 gap-2">
                            <i class="fa fa-comment-dots text-4xl text-gray-200"></i>
                            <p class="text-sm" x-text="isGroup ? 'No messages yet. Start the conversation!' : 'No messages yet. Say hello!'"></p>
                        </div>
                    </template>

                    <template x-for="(msg,i) in messages" :key="msg.id">
                        <div>
                            <template x-if="i===0||messages[i-1].date!==msg.date">
                                <p class="text-center text-xs text-gray-400 font-medium py-3" x-text="formatDate(msg.date)"></p>
                            </template>

                            <div class="msg-row flex items-end gap-2 py-0.5" :class="msg.mine?'flex-row-reverse':''">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0 self-end"
                                     :style="'background:'+(msg.mine?'#4F46E5':colorFor(msg.sender_id))">
                                    <span x-text="msg.sender.charAt(0).toUpperCase()"></span>
                                </div>

                                <div class="flex items-end gap-1" :class="msg.mine?'flex-row-reverse':''">
                                    <div class="msg-actions flex flex-col gap-1 mb-1">
                                        <button @click="setReply(msg)" title="Reply"
                                                class="w-6 h-6 rounded-full bg-white border border-gray-200 shadow-sm flex items-center justify-center hover:bg-indigo-50">
                                            <i class="fa fa-reply text-gray-400 hover:text-indigo-500" style="font-size:9px;"></i>
                                        </button>
                                    </div>

                                    <div class="flex flex-col max-w-xs" :class="msg.mine?'items-end':'items-start'">
                                        {{-- Sender name for group messages --}}
                                        <template x-if="isGroup && !msg.mine">
                                            <p class="text-xs font-semibold mb-0.5 px-1" :style="'color:'+colorFor(msg.sender_id)" x-text="msg.sender"></p>
                                        </template>

                                        <template x-if="msg.reply_to">
                                            <div :class="msg.mine?'reply-quote-mine':'reply-quote-their'" @click="scrollToMessage(msg.reply_to.id)">
                                                <p class="text-xs font-semibold" :class="msg.mine?'text-indigo-200':'text-indigo-600'" x-text="msg.reply_to.sender"></p>
                                                <p class="text-xs truncate" :class="msg.mine?'text-white/70':'text-gray-500'" x-text="msg.reply_to.body"></p>
                                            </div>
                                        </template>

                                        <div :class="msg.mine?'msg-bubble-mine':'msg-bubble-their'"
                                             class="px-4 py-2.5 text-sm" :id="'msg-'+msg.id">

                                            <template x-if="msg.body">
                                                <p x-html="highlightMentions(msg.body, msg.mine)"></p>
                                            </template>

                                            <template x-if="msg.file && msg.file.audio">
                                                <div x-data="voicePlayer(msg.file.url, msg.id)" class="vp-wrap mt-1">
                                                    <button @click="toggle()"
                                                            class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 transition"
                                                            :class="msg.mine ? 'bg-white/25 hover:bg-white/40' : 'bg-indigo-600 hover:bg-indigo-700'">
                                                        <i :class="playing ? 'fa fa-pause' : 'fa fa-play'" class="text-white text-xs" style="margin-left:1px;"></i>
                                                    </button>
                                                    <div class="flex items-center gap-px flex-1 cursor-pointer" style="height:32px;" @click="seek($event)">
                                                        <template x-for="(h, i) in bars" :key="i">
                                                            <div class="vp-bar flex-shrink-0" :style="'height:'+h+'px;'"
                                                                 :class="i < Math.round(progress * bars.length)
                                                                    ? (msg.mine ? 'bg-white' : 'bg-indigo-500')
                                                                    : (msg.mine ? 'bg-white/35' : 'bg-gray-300')"></div>
                                                        </template>
                                                    </div>
                                                    <span class="text-xs font-medium flex-shrink-0 w-8 text-right"
                                                          :class="msg.mine ? 'text-indigo-200' : 'text-gray-500'" x-text="timeDisplay"></span>
                                                    <a :href="msg.file.url" :download="msg.file.name||'voice.webm'" class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full hover:bg-white/20" title="Download" @click.stop>
                                                        <i class="fa fa-download text-xs" :class="msg.mine?'text-indigo-200':'text-gray-400'"></i>
                                                    </a>
                                                    <audio x-ref="audio" :src="src" preload="metadata"
                                                           @timeupdate="onTimeUpdate()" @ended="onEnded()" @loadedmetadata="onMeta()" style="display:none;"></audio>
                                                </div>
                                            </template>

                                            <template x-if="msg.file && msg.file.image">
                                                <div class="mt-1">
                                                    <a :href="msg.file.url" target="_blank">
                                                        <img :src="msg.file.url" class="max-w-full rounded-lg" style="max-height:200px;object-fit:cover;">
                                                    </a>
                                                    <a :href="msg.file.url" :download="msg.file.name" class="mt-1 flex items-center gap-1 text-xs opacity-70 hover:opacity-100" :class="msg.mine?'text-indigo-200':'text-gray-500'" @click.stop>
                                                        <i class="fa fa-download"></i><span>Download</span>
                                                    </a>
                                                </div>
                                            </template>

                                            <template x-if="msg.file && !msg.file.image && !msg.file.audio">
                                                <a :href="msg.file.url" :download="msg.file.name"
                                                   class="flex items-center gap-2 mt-1 px-3 py-2 rounded-lg"
                                                   :class="msg.mine?'bg-white/20 hover:bg-white/30':'bg-white hover:bg-gray-50 border border-gray-200'"
                                                   @click.stop>
                                                    <i class="fa fa-file-alt" :class="msg.mine?'text-white':'text-indigo-500'"></i>
                                                    <span class="text-xs truncate max-w-[140px]" x-text="msg.file.name"></span>
                                                    <i class="fa fa-download text-xs ml-auto" :class="msg.mine?'text-indigo-200':'text-gray-400'"></i>
                                                </a>
                                            </template>
                                        </div>

                                        <p class="text-xs text-gray-400 mt-1 px-1" x-text="msg.created_at"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Reply preview --}}
                <template x-if="replyingTo">
                    <div class="flex items-center gap-3 px-4 py-2 bg-indigo-50 border-t border-indigo-100 flex-shrink-0">
                        <i class="fa fa-reply text-indigo-400 text-sm flex-shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-indigo-600" x-text="'Replying to '+replyingTo.sender"></p>
                            <p class="text-xs text-gray-500 truncate" x-text="replyingTo.body"></p>
                        </div>
                        <button @click="replyingTo=null" class="text-gray-400 hover:text-gray-600"><i class="fa fa-times text-sm"></i></button>
                    </div>
                </template>

                {{-- File preview --}}
                <template x-if="pendingFile">
                    <div class="flex items-center gap-3 px-4 py-2 bg-gray-50 border-t border-gray-200 flex-shrink-0">
                        <template x-if="pendingFileIsImage">
                            <img :src="pendingFilePreview" class="h-10 w-10 object-cover rounded-lg flex-shrink-0">
                        </template>
                        <template x-if="pendingFileIsVoice">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fa fa-microphone text-red-500"></i>
                            </div>
                        </template>
                        <template x-if="!pendingFileIsImage && !pendingFileIsVoice">
                            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fa fa-file-alt text-indigo-500"></i>
                            </div>
                        </template>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-800 truncate" x-text="pendingFile.name"></p>
                            <p class="text-xs text-gray-400" x-text="formatFileSize(pendingFile.size)"></p>
                        </div>
                        <button @click="clearFile()" class="text-gray-400 hover:text-red-500"><i class="fa fa-times text-sm"></i></button>
                    </div>
                </template>

                {{-- @Mention dropdown --}}
                <template x-if="mentionSearch!==null && mentionResults.length>0">
                    <div class="relative flex-shrink-0">
                        <div class="absolute bottom-0 left-4 right-4 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden z-50 max-h-40 overflow-y-auto">
                            <template x-for="(m,idx) in mentionResults" :key="m.id">
                                <button @click.prevent="insertMention(m)"
                                        :class="idx===mentionIndex?'bg-indigo-50':''"
                                        class="mention-item w-full flex items-center gap-2 px-3 py-2 text-left">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" :style="'background:'+colorFor(m.id)">
                                        <span x-text="m.name.charAt(0).toUpperCase()"></span>
                                    </div>
                                    <span class="text-sm text-gray-800" x-text="m.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Input --}}
                <div class="px-4 py-3 border-t border-gray-100 flex-shrink-0">
                    <template x-if="recording">
                        <div class="flex items-center gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                            <span class="rec-dot w-3 h-3 rounded-full bg-red-500 flex-shrink-0"></span>
                            <span class="text-sm font-medium text-red-600">Recording</span>
                            <span class="text-sm text-red-500 font-mono" x-text="formatTime(recordingTime)"></span>
                            <div class="flex-1"></div>
                            <button @click="stopRecording()" class="px-4 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold rounded-lg transition flex items-center gap-1.5">
                                <i class="fa fa-stop"></i> Stop
                            </button>
                            <button @click="cancelRecording()" class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-600 text-xs font-semibold rounded-lg transition">Cancel</button>
                        </div>
                    </template>
                    <template x-if="!recording">
                        <div class="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-2 border border-gray-200 focus-within:border-indigo-300 focus-within:ring-2 focus-within:ring-indigo-100 transition">
                            <label class="cursor-pointer flex-shrink-0" title="Attach file">
                                <input type="file" x-ref="fileInput" class="hidden" @change="onFileSelected($event)">
                                <span class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-200 transition text-gray-400 hover:text-indigo-500">
                                    <i class="fa fa-paperclip text-sm"></i>
                                </span>
                            </label>
                            <button type="button" @click="startRecording()" title="Voice message"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-200 transition text-gray-400 hover:text-red-500 flex-shrink-0">
                                <i class="fa fa-microphone text-sm"></i>
                            </button>
                            <input type="text"
                                   x-model="newMessage"
                                   x-ref="msgInput"
                                   placeholder="Type something… Use @ to mention"
                                   @keydown.enter.prevent="sendMessage()"
                                   @keydown.escape="replyingTo=null;mentionSearch=null;"
                                   @input="onInput($event)"
                                   @keydown.arrow-up.prevent="mentionSearch!==null?moveMention(-1):null"
                                   @keydown.arrow-down.prevent="mentionSearch!==null?moveMention(1):null"
                                   @keydown.tab.prevent="mentionSearch!==null&&mentionResults.length?insertMention(mentionResults[mentionIndex]):null"
                                   class="flex-1 bg-transparent text-sm text-gray-700 focus:outline-none placeholder-gray-400 min-w-0">
                            <button type="button"
                                    :disabled="sending||(newMessage.trim()===''&&!pendingFile)"
                                    @click="sendMessage()"
                                    class="w-8 h-8 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 rounded-lg flex items-center justify-center text-white transition flex-shrink-0">
                                <i :class="sending?'fa fa-spinner fa-spin':'fa fa-paper-plane'" class="text-xs"></i>
                            </button>
                        </div>
                    </template>
                </div>

            </div>
        </template>
    </div>

    {{-- ── Details Panel ── --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 overflow-y-auto">

        {{-- Group details --}}
        <template x-if="isGroup">
            <div>
                <div class="text-center mb-5">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white text-2xl mx-auto mb-3" :style="'background:'+activeUserColor">
                        <i class="fa fa-users text-xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-900" x-text="activeGroupName"></h3>
                    <p class="text-xs text-gray-400 mt-1" x-text="groupMembers.length + ' members · ' + messages.length + ' messages'"></p>
                </div>

                {{-- Members list --}}
                <div class="mb-5">
                    <p class="text-xs font-semibold text-gray-700 mb-2">Members</p>
                    <div class="space-y-1.5">
                        <template x-for="m in groupMembers" :key="m.id">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" :style="'background:'+colorFor(m.id)">
                                    <span x-text="m.name.charAt(0).toUpperCase()"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-gray-800 truncate" x-text="m.name"></p>
                                    <p class="text-xs text-gray-400" x-text="m.role"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Add Member (group creator / admin) --}}
                <template x-if="canManageGroup">
                    <div class="mb-5">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                            <p class="text-xs font-semibold text-gray-700">Add Member</p>
                            <button @click="addMemberOpen=!addMemberOpen;addMemberSearch='';addMemberError='';"
                                    style="width:22px;height:22px;border-radius:6px;background:#EEF2FF;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6366F1;font-size:11px;transition:background .15s;"
                                    onmouseover="this.style.background='#E0E7FF'" onmouseout="this.style.background='#EEF2FF'"
                                    :title="addMemberOpen ? 'Close' : 'Add member'">
                                <i :class="addMemberOpen ? 'fas fa-times' : 'fas fa-plus'"></i>
                            </button>
                        </div>
                        <template x-if="addMemberOpen">
                            <div>
                                <input type="text"
                                       x-model="addMemberSearch"
                                       placeholder="Search users…"
                                       style="width:100%;padding:7px 10px;font-size:12px;border:1px solid #E5E7EB;border-radius:8px;outline:none;background:#F9FAFB;font-family:inherit;"
                                       @focus="this.style.borderColor='#A5B4FC';this.style.boxShadow='0 0 0 3px rgba(165,180,252,.2)'"
                                       @blur="this.style.borderColor='#E5E7EB';this.style.boxShadow='none'">
                                <template x-if="addMemberError">
                                    <p x-text="addMemberError" style="font-size:11px;color:#EF4444;margin:5px 0 0;"></p>
                                </template>
                                <div style="margin-top:6px;display:flex;flex-direction:column;gap:2px;">
                                    <template x-for="u in addMemberResults" :key="u.id">
                                        <button @click="addMember(u)"
                                                :disabled="addMemberAdding"
                                                style="display:flex;align-items:center;gap:8px;width:100%;padding:7px 8px;border-radius:8px;border:none;background:transparent;cursor:pointer;text-align:left;transition:background .12s;"
                                                onmouseover="this.style.background='#F5F3FF'" onmouseout="this.style.background='transparent'">
                                            <div style="width:26px;height:26px;border-radius:50%;background:#6366F1;display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;font-weight:700;flex-shrink:0;" x-text="u.name.charAt(0).toUpperCase()"></div>
                                            <div style="flex:1;min-width:0;">
                                                <p style="font-size:12px;font-weight:600;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="u.name"></p>
                                                <p style="font-size:10px;color:#9CA3AF;margin:0;" x-text="u.role"></p>
                                            </div>
                                            <i class="fas fa-plus" style="font-size:10px;color:#A5B4FC;flex-shrink:0;"></i>
                                        </button>
                                    </template>
                                    <template x-if="addMemberResults.length===0">
                                        <p style="font-size:12px;color:#9CA3AF;text-align:center;padding:10px 0;">
                                            <span x-text="addMemberSearch ? 'No users found' : 'All users are already members'"></span>
                                        </p>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Shared files --}}
                <div>
                    <p class="text-xs font-semibold text-gray-700 mb-2">Shared Files</p>
                    <template x-if="sharedFiles.length===0"><p class="text-xs text-gray-400">No files shared yet</p></template>
                    <template x-for="f in sharedFiles" :key="f.url">
                        <div class="flex items-center gap-2 py-1.5 border-b border-gray-50 last:border-0">
                            <div class="w-7 h-7 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i :class="f.audio?'fa fa-microphone':'fa fa-file-alt'" class="text-indigo-500 text-xs"></i>
                            </div>
                            <p class="text-xs font-medium text-gray-800 truncate flex-1" x-text="f.audio?'Voice Message':f.name"></p>
                            <a :href="f.url" :download="f.name||'file'" class="w-6 h-6 flex items-center justify-center rounded hover:bg-gray-100 text-gray-400 hover:text-indigo-500 flex-shrink-0">
                                <i class="fa fa-download text-xs"></i>
                            </a>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        {{-- Direct message details --}}
        <template x-if="!isGroup && activeUserId!==null">
            <div>
                <div class="text-center mb-5">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center text-white text-2xl font-bold mx-auto mb-3" :style="'background:'+activeUserColor">
                        <span x-text="activeUserName.charAt(0).toUpperCase()"></span>
                    </div>
                    <h3 class="font-bold text-gray-900" x-text="activeUserName"></h3>
                    <p class="text-xs text-gray-400 mt-1" x-text="messages.length+' messages'"></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-700 mb-2">Shared Files</p>
                    <template x-if="sharedFiles.length===0"><p class="text-xs text-gray-400">No files shared yet</p></template>
                    <template x-for="f in sharedFiles" :key="f.url">
                        <div class="flex items-center gap-2 py-1.5 border-b border-gray-50 last:border-0">
                            <div class="w-7 h-7 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i :class="f.audio?'fa fa-microphone':'fa fa-file-alt'" class="text-indigo-500 text-xs"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-gray-800 truncate" x-text="f.audio?'Voice Message':f.name"></p>
                            </div>
                            <a :href="f.url" :download="f.name||'file'" class="w-6 h-6 flex items-center justify-center rounded hover:bg-gray-100 text-gray-400 hover:text-indigo-500 flex-shrink-0">
                                <i class="fa fa-download text-xs"></i>
                            </a>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <template x-if="!isGroup && activeUserId===null">
            <div class="text-center text-gray-400 text-sm mt-8">
                <i class="fa fa-user-circle text-4xl text-gray-200 mb-3 block"></i>
                Select a contact or group
            </div>
        </template>
    </div>

</div>

@push('scripts')
<script>
/* ── Voice Player ── */
function voicePlayer(url, msgId) {
    function seededBars(seed, count) {
        const bars = []; let s = Math.abs(seed) || 1;
        for (let i = 0; i < count; i++) { s = (s * 1664525 + 1013904223) & 0x7fffffff; bars.push(4 + (s % 20)); }
        return bars;
    }
    return {
        src: url, playing: false, progress: 0, duration: 0, current: 0, bars: seededBars(msgId, 30),
        toggle() {
            const a = this.$refs.audio; if (!a) return;
            if (this.playing) { a.pause(); this.playing = false; }
            else { document.querySelectorAll('audio').forEach(el => { if (el !== a) el.pause(); }); a.play().then(() => { this.playing = true; }).catch(() => {}); }
        },
        onTimeUpdate() { const a = this.$refs.audio; if (!a) return; this.current = a.currentTime; this.progress = this.duration > 0 ? this.current / this.duration : 0; },
        onEnded() { this.playing = false; this.progress = 0; this.current = 0; if (this.$refs.audio) this.$refs.audio.currentTime = 0; },
        onMeta() { const a = this.$refs.audio; if (a && isFinite(a.duration)) this.duration = a.duration; },
        seek(e) { const a = this.$refs.audio; if (!a || !this.duration) return; const rect = e.currentTarget.getBoundingClientRect(); const ratio = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width)); a.currentTime = ratio * this.duration; this.progress = ratio; },
        get timeDisplay() { const t = this.playing ? this.current : this.duration; if (!t || isNaN(t) || !isFinite(t)) return '0:00'; return Math.floor(t/60)+':'+(Math.floor(t%60)).toString().padStart(2,'0'); },
    };
}

const TEAM_MEMBERS = {!! $teamMembersJson !!};
const COLORS = ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6','#3B82F6'];
function colorFor(id){ return COLORS[Math.abs(id) % COLORS.length]; }

/* ── New Direct Message Modal ── */
function newMsgModal() {
    return {
        open: false, search: '',
        get filtered() { return this.search ? TEAM_MEMBERS.filter(m => m.name.toLowerCase().includes(this.search.toLowerCase())) : TEAM_MEMBERS; },
        colorFor,
        startChat(member) {
            this.open = false; this.search = '';
            const btn = document.querySelector(`[data-user-id="${member.id}"]`);
            if (btn) btn.click();
        },
        init() {
            window.addEventListener('open-new-msg-modal', () => { this.open = true; this.$nextTick(() => this.$refs.searchInput?.focus()); });
        }
    };
}

/* ── Create Group Modal ── */
function createGroupModal() {
    return {
        open: false, name: '', memberSearch: '', selected: [], creating: false,
        get filteredMembers() {
            return this.memberSearch
                ? TEAM_MEMBERS.filter(m => m.name.toLowerCase().includes(this.memberSearch.toLowerCase()))
                : TEAM_MEMBERS;
        },
        colorFor,
        isSelected(id) { return this.selected.some(m => m.id === id); },
        toggle(m) { this.isSelected(m.id) ? this.deselect(m) : this.selected.push(m); },
        deselect(m) { this.selected = this.selected.filter(s => s.id !== m.id); },
        close() { this.open = false; this.name = ''; this.selected = []; this.memberSearch = ''; },
        async submit() {
            if (!this.name.trim() || this.selected.length === 0 || this.creating) return;
            this.creating = true;
            try {
                const res = await fetch('/messages/groups', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ name: this.name.trim(), member_ids: this.selected.map(m => m.id) }),
                });
                if (res.ok) {
                    const group = await res.json();
                    window.dispatchEvent(new CustomEvent('group-created', { detail: group }));
                    this.close();
                }
            } finally { this.creating = false; }
        },
        init() {
            window.addEventListener('open-new-group', () => { this.open = true; });
        }
    };
}

/* ── Main Chat App ── */
function messageApp() {
    return {
        // Direct
        activeUserId: null, activeUserName: '', activeUserColor: '#6366F1',
        // Group
        isGroup: false, activeGroupId: null, activeGroupName: '', groupMembers: [],
        showLeaveConfirm: false, canManageGroup: false,
        addMemberOpen: false, addMemberSearch: '', addMemberAdding: false, addMemberError: '',
        allUsers: {!! json_encode($teamMembers->map(fn($u) => ['id'=>$u->id,'name'=>$u->name,'role'=>ucfirst($u->role)])) !!},
        // Shared
        messages: [], newMessage: '', loading: false, sending: false,
        search: '', unreadCounts: {}, pollTimer: null, replyingTo: null,
        groups: {!! $groupsJson !!},
        // File
        pendingFile: null, pendingFilePreview: null, pendingFileIsImage: false, pendingFileIsVoice: false,
        // Voice recording
        recording: false, recorder: null, audioChunks: [], recordingTime: 0, recordingTimer: null, mediaStream: null,
        // @mention
        mentionSearch: null, mentionResults: [], mentionIndex: 0, mentionStart: -1,

        get sharedFiles() { return this.messages.filter(m => m.file).map(m => m.file).slice(-10); },

        init() {
            this.fetchUnread();
            setInterval(() => this.fetchUnread(), 5000);
            const uid = parseInt(new URLSearchParams(window.location.search).get('user'));
            if (uid) this.$nextTick(() => { const btn = document.querySelector(`[data-user-id="${uid}"]`); if (btn) btn.click(); });
        },

        onGroupCreated(group) {
            this.groups.push(group);
            this.$nextTick(() => this.selectGroup(group));
        },

        /* ── Direct conversation ── */
        async selectUser(id, name, color) {
            this.isGroup = false; this.activeGroupId = null; this.showLeaveConfirm = false;
            this.activeUserId = id; this.activeUserName = name; this.activeUserColor = color;
            this.messages = []; this.replyingTo = null;
            this.clearFile(); this.cancelRecording();
            clearInterval(this.pollTimer);
            await this.loadConversation();
            this.pollTimer = setInterval(() => this.loadConversation(true), 5000);
            this.$nextTick(() => this.$refs.msgInput?.focus());
        },

        async loadConversation(silent = false) {
            if (!this.activeUserId || this.isGroup) return;
            if (!silent) this.loading = true;
            try {
                const res = await fetch(`/messages/conversation/${this.activeUserId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) return;
                const data = await res.json();
                const wasAtBottom = this.isAtBottom();
                this.messages = data;
                if (this.unreadCounts.direct) { delete this.unreadCounts.direct[this.activeUserId]; this.unreadCounts = { ...this.unreadCounts }; }
                if (!silent || wasAtBottom) this.$nextTick(() => this.scrollToBottom());
            } finally { this.loading = false; }
        },

        /* ── Group conversation ── */
        async selectGroup(grp) {
            this.isGroup = true; this.activeUserId = null; this.showLeaveConfirm = false;
            this.activeGroupId = grp.id; this.activeGroupName = grp.name;
            this.activeUserColor = colorFor(grp.id * 3);
            this.groupMembers = []; this.messages = []; this.replyingTo = null;
            this.clearFile(); this.cancelRecording();
            clearInterval(this.pollTimer);
            await this.loadGroupConversation();
            this.pollTimer = setInterval(() => this.loadGroupConversation(true), 5000);
            this.$nextTick(() => this.$refs.msgInput?.focus());
        },

        async loadGroupConversation(silent = false) {
            if (!this.activeGroupId || !this.isGroup) return;
            if (!silent) this.loading = true;
            try {
                const res = await fetch(`/messages/groups/${this.activeGroupId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) return;
                const data = await res.json();
                const wasAtBottom = this.isAtBottom();
                this.messages = data.messages;
                this.groupMembers = data.members;
                this.canManageGroup = data.canManage ?? false;
                // Clear unread
                const grp = this.groups.find(g => g.id === this.activeGroupId);
                if (grp) grp.unread = 0;
                if (!silent || wasAtBottom) this.$nextTick(() => this.scrollToBottom());
            } finally { this.loading = false; }
        },

        /* ── Send message (handles both direct and group) ── */
        async sendMessage() {
            const body = this.newMessage.trim();
            if ((!body && !this.pendingFile) || this.sending) return;
            this.sending = true;
            try {
                const fd = new FormData();
                if (body)                    fd.append('body', body);
                if (this.pendingFile)        fd.append('file', this.pendingFile);
                if (this.pendingFileIsVoice) fd.append('is_voice', '1');
                if (this.replyingTo)         fd.append('reply_to_id', this.replyingTo.id);

                const url = this.isGroup
                    ? `/messages/groups/${this.activeGroupId}/send`
                    : '/messages/send';
                if (!this.isGroup) fd.append('receiver_id', this.activeUserId);

                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd,
                });
                if (res.ok) {
                    const msg = await res.json();
                    this.messages.push(msg);
                    this.newMessage = ''; this.replyingTo = null;
                    this.clearFile(); this.mentionSearch = null;
                    this.$nextTick(() => this.scrollToBottom());
                }
            } finally { this.sending = false; }
        },

        async leaveGroup() {
            const res = await fetch(`/messages/groups/${this.activeGroupId}/leave`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (res.ok) {
                this.groups = this.groups.filter(g => g.id !== this.activeGroupId);
                this.isGroup = false; this.activeGroupId = null; this.activeGroupName = '';
                this.messages = []; this.groupMembers = []; this.showLeaveConfirm = false;
                clearInterval(this.pollTimer);
            }
        },

        get addMemberResults() {
            const q = this.addMemberSearch.toLowerCase().trim();
            const memberIds = new Set(this.groupMembers.map(m => m.id));
            return this.allUsers
                .filter(u => !memberIds.has(u.id) && (!q || u.name.toLowerCase().includes(q)))
                .slice(0, 8);
        },

        async addMember(user) {
            if (this.addMemberAdding) return;
            this.addMemberAdding = true;
            this.addMemberError = '';
            try {
                const res = await fetch(`/messages/groups/${this.activeGroupId}/members`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ user_id: user.id }),
                });
                if (res.ok) {
                    this.groupMembers.push({ id: user.id, name: user.name, role: user.role });
                    this.addMemberSearch = '';
                } else {
                    this.addMemberError = 'Could not add member.';
                }
            } catch { this.addMemberError = 'Network error.'; }
            finally { this.addMemberAdding = false; }
        },

        setReply(msg) {
            this.replyingTo = { id: msg.id, sender: msg.sender, body: msg.body || (msg.file?.audio ? '🎤 Voice message' : (msg.file?.name ?? '📎 File')) };
            this.$nextTick(() => this.$refs.msgInput?.focus());
        },
        scrollToMessage(id) {
            const el = document.getElementById('msg-' + id); if (!el) return;
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            const parent = el.closest('.msg-row') || el;
            parent.style.transition = 'background 0.3s'; parent.style.background = '#EEF2FF';
            setTimeout(() => parent.style.background = '', 1400);
        },

        onFileSelected(e) {
            const file = e.target.files[0]; if (!file) return;
            this.pendingFile = file; this.pendingFileIsImage = file.type.startsWith('image/'); this.pendingFileIsVoice = file.type.startsWith('audio/');
            if (this.pendingFileIsImage) { const r = new FileReader(); r.onload = ev => this.pendingFilePreview = ev.target.result; r.readAsDataURL(file); }
        },
        clearFile() { this.pendingFile = null; this.pendingFilePreview = null; this.pendingFileIsImage = false; this.pendingFileIsVoice = false; if (this.$refs.fileInput) this.$refs.fileInput.value = ''; },
        formatFileSize(b) { if (b<1024) return b+' B'; if (b<1048576) return (b/1024).toFixed(1)+' KB'; return (b/1048576).toFixed(1)+' MB'; },

        async startRecording() {
            if (!navigator.mediaDevices?.getUserMedia) { alert('Your browser does not support audio recording.'); return; }
            try {
                this.mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                const mimeType = ['audio/webm','audio/ogg','audio/mp4'].find(t => MediaRecorder.isTypeSupported(t)) || '';
                this.recorder = new MediaRecorder(this.mediaStream, mimeType ? { mimeType } : {});
                this.audioChunks = [];
                this.recorder.ondataavailable = e => { if (e.data.size > 0) this.audioChunks.push(e.data); };
                this.recorder.onstop = () => {
                    const blob = new Blob(this.audioChunks, { type: this.recorder.mimeType || 'audio/webm' });
                    const ext  = (this.recorder.mimeType || 'audio/webm').split('/')[1].split(';')[0];
                    this.pendingFile = new File([blob], `voice-${Date.now()}.${ext}`, { type: blob.type });
                    this.pendingFileIsVoice = true; this.pendingFileIsImage = false;
                    this.stopMediaTracks();
                    this.$nextTick(() => this.sendMessage());
                };
                this.recorder.start(200); this.recording = true; this.recordingTime = 0;
                this.recordingTimer = setInterval(() => this.recordingTime++, 1000);
            } catch { alert('Microphone access denied.'); }
        },
        stopRecording() { if (this.recorder && this.recording) { this.recorder.stop(); this.recording = false; clearInterval(this.recordingTimer); } },
        cancelRecording() { if (this.recorder && this.recording) this.recorder.stop(); this.recording = false; clearInterval(this.recordingTimer); this.audioChunks = []; this.stopMediaTracks(); },
        stopMediaTracks() { if (this.mediaStream) { this.mediaStream.getTracks().forEach(t => t.stop()); this.mediaStream = null; } },
        formatTime(secs) { return Math.floor(secs/60).toString().padStart(2,'0')+':'+(secs%60).toString().padStart(2,'0'); },

        onInput() {
            const val = this.newMessage; const el = this.$refs.msgInput; const pos = el?.selectionStart ?? val.length;
            const match = val.slice(0, pos).match(/@(\w*)$/);
            if (match) { this.mentionSearch = match[1].toLowerCase(); this.mentionStart = pos - match[0].length; this.mentionResults = TEAM_MEMBERS.filter(m => m.name.toLowerCase().includes(this.mentionSearch)); this.mentionIndex = 0; }
            else { this.mentionSearch = null; }
        },
        moveMention(dir) { this.mentionIndex = Math.max(0, Math.min(this.mentionResults.length - 1, this.mentionIndex + dir)); },
        insertMention(member) {
            if (!member) return;
            const before = this.newMessage.slice(0, this.mentionStart); const after = this.newMessage.slice(this.$refs.msgInput?.selectionStart ?? this.newMessage.length);
            this.newMessage = before + '@' + member.name + ' ' + after; this.mentionSearch = null;
            this.$nextTick(() => { const pos = (before + '@' + member.name + ' ').length; this.$refs.msgInput?.setSelectionRange(pos, pos); this.$refs.msgInput?.focus(); });
        },
        highlightMentions(text, mine) {
            const escaped = text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            return escaped.replace(/@(\S+)/g, `<span class="${mine ? 'font-semibold text-yellow-300' : 'font-semibold text-indigo-600'}">@$1</span>`);
        },

        async fetchUnread() {
            try { const res = await fetch('/messages/unread', { headers: { 'X-Requested-With': 'XMLHttpRequest' } }); if (res.ok) { const data = await res.json(); this.unreadCounts = data; if (data.groups) { Object.entries(data.groups).forEach(([key, count]) => { const id = parseInt(key.replace('g_','')); const grp = this.groups.find(g => g.id === id); if (grp && id !== this.activeGroupId) grp.unread = count; }); } } } catch {}
        },
        scrollToBottom() { const el = document.getElementById('chat-messages'); if (el) el.scrollTop = el.scrollHeight; },
        isAtBottom() { const el = document.getElementById('chat-messages'); if (!el) return true; return el.scrollHeight - el.scrollTop - el.clientHeight < 60; },
        formatDate(d) { const t = new Date().toISOString().slice(0,10); const y = new Date(Date.now()-86400000).toISOString().slice(0,10); if (d===t) return 'TODAY'; if (d===y) return 'YESTERDAY'; return new Date(d).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}); },
        colorFor,
    };
}
</script>
@endpush

@endsection
