@extends('layouts.app')

@section('title', 'Import Completed - CRM Reconciliation')

@section('styles')
<style>
    .summary-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 1.5rem 0;
    }
    
    .success-card {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.08), rgba(5, 150, 105, 0.03));
        border: 1px solid rgba(16, 185, 129, 0.25);
        border-radius: 1.25rem;
        padding: 2.25rem;
        text-align: center;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(12px);
    }

    .success-icon {
        background-color: rgba(16, 185, 129, 0.15);
        color: #10b981;
        width: 64px;
        height: 64px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin-bottom: 1.25rem;
        border: 2px solid rgba(16, 185, 129, 0.3);
        box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);
    }

    .success-title {
        font-size: 1.6rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 0.5rem;
    }

    .success-subtitle {
        font-size: 0.9rem;
        color: var(--text-muted);
        max-width: 600px;
        margin: 0 auto 1.5rem auto;
    }

    .meta-details {
        display: flex;
        justify-content: center;
        gap: 2rem;
        font-size: 0.85rem;
        color: var(--text-muted);
        border-top: 1px solid var(--border);
        padding-top: 1.25rem;
    }

    .meta-item strong {
        color: #fff;
    }

    .grid-sections {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    @media (max-width: 768px) {
        .grid-sections {
            grid-template-columns: 1fr;
        }
    }

    .dashboard-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 1rem;
        padding: 1.5rem;
        backdrop-filter: blur(12px);
    }

    .card-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #fff;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.75rem;
    }

    .metric-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        font-size: 0.85rem;
    }

    .metric-row:last-child {
        border-bottom: none;
    }

    .metric-label {
        color: var(--text-muted);
    }

    .metric-value {
        font-weight: 700;
        color: #fff;
    }

    .metric-value.highlight-green {
        color: #10b981;
    }
    
    .metric-value.highlight-blue {
        color: var(--accent-blue);
    }

    .metric-value.highlight-cyan {
        color: var(--accent-cyan);
    }

    .metric-value.highlight-purple {
        color: var(--accent-purple);
    }

    .actions-footer {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-top: 2rem;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        font-size: 0.9rem;
        font-weight: 600;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        outline: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--accent-cyan), #0891b2);
        color: #fff;
        border: none;
        box-shadow: 0 4px 12px rgba(6, 182, 212, 0.2);
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(6, 182, 212, 0.3);
    }

    .btn-secondary {
        background-color: rgba(255, 255, 255, 0.05);
        color: var(--text-main);
        border: 1px solid var(--border);
    }

    .btn-secondary:hover {
        background-color: rgba(255, 255, 255, 0.08);
    }
</style>
@endsection

@section('content')
<div class="summary-container">

    <!-- Success Banner -->
    <div class="success-card">
        <div class="success-icon">✓</div>
        <div class="success-title">Import Completed</div>
        <p class="success-subtitle">
            The data ingestion batch has been successfully processed and written to the database production tables. All records are safely saved.
        </p>
        
        <div class="meta-details">
            <div class="meta-item">
                Batch ID: <strong>#{{ $batch->id }}</strong>
            </div>
            <div class="meta-item">
                Type: <strong>{{ strtoupper($batch->import_type) }}</strong>
            </div>
            <div class="meta-item">
                Imported At: <strong>{{ $batch->imported_at ? \Carbon\Carbon::parse($batch->imported_at)->format('d-M-Y H:i:s') : 'N/A' }}</strong>
            </div>
        </div>
    </div>

    <div class="grid-sections">
        <!-- Ingestion Metrics Card -->
        <div class="dashboard-card">
            <div class="card-title">
                📥 Ingestion Metrics (Batch Output)
            </div>
            
            <div class="metric-row">
                <span class="metric-label">Parser Mode</span>
                <span class="metric-value highlight-cyan">{{ $notesData['parser_mode'] ?? 'N/A' }}</span>
            </div>

            <div class="metric-row">
                <span class="metric-label">Total Rows Processed</span>
                <span class="metric-value">{{ $batch->total_rows ?? 0 }}</span>
            </div>

            <div class="metric-row">
                <span class="metric-label">Inserted Customers</span>
                <span class="metric-value highlight-purple">{{ $notesData['inserted_customers'] ?? 0 }}</span>
            </div>

            <div class="metric-row">
                <span class="metric-label">Inserted Products</span>
                <span class="metric-value highlight-blue">{{ $notesData['inserted_products'] ?? 0 }}</span>
            </div>

            <div class="metric-row">
                <span class="metric-label">Inserted SO Headers</span>
                <span class="metric-value highlight-cyan">{{ $notesData['inserted_so_headers'] ?? 0 }}</span>
            </div>

            <div class="metric-row">
                <span class="metric-label">Inserted SO Lines</span>
                <span class="metric-value highlight-green">{{ $notesData['inserted_so_lines'] ?? 0 }}</span>
            </div>

            <div class="metric-row">
                <span class="metric-label">Skipped Records (Active Allocations)</span>
                <span class="metric-value" style="color: var(--warning);">{{ $notesData['skipped_records'] ?? $batch->skipped_rows ?? 0 }}</span>
            </div>
        </div>

        <!-- Database Verification Card -->
        <div class="dashboard-card">
            <div class="card-title">
                📊 Database Verification (Total Counts)
            </div>

            <div class="metric-row">
                <span class="metric-label">customers table</span>
                <span class="metric-value highlight-purple">{{ $dbCounts['customers'] }}</span>
            </div>

            <div class="metric-row">
                <span class="metric-label">products table</span>
                <span class="metric-value highlight-blue">{{ $dbCounts['products'] }}</span>
            </div>

            <div class="metric-row">
                <span class="metric-label">sales_orders table</span>
                <span class="metric-value highlight-cyan">{{ $dbCounts['sales_orders'] }}</span>
            </div>

            <div class="metric-row">
                <span class="metric-label">sales_order_lines table</span>
                <span class="metric-value highlight-green">{{ $dbCounts['sales_order_lines'] }}</span>
            </div>

            <div class="metric-row">
                <span class="metric-label">shipment_lines table</span>
                <span class="metric-value">{{ $dbCounts['shipments'] }}</span>
            </div>

            <div class="metric-row">
                <span class="metric-label">stock_snapshots table</span>
                <span class="metric-value">{{ $dbCounts['stock_snapshots'] }}</span>
            </div>
        </div>
    </div>

    <!-- Details/Text Summary -->
    @if(isset($notesData['text_summary']))
    <div class="dashboard-card" style="margin-bottom: 2rem;">
        <div class="card-title">
            📝 Import Log Summary
        </div>
        <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6; margin: 0;">
            {{ $notesData['text_summary'] }}
        </p>
    </div>
    @endif

    <!-- Actions footer -->
    <div class="actions-footer">
        @php
            $redirectRoute = $batch->import_type === 'stock_snapshot' ? 'import.stock.index' : ($batch->import_type === 'outstanding_po' ? 'import.po.index' : 'import.shipment.index');
        @endphp
        <a href="{{ route($redirectRoute) }}" class="btn btn-primary">
            Back to Imports
        </a>
        <a href="{{ route('home') }}" class="btn btn-secondary">
            Go to Home
        </a>
    </div>

</div>
@endsection
