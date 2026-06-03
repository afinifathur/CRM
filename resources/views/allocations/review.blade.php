@extends('layouts.app')

@section('title', 'Manual Allocation Review Queue - CRM Reconciliation')

@section('styles')
<style>
    .card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 1.25rem;
        padding: 2rem;
        backdrop-filter: blur(12px);
        margin-bottom: 2.5rem;
    }

    .title {
        font-size: 1.35rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.75rem;
    }

    .title-icon {
        color: var(--accent-cyan);
    }

    .alert {
        border-radius: 0.75rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
        border: 1px solid transparent;
    }

    .alert-success {
        background-color: rgba(16, 185, 129, 0.08);
        border-color: rgba(16, 185, 129, 0.2);
        color: var(--success);
    }

    .alert-danger {
        background-color: rgba(239, 68, 68, 0.08);
        border-color: rgba(239, 68, 68, 0.2);
        color: var(--error);
    }

    .review-table-container {
        overflow-x: auto;
        width: 100%;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 0.9rem;
    }

    th {
        padding: 1rem;
        color: var(--text-muted);
        font-weight: 600;
        border-bottom: 1px solid var(--border);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    td {
        padding: 1.25rem 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        color: var(--text-main);
        vertical-align: middle;
    }

    tr:hover td {
        background-color: rgba(255, 255, 255, 0.01);
    }

    .confidence-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.35rem 0.65rem;
        font-size: 0.75rem;
        font-weight: 700;
        border-radius: 0.375rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .confidence-HIGH {
        background-color: rgba(16, 185, 129, 0.12);
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.25);
        box-shadow: 0 0 10px rgba(16, 185, 129, 0.15);
    }

    .confidence-MEDIUM {
        background-color: rgba(245, 158, 11, 0.12);
        color: var(--warning);
        border: 1px solid rgba(245, 158, 11, 0.25);
        box-shadow: 0 0 10px rgba(245, 158, 11, 0.15);
    }

    .confidence-LOW {
        background-color: rgba(239, 68, 68, 0.12);
        color: var(--error);
        border: 1px solid rgba(239, 68, 68, 0.25);
        box-shadow: 0 0 10px rgba(239, 68, 68, 0.15);
    }

    .confidence-NONE {
        background-color: rgba(255, 255, 255, 0.05);
        color: var(--text-muted);
        border: 1px solid var(--border);
    }

    .info-cell-bold {
        font-weight: 600;
        color: #fff;
    }

    .info-cell-sub {
        font-size: 0.8rem;
        color: var(--text-muted);
        display: block;
        margin-top: 0.25rem;
    }

    .select-wrapper {
        position: relative;
        width: 100%;
        min-width: 250px;
    }

    select {
        width: 100%;
        background-color: #111827;
        border: 1px solid var(--border);
        color: #fff;
        padding: 0.6rem 2rem 0.6rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.85rem;
        outline: none;
        appearance: none;
    }

    select:focus {
        border-color: var(--accent-cyan);
    }

    .select-wrapper::after {
        content: '▼';
        font-size: 0.7rem;
        color: var(--text-muted);
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border: none;
        padding: 0.55rem 0.9rem;
        border-radius: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-approve {
        background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
        color: #fff;
    }

    .btn-approve:hover {
        transform: translateY(-1px);
    }

    .btn-reset {
        background-color: rgba(239, 68, 68, 0.12);
        color: var(--error);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .btn-reset:hover {
        background-color: rgba(239, 68, 68, 0.2);
    }

    .empty-queue {
        text-align: center;
        color: var(--text-muted);
        padding: 4rem 2rem;
    }

    .empty-icon {
        font-size: 3rem;
        color: var(--border);
        margin-bottom: 1rem;
        display: block;
    }

    .pill-tag {
        background-color: rgba(255,255,255,0.05);
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.8rem;
        border: 1px solid var(--border);
    }
</style>
@endsection

@section('content')

    <!-- Section 1: Manual Review Queue -->
    <div class="card">
        <h2 class="title">
            <svg class="title-icon" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
            Manual Allocation Review Queue (Pending Review)
        </h2>

        <!-- Feedback Alerts -->
        @if (session('success'))
            <div class="alert alert-success">
                <strong>Success!</strong> {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Error:</strong> {{ $errors->first() }}
            </div>
        @endif

        <div class="review-table-container">
            @if ($pendingLines->isEmpty())
                <div class="empty-queue">
                    <span class="empty-icon">✓</span>
                    Semua baris shipment telah berhasil dialokasikan! Antrean kosong.
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Shipment / SJ Details</th>
                            <th>Customer & Product</th>
                            <th style="text-align: center;">Shipped Qty</th>
                            <th>Confidence</th>
                            <th>Outstanding Candidates Selection</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendingLines as $line)
                            @php
                                $sugg = $line->suggestion;
                                $selectedSoLineId = $sugg['suggested_line'] ? $sugg['suggested_line']->id : null;
                            @endphp
                            <tr>
                                <td>
                                    <span class="info-cell-bold">{{ $line->shipment->sj_number }}</span>
                                    <span class="info-cell-sub">Date: {{ $line->shipment->shipment_date ? \Carbon\Carbon::parse($line->shipment->shipment_date)->format('d M Y') : '-' }}</span>
                                </td>
                                <td>
                                    <span class="info-cell-bold">{{ $line->shipment->customer->customer_name }}</span>
                                    <span class="info-cell-sub">Product: <span style="color: var(--accent-cyan);">{{ $line->product->product_code }}</span> | {{ $line->product->product_name }}</span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="pill-tag" style="color: #fff; font-weight: 700;">{{ number_format($line->shipped_qty, 0) }} PCS</span>
                                </td>
                                <td>
                                    @if ($sugg)
                                        <span class="confidence-badge confidence-{{ $sugg['confidence'] }}">
                                            {{ $sugg['confidence'] }}
                                        </span>
                                        <span class="info-cell-sub" style="font-size: 0.75rem;">
                                            {{ $sugg['message'] }}
                                        </span>
                                    @else
                                        <span class="confidence-badge confidence-NONE">NONE</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($sugg && !empty($sugg['candidates']))
                                        <form id="approve-form-{{ $line->id }}" action="{{ route('allocations.approve', $line->id) }}" method="POST">
                                            @csrf
                                            <div class="select-wrapper">
                                                <select name="sales_order_line_id">
                                                    @foreach ($sugg['candidates'] as $cand)
                                                        <option value="{{ $cand->id }}" @selected($cand->id == $selectedSoLineId)>
                                                            SO: {{ $cand->salesOrder->so_number }} | Outstanding: {{ number_format($cand->outstanding_qty, 0) }} PCS
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </form>
                                    @else
                                        <span style="color: var(--error); font-size: 0.85rem; font-weight: 500;">
                                            ⚠️ No outstanding PO candidates found!
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($sugg && $selectedSoLineId)
                                        <button type="submit" form="approve-form-{{ $line->id }}" class="btn btn-approve">
                                            Approve
                                        </button>
                                    @else
                                        <button class="btn" style="background-color: rgba(255,255,255,0.03); color: var(--text-muted); cursor: not-allowed;" disabled>
                                            Approve
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <!-- Section 2: Recently Allocated (Rollback & Audit) -->
    <div class="card">
        <h2 class="title" style="border-color: rgba(16, 185, 129, 0.2);">
            <svg class="title-icon" style="color: var(--success);" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Recently Allocated Ledger (Audit & Rollback)
        </h2>

        <div class="review-table-container">
            @if ($allocatedLines->isEmpty())
                <div class="empty-queue" style="padding: 2.5rem 1rem;">
                    Belum ada item yang dialokasikan baru-baru ini.
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>SJ Details</th>
                            <th>Customer & Product</th>
                            <th style="text-align: center;">Allocated Qty</th>
                            <th>Allocated Target SO</th>
                            <th>Method</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($allocatedLines as $line)
                            <tr>
                                <td>
                                    <span class="info-cell-bold">{{ $line->shipment->sj_number }}</span>
                                    <span class="info-cell-sub">Date: {{ $line->shipment->shipment_date ? \Carbon\Carbon::parse($line->shipment->shipment_date)->format('d M Y') : '-' }}</span>
                                </td>
                                <td>
                                    <span class="info-cell-bold">{{ $line->shipment->customer->customer_name }}</span>
                                    <span class="info-cell-sub">Product: <span style="color: var(--accent-cyan); font-weight: 500;">{{ $line->product->product_code }}</span></span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="pill-tag" style="color: var(--success); font-weight: 700; background-color: rgba(16, 185, 129, 0.1);">
                                        {{ number_format($line->shipped_qty, 0) }} PCS
                                    </span>
                                </td>
                                <td>
                                    @if ($line->salesOrderLine)
                                        <span class="info-cell-bold" style="color: var(--accent-cyan);">SO: {{ $line->salesOrderLine->salesOrder->so_number }}</span>
                                        <span class="info-cell-sub">Original Ordered: {{ number_format($line->salesOrderLine->ordered_qty, 0) }} PCS</span>
                                    @else
                                        <span style="color: var(--error);">Missing SO Target Link</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-success">Manual Review</span>
                                </td>
                                <td>
                                    <form action="{{ route('allocations.reset', $line->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan alokasi ini? Seluruh outstanding balance akan dikembalikan.');">
                                        @csrf
                                        <button type="submit" class="btn btn-reset">
                                            Rollback
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
