@extends('layouts.app')

@section('title', 'Shipment Ingestion - CRM Reconciliation')

@section('styles')
<style>
    .grid-container {
        display: grid;
        grid-template-columns: 1.15fr 1.85fr;
        gap: 2rem;
        align-items: start;
    }

    @media (max-width: 992px) {
        .grid-container {
            grid-template-columns: 1fr;
        }
    }

    .card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 1.25rem;
        padding: 2rem;
        backdrop-filter: blur(12px);
    }

    .title {
        font-size: 1.3rem;
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

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-bottom: 1.25rem;
    }

    label {
        font-size: 0.82rem;
        color: var(--text-muted);
        font-weight: 500;
    }

    .file-upload-wrapper {
        border: 2px dashed var(--border);
        border-radius: 0.75rem;
        padding: 2.25rem 1.5rem;
        text-align: center;
        position: relative;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
        background-color: rgba(255,255,255,0.01);
    }

    .file-upload-wrapper:hover {
        border-color: var(--accent-cyan);
    }

    .file-upload-wrapper input[type="file"] {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .file-icon {
        color: var(--text-muted);
    }

    .btn-submit {
        background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
        color: #fff;
        border: none;
        width: 100%;
        padding: 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(6, 182, 212, 0.2);
    }

    .btn-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 15px rgba(6, 182, 212, 0.3);
    }

    .alert {
        border-radius: 0.75rem;
        padding: 1rem;
        margin-bottom: 1.5rem;
        font-size: 0.88rem;
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

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.5rem;
        margin-top: 0.75rem;
    }

    .stat-card {
        background-color: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--border);
        border-radius: 0.375rem;
        padding: 0.5rem;
        text-align: center;
    }

    .stat-val {
        font-size: 1.05rem;
        font-weight: 700;
    }

    .stat-label {
        font-size: 0.65rem;
        color: var(--text-muted);
        text-transform: uppercase;
        margin-top: 0.2rem;
    }

    .row-total { color: var(--accent-cyan); }
    .row-inserted { color: var(--success); }
    .row-skipped { color: var(--warning); }
    .row-candidates { color: var(--accent-purple); }

    .history-container {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
    }

    th {
        padding: 0.85rem;
        color: var(--text-muted);
        font-weight: 600;
        border-bottom: 1px solid var(--border);
        font-size: 0.78rem;
        text-transform: uppercase;
    }

    td {
        padding: 1rem 0.85rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    }

    .badge {
        display: inline-block;
        padding: 0.2rem 0.4rem;
        font-size: 0.72rem;
        font-weight: 600;
        border-radius: 0.25rem;
    }

    .badge-success { background-color: rgba(16, 185, 129, 0.15); color: var(--success); }
    .badge-danger { background-color: rgba(239, 68, 68, 0.15); color: var(--error); }
</style>
@endsection

@section('content')
<div class="grid-container">
    <!-- Left Column: Upload Form -->
    <div class="card">
        <h2 class="title">
            <svg class="title-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 104 0"></path></svg>
            Upload Shipment / SJ
        </h2>

        @if (session('success'))
            <div class="alert alert-success">
                <strong>Import Success!</strong>
                @if (session('latest_batch'))
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-val row-total">{{ session('latest_batch')['total_rows'] }}</div>
                            <div class="stat-label">Total</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-val row-inserted">{{ session('latest_batch')['inserted_rows'] }}</div>
                            <div class="stat-label">New SJ</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-val row-skipped">{{ session('latest_batch')['skipped_rows'] }}</div>
                            <div class="stat-label">Skipped</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-val row-candidates">{{ session('latest_batch')['candidates_count'] }}</div>
                            <div class="stat-label">PO Match</div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Error:</strong> {{ $errors->first() }}
            </div>
        @endif

        <div style="background-color: rgba(6, 182, 212, 0.05); border: 1px solid rgba(6, 182, 212, 0.15); border-radius: 0.5rem; padding: 0.85rem; margin-bottom: 1.25rem; font-size: 0.8rem; color: #22d3ee; line-height: 1.5;">
            💡 <strong>Carry-Forward Parsing:</strong><br>
            - A single Product row automatically applies to all detail rows below it.<br>
            - Live allocation targets (POs Match) are computed dynamically in real time.
        </div>

        <form action="{{ route('import.shipment.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label>Pilih File Excel / CSV Hasil Export</label>
                <div class="file-upload-wrapper">
                    <svg class="file-icon" width="36" height="36" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span id="file-label-text">Geser file kesini atau klik untuk memilih</span>
                    <span id="file-name" style="font-weight: 600; color: var(--accent-cyan);"></span>
                    <input type="file" name="file" id="file-input" accept=".xlsx,.xls,.csv" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                Proses Import Shipment
            </button>
        </form>
    </div>

    <!-- Right Column: Batch History -->
    <div class="card">
        <h2 class="title">
            <svg class="title-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Ingestion History (Last 5 Batches)
        </h2>

        <div class="history-container">
            @if ($batches->isEmpty())
                <div style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
                    Belum ada riwayat batch shipment.
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Batch ID</th>
                            <th>Filename</th>
                            <th>Imported At</th>
                            <th style="text-align: center;">Total</th>
                            <th style="text-align: center;">New</th>
                            <th style="text-align: center;">Skipped</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($batches as $batch)
                            <tr>
                                <td><strong>#{{ $batch->id }}</strong></td>
                                <td>{{ $batch->source_filename ?? '-' }}</td>
                                <td>{{ $batch->imported_at ? $batch->imported_at->format('d M Y H:i') : '-' }}</td>
                                <td style="text-align: center;" class="row-total">{{ $batch->total_rows }}</td>
                                <td style="text-align: center;" class="row-inserted">{{ $batch->inserted_rows }}</td>
                                <td style="text-align: center;" class="row-skipped">{{ $batch->skipped_rows }}</td>
                                <td>
                                    <span class="badge {{ str_contains($batch->notes ?? '', 'Failed') ? 'badge-danger' : 'badge-success' }}">
                                        {{ str_contains($batch->notes ?? '', 'Failed') ? 'Failed' : 'Success' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const fileInput = document.getElementById('file-input');
    const fileNameSpan = document.getElementById('file-name');
    const labelText = document.getElementById('file-label-text');

    fileInput.addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            fileNameSpan.textContent = e.target.files[0].name;
            labelText.style.display = 'none';
        } else {
            fileNameSpan.textContent = '';
            labelText.style.display = 'block';
        }
    });
</script>
@endsection
