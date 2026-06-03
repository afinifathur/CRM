@extends('layouts.app')

@section('title', 'Ingestion Preview & Validation - CRM Reconciliation')

@section('styles')
<!-- Handsontable CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/handsontable@12.4.0/dist/handsontable.full.min.css" />
<style>
    .preview-container {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        width: 100%;
        max-width: 1350px;
        margin: 0 auto;
        animation: fadeIn 0.4s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Stats Grid Layout */
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 0.5rem;
    }

    .summary-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 1rem;
        padding: 1.25rem;
        backdrop-filter: blur(12px);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .summary-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
    }

    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    }

    .card-total::before { background-color: var(--accent-cyan); }
    .card-valid::before { background-color: var(--success); }
    .card-warning::before { background-color: var(--warning); cursor: pointer; }
    .card-duplicate::before { background-color: var(--error); }
    .card-new-product::before { background-color: var(--accent-blue); }
    .card-new-customer::before { background-color: var(--accent-purple); }

    .summary-card.clickable {
        cursor: pointer;
    }
    
    .summary-card.clickable:hover {
        border-color: var(--warning);
    }

    .stat-title {
        font-size: 0.75rem;
        color: var(--text-muted);
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.05em;
    }

    .stat-number {
        font-size: 1.75rem;
        font-weight: 700;
        margin-top: 0.5rem;
        display: flex;
        align-items: baseline;
        gap: 0.25rem;
    }

    .stat-number .unit {
        font-size: 0.8rem;
        font-weight: 500;
        color: var(--text-muted);
    }

    .stat-footer {
        font-size: 0.72rem;
        color: var(--text-muted);
        margin-top: 0.5rem;
    }

    .card-total .stat-number { color: #e5e7eb; }
    .card-valid .stat-number { color: var(--success); }
    .card-warning .stat-number { color: var(--warning); }
    .card-duplicate .stat-number { color: var(--error); }
    .card-new-product .stat-number { color: var(--accent-blue); }
    .card-new-customer .stat-number { color: var(--accent-purple); }

    /* Shipment Confidence Cards */
    .confidence-section {
        background-color: rgba(255, 255, 255, 0.01);
        border: 1px solid var(--border);
        border-radius: 1rem;
        padding: 1.25rem;
        margin-bottom: 0.5rem;
    }

    .confidence-title {
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 0.85rem;
        letter-spacing: 0.05em;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .confidence-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }

    .confidence-pill-card {
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        border: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .conf-high { background-color: rgba(16, 185, 129, 0.04); border-color: rgba(16, 185, 129, 0.15); }
    .conf-med { background-color: rgba(245, 158, 11, 0.04); border-color: rgba(245, 158, 11, 0.15); }
    .conf-low { background-color: rgba(239, 68, 68, 0.04); border-color: rgba(239, 68, 68, 0.15); }

    .conf-label {
        font-size: 0.75rem;
        font-weight: 600;
    }
    .conf-high .conf-label { color: var(--success); }
    .conf-med .conf-label { color: var(--warning); }
    .conf-low .conf-label { color: var(--error); }

    .conf-value {
        font-size: 1.25rem;
        font-weight: 700;
    }
    .conf-high .conf-value { color: var(--success); }
    .conf-med .conf-value { color: var(--warning); }
    .conf-low .conf-value { color: var(--error); }

    /* Layout Cards */
    .preview-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 1.25rem;
        padding: 1.5rem;
        backdrop-filter: blur(12px);
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .card-header-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.85rem;
    }

    .card-main-title {
        font-size: 1.15rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .grid-info-badge {
        font-size: 0.8rem;
        font-weight: 500;
        background-color: rgba(255,255,255,0.05);
        color: var(--text-muted);
        padding: 0.25rem 0.65rem;
        border-radius: 9999px;
        border: 1px solid var(--border);
    }

    .actions-row {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 0.75rem;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.25rem;
        font-size: 0.88rem;
        font-weight: 600;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        outline: none;
    }

    .btn-secondary {
        background-color: rgba(255, 255, 255, 0.05);
        color: var(--text-main);
        border: 1px solid var(--border);
    }

    .btn-secondary:hover {
        background-color: rgba(255, 255, 255, 0.08);
    }

    .btn-danger-outline {
        background-color: transparent;
        color: var(--error);
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .btn-danger-outline:hover {
        background-color: rgba(239, 68, 68, 0.06);
        border-color: var(--error);
    }

    .btn-success {
        background: linear-gradient(135deg, var(--success), #059669);
        color: #fff;
        border: none;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
    }

    .btn-success:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(16, 185, 129, 0.3);
    }

    /* Handsontable custom integrations for Dark Mode */
    .handsontable-container {
        width: 100%;
        overflow: hidden;
        border-radius: 0.75rem;
        border: 1px solid var(--border);
        background-color: #111827;
        padding: 2px;
    }

    .handsontable {
        font-family: 'Outfit', sans-serif !important;
        font-size: 0.85rem !important;
    }

    .handsontable th {
        background-color: #182235 !important;
        color: #9ca3af !important;
        font-weight: 600 !important;
        border-color: rgba(255,255,255,0.06) !important;
        font-size: 0.78rem !important;
        text-transform: uppercase !important;
        padding: 8px !important;
    }

    .handsontable td {
        background-color: #111827 !important;
        color: #f3f4f6 !important;
        border-color: rgba(255,255,255,0.04) !important;
        padding: 8px !important;
    }

    /* Row validation coloring */
    .handsontable .row-warning-highlight {
        background-color: rgba(245, 158, 11, 0.08) !important;
        color: #fef08a !important;
    }
    
    .handsontable .row-duplicate-highlight {
        background-color: rgba(239, 68, 68, 0.08) !important;
        color: #fecaca !important;
    }

    .handsontable .row-blocked-highlight {
        background-color: rgba(239, 68, 68, 0.18) !important;
        color: #fca5a5 !important;
    }

    .handsontable .htRight {
        text-align: right !important;
    }

    .handsontable .badge-table {
        display: inline-block;
        padding: 0.15rem 0.4rem;
        font-size: 0.72rem;
        font-weight: 700;
        border-radius: 0.25rem;
    }
    .handsontable .badge-t-high { background-color: rgba(16, 185, 129, 0.2); color: var(--success); }
    .handsontable .badge-t-med { background-color: rgba(245, 158, 11, 0.2); color: var(--warning); }
    .handsontable .badge-t-low { background-color: rgba(239, 68, 68, 0.2); color: var(--error); }

    /* Warning Drawer styling (TASK 7) */
    .warning-drawer {
        position: fixed;
        right: 0;
        top: 0;
        width: 440px;
        height: 100vh;
        background-color: #0f1524;
        border-left: 1px solid var(--border);
        box-shadow: -10px 0 30px rgba(0,0,0,0.6);
        z-index: 1000;
        transform: translateX(100%);
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
    }

    .warning-drawer.open {
        transform: translateX(0);
    }

    .drawer-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0, 0, 0, 0.6);
        z-index: 999;
        display: none;
        backdrop-filter: blur(4px);
    }

    .drawer-overlay.active {
        display: block;
    }

    .drawer-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #151e30;
    }

    .drawer-title {
        font-size: 1.05rem;
        font-weight: 600;
        color: var(--warning);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .drawer-close {
        background: transparent;
        border: none;
        color: var(--text-muted);
        font-size: 1.5rem;
        cursor: pointer;
    }
    
    .drawer-close:hover {
        color: #fff;
    }

    .drawer-content {
        flex-grow: 1;
        overflow-y: auto;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .warning-log-card {
        background-color: rgba(255,255,255,0.02);
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        padding: 0.85rem;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        border-left: 3px solid var(--warning);
    }

    .warning-log-card.w-dup {
        border-left-color: var(--error);
    }

    .w-log-meta {
        font-size: 0.75rem;
        color: var(--text-muted);
        font-weight: 600;
        display: flex;
        justify-content: space-between;
    }

    .w-log-msg {
        font-size: 0.82rem;
        color: #fff;
        line-height: 1.4;
    }

    /* Info Alerts */
    .audit-alert {
        background-color: rgba(6, 182, 212, 0.03);
        border: 1px solid rgba(6, 182, 212, 0.15);
        color: #a5f3fc;
        padding: 1rem;
        border-radius: 0.75rem;
        font-size: 0.85rem;
        line-height: 1.5;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .alert-icon {
        color: var(--accent-cyan);
        flex-shrink: 0;
        margin-top: 0.1rem;
    }
</style>
@endsection

@section('content')
<div class="preview-container">
    
    <!-- Top Header bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700; background: linear-gradient(to right, #fff, var(--text-muted)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                {{ strtoupper($preview->type) }} Ingestion Preview & Verification
            </h1>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.25rem;">
                Original Ingestion File: <strong style="color: var(--accent-cyan);">{{ $preview->source_filename }}</strong> | Status: <span class="badge" style="background-color: rgba(6,182,212,0.15); color: var(--accent-cyan);">{{ strtoupper($preview->status) }}</span>
            </p>
        </div>
        <div>
            <a href="{{ route('imports.download_csv', $preview->id) }}" class="btn btn-secondary" style="font-size: 0.82rem; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4a2 2 0 11-4 0 2 2 0 014 0zM4 8h16"></path></svg>
                Download Parsed CSV
            </a>
        </div>
    </div>

    <!-- Alert audit warning rules -->
    <div class="audit-alert">
        <svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <div>
            <strong>Forensic Verification Stage:</strong> The carrier-forward sheet parser has reconstructed the rows below. No production ledger tables have been modified. New customers and new products are designated as warnings for validation, but are safe to confirm. Active allocations duplicates will be bypassed for safe idempotency. Please verify row integrity before clicking <strong>Confirm Import</strong>.
        </div>
    </div>

    <!-- TASK 3: Parser Summary Statistics Cards -->
    <div class="summary-grid">
        <div class="summary-card card-total">
            <div class="stat-title">Rows Found</div>
            <div class="stat-number">{{ $payload['total_rows'] ?? 0 }}<span class="unit">rows</span></div>
            <div class="stat-footer">Total rows parsed from sheet</div>
        </div>
        <div class="summary-card card-valid">
            <div class="stat-title">Valid Rows</div>
            <div class="stat-number">{{ $payload['valid_rows'] ?? 0 }}<span class="unit">rows</span></div>
            <div class="stat-footer">Ready to commit seamlessly</div>
        </div>
        <div class="summary-card card-warning clickable" id="warning-stat-card">
            <div class="stat-title">Warning Rows 🚨</div>
            <div class="stat-number">{{ $payload['warning_rows'] ?? 0 }}<span class="unit">rows</span></div>
            <div class="stat-footer">Click to view details audit</div>
        </div>
        <div class="summary-card card-duplicate">
            <div class="stat-title">Duplicate Rows</div>
            <div class="stat-number">{{ $payload['duplicate_rows'] ?? 0 }}<span class="unit">rows</span></div>
            <div class="stat-footer">Existing daily snapshots/allocated items</div>
        </div>
        <div class="summary-card card-new-product">
            <div class="stat-title">New Products</div>
            <div class="stat-number">{{ $payload['new_products_count'] ?? 0 }}<span class="unit">pcs</span></div>
            <div class="stat-footer">Will be registered automatically</div>
        </div>
        <div class="summary-card card-new-customer">
            <div class="stat-title">New Customers</div>
            <div class="stat-number">{{ $payload['new_customers_count'] ?? 0 }}<span class="unit">cust</span></div>
            <div class="stat-footer">Will be registered automatically</div>
        </div>
    </div>

    @if ($preview->type === 'po')
    <div style="background-color: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: 1rem; padding: 1.25rem; margin-bottom: 1.5rem;">
        <div style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.85rem; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.5rem;">
            🔍 Parser Diagnostics
        </div>
        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem;">
            <div style="background-color: rgba(255, 255, 255, 0.03); border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.75rem 1rem;">
                <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Parser Mode</div>
                <div style="font-size: 1.1rem; font-weight: 700; color: var(--accent-cyan); margin-top: 0.25rem;">
                    {{ $payload['parser_mode'] ?? 'Unknown' }}
                </div>
            </div>
            <div style="background-color: rgba(255, 255, 255, 0.03); border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.75rem 1rem;">
                <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Cust. Header Row</div>
                <div style="font-size: 1.1rem; font-weight: 700; color: #fff; margin-top: 0.25rem;">
                    Row #{{ $payload['detected_header_row'] ?? '-' }}
                </div>
            </div>
            <div style="background-color: rgba(255, 255, 255, 0.03); border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.75rem 1rem;">
                <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Detail Header Row</div>
                <div style="font-size: 1.1rem; font-weight: 700; color: #fff; margin-top: 0.25rem;">
                    {{ isset($payload['detected_detail_header_row']) && $payload['detected_detail_header_row'] ? 'Row #' . $payload['detected_detail_header_row'] : 'N/A' }}
                </div>
            </div>
            <div style="background-color: rgba(255, 255, 255, 0.03); border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.75rem 1rem;">
                <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Customer Groups</div>
                <div style="font-size: 1.1rem; font-weight: 700; color: var(--accent-purple); margin-top: 0.25rem;">
                    {{ $payload['total_customer_groups'] ?? 0 }}
                </div>
            </div>
            <div style="background-color: rgba(255, 255, 255, 0.03); border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.75rem 1rem;">
                <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Detail Rows</div>
                <div style="font-size: 1.1rem; font-weight: 700; color: var(--success); margin-top: 0.25rem;">
                    {{ $payload['total_detail_rows'] ?? 0 }}
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Import Readiness Indicator Badge -->
    @php
        $readiness = $payload['import_readiness'] ?? 'READY';
        $badgeText = '';
        $badgeDesc = '';
        $bgStyle = '';
        $borderStyle = '';
        $textStyle = '';
        
        if ($readiness === 'BLOCKED') {
            $badgeText = 'BLOCKED';
            $badgeDesc = 'Critical issues detected (e.g. missing required fields). You must fix these errors in the file before importing.';
            $bgStyle = 'rgba(239, 68, 68, 0.1)';
            $borderStyle = '1px solid rgba(239, 68, 68, 0.3)';
            $textStyle = '#ef4444';
        } elseif ($readiness === 'REVIEW') {
            $badgeText = 'REVIEW REQUIRED';
            $badgeDesc = 'Parser ambiguities or duplicate records found. Please review the highlighted rows in the grid below.';
            $bgStyle = 'rgba(245, 158, 11, 0.1)';
            $borderStyle = '1px solid rgba(245, 158, 11, 0.3)';
            $textStyle = '#f59e0b';
        } else {
            $badgeText = 'READY TO IMPORT';
            $badgeDesc = 'All rows parsed successfully. Informational warnings (new customers/products) are safe to proceed.';
            $bgStyle = 'rgba(16, 185, 129, 0.1)';
            $borderStyle = '1px solid rgba(16, 185, 129, 0.3)';
            $textStyle = '#10b981';
        }
    @endphp

    <div style="background-color: {{ $bgStyle }}; border: {{ $borderStyle }}; border-radius: 1rem; padding: 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="font-size: 1.25rem; font-weight: 800; color: {{ $textStyle }}; border: 2px solid {{ $textStyle }}; padding: 0.35rem 0.85rem; border-radius: 0.5rem; letter-spacing: 0.05em; text-transform: uppercase; background-color: rgba(0, 0, 0, 0.2); box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                {{ $badgeText }}
            </div>
            <div>
                <div style="font-size: 0.95rem; font-weight: 700; color: #fff;">Import Readiness Status</div>
                <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.15rem;">{{ $badgeDesc }}</div>
            </div>
        </div>
        @if ($readiness === 'BLOCKED')
            <div style="color: #ef4444; font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; gap: 0.25rem;">
                ⚠️ Confirm Import Disabled
            </div>
        @endif
    </div>

    <!-- Import Readiness Explanation Panel -->
    <div style="background-color: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: 1rem; padding: 1.25rem; margin-bottom: 1.5rem;">
        <div style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 1rem; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.5rem;">
            🛡️ Readiness Audit & Diagnostics
        </div>
        
        <!-- Warning classification counts -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.25rem;">
            <div style="background-color: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 0.5rem; padding: 0.75rem 1rem; text-align: center;">
                <div style="font-size: 0.72rem; color: #10b981; text-transform: uppercase; font-weight: 600;">Informational Warnings</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: #10b981; margin-top: 0.25rem;">
                    {{ $payload['informational_warning_count'] ?? 0 }}
                </div>
            </div>
            <div style="background-color: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 0.5rem; padding: 0.75rem 1rem; text-align: center;">
                <div style="font-size: 0.72rem; color: #f59e0b; text-transform: uppercase; font-weight: 600;">Review Warnings</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: #f59e0b; margin-top: 0.25rem;">
                    {{ $payload['review_warning_count'] ?? 0 }}
                </div>
            </div>
            <div style="background-color: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 0.5rem; padding: 0.75rem 1rem; text-align: center;">
                <div style="font-size: 0.72rem; color: #ef4444; text-transform: uppercase; font-weight: 600;">Blocking Warnings</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: #ef4444; margin-top: 0.25rem;">
                    {{ $payload['blocking_warning_count'] ?? 0 }}
                </div>
            </div>
        </div>

        <div style="background-color: rgba(0, 0, 0, 0.15); border-radius: 0.5rem; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.05);">
            @if ($readiness === 'BLOCKED')
                <div style="color: #ef4444; font-weight: 700; font-size: 0.95rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    🔴 {{ $payload['blocking_rows_count'] ?? 0 }} blocking rows detected.
                </div>
                <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 1rem;">
                    These critical validation failures must be fixed in the spreadsheet before importing. Below are the first 20 blocking errors:
                </div>
                <div style="max-height: 250px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem;">
                    @forelse ($payload['first_20_blocking_warnings'] ?? [] as $warn)
                        <div style="background-color: rgba(239, 68, 68, 0.08); border-left: 3px solid #ef4444; padding: 0.5rem 0.75rem; font-size: 0.82rem; border-radius: 0.25rem; display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #fca5a5; font-weight: 600;">Row {{ $warn['row'] }}</span>
                            <span style="color: #fca5a5; font-family: monospace;">{{ $warn['message'] }}</span>
                        </div>
                    @empty
                        <div style="color: var(--text-muted); font-size: 0.82rem;">No details available.</div>
                    @endforelse
                </div>
            @elseif ($readiness === 'REVIEW')
                <div style="color: #f59e0b; font-weight: 700; font-size: 0.95rem; margin-bottom: 0.5rem;">
                    🟡 {{ $payload['review_warning_count'] ?? 0 }} review warnings detected.
                </div>
                <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Import may proceed but should be reviewed carefully. Below are the first 20 review warnings:
                </div>
                <div style="max-height: 250px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem;">
                    @forelse ($payload['first_20_review_warnings'] ?? [] as $warn)
                        <div style="background-color: rgba(245, 158, 11, 0.08); border-left: 3px solid #f59e0b; padding: 0.5rem 0.75rem; font-size: 0.82rem; border-radius: 0.25rem; display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #fef08a; font-weight: 600;">Row {{ $warn['row'] }}</span>
                            <span style="color: #fef08a;">{{ $warn['message'] }}</span>
                        </div>
                    @empty
                        <div style="color: var(--text-muted); font-size: 0.82rem;">No details available.</div>
                    @endforelse
                </div>
            @else
                <div style="color: #10b981; font-weight: 700; font-size: 0.95rem; margin-bottom: 0.25rem;">
                    🟢 {{ $payload['valid_rows'] ?? 0 }} valid rows detected.
                </div>
                <div style="font-size: 0.82rem; color: var(--text-muted);">
                    Only informational warnings exist. Import may proceed safely.
                </div>
            @endif
        </div>
    </div>

    <!-- Statistics Validation (Sanity Checking) -->
    <div style="background-color: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: 1rem; padding: 1.25rem; margin-bottom: 1.5rem;">
        <div style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.85rem; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.5rem;">
            📊 Statistics Validation
        </div>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
            <div style="background-color: rgba(255, 255, 255, 0.03); border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.75rem 1rem;">
                <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Unique Customers</div>
                <div style="font-size: 1.1rem; font-weight: 700; color: var(--accent-purple); margin-top: 0.25rem;">
                    {{ $payload['unique_customers'] ?? 0 }}
                </div>
            </div>
            <div style="background-color: rgba(255, 255, 255, 0.03); border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.75rem 1rem;">
                <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Unique Products</div>
                <div style="font-size: 1.1rem; font-weight: 700; color: var(--accent-blue); margin-top: 0.25rem;">
                    {{ $payload['unique_products'] ?? 0 }}
                </div>
            </div>
            <div style="background-color: rgba(255, 255, 255, 0.03); border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.75rem 1rem;">
                <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Unique SO Numbers</div>
                <div style="font-size: 1.1rem; font-weight: 700; color: var(--accent-cyan); margin-top: 0.25rem;">
                    {{ $payload['unique_so_numbers'] ?? 0 }}
                </div>
            </div>
        </div>
    </div>

    <!-- TASK 6: Shipment Confidence Summary Card (Specifically for Shipments) -->
    @if ($preview->type === 'shipment')
    <div class="confidence-section">
        <div class="confidence-title">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
            Shipment carry-forward parser confidence summary
        </div>
        <div class="confidence-grid">
            <div class="confidence-pill-card conf-high">
                <span class="conf-label">HIGH CONFIDENCE</span>
                <span class="conf-value" id="conf-high-val">{{ $payload['shipment_confidence']['HIGH'] ?? 0 }}</span>
            </div>
            <div class="confidence-pill-card conf-med">
                <span class="conf-label">MEDIUM CONFIDENCE (New entities)</span>
                <span class="conf-value" id="conf-med-val">{{ $payload['shipment_confidence']['MEDIUM'] ?? 0 }}</span>
            </div>
            <div class="confidence-pill-card conf-low">
                <span class="conf-label">LOW CONFIDENCE (Guessed / Duplicates)</span>
                <span class="conf-value" id="conf-low-val">{{ $payload['shipment_confidence']['LOW'] ?? 0 }}</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Ingestion Grid Card -->
    <div class="preview-card">
        <div class="card-header-bar">
            <div class="card-main-title">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                Parsed Spreadsheet Grid Preview
                <span class="grid-info-badge" id="pagination-status">Loading spreadsheet rows...</span>
            </div>
            
            <!-- TASK 4: client-side pagination loading control -->
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <span style="font-size: 0.78rem; color: var(--text-muted);" id="rows-count-text"></span>
                <button type="button" class="btn btn-secondary" id="btn-load-more" style="font-size: 0.78rem; padding: 0.35rem 0.75rem; display: none;">
                    Show Next 50 Rows
                </button>
            </div>
        </div>

        <!-- Handsontable Instance Mounting Point -->
        <div class="handsontable-container">
            <div id="hot-grid"></div>
        </div>

        <!-- Confirm and Cancel forms -->
        <div class="card-header-bar" style="border-top: 1px solid var(--border); border-bottom: none; padding-top: 1.25rem; padding-bottom: 0;">
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                💡 <em>Verify that all rows align with expected columns before proceeding.</em>
            </div>
            <div class="actions-row">
                <form action="{{ route('imports.cancel', $preview->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel and discard this import preview batch? No production databases will be affected.');">
                    @csrf
                    <button type="submit" class="btn btn-danger-outline">
                        Cancel Ingestion
                    </button>
                </form>

                <form action="{{ route('imports.confirm', $preview->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to COMMIT this batch to production tables? New products and new customers will be registered.');">
                    @csrf
                    <button type="submit" class="btn btn-success" {{ ($payload['import_readiness'] ?? 'READY') === 'BLOCKED' ? 'disabled style=opacity:0.5;cursor:not-allowed;pointer-events:none;' : '' }}>
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Confirm Import
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- TASK 7: Validation Warnings Side Drawer Audit Log -->
<div class="drawer-overlay" id="drawer-overlay"></div>
<div class="warning-drawer" id="warning-drawer">
    <div class="drawer-header">
        <div class="drawer-title">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            Validation Warnings Audit Log ({{ count($payload['warning_details'] ?? []) }})
        </div>
        <button class="drawer-close" id="drawer-close">&times;</button>
    </div>
    
    <div class="drawer-content">
        @if(empty($payload['warning_details']))
            <div style="text-align: center; color: var(--text-muted); padding: 4rem 1rem; font-size: 0.88rem;">
                🎉 No validation warnings found in this batch! All rows are 100% compliant.
            </div>
        @else
            @foreach($payload['warning_details'] as $warn)
                <div class="warning-log-card @if(str_contains($warn['message'], 'Duplicate')) w-dup @endif">
                    <div class="w-log-meta">
                        <span>ROW #{{ $warn['row'] }}</span>
                        <span style="color: var(--text-muted);">{{ str_contains($warn['message'], 'Duplicate') ? 'DUPLICATE' : 'WARNING' }}</span>
                    </div>
                    <div class="w-log-msg">
                        {{ $warn['message'] }}
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
@endsection

@section('scripts')
<!-- Handsontable Full JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/handsontable@12.4.0/dist/handsontable.full.min.js"></script>
<script>
    // 1. Data payload loaded securely from PHP
    const allData = @json($payload['rows'] ?? []);
    const importType = "{{ $preview->type }}";
    const parserMode = "{{ $payload['parser_mode'] ?? 'Flat Table Mode' }}";
    
    // 2. Client-side Pagination Config (TASK 4)
    let currentLimit = 50;
    const increment = 50;
    const totalRows = allData.length;
    
    const btnLoadMore = document.getElementById('btn-load-more');
    const paginationStatus = document.getElementById('pagination-status');
    const rowsCountText = document.getElementById('rows-count-text');

    function getPaginatedData() {
        return allData.slice(0, currentLimit);
    }

    function updatePaginationUI() {
        const displayed = Math.min(currentLimit, totalRows);
        paginationStatus.textContent = `Showing first ${displayed} of ${totalRows} rows`;
        rowsCountText.textContent = `${displayed} / ${totalRows} loaded`;
        
        if (currentLimit < totalRows) {
            btnLoadMore.style.display = 'inline-flex';
        } else {
            btnLoadMore.style.display = 'none';
        }
    }

    // 3. Handsontable Schema definitions
    let colHeaders = [];
    let columnsConfig = [];

    if (importType === 'stock') {
        colHeaders = ['Row ID', 'Product Code', 'Product Name', 'Stock PCS', 'Stock KG', 'Warnings'];
        columnsConfig = [
            { data: 'row_index', type: 'numeric', readOnly: true, width: 70 },
            { data: 'product_code', type: 'text', readOnly: true, width: 150 },
            { data: 'product_name', type: 'text', readOnly: true, width: 300 },
            { data: 'stock_pcs', type: 'numeric', readOnly: true, width: 120, className: 'htRight' },
            { data: 'stock_kg', type: 'numeric', numericFormat: { pattern: '0.0000' }, readOnly: true, width: 120, className: 'htRight' },
            { 
                data: '_warnings', 
                type: 'text', 
                readOnly: true, 
                width: 350,
                renderer: function(instance, td, row, col, prop, value, cellProperties) {
                    td.textContent = value && value.length > 0 ? value.join('; ') : '';
                    td.style.color = '#fcd34d';
                    td.style.fontWeight = '500';
                    return td;
                }
            }
        ];
    } else if (importType === 'po') {
        if (parserMode === 'Grouped Customer ERP Mode') {
            colHeaders = ['Row ID', 'Customer Code', 'Customer Name', 'SO Number', 'SO Date', 'Product Name', 'UnDlv Qty', 'Warnings'];
            columnsConfig = [
                { data: 'row_index', type: 'numeric', readOnly: true, width: 70 },
                { data: 'customer_code', type: 'text', readOnly: true, width: 130 },
                { data: 'customer_name', type: 'text', readOnly: true, width: 220 },
                { data: 'so_number', type: 'text', readOnly: true, width: 120 },
                { data: 'order_date', type: 'text', readOnly: true, width: 110 },
                { data: 'product_name', type: 'text', readOnly: true, width: 220 },
                { data: 'outstanding', type: 'numeric', readOnly: true, width: 110, className: 'htRight' },
                { 
                    data: '_warnings', 
                    type: 'text', 
                    readOnly: true, 
                    width: 300,
                    renderer: function(instance, td, row, col, prop, value, cellProperties) {
                        td.textContent = value && value.length > 0 ? value.join('; ') : '';
                        td.style.color = '#fcd34d';
                        td.style.fontWeight = '500';
                        return td;
                    }
                }
            ];
        } else {
            colHeaders = ['Row ID', 'Customer Code', 'Customer Name', 'SO Number', 'Product Code', 'Product Name', 'Qty', 'Outstanding Qty', 'Order Date', 'Warnings'];
            columnsConfig = [
                { data: 'row_index', type: 'numeric', readOnly: true, width: 70 },
                { data: 'customer_code', type: 'text', readOnly: true, width: 130 },
                { data: 'customer_name', type: 'text', readOnly: true, width: 220 },
                { data: 'so_number', type: 'text', readOnly: true, width: 120 },
                { data: 'product', type: 'text', readOnly: true, width: 130 },
                { data: 'product_name', type: 'text', readOnly: true, width: 220 },
                { data: 'qty', type: 'numeric', readOnly: true, width: 100, className: 'htRight' },
                { data: 'outstanding', type: 'numeric', readOnly: true, width: 110, className: 'htRight' },
                { data: 'order_date', type: 'text', readOnly: true, width: 110 },
                { 
                    data: '_warnings', 
                    type: 'text', 
                    readOnly: true, 
                    width: 300,
                    renderer: function(instance, td, row, col, prop, value, cellProperties) {
                        td.textContent = value && value.length > 0 ? value.join('; ') : '';
                        td.style.color = '#fcd34d';
                        td.style.fontWeight = '500';
                        return td;
                    }
                }
            ];
        }
    } else if (importType === 'shipment') {
        colHeaders = ['Row ID', 'Shipment Date', 'SJ Number', 'Customer Code', 'Customer Name', 'Product Code', 'Product Name', 'Shipped Qty', 'Confidence', 'Warnings'];
        columnsConfig = [
            { data: 'row_index', type: 'numeric', readOnly: true, width: 70 },
            { data: 'shipment_date', type: 'text', readOnly: true, width: 110 },
            { data: 'sj_number', type: 'text', readOnly: true, width: 120 },
            { data: 'customer_code', type: 'text', readOnly: true, width: 130 },
            { data: 'customer_name', type: 'text', readOnly: true, width: 200 },
            { data: 'product', type: 'text', readOnly: true, width: 130 },
            { data: 'product_name', type: 'text', readOnly: true, width: 200 },
            { data: 'qty', type: 'numeric', readOnly: true, width: 100, className: 'htRight' },
            { 
                data: '_confidence', 
                type: 'text', 
                readOnly: true, 
                width: 110,
                renderer: function(instance, td, row, col, prop, value, cellProperties) {
                    Handsontable.renderers.TextRenderer.apply(this, arguments);
                    td.innerHTML = '';
                    const pill = document.createElement('span');
                    pill.className = 'badge-table';
                    if (value === 'HIGH') {
                        pill.className += ' badge-t-high';
                        pill.textContent = 'HIGH';
                    } else if (value === 'MEDIUM') {
                        pill.className += ' badge-t-med';
                        pill.textContent = 'MEDIUM';
                    } else {
                        pill.className += ' badge-t-low';
                        pill.textContent = 'LOW';
                    }
                    td.appendChild(pill);
                    td.style.textAlign = 'center';
                    return td;
                }
            },
            { 
                data: '_warnings', 
                type: 'text', 
                readOnly: true, 
                width: 250,
                renderer: function(instance, td, row, col, prop, value, cellProperties) {
                    td.textContent = value && value.length > 0 ? value.join('; ') : '';
                    td.style.color = '#fcd34d';
                    td.style.fontWeight = '500';
                    return td;
                }
            }
        ];
    }

    // 4. Initialize Handsontable
    const container = document.getElementById('hot-grid');
    const hot = new Handsontable(container, {
        data: getPaginatedData(),
        colHeaders: colHeaders,
        columns: columnsConfig,
        rowHeaders: false,
        height: 480,
        licenseKey: 'non-commercial-and-evaluation',
        stretchH: 'all',
        wordWrap: false,
        autoWrapRow: true,
        autoWrapCol: true,
        columnSorting: true,
        // Highlights row backgrounds if warning or duplicate exists
        cells: function(row, col, prop) {
            const cellProperties = {};
            const paginatedData = getPaginatedData();
            if (paginatedData[row]) {
                const rowData = paginatedData[row];
                let isBlocked = false;
                if (rowData._warnings && rowData._warnings.length > 0) {
                    for (let w of rowData._warnings) {
                        if (w.includes('Missing Required Field:')) {
                            isBlocked = true;
                            break;
                        }
                    }
                }

                if (isBlocked) {
                    cellProperties.className = 'row-blocked-highlight';
                } else if (rowData._duplicate === true) {
                    cellProperties.className = 'row-duplicate-highlight';
                } else if (rowData._warnings && rowData._warnings.length > 0) {
                    cellProperties.className = 'row-warning-highlight';
                }
            }
            return cellProperties;
        }
    });

    updatePaginationUI();

    // 5. Handle simple lazy-load pagination (TASK 4)
    btnLoadMore.addEventListener('click', function() {
        currentLimit += increment;
        hot.loadData(getPaginatedData());
        updatePaginationUI();
    });

    // 6. Warning side drawer open/close listeners (TASK 7)
    const warningCard = document.getElementById('warning-stat-card');
    const drawer = document.getElementById('warning-drawer');
    const overlay = document.getElementById('drawer-overlay');
    const drawerClose = document.getElementById('drawer-close');

    function openDrawer() {
        drawer.classList.add('open');
        overlay.classList.add('active');
    }

    function closeDrawer() {
        drawer.classList.remove('open');
        overlay.classList.remove('active');
    }

    if (warningCard) {
        warningCard.addEventListener('click', openDrawer);
    }
    drawerClose.addEventListener('click', closeDrawer);
    overlay.addEventListener('click', closeDrawer);
</script>
@endsection
