{{--
  Mobile restyle of resources/views/admin/customers/index.blade.php
  Scope: <=768px only. Desktop markup stays as it is.

  Vars (from Admin\CustomerController@index): $customers (paginated), $withOpenWork
  Customer: id, name, company, email, phone, projects_count, tasks_count
  Routes: admin.customers.index (?search=), admin.customers.show, admin.customers.report
  "New Customer" and "Summarize" reuse the desktop page's own Alpine modals
  (modal / summarizeModal on the x-data wrapper) rather than separate pages.
--}}

<div class="cus-mobile">

    {{-- HEADER: compact + New --}}
    <div class="cus-head">
        <div class="cus-head-text">
            <div class="cus-title">Customers</div>
            <div class="cus-sub">
                {{ $customers->total() }} clients · {{ $withOpenWork }} with open work
            </div>
        </div>
        <button type="button" @click="modal = true" class="cus-new">
            <i class="fas fa-plus"></i> New
        </button>
    </div>

    {{-- SEARCH + Summarize as an icon button, not a full-width block --}}
    <form method="GET" action="{{ route('admin.customers.index') }}" class="cus-searchrow">
        <label class="cus-search">
            <i class="fas fa-search"></i>
            <input type="search" name="search" value="{{ request('search') }}"
                   placeholder="Search name, company or email" autocomplete="off">
        </label>
        @if(($appSettings['hide_summarize_button'] ?? '0') !== '1')
        <button type="button"
                @click="summarizeModal = true; if (!_summaryLoaded) { _summaryLoaded = true; fetchSummary(); }"
                class="cus-icon-btn" aria-label="Summarize customers">
            <i class="fas fa-wand-magic-sparkles"></i>
        </button>
        @endif
    </form>

    @if ($customers->isEmpty())
        @php
            $customersEmptySub = request('search')
                ? "Nothing matches '".request('search')."'."
                : 'Add your first client to get started.';
        @endphp
        <x-mobile.empty-state
            title="No customers found"
            :sub="$customersEmptySub"
            icon="fa-address-book"
        />
    @else
        <div class="cus-list">
            @foreach ($customers as $c)
                <article class="uds-card">

                    {{-- identity row: counts inline, no big grey stat boxes --}}
                    <div class="cus-idrow">
                        <span class="cus-avatar">{{ mb_substr($c->name, 0, 1) }}</span>
                        <a href="{{ route('admin.customers.show', $c) }}" class="cus-idtext">
                            <span class="cus-name">{{ $c->name }}</span>
                            <span class="cus-counts">
                                {{ trans_choice(':count project|:count projects', (int) $c->projects_count) }}
                                · {{ (int) $c->tasks_count }} tasks
                            </span>
                        </a>
                        <a href="{{ route('admin.customers.show', $c) }}" class="cus-more" aria-label="Customer actions">···</a>
                    </div>

                    {{-- contact lines: grey inline icons, no colored tiles --}}
                    <div class="cus-contact">
                        @if ($c->email)
                            <div class="cus-line">
                                <i class="fas fa-envelope"></i>
                                <a href="mailto:{{ $c->email }}" class="cus-line-val">{{ $c->email }}</a>
                            </div>
                        @endif
                        @if ($c->phone)
                            <div class="cus-line">
                                <i class="fas fa-phone"></i>
                                <a href="tel:{{ $c->phone }}" class="cus-line-val cus-num">{{ $c->phone }}</a>
                                @if ($c->whatsappUrl())
                                    <a href="{{ $c->whatsappUrl() }}" target="_blank" rel="noopener" class="cus-wa">WhatsApp</a>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="cus-rule"></div>

                    <div class="uds-actions-row">
                        <a href="{{ route('admin.customers.show', $c) }}" class="cus-btn-ghost">View</a>
                        <a href="{{ route('admin.customers.report', $c) }}" class="cus-btn-soft">Report</a>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($customers->hasPages())
            <div class="cus-pager">{{ $customers->withQueryString()->links() }}</div>
        @endif
    @endif
</div>

<style>
.cus-mobile { display: none; }

