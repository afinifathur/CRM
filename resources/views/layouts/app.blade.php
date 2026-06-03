<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'CRM Overlay - operational Intelligence')</title>
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
            --sidebar-width: 260px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(59, 130, 246, 0.04) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(139, 92, 246, 0.04) 0%, transparent 40%);
        }

        /* Sidebar styling */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--bg-secondary);
            border-right: 1px solid var(--border);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
            z-index: 100;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2.5rem;
            text-decoration: none;
        }

        .logo-icon {
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.5rem;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 700;
            font-size: 1.1rem;
            color: #fff;
            box-shadow: 0 0 15px rgba(6, 182, 212, 0.4);
        }

        .logo-text {
            font-size: 1.25rem;
            font-weight: 700;
            background: linear-gradient(to right, #fff, var(--text-muted));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-section-title {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }

        .nav-list {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            list-style: none;
        }

        .nav-item-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-muted);
            text-decoration: none;
            padding: 0.65rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-item-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.03);
        }

        .nav-active {
            color: #fff;
            background-color: rgba(6, 182, 212, 0.1) !important;
            border-left: 3px solid var(--accent-cyan);
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            font-weight: 600;
        }

        .nav-icon {
            width: 1.25rem;
            height: 1.25rem;
            flex-shrink: 0;
        }

        /* Content area adjustments */
        .wrapper {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Top Navbar */
        .top-navbar {
            height: 70px;
            background-color: rgba(11, 15, 25, 0.5);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .page-title {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .indicators {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .indicator-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.82rem;
            color: var(--text-muted);
            background-color: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border);
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
        }

        .ind-pill {
            font-weight: 700;
            padding: 0.15rem 0.45rem;
            border-radius: 9999px;
            font-size: 0.75rem;
        }

        .ind-cyan { color: var(--accent-cyan); background-color: rgba(6, 182, 212, 0.1); border: 1px solid rgba(6, 182, 212, 0.2); }
        .ind-blue { color: var(--accent-blue); background-color: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); }
        .ind-red { color: var(--error); background-color: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); animation: pulseSoft 2s infinite; }

        @keyframes pulseSoft {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.3); }
            70% { box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        /* Dynamic view content body */
        .content-body {
            padding: 2rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 1250px;
            margin: 0 auto;
        }
    </style>
    @yield('styles')
</head>
<body>

    <!-- Left Sidebar -->
    <aside class="sidebar">
        <a href="{{ route('home') }}" class="logo-container">
            <div class="logo-icon">C</div>
            <h1 class="logo-text">CRM Overlay</h1>
        </a>

        <div class="nav-section-title">Home</div>
        <ul class="nav-list">
            <li>
                <a href="{{ route('home') }}" class="nav-item-link @if(Route::is('home')) nav-active @endif">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Home Dashboard
                </a>
            </li>
        </ul>

        <div class="nav-section-title">Imports</div>
        <ul class="nav-list">
            <li>
                <a href="{{ route('import.stock.index') }}" class="nav-item-link @if(Route::is('import.stock.*')) nav-active @endif">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    Stock Ingestion
                </a>
            </li>
            <li>
                <a href="{{ route('import.po.index') }}" class="nav-item-link @if(Route::is('import.po.*')) nav-active @endif">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    PO Ingestion
                </a>
            </li>
            <li>
                <a href="{{ route('import.shipment.index') }}" class="nav-item-link @if(Route::is('import.shipment.*')) nav-active @endif">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6 0a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-3a2 2 0 00-2-2H9a2 2 0 00-2 2v3a2 2 0 002 2z"></path></svg>
                    Shipment Ingestion
                </a>
            </li>
            <li>
                <a href="{{ route('imports.history') }}" class="nav-item-link @if(Route::is('imports.history*')) nav-active @endif">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Import History
                </a>
            </li>
        </ul>

        <div class="nav-section-title">Operations</div>
        <ul class="nav-list">
            <li>
                <a href="{{ route('allocations.index') }}" class="nav-item-link @if(Route::is('allocations.*')) nav-active @endif">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    Allocation Review
                </a>
            </li>
            <li>
                <a href="{{ route('dashboard.outstanding') }}" class="nav-item-link @if(Route::is('dashboard.outstanding')) nav-active @endif">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    Outstanding PO
                </a>
            </li>
            <li>
                <a href="{{ route('dashboard.freestock') }}" class="nav-item-link @if(Route::is('dashboard.freestock')) nav-active @endif">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    Free Stock Monitor
                </a>
            </li>
        </ul>
    </aside>

    <!-- Content Wrapper -->
    <div class="wrapper">
        <!-- Top Navbar -->
        <nav class="top-navbar">
            <div class="page-title">
                CRM Reconciliation System Overlay
            </div>
            
            <!-- Dynamic Layout Counts from View Composer -->
            <div class="indicators">
                <div class="indicator-item">
                    Pending Allocations
                    <span class="ind-pill ind-cyan">{{ $pendingCount }}</span>
                </div>
                <div class="indicator-item">
                    Outstanding PO Items
                    <span class="ind-pill ind-blue">{{ $outstandingCount }}</span>
                </div>
                <div class="indicator-item">
                    Shortages (Negative Free)
                    <span class="ind-pill ind-red">{{ $negativeCount }}</span>
                </div>
            </div>
        </nav>

        <!-- Main Body -->
        <main class="content-body">
            @yield('content')
        </main>
    </div>

    @yield('scripts')
</body>
</html>
