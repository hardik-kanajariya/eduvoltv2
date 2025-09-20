<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'EduVoltV2') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            * {
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                background-color: #f8fafc;
                margin: 0;
                padding: 0;
                line-height: 1.6;
            }

            /* Dashboard Layout */
            .dashboard-container {
                display: flex;
                min-height: 100vh;
                background-color: #f8fafc;
            }

            /* Sidebar Styles */
            .sidebar {
                width: 280px;
                background: #1f2937;
                color: white;
                position: fixed;
                height: 100vh;
                overflow-y: auto;
                z-index: 1000;
                transition: transform 0.3s ease;
            }

            .sidebar-header {
                padding: 1.5rem;
                border-bottom: 1px solid #374151;
                text-align: center;
            }

            .sidebar-logo {
                font-size: 1.5rem;
                font-weight: 600;
                color: #3b82f6;
                text-decoration: none;
            }

            .sidebar-nav {
                padding: 1rem 0;
            }

            .nav-section {
                margin-bottom: 2rem;
            }

            .nav-section-title {
                padding: 0.5rem 1.5rem;
                font-size: 0.75rem;
                font-weight: 600;
                color: #9ca3af;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }

            .nav-item {
                display: block;
                padding: 0.75rem 1.5rem;
                color: #d1d5db;
                text-decoration: none;
                transition: all 0.2s ease;
                border-left: 3px solid transparent;
            }

            .nav-item:hover {
                background-color: #374151;
                color: white;
                border-left-color: #3b82f6;
            }

            .nav-item.active {
                background-color: #1e40af;
                color: white;
                border-left-color: #60a5fa;
            }

            .nav-icon {
                display: inline-block;
                width: 1.25rem;
                margin-right: 0.75rem;
                text-align: center;
            }

            /* Main Content Area */
            .main-content {
                flex: 1;
                margin-left: 280px;
                display: flex;
                flex-direction: column;
                min-height: 100vh;
            }

            /* Top Navigation */
            .top-nav {
                background: white;
                border-bottom: 1px solid #e5e7eb;
                padding: 0 2rem;
                height: 4rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
                position: sticky;
                top: 0;
                z-index: 100;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }

            .breadcrumb {
                display: flex;
                align-items: center;
                color: #6b7280;
                font-size: 0.875rem;
            }

            .breadcrumb-item {
                color: #6b7280;
                text-decoration: none;
            }

            .breadcrumb-item.active {
                color: #1f2937;
                font-weight: 500;
            }

            .breadcrumb-separator {
                margin: 0 0.5rem;
                color: #d1d5db;
            }

            .top-nav-actions {
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            /* User Dropdown */
            .user-menu {
                position: relative;
                display: inline-block;
            }

            .user-menu-trigger {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.5rem;
                border-radius: 0.5rem;
                cursor: pointer;
                transition: background-color 0.2s ease;
            }

            .user-menu-trigger:hover {
                background-color: #f3f4f6;
            }

            .user-avatar {
                width: 2rem;
                height: 2rem;
                border-radius: 50%;
                background: #3b82f6;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: 600;
                font-size: 0.875rem;
            }

            .user-info {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
            }

            .user-name {
                font-weight: 500;
                color: #1f2937;
                font-size: 0.875rem;
            }

            .user-role {
                font-size: 0.75rem;
                color: #6b7280;
            }

            /* Content Area */
            .content-wrapper {
                flex: 1;
                display: flex;
                background-color: #f8fafc;
            }

            .main-section {
                flex: 1;
                padding: 2rem;
            }

            .right-sidebar {
                width: 320px;
                background: white;
                border-left: 1px solid #e5e7eb;
                padding: 2rem;
                display: none; /* Hidden by default, shown when needed */
            }

            .right-sidebar.visible {
                display: block;
            }

            /* Page Header */
            .page-header {
                margin-bottom: 2rem;
            }

            .page-title {
                font-size: 1.875rem;
                font-weight: 600;
                color: #1f2937;
                margin: 0;
            }

            .page-subtitle {
                color: #6b7280;
                margin-top: 0.25rem;
                font-size: 0.875rem;
            }

            /* Cards */
            .card {
                background: white;
                border-radius: 0.5rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                border: 1px solid #e5e7eb;
                overflow: hidden;
            }

            .card-header {
                padding: 1.5rem;
                border-bottom: 1px solid #e5e7eb;
                background: #f9fafb;
            }

            .card-title {
                font-size: 1.125rem;
                font-weight: 600;
                color: #1f2937;
                margin: 0;
            }

            .card-body {
                padding: 1.5rem;
            }

            /* Mobile Sidebar Overlay */
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
                display: none;
            }

            .sidebar-overlay.active {
                display: block;
            }

            /* Mobile Responsive */
            @media (max-width: 768px) {
                .sidebar {
                    transform: translateX(-100%);
                }

                .sidebar.mobile-open {
                    transform: translateX(0);
                }

                .main-content {
                    margin-left: 0;
                }

                .right-sidebar {
                    display: none !important;
                }

                .main-section {
                    padding: 1rem;
                }

                .top-nav {
                    padding: 0 1rem;
                }
            }

            /* Mobile Menu Toggle */
            .mobile-menu-toggle {
                display: none;
                background: none;
                border: none;
                color: #6b7280;
                cursor: pointer;
                font-size: 1.25rem;
                padding: 0.5rem;
            }

            @media (max-width: 768px) {
                .mobile-menu-toggle {
                    display: block;
                }
            }

            /* Notifications */
            .notification {
                padding: 1rem 1.5rem;
                border-radius: 0.5rem;
                margin-bottom: 1rem;
                font-size: 0.875rem;
            }

            .notification.success {
                background-color: #dcfce7;
                border: 1px solid #bbf7d0;
                color: #166534;
            }

            .notification.warning {
                background-color: #fef3c7;
                border: 1px solid #fde68a;
                color: #92400e;
            }

            .notification.error {
                background-color: #fee2e2;
                border: 1px solid #fecaca;
                color: #991b1b;
            }
        </style>
    @endif

    @stack('styles')
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="{{ route('dashboard') }}" class="sidebar-logo">
                    {{ config('app.name', 'EduVoltV2') }}
                </a>
            </div>

            <div class="sidebar-nav">
                <!-- Main Navigation -->
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <span class="nav-icon">🏠</span>
                        Dashboard
                    </a>
                </div>

                <!-- Academic Management -->
                <div class="nav-section">
                    <div class="nav-section-title">Academic</div>
                    <a href="#" class="nav-item">
                        <span class="nav-icon">👥</span>
                        Students
                    </a>
                    <a href="#" class="nav-item">
                        <span class="nav-icon">👨‍🏫</span>
                        Teachers
                    </a>
                    <a href="#" class="nav-item">
                        <span class="nav-icon">📚</span>
                        Courses
                    </a>
                    <a href="#" class="nav-item">
                        <span class="nav-icon">🏛️</span>
                        Classes
                    </a>
                </div>

                <!-- Operations -->
                <div class="nav-section">
                    <div class="nav-section-title">Operations</div>
                    <a href="#" class="nav-item">
                        <span class="nav-icon">📅</span>
                        Timetable
                    </a>
                    <a href="#" class="nav-item">
                        <span class="nav-icon">✅</span>
                        Attendance
                    </a>
                    <a href="#" class="nav-item">
                        <span class="nav-icon">💰</span>
                        Fees
                    </a>
                    <a href="#" class="nav-item">
                        <span class="nav-icon">📊</span>
                        Reports
                    </a>
                </div>

                <!-- Settings -->
                <div class="nav-section">
                    <div class="nav-section-title">Settings</div>
                    <a href="#" class="nav-item">
                        <span class="nav-icon">👤</span>
                        Users
                    </a>
                    <a href="#" class="nav-item">
                        <span class="nav-icon">🔒</span>
                        Roles & Permissions
                    </a>
                    <a href="#" class="nav-item">
                        <span class="nav-icon">⚙️</span>
                        System Settings
                    </a>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navigation -->
            <header class="top-nav">
                <div class="breadcrumb">
                    <button class="mobile-menu-toggle" onclick="toggleSidebar()">☰</button>
                    @yield('breadcrumb')
                </div>

                <div class="top-nav-actions">
                    <!-- Notifications -->
                    @if (!auth()->user()->hasVerifiedEmail())
                        <div style="padding: 0.5rem 1rem; background: #fef3c7; color: #92400e; border-radius: 0.375rem; font-size: 0.875rem;">
                            📧 Email verification required
                        </div>
                    @endif

                    <!-- User Menu -->
                    <div class="user-menu">
                        <div class="user-menu-trigger">
                            <div class="user-avatar">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="user-info">
                                <div class="user-name">{{ auth()->user()->name }}</div>
                                <div class="user-role">Administrator</div>
                            </div>
                        </div>
                    </div>

                    <!-- Logout Button -->
                    <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                        @csrf
                        <button type="submit" style="background: #dc2626; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem;">
                            Logout
                        </button>
                    </form>
                </div>
            </header>

            <!-- Content Wrapper -->
            <div class="content-wrapper">
                <!-- Main Content Area -->
                <main class="main-section">
                    @yield('content')
                </main>

                <!-- Right Sidebar (Optional) -->
                @hasSection('right-sidebar')
                    <aside class="right-sidebar visible">
                        @yield('right-sidebar')
                    </aside>
                @endif
            </div>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        }

        // Close sidebar when clicking on a link (mobile)
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-item');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        toggleSidebar();
                    }
                });
            });
        });
    </script>

    @stack('scripts')
</body>
</html>