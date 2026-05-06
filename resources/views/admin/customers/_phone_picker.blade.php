<div x-data="phonePicker('{{ $initialPhone ?? '' }}')" @keydown.escape.window="open = false">
    {{-- Trigger row --}}
    <div style="display:flex;border:1.5px solid #E5E7EB;border-radius:10px;overflow:hidden;background:#fff;">
        <button type="button" @click.prevent="open = true"
                style="display:flex;align-items:center;gap:5px;padding:10px 12px;background:#F9FAFB;border:none;border-right:1.5px solid #E5E7EB;cursor:pointer;outline:none;flex-shrink:0;min-width:84px;">
            <span x-text="selected.flag" style="font-size:16px;line-height:1;"></span>
            <span x-text="selected.dial" style="font-size:13px;font-weight:600;color:#374151;"></span>
            <i class="fas fa-chevron-down" style="font-size:9px;color:#9CA3AF;transition:transform .15s;" :style="open ? 'transform:rotate(180deg)' : ''"></i>
        </button>
        <input type="tel" x-model="local" placeholder="50 123 4567" maxlength="25"
               style="flex:1;padding:10px 14px;border:none;font-size:14px;background:transparent;color:#111827;outline:none;min-width:0;">
    </div>
    <input type="hidden" name="phone" :value="full">

    {{-- Backdrop --}}
    <div x-show="open" x-cloak @click="open=false; search=''"
         style="position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9998;"></div>

    {{-- Panel wrapper: x-show only toggles this; flex centering is on the inner div --}}
    <div x-show="open" x-cloak style="position:fixed;inset:0;z-index:9999;">
        <div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;padding:16px;">
        <div @click.stop
             style="background:#fff;border-radius:18px;width:100%;max-width:420px;max-height:min(620px,88vh);display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,0.22);overflow:hidden;">

            {{-- Header --}}
            <div style="display:flex;align-items:center;gap:10px;padding:16px 20px;border-bottom:1px solid #F3F4F6;flex-shrink:0;">
                <button type="button" @click="open=false; search=''"
                        style="display:flex;align-items:center;justify-content:center;width:32px;height:32px;border:none;background:#F3F4F6;border-radius:8px;cursor:pointer;color:#374151;flex-shrink:0;">
                    <i class="fas fa-arrow-left" style="font-size:12px;"></i>
                </button>
                <span style="font-size:15px;font-weight:700;color:#111827;flex:1;text-align:center;">Select your country code</span>
                <div style="width:32px;"></div>
            </div>

            {{-- Search --}}
            <div style="padding:12px 16px;border-bottom:1px solid #F3F4F6;flex-shrink:0;">
                <div style="position:relative;">
                    <i class="fas fa-search" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:13px;pointer-events:none;"></i>
                    <input type="text" x-model="search" @click.stop
                           placeholder="Enter country code to filter"
                           x-ref="searchInput"
                           x-effect="if(open) $nextTick(()=>{ $refs.searchInput && $refs.searchInput.focus(); })"
                           style="width:100%;padding:9px 12px 9px 34px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;outline:none;box-sizing:border-box;background:#F9FAFB;color:#111827;">
                </div>
            </div>

            {{-- Country list --}}
            <div style="flex:1;overflow-y:auto;">
                <template x-for="group in grouped" :key="group.letter">
                    <div>
                        <div x-show="group.letter" x-text="group.letter"
                             style="padding:4px 20px;font-size:11px;font-weight:700;color:#9CA3AF;background:#F9FAFB;letter-spacing:.06em;position:sticky;top:0;z-index:1;"></div>
                        <template x-for="c in group.items" :key="c.code">
                            <button type="button" @click.stop="pick(c)"
                                    :style="selected.code === c.code ? 'background:#EEF2FF;' : ''"
                                    style="display:block;width:100%;border:none;border-bottom:1px solid #F3F4F6;background:transparent;cursor:pointer;padding:0;text-align:left;box-sizing:border-box;">
                                <div style="display:flex;align-items:center;gap:14px;padding:11px 20px;">
                                    <span x-text="c.dial" style="font-size:13px;font-weight:700;color:#111827;min-width:50px;flex-shrink:0;"></span>
                                    <span x-text="c.flag" style="font-size:20px;flex-shrink:0;line-height:1;"></span>
                                    <span x-text="c.name" style="font-size:13px;color:#374151;flex:1;"></span>
                                    <i x-show="selected.code === c.code" class="fas fa-check" style="font-size:11px;color:#6366F1;flex-shrink:0;"></i>
                                </div>
                            </button>
                        </template>
                    </div>
                </template>
                <div x-show="grouped.length === 0 || grouped.every(g => g.items.length === 0)"
                     style="padding:40px 20px;text-align:center;color:#9CA3AF;font-size:13px;">
                    No countries found
                </div>
            </div>
        </div>
        </div>{{-- /flex center --}}
    </div>{{-- /panel wrapper --}}
</div>{{-- /x-data --}}
