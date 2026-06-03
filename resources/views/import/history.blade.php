@extends('layouts.app')

@section('title', 'Ingestion History Audit - CRM Reconciliation')

@section('styles')
<style>
    .history-wrapper {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        width: 100%;
        max-width: 1250px;
        margin: 0 auto;
        animation: fadeIn 0.4s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 1.25rem;
        padding: 2rem;
        backdrop-filter: blur(12px);
    }

    .title-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border);
        padding-bottom: 1rem;
        margin-bottom: 1.5rem;
    }

    .title {
        font-size: 1.3rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .title-icon {
        color: var(--accent-cyan);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
    }

    th {
        padding: 1rem 0.85rem;
        color: var(--text-muted);
        font-weight: 600;
        border-bottom: 1px solid var(--border);
        font-size: 0.78rem;
        text-transform: uppercase;
        text-align: left;
    }

    td {
        padding: 1rem 0.85rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        vertical-align: middle;
    }

    .badge {
        display: inline-block;
        padding: 0.2rem 0.45rem;
        font-size: 0.72rem;
        font-weight: 700;
        border-radius: 0.25rem;
        text-transform: uppercase;
    }

    .badge-preview { background-color: rgba(6, 182, 212, 0.15); color: var(--accent-cyan); }
    .badge-confirmed { background-color: rgba(16, 185, 129, 0.15); color: var(--success); }
    .badge-cancelled { background-color: rgba(239, 68, 68, 0.15); color: var(--error); }

    .badge-type {
        background-color: rgba(255,255,255,0.06);
        color: var(--text-main);
        border: 1px solid var(--border);
        font-size: 0.7rem;
    }

    .row-total { color: var(--accent-cyan); font-weight: 600; }
    .row-valid { color: var(--success); }
    .row-warning { color: var(--warning); }
    .row-duplicate { color: var(--error); }

    .btn-small {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 0.375rem;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }

    .btn-outline {
        border-color: var(--border);
        background-color: rgba(255,255,255,0.02);
        color: var(--text-main);
    }

    .btn-outline:hover {
        background-color: rgba(255,255,255,0.06);
        border-color: var(--text-muted);
    }

    .btn-cyan-outline {
        border-color: rgba(6, 182, 212, 0.4);
        background-color: transparent;
        color: var(--accent-cyan);
    }

    .btn-cyan-outline:hover {
        background-color: rgba(6, 182, 212, 0.08);
        border-color: var(--accent-cyan);
    }

    /* Warning Drawer styling */
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
</style>
@endsection

@section('content')
<div class="history-wrapper">
    
    <div class="card">
        <div class="title-bar">
            <h2 class="title">
                <svg class="title-icon" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Ingestion Preview Sessions History & Warnings Audit Log
            </h2>
            <div style="font-size: 0.82rem; color: var(--text-muted);">
                Total Sessions: <strong>{{ count($batches) }}</strong>
            </div>
        </div>

        @if (session('error'))
            <div style="background-color: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: var(--error); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.88rem;">
                {{ session('error') }}
            </div>
        @endif

        <div style="overflow-x: auto;">
            @if ($batches->isEmpty())
                <div style="text-align: center; color: var(--text-muted); padding: 4rem 1rem;">
                    Belum ada riwayat sesi import preview. Silakan upload file ERP untuk memulai.
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Session ID</th>
                            <th>Date Uploaded</th>
                            <th>Ingestion Type</th>
                            <th>ERP Source Filename</th>
                            <th style="text-align: center;">Total Rows</th>
                            <th style="text-align: center;">Valid</th>
                            <th style="text-align: center;">Warnings</th>
                            <th style="text-align: center;">Duplicates</th>
                            <th>Status</th>
                            <th style="text-align: center;">Audit Trail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($batches as $batch)
                            <tr>
                                <td><strong>#{{ $batch->id }}</strong></td>
                                <td>{{ $batch->created_at ? $batch->created_at->format('d M Y H:i') : '-' }}</td>
                                <td>
                                    <span class="badge badge-type">{{ strtoupper($batch->type) }}</span>
                                </td>
                                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $batch->source_filename ?? '-' }}
                                </td>
                                <td style="text-align: center;" class="row-total">{{ $batch->total_rows }}</td>
                                <td style="text-align: center;" class="row-valid">{{ $batch->valid_rows }}</td>
                                <td style="text-align: center;" class="row-warning">{{ $batch->warning_rows }}</td>
                                <td style="text-align: center;" class="row-duplicate">{{ $batch->duplicate_rows }}</td>
                                <td>
                                    <span class="badge badge-{{ $batch->status }}">
                                        {{ $batch->status }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    @if ($batch->status === 'preview')
                                        <a href="{{ route('imports.preview', $batch->id) }}" class="btn-small btn-cyan-outline">
                                            Resume Preview
                                        </a>
                                    @else
                                        <button class="btn-small btn-outline btn-view-drawer" data-id="{{ $batch->id }}">
                                            View Warnings
                                        </button>
                                        <!-- Hidden container for JS drawer reading -->
                                        <div id="warnings-data-{{ $batch->id }}" style="display: none;">
                                            @json($batch->preview_payload['warning_details'] ?? [])
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>

<!-- Validation Warnings Side Drawer Audit Log -->
<div class="drawer-overlay" id="drawer-overlay"></div>
<div class="warning-drawer" id="warning-drawer">
    <div class="drawer-header">
        <div class="drawer-title">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            Warnings Historical Audit Log (<span id="drawer-warn-count">0</span>)
        </div>
        <button class="drawer-close" id="drawer-close">&times;</button>
    </div>
    
    <div class="drawer-content" id="drawer-log-content">
        <!-- Rendered dynamically -->
    </div>
</div>
@endsection

@section('scripts')
<script>
    const drawer = document.getElementById('warning-drawer');
    const overlay = document.getElementById('drawer-overlay');
    const drawerClose = document.getElementById('drawer-close');
    const drawerContent = document.getElementById('drawer-log-content');
    const drawerWarnCount = document.getElementById('drawer-warn-count');

    function closeDrawer() {
        drawer.classList.remove('open');
        overlay.classList.remove('active');
    }

    document.querySelectorAll('.btn-view-drawer').forEach(button => {
        button.addEventListener('click', function() {
            const batchId = this.getAttribute('data-id');
            const dataContainer = document.getElementById(`warnings-data-${batchId}`);
            
            if (dataContainer) {
                const warnings = JSON.parse(dataContainer.textContent);
                drawerContent.innerHTML = '';
                drawerWarnCount.textContent = warnings.length;

                if (warnings.length === 0) {
                    drawerContent.innerHTML = `
                        <div style="text-align: center; color: var(--text-muted); padding: 4rem 1rem; font-size: 0.88rem;">
                            🎉 No validation warnings found in this batch! All rows were 100% compliant.
                        </div>
                    `;
                } else {
                    warnings.forEach(warn => {
                        const card = document.createElement('div');
                        card.className = 'warning-log-card';
                        if (warn.message.includes('Duplicate')) {
                            card.className += ' w-dup';
                        }
                        
                        card.innerHTML = `
                            <div class="w-log-meta">
                                <span>ROW #${warn.row}</span>
                                <span style="color: var(--text-muted);">${warn.message.includes('Duplicate') ? 'DUPLICATE' : 'WARNING'}</span>
                            </div>
                            <div class="w-log-msg">${warn.message}</div>
                        `;
                        drawerContent.appendChild(card);
                    });
                }
                
                drawer.classList.add('open');
                overlay.classList.add('active');
            }
        });
    });

    drawerClose.addEventListener('click', closeDrawer);
    overlay.addEventListener('click', closeDrawer);
</script>
@endsection