@media (max-width: 768px) {
    .cus-mobile { display: block; }

    /* hide desktop header, search row, view toggle, and both table/cards views —
       selectors doubled (.x.x) to out-specificity this page's own later !important
       mobile rules for these same classes (it already force-converts the cards
       view into a mobile grid; this new block replaces that entirely). */
    .cust-header.cust-header, .cust-search-wrap.cust-search-wrap,
    .cust-view-toggle.cust-view-toggle, .cust-view-table-wrap.cust-view-table-wrap,
    .cust-view-cards-wrap.cust-view-cards-wrap { display: none !important; }

    .cus-head { display: flex; align-items: flex-start; gap: 10px; padding: 2px 0 12px; }
    .cus-head-text { flex: 1; min-width: 0; }
    .cus-title { font-size: 20px; font-weight: 700; letter-spacing: -.02em; color: #111827; }
    .cus-sub { font-size: 12px; font-weight: 500; color: #6B7280; margin-top: 2px; }
    .cus-new {
        flex: none; min-height: 44px; padding: 0 13px; border-radius: 11px; border: 0; text-decoration: none;
        background: var(--mob-brand-grad, linear-gradient(135deg,#4F46E5,#6366F1)); color: #fff;
        font-size: 13px; font-weight: 700; letter-spacing: -.01em; cursor: pointer;
        display: inline-flex; align-items: center; gap: 6px;
        box-shadow: 0 8px 16px -8px rgba(79,70,229,.7);
    }
    .cus-new i { font-size: 11px; }

    .cus-searchrow { display: flex; gap: 9px; align-items: center; margin-bottom: 14px; }
    .cus-search {
        flex: 1; display: flex; align-items: center; gap: 9px; min-height: 44px;
        background: #F7F8FC; border: 1px solid #E5E7EB; border-radius: 12px; padding: 0 12px;
    }
    .cus-search i { font-size: 13px; color: #9CA3AF; }
    .cus-search input {
        flex: 1; min-width: 0; border: 0; background: none; outline: none;
        font-family: inherit; font-size: 16px; font-weight: 500; color: #111827; /* 16px = no iOS zoom */
    }
    .cus-search input::placeholder { font-size: 13.5px; color: #9CA3AF; }
    .cus-icon-btn {
        flex: none; width: 44px; height: 44px; border: 1px solid #C7D2FE; border-radius: 12px;
        background: #fff; color: var(--mob-brand, #4F46E5); font-size: 15px; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
    }

    .cus-list { display: flex; flex-direction: column; gap: 10px; }

    .cus-idrow { display: flex; align-items: center; gap: 11px; }
    .cus-avatar {
        width: 40px; height: 40px; flex: none; border-radius: 12px;
        background: var(--mob-brand, #4F46E5); color: #fff;
        font-size: 15px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center;
    }
    .cus-idtext { flex: 1; min-width: 0; text-decoration: none; }
    .cus-name {
        display: block; font-size: 15px; font-weight: 700; color: #111827; letter-spacing: -.015em;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .cus-counts { display: block; font-size: 11.5px; font-weight: 600; color: #6B7280; margin-top: 3px; }
    .cus-more {
        flex: none; width: 34px; height: 34px; border: 1px solid #E1E4EA; border-radius: 11px;
        background: #fff; color: #6B7280; font-size: 15px; font-weight: 700; line-height: 1;
        display: flex; align-items: center; justify-content: center; text-decoration: none;
    }

    .cus-contact { display: flex; flex-direction: column; gap: 6px; margin-top: 12px; }
    .cus-line { display: flex; align-items: center; gap: 8px; }
    .cus-line i { font-size: 12px; color: #9CA3AF; flex: none; width: 13px; text-align: center; }
    .cus-line-val {
        flex: 1; min-width: 0; font-size: 12.5px; font-weight: 500; color: #6B7280; text-decoration: none;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .cus-num { font-variant-numeric: tabular-nums; }
    .cus-wa {
        flex: none; min-height: 44px; padding: 0 9px; border: 1px solid #D1FAE5; border-radius: 8px;
        background: #ECFDF5; color: #047857; font-size: 11px; font-weight: 700; text-decoration: none;
        display: inline-flex; align-items: center;
    }

    .cus-rule { height: 1px; background: #F0F1F5; margin: 13px 0 12px; }

    .cus-btn-ghost, .cus-btn-soft {
        flex: 1; min-height: 44px; border-radius: 12px; text-decoration: none;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 13.5px; font-weight: 700; letter-spacing: -.01em;
    }
    .cus-btn-ghost { border: 1px solid #E1E4EA; background: #fff; color: #374151; }
    .cus-btn-soft  { border: 0; background: #EEF2FF; color: var(--mob-brand, #4F46E5); }

    .cus-pager { margin-top: 16px; }
}
</style>
