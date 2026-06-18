{{--
    Expects:
      $account   — SocialAccount|null  (null = create mode)
      $allUsers  — Collection of users
      $platforms — array from SocialAccount::platforms()
--}}
@php $platforms = \App\Models\SocialAccount::platforms(); @endphp

<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

    {{-- Account Name --}}
    <div style="grid-column:1/-1;">
        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Account Name *</label>
        <input type="text" name="name" value="{{ old('name', $account?->name) }}" required
               placeholder="e.g. PromoSeven Facebook Page"
               style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
               onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
    </div>

    {{-- Customer — custom logo picker --}}
    <div style="grid-column:1/-1;position:relative;"
         x-data="{
             open: false,
             selected: {{ old('customer_id', $account?->customer_id ?? 'null') }},
             customers: saFormCustomers,
             get current() { return this.selected ? this.customers.find(c => c.id === this.selected) ?? null : null; },
             search: '',
             get filtered() {
                 const q = this.search.toLowerCase();
                 return this.customers.filter(c => !q || c.name.toLowerCase().includes(q) || c.company.toLowerCase().includes(q));
             }
         }">
        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
            Customer
            <span style="font-size:11px;font-weight:400;color:#9CA3AF;"> — which client owns this account</span>
        </label>
        <input type="hidden" name="customer_id" :value="selected ?? ''">

        {{-- Trigger --}}
        <button type="button" @click="open=!open; if(open) $nextTick(() => $refs.csearch.focus())" @keydown.escape.window="open=false"
                style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;background:#fff;box-sizing:border-box;display:flex;align-items:center;gap:10px;cursor:pointer;text-align:left;"
                :style="{'border-color': open ? '#6366F1' : '#E5E7EB'}">
            {{-- Avatar --}}
            <span style="width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;"
                  :style="current ? {'background': current.color} : {'background': '#F3F4F6'}">
                <template x-if="current && current.logo">
                    <img :src="current.logo" style="width:100%;height:100%;object-fit:cover;">
                </template>
                <template x-if="current && !current.logo">
                    <span x-text="current.initials" style="font-size:12px;font-weight:700;color:#fff;"></span>
                </template>
                <template x-if="!current">
                    <i class="fas fa-building" style="font-size:12px;color:#9CA3AF;"></i>
                </template>
            </span>
            {{-- Label --}}
            <span style="flex:1;font-size:13px;" :style="{'color': current ? '#111827' : '#9CA3AF'}"
                  x-text="current ? current.name + (current.company ? ' — ' + current.company : '') : '— No customer / Internal —'"></span>
            <i class="fas fa-chevron-down" style="font-size:10px;color:#9CA3AF;transition:transform .15s;"
               :style="{'transform': open ? 'rotate(180deg)' : 'none'}"></i>
        </button>

        {{-- Dropdown --}}
        <div x-show="open" @click.outside="open=false" x-cloak
             style="position:absolute;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1.5px solid #E5E7EB;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.12);z-index:300;overflow:hidden;">
            {{-- Search --}}
            <div style="padding:8px 10px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;gap:7px;background:#FAFAFA;">
                <i class="fas fa-magnifying-glass" style="font-size:11px;color:#9CA3AF;flex-shrink:0;"></i>
                <input type="text" x-model="search" x-ref="csearch" placeholder="Search customers..."
                       style="border:none;background:transparent;font-size:12.5px;outline:none;flex:1;color:#374151;">
            </div>
            {{-- None option --}}
            <button type="button" @click="selected=null; open=false"
                    style="width:100%;padding:9px 12px;display:flex;align-items:center;gap:10px;border:none;cursor:pointer;text-align:left;transition:background .1s;"
                    :style="{'background': !selected ? '#EEF2FF' : 'transparent'}"
                    @mouseover="if(selected) $el.style.background='#F9FAFB'" @mouseout="$el.style.background=!selected?'#EEF2FF':'transparent'">
                <span style="width:28px;height:28px;border-radius:8px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-building" style="font-size:12px;color:#9CA3AF;"></i>
                </span>
                <span style="font-size:13px;color:#9CA3AF;font-style:italic;">— No customer / Internal —</span>
                <i x-show="!selected" class="fas fa-check" style="font-size:11px;color:#4F46E5;margin-left:auto;"></i>
            </button>
            {{-- Customer list --}}
            <div style="max-height:200px;overflow-y:auto;">
                <template x-for="c in filtered" :key="c.id">
                    <button type="button" @click="selected=c.id; open=false"
                            style="width:100%;padding:9px 12px;display:flex;align-items:center;gap:10px;border:none;cursor:pointer;text-align:left;transition:background .1s;"
                            :style="{'background': selected===c.id ? '#EEF2FF' : 'transparent'}"
                            @mouseover="if(selected!==c.id) $el.style.background='#F9FAFB'"
                            @mouseout="$el.style.background=selected===c.id?'#EEF2FF':'transparent'">
                        {{-- Logo / initials --}}
                        <span style="width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;"
                              :style="{'background': c.color}">
                            <template x-if="c.logo">
                                <img :src="c.logo" style="width:100%;height:100%;object-fit:cover;">
                            </template>
                            <template x-if="!c.logo">
                                <span x-text="c.initials" style="font-size:12px;font-weight:700;color:#fff;"></span>
                            </template>
                        </span>
                        <div style="flex:1;min-width:0;">
                            <div x-text="c.name" style="font-size:13px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
                            <div x-show="c.company" x-text="c.company" style="font-size:11px;color:#9CA3AF;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
                        </div>
                        <i x-show="selected===c.id" class="fas fa-check" style="font-size:11px;color:#4F46E5;flex-shrink:0;margin-left:auto;"></i>
                    </button>
                </template>
                <div x-show="filtered.length===0" style="padding:14px;text-align:center;font-size:12px;color:#9CA3AF;">No customers found</div>
            </div>
        </div>
    </div>

    {{-- Platform — custom icon dropdown --}}
    <div x-data="{
            open: false,
            selected: '{{ old('platform', $account?->platform ?? '') }}',
            platforms: saFormPlatforms,
            get current() { return this.platforms.find(p => p.key === this.selected) ?? null; }
         }" style="position:relative;">
        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Platform *</label>
        <input type="hidden" name="platform" :value="selected">

        {{-- Trigger button — all border state via object :style so static styles aren't wiped --}}
        <button type="button" @click="open=!open" @keydown.escape.window="open=false"
                style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;background:#fff;box-sizing:border-box;display:flex;align-items:center;gap:8px;cursor:pointer;text-align:left;"
                :style="{'border-color': open ? '#6366F1' : '#E5E7EB'}">
            {{-- Icon badge: single span always visible, icon class switches reactively --}}
            <span style="width:22px;height:22px;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"
                  :style="{'background': current?.bg ?? '#F3F4F6'}">
                <i :class="current ? 'fab '+current.icon : 'fas fa-share-nodes'"
                   :style="{'font-size': '12px', 'color': current?.color ?? '#9CA3AF'}"></i>
            </span>
            <span style="flex:1;font-size:13px;"
                  :style="{'color': current ? '#111827' : '#9CA3AF'}"
                  x-text="current?.label ?? '— Select platform —'"></span>
            <i class="fas fa-chevron-down" style="font-size:10px;color:#9CA3AF;transition:transform .15s;"
               :style="{'transform': open ? 'rotate(180deg)' : 'none'}"></i>
        </button>

        {{-- Dropdown list --}}
        <div x-show="open" @click.outside="open=false" x-cloak
             style="position:absolute;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1.5px solid #E5E7EB;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:200;overflow:hidden;">
            <template x-for="p in platforms" :key="p.key">
                <button type="button" @click="selected=p.key; open=false"
                        style="width:100%;padding:9px 12px;display:flex;align-items:center;gap:10px;border:none;background:transparent;cursor:pointer;text-align:left;transition:background .1s;"
                        :style="{'background': selected===p.key ? '#EEF2FF' : 'transparent'}"
                        @mouseover="if(selected!==p.key) $el.style.background='#F9FAFB'"
                        @mouseout="$el.style.background = selected===p.key ? '#EEF2FF' : 'transparent'">
                    <span style="width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"
                          :style="{'background': p.bg}">
                        <i :class="'fab '+p.icon" :style="{'font-size':'13px','color':p.color}"></i>
                    </span>
                    <span x-text="p.label" style="font-size:13px;color:#111827;font-weight:500;flex:1;"></span>
                    <i x-show="selected===p.key" class="fas fa-check" style="font-size:11px;color:#4F46E5;margin-left:auto;"></i>
                </button>
            </template>
        </div>
    </div>

    {{-- Status --}}
    <div>
        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Status *</label>
        <select name="status" required
                style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;background:#fff;box-sizing:border-box;"
                onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
            <option value="active"    {{ old('status', $account?->status ?? 'active') === 'active'    ? 'selected' : '' }}>Active</option>
            <option value="inactive"  {{ old('status', $account?->status) === 'inactive'  ? 'selected' : '' }}>Inactive</option>
            <option value="suspended" {{ old('status', $account?->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
        </select>
    </div>

    {{-- Username --}}
    <div>
        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Username / Handle</label>
        <input type="text" name="username" value="{{ old('username', $account?->username) }}"
               placeholder="@handle"
               style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
               onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
    </div>

    {{-- Email --}}
    <div>
        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Login Email</label>
        <input type="text" name="email" value="{{ old('email', $account?->email) }}" autocomplete="off"
               placeholder="login@example.com"
               style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
               onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
    </div>

    {{-- Password --}}
    <div x-data="{showPw:false}">
        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
            Password
            @if($account?->password)
            <span style="font-size:11px;font-weight:400;color:#9CA3AF;"> — leave blank to keep</span>
            @endif
        </label>
        <div style="position:relative;">
            <input :type="showPw?'text':'password'" name="password" autocomplete="new-password"
                   placeholder="{{ $account?->password ? 'Leave blank to keep current' : 'Account password' }}"
                   style="width:100%;padding:9px 40px 9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
                   onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
            <button type="button" @click="showPw=!showPw"
                    style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9CA3AF;padding:0;">
                <i :class="showPw?'fas fa-eye-slash':'fas fa-eye'" style="font-size:13px;"></i>
            </button>
        </div>
    </div>

    {{-- Account ID --}}
    <div>
        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Account / Page ID</label>
        <input type="text" name="account_id" value="{{ old('account_id', $account?->account_id) }}"
               placeholder="e.g. 123456789"
               style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
               onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
    </div>

    {{-- Page URL --}}
    <div style="grid-column:1/-1;">
        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Page / Profile URL</label>
        <input type="url" name="page_url" value="{{ old('page_url', $account?->page_url) }}"
               placeholder="https://facebook.com/yourpage"
               style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;box-sizing:border-box;"
               onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">
    </div>

    {{-- Who has access --}}
    <div style="grid-column:1/-1;"
         x-data="{
             userSearch: '',
             selectedIds: [],
             get filtered() {
                 const q = this.userSearch.toLowerCase();
                 return allFormUsers.filter(u => !q || u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
             },
             toggle(id) {
                 const i = this.selectedIds.indexOf(id);
                 if (i === -1) this.selectedIds.push(id);
                 else this.selectedIds.splice(i, 1);
             },
             isSelected(id) { return this.selectedIds.includes(id); }
         }"
         x-init="selectedIds = (formPreselectedUserIds || []).slice()">

        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
            Who Has Access
            <span style="font-size:11px;font-weight:400;color:#9CA3AF;"> — team members with credentials access</span>
        </label>

        {{-- Selected chips --}}
        <div style="display:flex;flex-wrap:wrap;gap:6px;min-height:28px;margin-bottom:8px;" x-show="selectedIds.length > 0">
            <template x-for="id in selectedIds" :key="id">
                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px 4px 6px;background:#EEF2FF;border:1px solid #C7D2FE;border-radius:20px;font-size:12px;font-weight:500;color:#4F46E5;">
                    <span x-text="allFormUsers.find(u=>u.id===id)?.name ?? id"
                          style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
                    <button type="button" @click="toggle(id)"
                            style="background:none;border:none;cursor:pointer;padding:0;line-height:1;color:#6366F1;display:flex;align-items:center;">
                        <i class="fas fa-times" style="font-size:9px;"></i>
                    </button>
                </span>
            </template>
        </div>

        {{-- Hidden inputs for form submission --}}
        <template x-for="id in selectedIds" :key="'uid-'+id">
            <input type="hidden" name="user_ids[]" :value="id">
        </template>

        {{-- Search + scrollable user list --}}
        <div style="border:1.5px solid #E5E7EB;border-radius:10px;overflow:hidden;">
            <div style="padding:8px 10px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;gap:7px;background:#FAFAFA;">
                <i class="fas fa-magnifying-glass" style="font-size:11px;color:#9CA3AF;flex-shrink:0;"></i>
                <input type="text" x-model="userSearch" placeholder="Search team members..."
                       style="border:none;background:transparent;font-size:12.5px;outline:none;flex:1;color:#374151;">
            </div>
            <div style="max-height:160px;overflow-y:auto;">
                <template x-for="u in filtered" :key="u.id">
                    {{-- Use div not label to avoid click conflicts; whole row is clickable --}}
                    <div @click="toggle(u.id)"
                         style="display:flex;align-items:center;gap:10px;padding:8px 12px;cursor:pointer;transition:background .1s;"
                         :style="{'background': isSelected(u.id) ? '#EEF2FF' : 'transparent'}"
                         @mouseover="if(!isSelected(u.id)) $el.style.background='#F9FAFB'"
                         @mouseout="$el.style.background = isSelected(u.id) ? '#EEF2FF' : 'transparent'">
                        <div style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:11px;font-weight:700;color:#fff;"
                             :style="{'background': ['#6366F1','#10B981','#F59E0B','#EF4444','#8B5CF6'][u.id % 5]}">
                            <span x-text="u.name.charAt(0).toUpperCase()"></span>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div x-text="u.name" style="font-size:12.5px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
                            <div x-text="u.email" style="font-size:11px;color:#9CA3AF;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
                        </div>
                        {{-- Checkbox indicator --}}
                        <div style="width:18px;height:18px;border-radius:5px;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:all .1s;"
                             :style="isSelected(u.id)
                                 ? {'background':'#4F46E5','border':'none'}
                                 : {'border':'1.5px solid #D1D5DB','background':'transparent'}">
                            <i x-show="isSelected(u.id)" class="fas fa-check" style="font-size:9px;color:#fff;pointer-events:none;"></i>
                        </div>
                    </div>
                </template>
                <div x-show="filtered.length === 0"
                     style="padding:16px;text-align:center;font-size:12px;color:#9CA3AF;">No users found</div>
            </div>
        </div>
    </div>

    {{-- Notes --}}
    <div style="grid-column:1/-1;">
        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Notes</label>
        <textarea name="notes" rows="3"
                  placeholder="Ad account ID, audience notes, admin access info..."
                  style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;resize:vertical;box-sizing:border-box;"
                  onfocus="this.style.borderColor='#6366F1'" onblur="this.style.borderColor='#E5E7EB'">{{ old('notes', $account?->notes) }}</textarea>
    </div>

</div>
