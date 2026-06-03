@extends('layouts.app')

@section('title', 'Operational Home Dashboard - CRM Overlay')

@section('styles')
<style>
    .grid-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }

    @media (max-width: 992px) {
        .grid-stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 576px) {
        .grid-stats {
            grid-template-columns: 1fr;
        }
    }

    .stat-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 1rem;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 140px;
        transition: all 0.3s ease;
        text-decoration: none;
        color: inherit;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        border-color: var(--accent-cyan);
        box-shadow: 0 8px 24px rgba(6, 182, 212, 0.15);
    }

    .stat-title {
        font-size: 0.82rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
    }

    .stat-value {
        font-size: 2.25rem;
        font-weight: 700;
        margin-top: 0.5rem;
        margin-bottom: 0.25rem;
    }

    .stat-desc {
        font-size: 0.8rem;
        color: var(--text-muted);
    }

    .border-cyan { border-left: 4px solid var(--accent-cyan); }
    .border-blue { border-left: 4px solid var(--accent-blue); }
    .border-red { border-left: 4px solid var(--error); }
    .border-purple { border-left: 4px solid var(--accent-purple); }

    .grid-sections {
        display: grid;
        grid-template-columns: 1.8fr 1.2fr;
        gap: 2rem;
    }

    @media (max-width: 992px) {
        .grid-sections {
            grid-template-columns: 1fr;
        }
    }

    .section-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 1.25rem;
        padding: 2rem;
    }

    .section-title {
        font-size: 1.15rem;
        font-weight: 600;
        margin-bottom: 1.25rem;
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* History and shortcuts list */
    .batch-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .batch-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.85rem 1rem;
        background-color: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        font-size: 0.88rem;
    }

    .batch-type-badge {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 0.2rem 0.5rem;
        border-radius: 0.25rem;
    }

    .type-stock { background-color: rgba(20, 184, 166, 0.15); color: #2dd4bf; }
    .type-po { background-color: rgba(59, 130, 246, 0.15); color: #60a5fa; }
    .type-shipment { background-color: rgba(139, 92, 246, 0.15); color: #a78bfa; }

    .batch-status {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--success);
    }

    .shortcut-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .shortcut-btn {
        background-color: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        padding: 1rem;
        text-align: center;
        text-decoration: none;
        color: inherit;
        font-size: 0.88rem;
        font-weight: 600;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .shortcut-btn:hover {
        border-color: var(--accent-cyan);
        background-color: rgba(6, 182, 212, 0.05);
        transform: translateY(-1px);
    }

    .shortcut-icon {
        width: 1.5rem;
        height: 1.5rem;
        color: var(--accent-cyan);
    }
</style>
@endsection

@section('content')

    <!-- Top Headline -->
    <div style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.75rem; font-weight: 700; background: linear-gradient(to right, #fff, var(--text-muted)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            Selamat Datang di CRM Overlay Platform
        </h2>
        <p style="color: var(--text-muted); font-size: 0.92rem; margin-top: 0.25rem;">
            Pusat Rekonsiliasi Operasional & Manajemen Alokasi. ERP Legacy VB6 Integration Overlay.
        </p>
    </div>

    <!-- Summary Metrics Grid -->
    <div class="grid-stats">
        <!-- Card 1: Pending Allocations -->
        <a href="{{ route('allocations.index') }}" class="stat-card border-cyan">
            <span class="stat-title">Pending Allocations</span>
            <span class="stat-value" style="color: var(--accent-cyan);">{{ $pendingCount }}</span>
            <span class="stat-desc">Shipment lines waiting for SO binding review</span>
        </a>

        <!-- Card 2: Outstanding PO Items -->
        <a href="{{ route('dashboard.outstanding') }}" class="stat-card border-blue">
            <span class="stat-title">Outstanding PO Items</span>
            <span class="stat-value" style="color: var(--accent-blue);">{{ $outstandingCount }}</span>
            <span class="stat-desc">Open & partially shipped sales order lines</span>
        </a>

        <!-- Card 3: Free Stock Shortages -->
        <a href="{{ route('dashboard.freestock') }}" class="stat-card border-red">
            <span class="stat-title">Shortages (Negative Free)</span>
            <span class="stat-value" style="color: var(--error);">{{ $negativeCount }}</span>
            <span class="stat-desc">Products where reservations exceed snapshots</span>
        </a>

        <!-- Card 4: Latest ERP Stock Date -->
        <a href="{{ route('import.stock.index') }}" class="stat-card border-purple">
            <span class="stat-title">Latest Snapshot Date</span>
            <span class="stat-value" style="font-size: 1.5rem; margin-top: 1.25rem; color: var(--accent-purple);">
                {{ $latestSnapshotDate ? \Carbon\Carbon::parse($latestSnapshotDate)->format('d M Y') : 'No Data' }}
            </span>
            <span class="stat-desc">Active baseline daily stock reference date</span>
        </a>
    </div>

    <!-- Multi-section Grid -->
    <div class="grid-sections">
        <!-- Section A: Latest Inflow (Ingestion Batches) -->
        <div class="section-card">
            <h3 class="section-title">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Latest Ingestion Logs (Last 5 Batches)
            </h3>

            <div class="batch-list">
                @if ($latestBatches->isEmpty())
                    <div style="text-align: center; padding: 2rem; color: var(--text-muted); font-size: 0.88rem;">
                        Belum ada import batch data terdaftar. Silakan unggah spreadsheet Excel Anda.
                    </div>
                @else
                    @foreach ($latestBatches as $batch)
                        <div class="batch-item">
                            <div>
                                <span class="batch-type-badge type-{{ strtolower($batch->batch_type) }}">
                                    {{ $batch->batch_type }}
                                </span>
                                <strong style="margin-left: 0.5rem;">Batch #{{ $batch->id }}</strong>
                                <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-top: 0.2rem;">
                                    Uploaded: {{ $batch->created_at->format('d M Y H:i:s') }} | Filename: {{ $batch->filename }}
                                </span>
                            </div>
                            <div style="text-align: right;">
                                <span class="batch-status">Success</span>
                                <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-top: 0.2rem;">
                                    Rows: {{ $batch->inserted_rows }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <!-- Section B: Shortcuts & Links -->
        <div class="section-card">
            <h3 class="section-title">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                Quick Operational Shortcuts
            </h3>

            <div class="shortcut-grid">
                <a href="{{ route('import.stock.index') }}" class="shortcut-btn">
                    <svg class="shortcut-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    Stock Ingestion
                </a>
                <a href="{{ route('import.po.index') }}" class="shortcut-btn">
                    <svg class="shortcut-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    PO Ingestion
                </a>
                <a href="{{ route('import.shipment.index') }}" class="shortcut-btn">
                    <svg class="shortcut-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6 0a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-3a2 2 0 00-2-2H9a2 2 0 00-2 2v3a2 2 0 002 2z"></path></svg>
                    Shipment Ingestion
                </a>
                <a href="{{ route('allocations.index') }}" class="shortcut-btn">
                    <svg class="shortcut-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    Allocation Review
                </a>
            </div>

            <div style="margin-top: 1.5rem; padding: 1rem; border: 1px solid var(--border); border-radius: 0.5rem; background-color: rgba(255,255,255,0.01); font-size: 0.8rem; line-height: 1.6; color: var(--text-muted);">
                💡 <strong>PPIC & Sales Guideline:</strong><br>
                1. Upload stock snapshot daily.<br>
                2. Upload outstanding order ledgers when customer details update.<br>
                3. Load the Shipment file to auto-detect matching order targets.<br>
                4. Approve allocations under the <strong>Allocation Review</strong> tab.
            </div>
        </div>
    </div>

@endsection
