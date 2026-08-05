{{--
  Mobile restyle of resources/views/messages/index.blade.php
  Scope: <=768px only. Lives inside the existing x-data="messageApp()" scope
  (the .msg-chat-grid div) so it can drive the same single-page chat via
  selectUser()/selectGroup() — this app has no per-conversation page to link
  to (messages.conversation / messages.groups.conversation are JSON endpoints
  the desktop UI fetches via AJAX, not renderable pages). Replaces
  .msg-contacts-sidebar entirely on mobile; the chat window (.msg-chat-window)
  is unchanged and already mobile-adapted via .mob-thread-active.

  Vars (from MessagesController@index): $directThreads, $groupThreads, $unreadTotal
  Thread: id, kind ('direct'|'group'), display_name, avatar_color, avatar_url,
          is_online, unread_count, last_message_at, role_label,
          last_message (object|null): body, deleted, attachment_name, mime_type, from_me
--}}

@php
    // preview text — never leak a filename or a URL into the list
    $preview = function ($t) {
        $m = $t->last_message;
        if (! $m) return $t->role_label ? $t->role_label.' · no messages yet' : 'No messages yet';
        if ($m->deleted) return '🚫 Message deleted';
        $mine = $m->from_me ? 'You sent ' : 'Sent ';
        if ($m->attachment_name) {
            $mime = (string) $m->mime_type;
            $what = str_starts_with($mime, 'image/') ? 'an image'
                  : (str_starts_with($mime, 'video/') ? 'a video'
                  : (str_starts_with($mime, 'audio/') ? 'a voice note' : 'an attachment'));
            return $mine.$what;
        }
        $body = trim((string) $m->body);
        if ($body === '' || filter_var($body, FILTER_VALIDATE_URL) || str_starts_with($body, '/')) {
            return $mine.'a link';
        }
        return ($m->from_me ? 'You: ' : '').\Illuminate\Support\Str::limit($body, 46);
    };
@endphp

<div class="msg-mobile">

    {{-- No title/New-message header here — .mob-msg-header above (outside this
         partial) already covers that, is already mobile-adapted, and stays visible. --}}

    <div class="m-searchrow">
        <label class="m-search">
            <i class="fas fa-search"></i>
            <input type="search" x-model="search" placeholder="Search people and groups">
        </label>
    </div>

    <div class="msg-section">Direct</div>
    <div class="msg-group">
        @foreach ($directThreads as $t)
            <button type="button"
                    x-show="search===''||{{ \Illuminate\Support\Js::from(strtolower($t->display_name)) }}.includes(search.toLowerCase())"
                    @click="selectUser({{ $t->id }}, {{ \Illuminate\Support\Js::from($t->display_name) }}, {{ \Illuminate\Support\Js::from($t->avatar_color) }}, {{ $t->is_online ? 'true' : 'false' }}, {{ \Illuminate\Support\Js::from($t->avatar_url) }})"
                    class="msg-row">
                <span class="msg-av-wrap">
                    <span class="msg-av" style="background:{{ $t->avatar_color }}">{{ mb_substr($t->display_name, 0, 1) }}</span>
                    <span class="msg-presence {{ $t->is_online ? 'is-on' : '' }}"></span>
                </span>
                <span class="msg-body">
                    <span class="msg-name">{{ $t->display_name }}</span>
                    <span class="msg-preview {{ $t->unread_count ? 'is-unread' : '' }}">{{ $preview($t) }}</span>
                </span>
                <span class="msg-right">
                    <span class="msg-time">{{ optional($t->last_message_at)->diffForHumans(null, true, true) }}</span>
                    @if ($t->unread_count)
                        <span class="msg-badge">{{ $t->unread_count }}</span>
                    @endif
                </span>
            </button>
        @endforeach
    </div>

    <div class="msg-section-row">
        <span class="msg-section">Groups</span>
        <span class="m-spacer"></span>
        @if(in_array(auth()->user()->role, ['admin', 'manager']))
        <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-new-group'))"
                class="msg-newgroup" title="New group" aria-label="New group">
            <i class="fa fa-plus"></i>
        </button>
        @endif
    </div>

    @if ($groupThreads->isEmpty())
        <x-mobile.empty-state style="margin-top:6px;" title="No groups yet" sub="Group a project team into one thread." icon="fa-users" />
    @else
        <div class="msg-group">
            @foreach ($groupThreads as $t)
                <button type="button"
                        x-show="search===''||{{ \Illuminate\Support\Js::from(strtolower($t->display_name)) }}.includes(search.toLowerCase())"
                        @click="selectGroup({ id: {{ $t->id }}, name: {{ \Illuminate\Support\Js::from($t->display_name) }}, unread: {{ $t->unread_count }} })"
                        class="msg-row">
                    <span class="msg-av-wrap">
                        <span class="msg-av" style="background:{{ $t->avatar_color }}"><i class="fas fa-users"></i></span>
                    </span>
                    <span class="msg-body">
                        <span class="msg-name">{{ $t->display_name }}</span>
                        <span class="msg-preview {{ $t->unread_count ? 'is-unread' : '' }}">{{ $preview($t) }}</span>
                    </span>
                    <span class="msg-right">
                        <span class="msg-time">{{ optional($t->last_message_at)->diffForHumans(null, true, true) }}</span>
                        @if ($t->unread_count)<span class="msg-badge">{{ $t->unread_count }}</span>@endif
                    </span>
                </button>
            @endforeach
        </div>
    @endif
