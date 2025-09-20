@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="breadcrumb-item active">Dashboard</a>
@endsection

@section('content')
    <!-- Verification Notice -->
    @if (request()->get('verified'))
        <div class="notification success">
            ✅ Your email has been verified successfully!
        </div>
    @elseif (!$user->hasVerifiedEmail())
        <div class="notification warning">
            📧 Please verify your email address. <a href="{{ route('verification.notice') }}" style="color: inherit; text-decoration: underline;">Click here to resend verification email</a>.
        </div>
    @endif

    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">Welcome to {{ config('app.name', 'EduVoltV2') }}</h1>
        <p class="page-subtitle">
            Logged in as: {{ $user->name }} ({{ $user->email }})
            @if ($user->email_verified_at)
                • Email verified ✅
            @else
                • Email not verified ❌
            @endif
        </p>
    </div>

    <!-- Dashboard Overview -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <!-- Quick Stats Cards -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Students</h3>
            </div>
            <div class="card-body">
                <div style="font-size: 2rem; font-weight: 600; color: #3b82f6; margin-bottom: 0.5rem;">0</div>
                <p style="color: #6b7280; margin: 0;">Total enrolled students</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Teachers</h3>
            </div>
            <div class="card-body">
                <div style="font-size: 2rem; font-weight: 600; color: #10b981; margin-bottom: 0.5rem;">0</div>
                <p style="color: #6b7280; margin: 0;">Active teaching staff</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Classes</h3>
            </div>
            <div class="card-body">
                <div style="font-size: 2rem; font-weight: 600; color: #f59e0b; margin-bottom: 0.5rem;">0</div>
                <p style="color: #6b7280; margin: 0;">Active classes</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Courses</h3>
            </div>
            <div class="card-body">
                <div style="font-size: 2rem; font-weight: 600; color: #ef4444; margin-bottom: 0.5rem;">0</div>
                <p style="color: #6b7280; margin: 0;">Available courses</p>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Content -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem;">
        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Activity</h3>
            </div>
            <div class="card-body">
                <div style="text-align: center; padding: 2rem; color: #6b7280;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
                    <p>No recent activity to display.</p>
                    <p style="font-size: 0.875rem;">Activities will appear here as users interact with the system.</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-body">
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <a href="#" style="display: flex; align-items: center; padding: 0.75rem; background: #f3f4f6; border-radius: 0.375rem; text-decoration: none; color: #374151; transition: background-color 0.2s;">
                        <span style="margin-right: 0.75rem;">👥</span>
                        Add New Student
                    </a>
                    <a href="#" style="display: flex; align-items: center; padding: 0.75rem; background: #f3f4f6; border-radius: 0.375rem; text-decoration: none; color: #374151; transition: background-color 0.2s;">
                        <span style="margin-right: 0.75rem;">👨‍🏫</span>
                        Add New Teacher
                    </a>
                    <a href="#" style="display: flex; align-items: center; padding: 0.75rem; background: #f3f4f6; border-radius: 0.375rem; text-decoration: none; color: #374151; transition: background-color 0.2s;">
                        <span style="margin-right: 0.75rem;">🏛️</span>
                        Create New Class
                    </a>
                    <a href="#" style="display: flex; align-items: center; padding: 0.75rem; background: #f3f4f6; border-radius: 0.375rem; text-decoration: none; color: #374151; transition: background-color 0.2s;">
                        <span style="margin-right: 0.75rem;">📚</span>
                        Add New Course
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Account Information -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Account Information</h3>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <div>
                    <h4 style="margin: 0 0 0.5rem 0; color: #374151;">Profile Details</h4>
                    <ul style="color: #6b7280; line-height: 1.6; margin: 0; padding-left: 1.25rem;">
                        <li><strong>Name:</strong> {{ $user->name }}</li>
                        <li><strong>Email:</strong> {{ $user->email }}</li>
                        <li><strong>Member since:</strong> {{ $user->created_at->format('F j, Y') }}</li>
                        @if ($user->email_verified_at)
                            <li><strong>Email verified:</strong> {{ $user->email_verified_at->format('F j, Y g:i A') }}</li>
                        @endif
                    </ul>
                </div>
                
                <div>
                    <h4 style="margin: 0 0 0.5rem 0; color: #374151;">System Status</h4>
                    <ul style="color: #6b7280; line-height: 1.6; margin: 0; padding-left: 1.25rem;">
                        @if ($user->email_verified_at)
                            <li style="color: #059669;">🎉 Account is fully set up and ready to use</li>
                        @else
                            <li style="color: #dc2626;">⚠️ Email verification required</li>
                        @endif
                        <li>✅ Authentication system active</li>
                        <li>✅ Dashboard access granted</li>
                        <li>✅ Multi-tenant support enabled</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('right-sidebar')
    <!-- System Information -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <h3 class="card-title">System Info</h3>
        </div>
        <div class="card-body">
            <div style="font-size: 0.875rem; color: #6b7280; line-height: 1.6;">
                <div style="margin-bottom: 0.75rem;">
                    <strong>Platform:</strong> EduVoltV2
                </div>
                <div style="margin-bottom: 0.75rem;">
                    <strong>Version:</strong> 2.0.0
                </div>
                <div style="margin-bottom: 0.75rem;">
                    <strong>Environment:</strong> {{ app()->environment() }}
                </div>
                <div>
                    <strong>Laravel:</strong> {{ app()->version() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Updates -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Updates</h3>
        </div>
        <div class="card-body">
            <div style="font-size: 0.875rem; color: #6b7280; line-height: 1.6;">
                <div style="padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb;">
                    <div style="font-weight: 500; color: #374151;">Dashboard Layout</div>
                    <div style="font-size: 0.75rem;">3-column admin interface implemented</div>
                </div>
                <div style="padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb;">
                    <div style="font-weight: 500; color: #374151;">Authentication</div>
                    <div style="font-size: 0.75rem;">Login and auth issues resolved</div>
                </div>
                <div style="padding: 0.75rem 0;">
                    <div style="font-weight: 500; color: #374151;">RBAC System</div>
                    <div style="font-size: 0.75rem;">Role-based access control active</div>
                </div>
            </div>
        </div>
    </div>
@endsection