@extends('layouts.app')

@section('title', 'Free Stock Dashboard - CRM Reconciliation')

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
        color: var(--accent-cyan);
    }

    .filter-form {
        display: grid;
        grid-template-columns: 3fr auto;
        gap: 1.5rem;
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

    input[type="text"] {
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

    input[type="text"]:focus {
        border-color: var(--accent-cyan);
    }

    .btn-filter {
        background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
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
        vertical-align: middle;
    }

    th a {
        color: var(--text-muted);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        transition: color 0.3s ease;
    }

    th a:hover {
        color: #fff;
    }

    .active-sort {
        color: var(--accent-cyan) !important;
        font-weight: 700;
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

    .free-positive {
        color: var(--success);
        font-weight: 700;
    }

    .free-negative {
        background-color: rgba(239, 68, 68, 0.12);
        color: var(--error);
        font-weight: 700;
        padding: 0.35rem 0.65rem;
        border-radius: 0.375rem;
        border: 1px solid rgba(239, 68, 68, 0.2);
        box-shadow: 0 0 10px rgba(239, 68, 68, 0.15);
        display: inline-block;
    }

    .text-bold {
        font-weight: 600;
        color: #fff;
    }

    .text-muted-sub {
        font-size: 0.8rem;
        color: var(--text-muted);
        display: block;
        margin-top: 0.2rem;
    }

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
        border-color: var(--accent-cyan);
        background-color: rgba(6, 182, 212, 0.05);
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
        <svg class="title-icon" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
        Free Stock Inventory Monitor (Dynamic Computations)
    </h2>

    <!-- Filtering form -->
    <form action="{{ route('dashboard.freestock') }}" method="GET" class="filter-form">
        <div class="form-group">
            <label>Pencarian Produk (Code / Name)</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode atau nama produk...">
        </div>

        <button type="submit" class="btn-filter">
            Search Product
        </button>
    </form>

    <!-- Table content -->
    <div class="table-container">
        @if ($items->isEmpty())
            <div class="empty-state">
                Tidak ada data free stock untuk produk tersebut.
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'product_code', 'sort_dir' => ($sortBy === 'product_code' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="@if($sortBy === 'product_code') active-sort @endif">
                                Product Code
                                {!! $sortBy === 'product_code' ? ($sortDir === 'asc' ? '▲' : '▼') : '' !!}
                            </a>
                        </th>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'product_name', 'sort_dir' => ($sortBy === 'product_name' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="@if($sortBy === 'product_name') active-sort @endif">
                                Product Name
                                {!! $sortBy === 'product_name' ? ($sortDir === 'asc' ? '▲' : '▼') : '' !!}
                            </a>
                        </th>
                        <th style="text-align: right;">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'latest_stock_pcs', 'sort_dir' => ($sortBy === 'latest_stock_pcs' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="@if($sortBy === 'latest_stock_pcs') active-sort @endif">
                                Latest Stock Snapshot
                                {!! $sortBy === 'latest_stock_pcs' ? ($sortDir === 'asc' ? '▲' : '▼') : '' !!}
                            </a>
                        </th>
                        <th style="text-align: right;">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'total_outstanding', 'sort_dir' => ($sortBy === 'total_outstanding' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="@if($sortBy === 'total_outstanding') active-sort @endif">
                                Total Outstanding PO
                                {!! $sortBy === 'total_outstanding' ? ($sortDir === 'asc' ? '▲' : '▼') : '' !!}
                            </a>
                        </th>
                        <th style="text-align: right;">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'free_stock_pcs', 'sort_dir' => ($sortBy === 'free_stock_pcs' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="@if($sortBy === 'free_stock_pcs') active-sort @endif">
                                Free Stock
                                {!! $sortBy === 'free_stock_pcs' ? ($sortDir === 'asc' ? '▲' : '▼') : '' !!}
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td class="text-bold" style="color: var(--accent-cyan);">{{ $item->product_code }}</td>
                            <td class="text-bold">{{ $item->product_name }}</td>
                            <td style="text-align: right;">
                                <span class="text-bold">{{ number_format($item->latest_stock_pcs, 0) }} PCS</span>
                                <span class="text-muted-sub">{{ number_format($item->latest_stock_kg, 2) }} KG</span>
                            </td>
                            <td style="text-align: right;">
                                <span class="text-bold" style="color: var(--accent-purple);">{{ number_format($item->total_outstanding, 0) }} PCS</span>
                            </td>
                            <td style="text-align: right;">
                                @if ($item->free_stock_pcs < 0)
                                    <span class="free-negative">
                                        {{ number_format($item->free_stock_pcs, 0) }} PCS
                                    </span>
                                    <span class="text-muted-sub" style="color: var(--error); font-size: 0.75rem;">
                                        {{ number_format($item->free_stock_kg, 2) }} KG (Shortage)
                                    </span>
                                @else
                                    <span class="free-positive">
                                        {{ number_format($item->free_stock_pcs, 0) }} PCS
                                    </span>
                                    <span class="text-muted-sub" style="color: var(--success);">
                                        {{ number_format($item->free_stock_kg, 2) }} KG
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Pagination controls -->
            <div class="pagination-wrapper">
                <div class="pagination-info">
                    Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} of {{ $items->total() }} entries
                </div>
                <div class="pagination-buttons">
                    <a href="{{ $items->previousPageUrl() ?? '#' }}" class="pagination-btn @if($items->onFirstPage()) disabled @endif">
                        &larr; Previous
                    </a>
                    <a href="{{ $items->nextPageUrl() ?? '#' }}" class="pagination-btn @if(!$items->hasMorePages()) disabled @endif">
                        Next &rarr;
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
