<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Allocation Review Queue - CRM Reconciliation</title>
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
            background: rgba(139, 92, 246, 0.15);
            border: 1px solid rgba(139, 92, 246, 0.3);
            color: var(--accent-purple);
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
            display: flex;
            flex-direction: column;
            gap: 2.5rem;
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 1.25rem;
            padding: 2rem;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
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

        /* Review Grid styling */
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

        /* Glowing Confidence Badges */
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

        /* Details info display */
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

        /* Dropdown Selection style */
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
            transition: all 0.3s ease;
            appearance: none;
            -webkit-appearance: none;
        }

        select:focus {
            border-color: var(--accent-cyan);
            box-shadow: 0 0 8px rgba(6, 182, 212, 0.3);
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

        /* Action Buttons */
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
            transition: all 0.3s ease;
        }

        .btn-approve {
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
            color: #fff;
            box-shadow: 0 4px 10px rgba(6, 182, 212, 0.2);
        }

        .btn-approve:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(6, 182, 212, 0.3);
        }

        .btn-reset {
            background-color: rgba(239, 68, 68, 0.12);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .btn-reset:hover {
            background-color: rgba(239, 68, 68, 0.2);
            transform: translateY(-1px);
        }

        .empty-queue {
            text-align: center;
            color: var(--text-muted);
            padding: 4rem 2rem;
            font-size: 1rem;
        }

        .empty-icon {
            font-size: 3rem;
            color: var(--border);
            margin-bottom: 1rem;
            display: block;
        }

        .menu-links {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .menu-link {
            font-size: 0.9rem;
            color: var(--accent-cyan);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-weight: 500;
        }

        .menu-link:hover {
            text-decoration: underline;
        }

        .pill-tag {
            background-color: rgba(255,255,255,0.05);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.8rem;
            border: 1px solid var(--border);
        }
    </style>
</head>
<body>

    <header>
        <div class="logo-container">
            <div class="logo-icon">C</div>
            <h1 class="logo-text">CRM Overlay</h1>
        </div>
        <div class="badge-pill">Allocation Engine</div>
    </header>

    <main>
        <!-- Top Navigation Links -->
        <div class="menu-links">
            <a href="{{ route('import.shipment.index') }}" class="menu-link">&larr; Ke Import Shipment</a>
            <a href="{{ route('import.po.index') }}" class="menu-link">&larr; Ke Import Outstanding PO</a>
            <a href="{{ route('import.stock.index') }}" class="menu-link">&larr; Ke Import Stock</a>
        </div>

        <!-- Section 1: Manual Review Queue -->
        <div class="card" style="margin-bottom: 2.5rem;">
            <h2 class="title">
                <svg class="title-icon" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                Manual Allocation Review Queue (Pending Review)
            </h2>

            <!-- Feedback Alerts -->
            @if (session('success'))
                <div class="alert alert-success">
                    <div class="alert-title">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Success
                    </div>
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <div class="alert-title">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Error
                    </div>
                    <p>{{ $errors->first() }}</p>
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
                                    <!-- SJ Details -->
                                    <td>
                                        <span class="info-cell-bold">{{ $line->shipment->sj_number }}</span>
                                        <span class="info-cell-sub">Date: {{ $line->shipment->shipment_date ? \Carbon\Carbon::parse($line->shipment->shipment_date)->format('d M Y') : '-' }}</span>
                                    </td>
                                    <!-- Customer & Product -->
                                    <td>
                                        <span class="info-cell-bold">{{ $line->shipment->customer->customer_name }}</span>
                                        <span class="info-cell-sub">Product: <span style="color: var(--accent-cyan);">{{ $line->product->product_code }}</span> | {{ $line->product->product_name }}</span>
                                    </td>
                                    <!-- Quantity Shipped -->
                                    <td style="text-align: center;">
                                        <span class="pill-tag" style="color: #fff; font-weight: 700;">{{ number_format($line->shipped_qty, 0) }} PCS</span>
                                    </td>
                                    <!-- Confidence dynamic badge -->
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
                                    <!-- Dynamic Selection Dropdown -->
                                    <td>
                                        @if ($sugg && !empty($sugg['candidates']))
                                            <form id="approve-form-{{ $line->id }}" action="{{ route('allocations.approve', $line->id) }}" method="POST">
                                                @csrf
                                                <div class="select-wrapper">
                                                    <select name="sales_order_line_id" onchange="updateSoDetails({{ $line->id }}, this)">
                                                        @foreach ($sugg['candidates'] as $cand)
                                                            <option value="{{ $cand->id }}" 
                                                                data-outstanding="{{ number_format($cand->outstanding_qty, 0) }}"
                                                                data-so="{{ $cand->salesOrder->so_number }}"
                                                                @selected($cand->id == $selectedSoLineId)>
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
                                    <!-- Approve Button -->
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
                <svg class="title-icon" style="color: var(--success);" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
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
    </main>

    <script>
        // Place for dynamic scripts if required in future iterations.
        function updateSoDetails(lineId, selectEl) {
            // Optional UI details feedback when select updates
        }
    </script>
</body>
</html>
