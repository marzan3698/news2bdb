@extends('layouts.admin')

@section('title', 'Video News List')
@section('page_header', 'All Video News')
@section('page_title', 'Video News')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mt-0 header-title">Generated Video News History</h4>
                    <a href="{{ route('admin.video-news.create') }}" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-plus"></i> Generate New Video
                    </a>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Category</th>
                                <th>Concept Title</th>
                                <th>Status</th>
                                <th>Facebook Video</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse( as )
                                <tr>
                                    <td>{{ ->iteration }}</td>
                                    <td><span class="badge badge-soft-primary">{{ ->category }}</span></td>
                                    <td>
                                        @if(->concept_title)
                                            <strong>{{ ->concept_title }}</strong>
                                        @else
                                            <span class="text-muted"><i class="mdi mdi-loading mdi-spin"></i> Waiting for n8n...</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(->status == 'completed')
                                            <span class="badge badge-success">Completed</span>
                                        @elseif(->status == 'processing')
                                            <span class="badge badge-warning"><i class="mdi mdi-loading mdi-spin"></i> Processing</span>
                                        @else
                                            <span class="badge badge-danger">Failed</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(->facebook_video_url)
                                            <a href="{{ ->facebook_video_url }}" target="_blank" class="btn btn-sm btn-outline-info">
                                                <i class="mdi mdi-facebook"></i> View on FB
                                            </a>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>{{ ->created_at->format('d M, Y h:i A') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No video news generated yet.</td>
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
