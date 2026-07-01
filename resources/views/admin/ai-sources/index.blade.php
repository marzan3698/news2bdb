@extends('layouts.admin')

@section('content')
<div class="row pt-4">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="float-right">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">BDB News</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0);">Admin</a></li>
                    <li class="breadcrumb-item active">AI Sources</li>
                </ol>
            </div>
            <h4 class="page-title">Manage AI Sources</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        @if(session('success'))
            <div class="alert alert-success border-0">
                <strong>Success!</strong> {{ session('success') }}
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="header-title mt-0">All AI Sources</h5>
                    <a href="{{ route('admin.ai-sources.create') }}" class="btn btn-primary"><i class="mdi mdi-plus-circle mr-2"></i>Add Source</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped dt-responsive nowrap" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>URL / Feed Link</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sources as $source)
                                <tr>
                                    <td>{{ $source->id }}</td>
                                    <td><strong>{{ $source->name }}</strong></td>
                                    <td>
                                        @if($source->type == 'rss')
                                            <span class="badge badge-info">RSS Feed</span>
                                        @elseif($source->type == 'facebook')
                                            <span class="badge badge-primary">Facebook Page</span>
                                        @else
                                            <span class="badge badge-secondary">Web Scraping</span>
                                        @endif
                                    </td>
                                    <td><code class="text-dark">{{ $source->url }}</code></td>
                                    <td>
                                        @if($source->status)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.ai-sources.edit', $source->id) }}" class="btn btn-sm btn-warning text-dark"><i class="mdi mdi-pencil"></i> Edit</a>
                                        
                                        <form action="{{ route('admin.ai-sources.destroy', $source->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this source?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="mdi mdi-trash-can"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No AI Sources configured yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