</div>

<style>
.msg-mobile { display: none; }

@media (max-width: 768px) {
    /* replace the existing .msg-contacts-sidebar entirely — doubled selector to
       out-specificity this page's own later !important mobile rules for it */
    .msg-contacts-sidebar.msg-contacts-sidebar { display: none !important; }
    .msg-mobile { display: block; }
    .msg-chat-grid.mob-thread-active .msg-mobile { display: none; }

    .m-searchrow { margin-bottom: 16px; }
    .m-search {
        display: flex; align-items: center; gap: 9px; min-height: 44px;
        background: #F7F8FC; border: 1px solid #E5E7EB; border-radius: 12px; padding: 0 12px;
    }
    .m-search i { font-size: 13px; color: #9CA3AF; }
    .m-search input {
        flex: 1; min-width: 0; min-height: 44px; border: 0; background: none; outline: none;
        font-family: inherit; font-size: 16px; font-weight: 500; color: #111827; /* 16px = no iOS zoom */
    }
    .m-search input::placeholder { font-size: 13.5px; color: #9CA3AF; }

    .msg-section {
        font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em;
        color: #9CA3AF; padding: 4px 2px; margin-top: 6px;
    }
    .msg-section-row { display: flex; align-items: center; margin-top: 18px; }
    .m-spacer { flex: 1; }
    .msg-newgroup {
        border: 0; background: none; color: var(--mob-brand, #4F46E5);
        width: 44px; height: 44px; border-radius: var(--mob-r-sm, 12px);
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; cursor: pointer; flex-shrink: 0;
    }
    .msg-newgroup:active { background: #F3F4F6; }

    /* C6: one hairline-split container (radius 18px) instead of gapped, borderless rows */
    .msg-group {
        display: flex; flex-direction: column; margin-top: 4px;
        background: #fff; border-radius: var(--mob-r-lg, 18px); overflow: hidden;
    }
    .msg-row {
        width: 100%; display: flex; align-items: center; gap: 11px; padding: 12px 14px;
        border: 0; border-bottom: 1px solid #F0F1F5; background: none; text-align: left; cursor: pointer;
    }
    .msg-row:last-child { border-bottom: none; }
    .msg-av-wrap { position: relative; flex: none; }
    .msg-av {
        width: 44px; height: 44px; border-radius: 99px; color: #fff;
        font-size: 16px; font-weight: 700; display: flex; align-items: center; justify-content: center;
    }
    .msg-presence {
        position: absolute; right: -1px; bottom: -1px; width: 12px; height: 12px;
        border-radius: 99px; background: #D1D5DB; border: 2px solid #fff;
    }
    .msg-presence.is-on { background: #10B981; }
    .msg-body { flex: 1; min-width: 0; }
    .msg-name {
        display: block; font-size: 15px; font-weight: 700; color: #111827; letter-spacing: -.015em;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .msg-preview {
        display: block; font-size: 12.5px; font-weight: 500; color: #9CA3AF; margin-top: 2px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .msg-preview.is-unread { color: #374151; font-weight: 600; }
    .msg-right { flex: none; display: flex; flex-direction: column; align-items: flex-end; gap: 5px; }
    .msg-time { font-size: 11px; font-weight: 600; color: #9CA3AF; font-variant-numeric: tabular-nums; }
    .msg-badge {
        min-width: 18px; height: 18px; padding: 0 5px; border-radius: 99px;
        background: var(--mob-brand, #4F46E5); color: #fff; font-size: 10.5px; font-weight: 700;
        display: inline-flex; align-items: center; justify-content: center;
    }
}
</style>
