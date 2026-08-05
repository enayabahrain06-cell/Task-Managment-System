@extends('layouts.app')
@section('title', 'New Customer')

@push('styles')
<style>
.cust-create-wrap { max-width:640px; width:100%; }
.cust-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
.custform-section-title { display:none; }
@media(max-width:768px){
    .cust-back-btn { width:44px !important; height:44px !important; }
    .cust-create-wrap { padding:0; }
    .cust-create-wrap > div.cust-form-card {
        background:transparent !important; border:none !important; box-shadow:none !important; padding:0 !important;
    }
    .cust-grid2 { grid-template-columns:1fr !important; gap:0 !important; margin-bottom:0 !important; }

    .cust-create-wrap input,
    .cust-create-wrap select,
    .cust-create-wrap textarea {
        width:100% !important; min-height:46px !important; font-size:16px !important; box-sizing:border-box !important;
        border-radius:var(--mob-r-sm) !important;
    }
    .cust-create-wrap textarea { min-height:100px !important; }

    .cust-form-actions { flex-direction:column !important; gap:10px !important; margin-top:4px !important; }
    .cust-form-actions > * { width:100% !important; min-height:46px !important; font-size:15px !important; }

    /* Section cards */
    .custform-section {
        background:#fff; border-radius:var(--mob-r-lg); box-shadow:var(--mob-shadow-1);
        padding:var(--mob-sp-2); margin-bottom:var(--mob-sp-2);
    }
    .custform-section-title {
        display:block !important; margin:0 0 var(--mob-sp-2) !important;
    }

    .custform-section .mob-field,
    .custform-section .cust-field { margin-bottom:var(--mob-sp-2) !important; }
    .custform-section .mob-field:last-child,
    .custform-section .cust-field:last-child { margin-bottom:0 !important; }
}
</style>
@endpush

@section('content')
<div class="cust-create-wrap" style="max-width:640px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
        <a href="{{ route('admin.customers.index') }}" class="cust-back-btn"
           style="width:34px;height:34px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;color:#6B7280;text-decoration:none;">
            <i class="fa fa-arrow-left" style="font-size:13px;"></i>
        </a>
        <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0;">New Customer</h1>
    </div>

    <div class="cust-form-card" style="background:#fff;border-radius:14px;border:1px solid #F0F0F0;box-shadow:0 1px 4px rgba(0,0,0,.04);padding:28px;">
        <form method="POST" action="{{ route('admin.customers.store') }}">
            @csrf

            <div class="custform-section">
                <p class="custform-section-title mob-section-title">Basic Info</p>
                <div class="cust-grid2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                    <div class="mob-field">
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                            Customer Name <span style="color:#EF4444;">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               placeholder="e.g. John Smith"
                               style="width:100%;padding:10px 14px;border:1.5px solid {{ $errors->has('name') ? '#EF4444' : '#E5E7EB' }};border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;">
                        @error('name')<p style="font-size:11px;color:#DC2626;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                    <div class="mob-field">
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Company</label>
                        <input type="text" name="company" value="{{ old('company') }}"
                               placeholder="e.g. Acme Corp"
                               style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;">
                    </div>
                </div>
            </div>

            <div class="custform-section">
                <p class="custform-section-title mob-section-title">Contact Details</p>
                <div class="cust-grid2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                    <div class="mob-field">
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               placeholder="john@example.com"
                               style="width:100%;padding:10px 14px;border:1.5px solid {{ $errors->has('email') ? '#EF4444' : '#E5E7EB' }};border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;">
                        @error('email')<p style="font-size:11px;color:#DC2626;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                    <div class="mob-field">
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Phone</label>
                        @include('admin.customers._phone_picker', ['initialPhone' => old('phone', '')])
                    </div>
                </div>
            </div>

            <div class="custform-section">
                <p class="custform-section-title mob-section-title">Notes</p>
                <div class="cust-field mob-field" style="margin-bottom:24px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Notes</label>
                    <textarea name="notes" rows="4" placeholder="Any extra notes about this customer..."
                              style="width:100%;padding:10px 14px;border:1.5px solid #E5E7EB;border-radius:10px;font-size:14px;color:#111827;box-sizing:border-box;outline:none;resize:vertical;font-family:'Inter',sans-serif;">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="cust-form-actions" style="display:flex;gap:10px;">
                <button type="submit" class="mob-btn-primary"
                        style="flex:1;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;padding:11px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                    <i class="fas fa-plus" style="margin-right:6px;"></i>Create Customer
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

@push('scripts')
@include('admin.customers._phone_picker_script')
@endpush
