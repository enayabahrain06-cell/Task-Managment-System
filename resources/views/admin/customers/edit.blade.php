@extends('layouts.app')
@section('title', 'Edit Customer')

@section('content')
<div style="max-width:640px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
        <a href="{{ route('admin.customers.index') }}"
           style="width:34px;height:34px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;color:#6B7280;text-decoration:none;">
            <i class="fa fa-arrow-left" style="font-size:13px;"></i>
        </a>
        <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0;">Edit Customer</h1>
    </div>

    <div style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:28px;">
        <form method="POST" action="{{ route('admin.customers.update', $customer) }}" enctype="multipart/form-data"
              x-data="{
                  logoPreview: {{ $customer->logo ? '\''.Storage::url($customer->logo).'\'' : 'null' }},
                  hasLogo: {{ $customer->logo ? 'true' : 'false' }},
                  removeLogo: false,
                  pickLogo(e) {
                      const f = e.target.files[0];
                      if (f) { this.logoPreview = URL.createObjectURL(f); this.hasLogo = true; this.removeLogo = false; }
                  },
                  clearLogo() { this.logoPreview = null; this.hasLogo = false; this.removeLogo = true; this.$refs.logoInput.value = ''; }
              }">
            @csrf @method('PUT')
            <input type="hidden" name="remove_logo" :value="removeLogo ? '1' : '0'">

            {{-- Logo --}}
            <div style="display:flex;align-items:flex-start;gap:20px;margin-bottom:22px;padding-bottom:22px;border-bottom:1px solid #F3F4F6;">
                <div>
                    {{-- Current / preview --}}
                    <div style="position:relative;width:88px;height:88px;">
                        <template x-if="logoPreview">
                            <img :src="logoPreview"
                                 style="width:88px;height:88px;border-radius:16px;object-fit:cover;border:2px solid #E5E7EB;">
                        </template>
                        <template x-if="!logoPreview">
                            <div style="width:88px;height:88px;border-radius:16px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-building" style="font-size:26px;color:#fff;opacity:.8;"></i>
                            </div>
                        </template>
                        {{-- Remove X --}}
                        <button type="button" x-show="hasLogo" @click="clearLogo()"
                                style="position:absolute;top:-6px;right:-6px;width:20px;height:20px;border-radius:50%;background:#EF4444;border:2px solid #fff;color:#fff;font-size:9px;cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:1;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <input type="file" name="logo" accept="image/*" x-ref="logoInput" style="display:none;" @change="pickLogo($event)">
                    @error('logo')<p style="font-size:10px;color:#DC2626;margin-top:3px;width:88px;word-break:break-word;">{{ $message }}</p>@enderror
                </div>

                <div style="flex:1;">
                    <p style="font-size:13px;font-weight:600;color:#374151;margin:0 0 4px;">Company Logo</p>
                    <p style="font-size:12px;color:#9CA3AF;margin:0 0 12px;">JPG, PNG, WebP or SVG. Max 5 MB.</p>
                    <button type="button" @click="$refs.logoInput.click()"
                            style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#EEF2FF;color:#4F46E5;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                        <i class="fas fa-upload" style="font-size:10px;"></i>
                        <span x-text="hasLogo ? 'Replace Logo' : 'Upload Logo'"></span>
                    </button>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                        Customer Name <span style="color:#EF4444;">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $customer->name) }}" required
                           style="width:100%;padding:10px 14px;border:1.5px solid {{ $errors->has('name') ? '#EF4444' : '#E5E7EB' }};border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;">
                    @error('name')<p style="font-size:11px;color:#DC2626;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Company</label>
                    <input type="text" name="company" value="{{ old('company', $customer->company) }}"
                           style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Email</label>
                    <input type="email" name="email" value="{{ old('email', $customer->email) }}"
                           style="width:100%;padding:10px 14px;border:1.5px solid {{ $errors->has('email') ? '#EF4444' : '#E5E7EB' }};border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;">
                    @error('email')<p style="font-size:11px;color:#DC2626;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}"
                           style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;">
                </div>
            </div>

            <div style="margin-bottom:24px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Notes</label>
                <textarea name="notes" rows="4"
                          style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;resize:vertical;font-family:'Inter',sans-serif;">{{ old('notes', $customer->notes) }}</textarea>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="submit"
                        style="flex:1;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;padding:11px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                    <i class="fas fa-save" style="margin-right:6px;"></i>Save Changes
                </button>
                <a href="{{ route('admin.customers.index') }}"
                   style="flex:1;background:#F3F4F6;color:#374151;border:none;padding:11px;border-radius:10px;font-size:14px;font-weight:600;text-align:center;text-decoration:none;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
