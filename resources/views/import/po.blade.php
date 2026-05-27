<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outstanding PO Import - CRM Reconciliation</title>
    <!-- Modern Premium Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-primary: #0b0f19;
            --bg-secondary: #131a26;
            --bg-card: rgba(20, 28, 45, 0.7);
            --accent-cyan: #06b6d4;
            --accent-blue: #3b82f6;
            --accent-purple: #8b5cf6;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --border: rgba(255, 255, 255, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
            scroll-behavior: smooth;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(59, 130, 246, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(139, 92, 246, 0.08) 0%, transparent 40%);
        }

        header {
            width: 100%;
            max-width: 1200px;
            padding: 2.5rem 1.5rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-icon {
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.75rem;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 700;
            font-size: 1.25rem;
            color: #fff;
            box-shadow: 0 0 15px rgba(6, 182, 212, 0.4);
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(to right, #fff, var(--text-muted));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .badge-pill {
            background: rgba(6, 182, 212, 0.15);
            border: 1px solid rgba(6, 182, 212, 0.3);
            color: var(--accent-cyan);
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        main {
            width: 100%;
            max-width: 1200px;
            padding: 0 1.5rem 3.5rem;
            display: grid;
            grid-template-columns: 1.2fr 1.8fr;
            gap: 2.5rem;
        }

        @media (max-width: 900px) {
            main {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 1.25rem;
            padding: 2rem;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        .title {
            font-size: 1.25rem;
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

        /* Forms Styling */
        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        /* File Upload area */
        .file-upload-wrapper {
            position: relative;
            width: 100%;
            height: 150px;
            background-color: rgba(19, 26, 38, 0.5);
            border: 2px dashed var(--border);
            border-radius: 0.75rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            gap: 0.5rem;
        }

        .file-upload-wrapper:hover {
            border-color: var(--accent-cyan);
            background-color: rgba(6, 182, 212, 0.03);
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
            font-size: 2.25rem;
            color: var(--text-muted);
            transition: color 0.3s ease;
        }

        .file-upload-wrapper:hover .file-icon {
            color: var(--accent-cyan);
        }

        .file-name-display {
            font-size: 0.85rem;
            color: var(--accent-cyan);
            font-weight: 500;
            text-align: center;
            max-width: 90%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
            color: #fff;
            border: none;
            padding: 1rem;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(6, 182, 212, 0.3);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(6, 182, 212, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Notifications & Alerts */
        .alert {
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            line-height: 1.5;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            animation: slideDown 0.3s ease;
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

        .alert-title {
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 0.75rem;
        }

        .stat-card {
            background-color: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            padding: 0.75rem;
            text-align: center;
        }

        .stat-val {
            font-size: 1.3rem;
            font-weight: 700;
            color: #fff;
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-top: 0.25rem;
        }

        /* History Table Styling */
        .history-container {
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
        }

        tr:hover td {
            background-color: rgba(255, 255, 255, 0.01);
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 0.25rem;
        }

        .badge-success {
            background-color: rgba(16, 185, 129, 0.15);
            color: var(--success);
        }

        .badge-danger {
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--error);
        }

        .row-total { color: var(--accent-cyan); font-weight: 600; }
        .row-inserted { color: var(--success); font-weight: 600; }
        .row-skipped { color: var(--warning); font-weight: 600; }

        .empty-history {
            text-align: center;
            color: var(--text-muted);
            padding: 3rem 1rem;
        }

        /* Keyframes */
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .info-panel {
            background-color: rgba(139, 92, 246, 0.05);
            border: 1px solid rgba(139, 92, 246, 0.15);
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            color: #c084fc;
            line-height: 1.5;
        }

        .menu-link {
            font-size: 0.9rem;
            color: var(--accent-cyan);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-weight: 500;
            margin-top: 1rem;
        }

        .menu-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <header>
        <div class="logo-container">
            <div class="logo-icon">C</div>
            <h1 class="logo-text">CRM Overlay</h1>
        </div>
        <div class="badge-pill">Reconciliation System</div>
    </header>

    <main>
        <!-- Left Column: Upload Form -->
        <section>
            <div class="card">
                <h2 class="title">
                    <svg class="title-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    Upload Outstanding PO
                </h2>

                <!-- Status Feedback Alerts -->
                @if (session('success'))
                    <div class="alert alert-success">
                        <div class="alert-title">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            {{ session('success') }}
                        </div>
                        @if (session('latest_batch'))
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-val row-total">{{ session('latest_batch')->total_rows }}</div>
                                    <div class="stat-label">Total Items</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-val row-inserted">{{ session('latest_batch')->inserted_rows }}</div>
                                    <div class="stat-label">New/Updated</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-val row-skipped">{{ session('latest_batch')->skipped_rows }}</div>
                                    <div class="stat-label">Skipped*</div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <div class="alert-title">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Import Error
                        </div>
                        <p>{{ $errors->first() }}</p>
                    </div>
                @endif

                <div class="info-panel">
                    📋 <strong>PO Snapshot Rules:</strong><br>
                    - Customer Code & Product Code are unique identifiers.<br>
                    - Same customer can order the same product multiple times.<br>
                    - *Lines with existing shipments allocated in CRM are skipped to protect local ledger.
                </div>

                <form action="{{ route('import.po.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- File Drop Area -->
                    <div class="form-group">
                        <label>Pilih File Excel / CSV Hasil Export</label>
                        <div class="file-upload-wrapper" id="drop-area">
                            <svg class="file-icon" width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span id="file-label-text">Geser file kesini atau klik untuk browse</span>
                            <span class="file-name-display" id="file-name"></span>
                            <input type="file" name="file" id="file-input" accept=".xlsx,.xls,.csv" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        Proses Import PO
                    </button>
                </form>

                <a href="{{ route('import.stock.index') }}" class="menu-link">
                    &larr; Ke Import Stock Snapshot
                </a>
            </div>
        </section>

        <!-- Right Column: Batch History -->
        <section>
            <div class="card">
                <h2 class="title">
                    <svg class="title-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Import Batches History (Last 5)
                </h2>

                <div class="history-container">
                    @if ($batches->isEmpty())
                        <div class="empty-history">
                            Belum ada riwayat batch outstanding PO.
                        </div>
                    @else
                        <table>
                            <thead>
                                <tr>
                                    <th>Batch ID</th>
                                    <th>File Name</th>
                                    <th>Imported At</th>
                                    <th style="text-align: center;">Total</th>
                                    <th style="text-align: center;">Processed</th>
                                    <th style="text-align: center;">Skipped</th>
                                    <th>Notes</th>
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
                                            @if (str_contains($batch->notes ?? '', 'Failed'))
                                                <span class="badge badge-danger">Failed</span>
                                            @else
                                                <span class="badge badge-success">Success</span>
                                            @endif
                                            <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-top: 0.25rem;">
                                                {{ $batch->notes }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </section>
    </main>

    <script>
        const fileInput = document.getElementById('file-input');
        const fileNameSpan = document.getElementById('file-name');
        const labelText = document.getElementById('file-label-text');

        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const name = e.target.files[0].name;
                fileNameSpan.textContent = name;
                labelText.style.display = 'none';
            } else {
                fileNameSpan.textContent = '';
                labelText.style.display = 'block';
            }
        });
    </script>
</body>
</html>
