@extends('layouts.app')

@section('title', 'Outstanding PO Dashboard - CRM Reconciliation')

@section('styles')
<style>
    .card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 1.25rem;
        padding: 2rem;
        backdrop-filter: blur(12px);
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
        color: var(--accent-blue);
    }

    .filter-form {
        display: grid;
        grid-template-columns: 2fr 1.5fr 1.2fr auto;
        gap: 1rem;
        align-items: flex-end;
        margin-bottom: 1.5rem;
        background-color: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border);
        padding: 1.25rem;
        border-radius: 0.75rem;
    }

    @media (max-width: 768px) {
        .filter-form {
            grid-template-columns: 1fr;
        }
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    label {
        font-size: 0.8rem;
        color: var(--text-muted);
        font-weight: 500;
    }

    input[type="text"], select {
        width: 100%;
        background-color: #111827;
        border: 1px solid var(--border);
        color: #fff;
        padding: 0.65rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.85rem;
        outline: none;
        transition: all 0.3s ease;
    }

    input[type="text"]:focus, select:focus {
        border-color: var(--accent-blue);
    }

    .btn-filter {
        background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
        color: #fff;
        border: none;
        padding: 0.7rem 1.5rem;
        border-radius: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        height: 2.25rem;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .btn-filter:hover {
        transform: translateY(-1px);
    }

    .table-container {
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
        padding: 1.15rem 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        color: var(--text-main);
        vertical-align: middle;
    }

    tr:hover td {
        background-color: rgba(255, 255, 255, 0.01);
    }

    .status-pill {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 0.25rem;
        text-transform: capitalize;
    }

    .status-open { background-color: rgba(6, 182, 212, 0.15); color: var(--accent-cyan); }
    .status-partially_shipped { background-color: rgba(245, 158, 11, 0.15); color: var(--warning); }
    .status-completed { background-color: rgba(16, 185, 129, 0.15); color: var(--success); }

    .val-ordered { color: #fff; font-weight: 600; }
    .val-allocated { color: var(--success); font-weight: 600; }
    .val-outstanding { color: var(--accent-cyan); font-weight: 700; }

    .empty-state {
        text-align: center;
        color: var(--text-muted);
        padding: 4rem 2rem;
    }

    .pagination-wrapper {
        margin-top: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .pagination-info {
        font-size: 0.8rem;
        color: var(--text-muted);
    }

    .pagination-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .pagination-btn {
        background-color: #111827;
        border: 1px solid var(--border);
        color: #fff;
        padding: 0.4rem 0.8rem;
        border-radius: 0.375rem;
        font-size: 0.8rem;
        text-decoration: none;
    }

    .pagination-btn:hover:not(.disabled) {
        border-color: var(--accent-blue);
        background-color: rgba(59, 130, 246, 0.05);
    }

    .pagination-btn.disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
</style>
@endsection

@section('content')
<div class="card">
    <h2 class="title">
        <svg class="title-icon" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
        Outstanding PO Ledger (Reservation Ledger)
    </h2>

    <!-- Filtering form -->
    <form action="{{ route('dashboard.outstanding') }}" method="GET" class="filter-form">
        <div class="form-group">
            <label>Pencarian (SO No, Product Code/Name)</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari SO atau item...">
        </div>

        <div class="form-group">
            <label>Filter Customer</label>
            <select name="customer_id">
                <option value="">-- Semua Customer --</option>
                @foreach ($customers as $cust)
                    <option value="{{ $cust->id }}" @selected(request('customer_id') == $cust->id)>
                        {{ $cust->customer_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label>Filter Status</label>
            <select name="status">
                <option value="" @selected(request('status') === null)>Outstanding Only</option>
                <option value="open" @selected(request('status') === 'open')>Open</option>
                <option value="partially_shipped" @selected(request('status') === 'partially_shipped')>Partially Shipped</option>
                <option value="completed" @selected(request('status') === 'completed')>Completed</option>
            </select>
        </div>

        <button type="submit" class="btn-filter">
            Apply Filter
        </button>
    </form>

    <!-- Table content -->
    <div class="table-container">
        @if ($lines->isEmpty())
            <div class="empty-state">
                Tidak ada data outstanding PO yang sesuai filter.
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Order Date</th>
                        <th>SO Number</th>
                        <th>Customer Name</th>
                        <th>Product Details</th>
                        <th style="text-align: center;">Ordered</th>
                        <th style="text-align: center;">Allocated</th>
                        <th style="text-align: center;">Outstanding</th>
                        <th style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lines as $line)
                        <tr>
                            <td>{{ $line->salesOrder->order_date ? \Carbon\Carbon::parse($line->salesOrder->order_date)->format('d M Y') : '-' }}</td>
                            <td><strong>{{ $line->salesOrder->so_number }}</strong></td>
                            <td>{{ $line->salesOrder->customer->customer_name }}</td>
                            <td>
                                <span class="val-ordered">{{ $line->product->product_code }}</span>
                                <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-top: 0.2rem;">
                                    {{ $line->product->product_name }}
                                </span>
                            </td>
                            <td style="text-align: center;" class="val-ordered">{{ number_format($line->ordered_qty, 0) }} PCS</td>
                            <td style="text-align: center;" class="val-allocated">{{ number_format($line->allocated_qty, 0) }} PCS</td>
                            <td style="text-align: center;" class="val-outstanding">{{ number_format($line->outstanding_qty, 0) }} PCS</td>
                            <td style="text-align: center;">
                                <span class="status-pill status-{{ $line->status }}">
                                    {{ str_replace('_', ' ', $line->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Custom Pagination controls -->
            <div class="pagination-wrapper">
                <div class="pagination-info">
                    Showing {{ $lines->firstItem() }} to {{ $lines->lastItem() }} of {{ $lines->total() }} entries
                </div>
                <div class="pagination-buttons">
                    <a href="{{ $lines->previousPageUrl() ?? '#' }}" class="pagination-btn @if($lines->onFirstPage()) disabled @endif">
                        &larr; Previous
                    </a>
                    <a href="{{ $lines->nextPageUrl() ?? '#' }}" class="pagination-btn @if(!$lines->hasMorePages()) disabled @endif">
                        Next &rarr;
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
