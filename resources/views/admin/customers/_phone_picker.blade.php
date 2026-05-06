<div x-data="phonePicker('{{ $initialPhone ?? '' }}')" @click.outside="open = false">
    <div style="display:flex;border:1.5px solid #E5E7EB;border-radius:10px;overflow:hidden;background:#fff;">
        <button type="button" x-ref="phoneBtn"
                @click.stop="toggle($refs.phoneBtn)"
                style="display:flex;align-items:center;gap:5px;padding:10px 12px;background:#F9FAFB;border:none;border-right:1.5px solid #E5E7EB;cursor:pointer;outline:none;flex-shrink:0;min-width:84px;">
            <span x-text="selected.flag" style="font-size:16px;line-height:1;"></span>
            <span x-text="selected.dial" style="font-size:13px;font-weight:600;color:#374151;"></span>
            <i class="fas fa-chevron-down" style="font-size:9px;color:#9CA3AF;transition:transform .15s;" :style="open ? 'transform:rotate(180deg)' : ''"></i>
        </button>
        <input type="tel" x-model="local" placeholder="50 123 4567" maxlength="25"
               style="flex:1;padding:10px 14px;border:none;font-size:14px;background:transparent;color:#111827;outline:none;min-width:0;">
    </div>
    <input type="hidden" name="phone" :value="full">
    <div x-show="open" x-cloak
         :style="`position:fixed;top:${dropTop}px;left:${dropLeft}px;width:${dropW}px;`"
         style="z-index:9999;background:#fff;border:1.5px solid #E5E7EB;border-radius:12px;box-shadow:0 8px 28px rgba(0,0,0,0.15);overflow:hidden;">
        <div style="padding:8px;">
            <input type="text" x-model="search" placeholder="Search country…" @click.stop
                   style="width:100%;padding:7px 10px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:12px;outline:none;box-sizing:border-box;">
        </div>
        <div style="max-height:220px;overflow-y:auto;">
            <template x-for="c in filtered" :key="c.code">
                <button type="button" @click.stop="pick(c)"
                        :style="selected.code === c.code ? 'background:#EEF2FF;' : ''"
                        style="width:100%;text-align:left;padding:8px 14px;border:none;background:transparent;cursor:pointer;display:flex;align-items:center;gap:8px;">
                    <span x-text="c.flag" style="font-size:16px;line-height:1;flex-shrink:0;"></span>
                    <span x-text="c.name" style="flex:1;font-size:13px;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></span>
                    <span x-text="c.dial" style="font-size:11px;color:#9CA3AF;font-weight:600;flex-shrink:0;"></span>
                </button>
            </template>
        </div>
    </div>
</div>
