@extends('layouts.admin')

@section('title', 'Admin Control Panel')
@section('page_header', 'Control Panel')
@section('page_title', 'Dashboard')

@section('content')
<!-- Alert Banner -->
@if(session('success'))
    <div class="row">
        <div class="col-lg-12">
            <div class="alert alert-success alert-dismissible fade show role="alert"">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true"><i class="mdi mdi-close"></i></span>
                </button>
                <strong>Success!</strong> {{ session('success') }}
            </div>
        </div>
    </div>
@endif

<!-- Stats Cards Row -->
<div class="row">
    <!-- Stat 1: Total Users -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body mb-0">
                <div class="row">
                    <div class="col-8 align-self-center">
                        <div class="">
                            <h4 class="mt-0 header-title" style="color: #6c757d; font-size: 13px; font-weight: 600; text-transform: uppercase;">Total Users</h4>
                            <h2 class="mt-2 font-weight-bold text-dark">{{ $stats['total_users'] }}</h2> 
                            <p class="mb-0 text-muted"><span class="text-success"><i class="mdi mdi-arrow-up"></i>100%</span> Active database profiles</p>
                        </div>
                    </div><!--end col-->
                    <div class="col-4 align-self-center">
                        <div class="icon-info text-right">
                            <i class="dripicons-user bg-soft-primary text-primary" style="font-size: 24px; padding: 12px; border-radius: 50%;"></i>
                        </div>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end card-body-->
        </div><!--end card-->
    </div><!--end col-->

    <!-- Stat 2: Administrators -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body mb-0">
                <div class="row">
                    <div class="col-8 align-self-center">
                        <div class="">
                            <h4 class="mt-0 header-title" style="color: #6c757d; font-size: 13px; font-weight: 600; text-transform: uppercase;">Administrators</h4>
                            <h2 class="mt-2 font-weight-bold text-dark">{{ $stats['admins_count'] }}</h2> 
                            <p class="mb-0 text-muted"><span class="text-danger"><i class="mdi mdi-shield-outline"></i></span> Full system capabilities</p>
                        </div>
                    </div><!--end col-->
                    <div class="col-4 align-self-center">
                        <div class="icon-info text-right">
                            <i class="dripicons-lock bg-soft-danger text-danger" style="font-size: 24px; padding: 12px; border-radius: 50%;"></i>
                        </div>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end card-body-->
        </div><!--end card-->
    </div><!--end col-->

    <!-- Stat 3: Subscribers -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body mb-0">
                <div class="row">
                    <div class="col-8 align-self-center">
                        <div class="">
                            <h4 class="mt-0 header-title" style="color: #6c757d; font-size: 13px; font-weight: 600; text-transform: uppercase;">Subscribers</h4>
                            <h2 class="mt-2 font-weight-bold text-dark">{{ $stats['regular_users'] }}</h2> 
                            <p class="mb-0 text-muted"><span class="text-success"><i class="mdi mdi-account-multiple"></i></span> Portal members</p>
                        </div>
                    </div><!--end col-->
                    <div class="col-4 align-self-center">
                        <div class="icon-info text-right">
                            <i class="dripicons-user-group bg-soft-success text-success" style="font-size: 24px; padding: 12px; border-radius: 50%;"></i>
                        </div>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end card-body-->
        </div><!--end card-->
    </div><!--end col-->
</div><!--end row-->

<!-- Database Content Row -->
<div class="row">
    <!-- User Listing (Col-8) -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h4 class="mt-0 header-title font-weight-bold">Registered Users Accounts</h4>
                <p class="text-muted mb-4 font-13">All registered users currently matching credentials in the BDB News system.</p>

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th># ID</th>
                                <th>Full Name</th>
                                <th>Email Address</th>
                                <th>Assigned Role</th>
                                <th>Registration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td><strong>#{{ $user->id }}</strong></td>
                                    <td>
                                        <img src="{{ asset('admin-assets/images/users/user-1.jpg') }}" alt="" class="thumb-sm rounded-circle mr-2"> 
                                        {{ $user->name }}
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @if($user->role === 'admin')
                                            <span class="badge badge-boxed badge-soft-danger font-weight-bold" style="padding: 5px 10px;">ADMINISTRATOR</span>
                                        @else
                                            <span class="badge badge-boxed badge-soft-primary font-weight-bold" style="padding: 5px 10px;">SUBSCRIBER</span>
                                        @endif
                                    </td>
                                    <td>{{ $user->created_at->format('M d, Y H:i A') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No database users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Options & Panel Info (Col-4) -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h4 class="mt-0 header-title font-weight-bold">Quick Administrative Tools</h4>
                <p class="text-muted mb-4 font-13">Useful shortcuts for quick database management updates.</p>

                <div class="space-y-3">
                    <button class="btn btn-primary btn-block p-3 font-weight-bold" style="letter-spacing: 0.5px;">
                        <i class="mdi mdi-plus-circle mr-1"></i> Create News Post
                    </button>
                    <button class="btn btn-light btn-block p-3 font-weight-bold border" style="letter-spacing: 0.5px;">
                        <i class="mdi mdi-settings mr-1"></i> Control Configuration
                    </button>
                </div>
            </div>
        </div>

        <div class="card bg-gradient1 text-white">
            <div class="card-body">
                <div class="media">
                    <div class="media-body align-self-center">
                        <h4 class="mt-0 text-white font-weight-bold">System Metrics</h4>
                        <p class="text-white-50 mb-0">Apache and database status: OK</p>
                    </div>
                    <i class="dripicons-device-desktop text-white-50 font-40"></i>
                </div>
                <div class="mt-4 pt-3 border-top border-white-10">
                    <div class="d-flex justify-content-between text-sm text-white-50">
                        <span>Laravel Framework:</span>
                        <span class="text-white font-weight-bold">v{{ app()->version() }}</span>
                    </div>
                    <div class="d-flex justify-content-between text-sm text-white-50 mt-2">
                        <span>PHP version:</span>
                        <span class="text-white font-weight-bold">{{ PHP_VERSION }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
