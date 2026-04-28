@extends('layouts.app')
@section('title', 'Customers')

@section('content')
<div x-data="{
        modal: {{ $errors->any() ? 'true' : 'false' }},
        view: localStorage.getItem('customers_view') || 'table',
        setView(v) { this.view = v; localStorage.setItem('customers_view', v); }
     }" style="max-width:1100px;">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Customers</h1>
            <p style="font-size:13px;color:#9CA3AF;margin:4px 0 0;">Manage your clients and link them to projects & tasks</p>
        </div>
        <button @click="modal = true"
                style="display:inline-flex;align-items:center;gap:7px;padding:9px 18px;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">
            <i class="fas fa-plus" style="font-size:11px;"></i> New Customer
        </button>
    </div>

    {{-- View toggle --}}
    <div style="display:flex;gap:2px;background:#F3F4F6;border-radius:12px;padding:4px;margin-bottom:22px;width:fit-content;">
        <button @click="setView('table')" :style="view==='table'
                    ? 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#4F46E5;box-shadow:0 1px 4px rgba(0,0,0,.08);'
                    : 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:#6B7280;'" style="display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#4F46E5;box-shadow:0 1px 4px rgba(0,0,0,.08);">
            <i class="fa fa-table-list" style="font-size:11px;"></i> Table
        </button>
        <button @click="setView('cards')" :style="view==='cards'
                    ? 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#4F46E5;box-shadow:0 1px 4px rgba(0,0,0,.08);'
                    : 'display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:#6B7280;'" style="display:flex;align-items:center;gap:7px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:#6B7280;">
            <i class="fa fa-grip" style="font-size:11px;"></i> Cards
        </button>
    </div>

    @if(session('success'))
    <div style="background:#ECFDF5;border:1px solid #6EE7B7;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#065F46;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif

    {{-- Search --}}
    <form method="GET" style="margin-bottom:16px;">
        <div style="position:relative;max-width:360px;">
            <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:13px;"></i>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search by name, company or email..."
                   style="width:100%;padding:9px 14px 9px 36px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;background:#fff;">
        </div>
    </form>

    @if($customers->isEmpty())
    {{-- Empty state --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:60px;text-align:center;">
        <div style="width:56px;height:56px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
            <i class="fas fa-building" style="font-size:22px;color:#D1D5DB;"></i>
        </div>
        <p style="font-size:14px;font-weight:600;color:#374151;margin:0 0 4px;">No customers yet</p>
        <p style="font-size:13px;color:#9CA3AF;margin:0 0 16px;">Create your first customer to link them to projects and tasks.</p>
        <button @click="modal = true"
                style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#6366F1;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
            <i class="fas fa-plus" style="font-size:10px;"></i> New Customer
        </button>
    </div>

    @else

    {{-- ── TABLE VIEW ── --}}
    <div x-show="view === 'table'">
        <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);overflow:hidden;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1.5px solid #F3F4F6;">
                        <th style="padding:12px 20px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Customer</th>
                        <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Contact</th>
                        <th style="padding:12px 16px;text-align:center;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Projects</th>
                        <th style="padding:12px 16px;text-align:center;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Tasks</th>
                        <th style="padding:12px 16px;text-align:right;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $customer)
                    <tr style="border-bottom:1px solid #F9FAFB;transition:background .1s;" onmouseover="this.style.background='#FAFAFA'" onmouseout="this.style.background=''">
                        <td style="padding:14px 20px;">
                            <div style="display:flex;align-items:center;gap:12px;">
                                @if($customer->logo)
                                <img src="{{ Storage::url($customer->logo) }}" alt="{{ $customer->name }}"
                                     style="width:38px;height:38px;border-radius:10px;object-fit:cover;border:1px solid #E5E7EB;flex-shrink:0;">
                                @else
                                <div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0;">
                                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                                </div>
                                @endif
                                <div>
                                    <a href="{{ route('admin.customers.show', $customer) }}"
                                       style="font-size:14px;font-weight:600;color:#111827;text-decoration:none;">{{ $customer->name }}</a>
                                    @if($customer->company)
                                    <p style="font-size:12px;color:#9CA3AF;margin:1px 0 0;">{{ $customer->company }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td style="padding:14px 16px;">
                            <div style="font-size:13px;color:#374151;">
                                @if($customer->email)
                                <div style="display:flex;align-items:center;gap:5px;margin-bottom:2px;">
                                    <i class="fas fa-envelope" style="font-size:11px;color:#9CA3AF;"></i>
                                    <span>{{ $customer->email }}</span>
                                </div>
                                @endif
                                @if($customer->phone)
                                <div style="display:flex;align-items:center;gap:5px;">
                                    <i class="fas fa-phone" style="font-size:11px;color:#9CA3AF;"></i>
                                    <span>{{ $customer->phone }}</span>
                                </div>
                                @endif
                                @if(!$customer->email && !$customer->phone)
                                <span style="color:#D1D5DB;">—</span>
                                @endif
                            </div>
                        </td>
                        <td style="padding:14px 16px;text-align:center;">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:8px;background:#EEF2FF;color:#4F46E5;font-size:13px;font-weight:700;">
                                {{ $customer->projects_count }}
                            </span>
                        </td>
                        <td style="padding:14px 16px;text-align:center;">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:8px;background:#F0FDF4;color:#16A34A;font-size:13px;font-weight:700;">
                                {{ $customer->tasks_count }}
                            </span>
                        </td>
                        <td style="padding:14px 16px;text-align:right;">
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px;">
                                <a href="{{ route('admin.customers.show', $customer) }}"
                                   style="padding:5px 10px;border-radius:7px;background:#F3F4F6;color:#374151;font-size:12px;font-weight:600;text-decoration:none;">
                                    <i class="fas fa-eye" style="font-size:11px;"></i>
                                </a>
                                <a href="{{ route('admin.customers.edit', $customer) }}"
                                   style="padding:5px 10px;border-radius:7px;background:#EEF2FF;color:#4F46E5;font-size:12px;font-weight:600;text-decoration:none;">
                                    <i class="fas fa-pencil" style="font-size:11px;"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}"
                                      onsubmit="return confirm('Delete customer {{ addslashes($customer->name) }}? Projects and tasks linked to them will not be deleted.')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            style="padding:5px 10px;border-radius:7px;background:#FEF2F2;color:#DC2626;border:none;font-size:12px;font-weight:600;cursor:pointer;">
                                        <i class="fas fa-trash" style="font-size:11px;"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @if($customers->hasPages())
            <div style="padding:14px 20px;border-top:1px solid #F3F4F6;">
                {{ $customers->links() }}
            </div>
            @endif
        </div>
    </div>

    {{-- ── CARD VIEW ── --}}
    <div x-show="view === 'cards'">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
            @foreach($customers as $customer)
            <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:20px;display:flex;flex-direction:column;gap:14px;transition:box-shadow .15s;"
                 onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.08)'" onmouseout="this.style.boxShadow='0 1px 4px rgba(0,0,0,.04)'">

                {{-- Card top: avatar + name + actions --}}
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                    <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                        @if($customer->logo)
                        <img src="{{ Storage::url($customer->logo) }}" alt="{{ $customer->name }}"
                             style="width:46px;height:46px;border-radius:12px;object-fit:cover;border:1.5px solid #E5E7EB;flex-shrink:0;">
                        @else
                        <div style="width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#fff;flex-shrink:0;">
                            {{ strtoupper(substr($customer->name, 0, 1)) }}
                        </div>
                        @endif
                        <div style="min-width:0;">
                            <a href="{{ route('admin.customers.show', $customer) }}"
                               style="font-size:14px;font-weight:700;color:#111827;text-decoration:none;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $customer->name }}</a>
                            @if($customer->company)
                            <p style="font-size:12px;color:#9CA3AF;margin:1px 0 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $customer->company }}</p>
                            @endif
                        </div>
                    </div>
                    {{-- Action buttons --}}
                    <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
                        <a href="{{ route('admin.customers.edit', $customer) }}"
                           style="width:28px;height:28px;border-radius:7px;background:#EEF2FF;color:#4F46E5;display:flex;align-items:center;justify-content:center;text-decoration:none;font-size:11px;">
                            <i class="fas fa-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}"
                              onsubmit="return confirm('Delete customer {{ addslashes($customer->name) }}? Projects and tasks linked to them will not be deleted.')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    style="width:28px;height:28px;border-radius:7px;background:#FEF2F2;color:#DC2626;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Contact info --}}
                @if($customer->email || $customer->phone)
                <div style="display:flex;flex-direction:column;gap:5px;">
                    @if($customer->email)
                    <div style="display:flex;align-items:center;gap:7px;">
                        <div style="width:24px;height:24px;border-radius:6px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-envelope" style="font-size:10px;color:#6366F1;"></i>
                        </div>
                        <a href="mailto:{{ $customer->email }}" style="font-size:12px;color:#374151;text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $customer->email }}</a>
                    </div>
                    @endif
                    @if($customer->phone)
                    <div style="display:flex;align-items:center;gap:7px;">
                        <div style="width:24px;height:24px;border-radius:6px;background:#F0FDF4;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-phone" style="font-size:10px;color:#16A34A;"></i>
                        </div>
                        <a href="tel:{{ $customer->phone }}" style="font-size:12px;color:#374151;text-decoration:none;">{{ $customer->phone }}</a>
                    </div>
                    @endif
                </div>
                @endif

                {{-- Stats row --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <div style="background:#F9FAFB;border-radius:10px;padding:10px 12px;text-align:center;">
                        <p style="font-size:18px;font-weight:800;color:#4F46E5;margin:0;line-height:1;">{{ $customer->projects_count }}</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">Projects</p>
                    </div>
                    <div style="background:#F9FAFB;border-radius:10px;padding:10px 12px;text-align:center;">
                        <p style="font-size:18px;font-weight:800;color:#16A34A;margin:0;line-height:1;">{{ $customer->tasks_count }}</p>
                        <p style="font-size:11px;color:#9CA3AF;margin:3px 0 0;">Tasks</p>
                    </div>
                </div>

                {{-- View button --}}
                <a href="{{ route('admin.customers.show', $customer) }}"
                   style="display:flex;align-items:center;justify-content:center;gap:6px;padding:8px;background:#F9FAFB;border:1px solid #F0F0F0;border-radius:9px;font-size:12px;font-weight:600;color:#374151;text-decoration:none;transition:background .15s;"
                   onmouseover="this.style.background='#EEF2FF';this.style.color='#4F46E5';this.style.borderColor='#C7D2FE'"
                   onmouseout="this.style.background='#F9FAFB';this.style.color='#374151';this.style.borderColor='#F0F0F0'">
                    <i class="fas fa-arrow-right" style="font-size:10px;"></i> View Details
                </a>
            </div>
            @endforeach
        </div>

        @if($customers->hasPages())
        <div style="margin-top:16px;">
            {{ $customers->links() }}
        </div>
        @endif
    </div>

    @endif

    {{-- Create Customer Modal --}}
    <div x-show="modal" x-cloak
         style="position:fixed;top:0;left:0;right:0;bottom:0;z-index:1000;">

        {{-- Backdrop --}}
        <div @click="modal = false"
             style="position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.45);backdrop-filter:blur(2px);"></div>

        {{-- Centering wrapper --}}
        <div style="position:absolute;top:0;left:0;right:0;bottom:0;display:flex;align-items:center;justify-content:center;padding:20px;pointer-events:none;">

        {{-- Panel --}}
        <div x-show="modal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95"
             style="position:relative;width:100%;max-width:560px;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.18);overflow:hidden;pointer-events:auto;">

            {{-- Modal header --}}
            <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px 0;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-building" style="font-size:15px;color:#fff;"></i>
                    </div>
                    <div>
                        <h2 style="font-size:16px;font-weight:700;color:#111827;margin:0;">New Customer</h2>
                        <p style="font-size:12px;color:#9CA3AF;margin:1px 0 0;">Add a client to link with projects & tasks</p>
                    </div>
                </div>
                <button @click="modal = false" type="button"
                        style="width:30px;height:30px;border-radius:8px;background:#F3F4F6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;font-size:13px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Form --}}
            <form method="POST" action="{{ route('admin.customers.store') }}" enctype="multipart/form-data"
                  x-data="{ logoPreview: null }" style="padding:20px 24px 24px;">
                @csrf

                {{-- Logo upload + Name/Company row --}}
                <div style="display:flex;align-items:flex-start;gap:16px;margin-bottom:14px;">

                    {{-- Logo picker --}}
                    <div style="flex-shrink:0;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Logo</label>
                        <div style="position:relative;width:72px;height:72px;cursor:pointer;"
                             @click="$refs.logoInput.click()">
                            <div x-show="!logoPreview"
                                 style="width:72px;height:72px;border-radius:14px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;border:2px dashed transparent;">
                                <i class="fas fa-building" style="font-size:22px;color:#fff;opacity:.7;"></i>
                            </div>
                            <img x-show="logoPreview" :src="logoPreview" x-cloak
                                 style="width:72px;height:72px;border-radius:14px;object-fit:cover;border:2px solid #E5E7EB;">
                            <div style="position:absolute;bottom:-4px;right:-4px;width:22px;height:22px;border-radius:50%;background:#6366F1;display:flex;align-items:center;justify-content:center;border:2px solid #fff;">
                                <i class="fas fa-camera" style="font-size:9px;color:#fff;"></i>
                            </div>
                        </div>
                        <input type="file" name="logo" accept="image/*" x-ref="logoInput" style="display:none;"
                               @change="logoPreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
                        @error('logo')<p style="font-size:10px;color:#DC2626;margin-top:3px;width:72px;word-break:break-word;">{{ $message }}</p>@enderror
                    </div>

                    {{-- Name + Company --}}
                    <div style="flex:1;display:flex;flex-direction:column;gap:10px;">
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">
                                Name <span style="color:#EF4444;">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name') }}" required autofocus
                                   placeholder="e.g. John Smith"
                                   style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('name') ? '#EF4444' : '#E5E7EB' }};border-radius:9px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;">
                            @error('name')<p style="font-size:11px;color:#DC2626;margin-top:3px;">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Company</label>
                            <input type="text" name="company" value="{{ old('company') }}"
                                   placeholder="e.g. Acme Corp"
                                   style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:9px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;">
                        </div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               placeholder="john@example.com"
                               style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('email') ? '#EF4444' : '#E5E7EB' }};border-radius:9px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;">
                        @error('email')<p style="font-size:11px;color:#DC2626;margin-top:3px;">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                               placeholder="+1 555 000 0000"
                               style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:9px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;">
                    </div>
                </div>

                <div style="margin-bottom:20px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Notes</label>
                    <textarea name="notes" rows="2" placeholder="Any extra notes about this customer..."
                              style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:9px;font-size:13px;color:#111827;box-sizing:border-box;outline:none;resize:vertical;font-family:'Inter',sans-serif;">{{ old('notes') }}</textarea>
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="submit"
                            style="flex:1;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;padding:10px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
                        <i class="fas fa-plus" style="margin-right:5px;font-size:11px;"></i>Create Customer
                    </button>
                    <button type="button" @click="modal = false"
                            style="flex:1;background:#F3F4F6;color:#374151;border:none;padding:10px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
        </div>{{-- /centering wrapper --}}
    </div>{{-- /modal --}}

</div>
@endsection
