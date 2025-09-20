@extends('layouts.dashboard')

@section('title', 'Students')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="breadcrumb-item">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span class="breadcrumb-item active">Students</span>
@endsection

@section('content')
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">Students Management</h1>
        <p class="page-subtitle">Manage student enrollment, profiles, and academic records</p>
    </div>

    <!-- Search and Actions -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-body">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px;">
                    <input type="text" placeholder="Search students..." style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;">
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <button style="background: #3b82f6; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; font-weight: 500;">
                        + Add Student
                    </button>
                    <button style="background: #6b7280; color: white; border: none; padding: 0.75rem 1rem; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem;">
                        Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Students Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">All Students</h3>
        </div>
        <div class="card-body">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <th style="text-align: left; padding: 0.75rem; color: #374151; font-weight: 600;">Student ID</th>
                            <th style="text-align: left; padding: 0.75rem; color: #374151; font-weight: 600;">Name</th>
                            <th style="text-align: left; padding: 0.75rem; color: #374151; font-weight: 600;">Class</th>
                            <th style="text-align: left; padding: 0.75rem; color: #374151; font-weight: 600;">Email</th>
                            <th style="text-align: left; padding: 0.75rem; color: #374151; font-weight: 600;">Status</th>
                            <th style="text-align: left; padding: 0.75rem; color: #374151; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Sample Data -->
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 1rem 0.75rem; color: #6b7280;">#STU001</td>
                            <td style="padding: 1rem 0.75rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #e0e7ff; display: flex; align-items: center; justify-content: center; color: #3730a3; font-weight: 600; font-size: 0.875rem;">
                                        JS
                                    </div>
                                    <div>
                                        <div style="font-weight: 500; color: #1f2937;">John Smith</div>
                                        <div style="font-size: 0.875rem; color: #6b7280;">Grade 10</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 1rem 0.75rem; color: #6b7280;">10-A</td>
                            <td style="padding: 1rem 0.75rem; color: #6b7280;">john.smith@example.com</td>
                            <td style="padding: 1rem 0.75rem;">
                                <span style="background: #dcfce7; color: #166534; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500;">Active</span>
                            </td>
                            <td style="padding: 1rem 0.75rem;">
                                <div style="display: flex; gap: 0.5rem;">
                                    <button style="background: #3b82f6; color: white; border: none; padding: 0.375rem 0.75rem; border-radius: 0.25rem; cursor: pointer; font-size: 0.75rem;">
                                        View
                                    </button>
                                    <button style="background: #6b7280; color: white; border: none; padding: 0.375rem 0.75rem; border-radius: 0.25rem; cursor: pointer; font-size: 0.75rem;">
                                        Edit
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 1rem 0.75rem; color: #6b7280;">#STU002</td>
                            <td style="padding: 1rem 0.75rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #fecaca; display: flex; align-items: center; justify-content: center; color: #991b1b; font-weight: 600; font-size: 0.875rem;">
                                        ED
                                    </div>
                                    <div>
                                        <div style="font-weight: 500; color: #1f2937;">Emily Davis</div>
                                        <div style="font-size: 0.875rem; color: #6b7280;">Grade 9</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 1rem 0.75rem; color: #6b7280;">9-B</td>
                            <td style="padding: 1rem 0.75rem; color: #6b7280;">emily.davis@example.com</td>
                            <td style="padding: 1rem 0.75rem;">
                                <span style="background: #dcfce7; color: #166534; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500;">Active</span>
                            </td>
                            <td style="padding: 1rem 0.75rem;">
                                <div style="display: flex; gap: 0.5rem;">
                                    <button style="background: #3b82f6; color: white; border: none; padding: 0.375rem 0.75rem; border-radius: 0.25rem; cursor: pointer; font-size: 0.75rem;">
                                        View
                                    </button>
                                    <button style="background: #6b7280; color: white; border: none; padding: 0.375rem 0.75rem; border-radius: 0.25rem; cursor: pointer; font-size: 0.75rem;">
                                        Edit
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem 0.75rem; color: #6b7280;">#STU003</td>
                            <td style="padding: 1rem 0.75rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #d1fae5; display: flex; align-items: center; justify-content: center; color: #065f46; font-weight: 600; font-size: 0.875rem;">
                                        MW
                                    </div>
                                    <div>
                                        <div style="font-weight: 500; color: #1f2937;">Michael Wilson</div>
                                        <div style="font-size: 0.875rem; color: #6b7280;">Grade 11</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 1rem 0.75rem; color: #6b7280;">11-A</td>
                            <td style="padding: 1rem 0.75rem; color: #6b7280;">michael.wilson@example.com</td>
                            <td style="padding: 1rem 0.75rem;">
                                <span style="background: #fef3c7; color: #92400e; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500;">Pending</span>
                            </td>
                            <td style="padding: 1rem 0.75rem;">
                                <div style="display: flex; gap: 0.5rem;">
                                    <button style="background: #3b82f6; color: white; border: none; padding: 0.375rem 0.75rem; border-radius: 0.25rem; cursor: pointer; font-size: 0.75rem;">
                                        View
                                    </button>
                                    <button style="background: #6b7280; color: white; border: none; padding: 0.375rem 0.75rem; border-radius: 0.25rem; cursor: pointer; font-size: 0.75rem;">
                                        Edit
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('right-sidebar')
    <!-- Student Statistics -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <h3 class="card-title">Statistics</h3>
        </div>
        <div class="card-body">
            <div style="font-size: 0.875rem; line-height: 1.6;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                    <span style="color: #6b7280;">Total Students:</span>
                    <span style="font-weight: 600; color: #1f2937;">3</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                    <span style="color: #6b7280;">Active:</span>
                    <span style="font-weight: 600; color: #059669;">2</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                    <span style="color: #6b7280;">Pending:</span>
                    <span style="font-weight: 600; color: #d97706;">1</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #6b7280;">Suspended:</span>
                    <span style="font-weight: 600; color: #dc2626;">0</span>
                </div>
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
                <button style="width: 100%; padding: 0.75rem; background: #3b82f6; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem;">
                    Bulk Import
                </button>
                <button style="width: 100%; padding: 0.75rem; background: #059669; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem;">
                    Export Data
                </button>
                <button style="width: 100%; padding: 0.75rem; background: #d97706; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem;">
                    Generate Report
                </button>
            </div>
        </div>
    </div>
@endsection