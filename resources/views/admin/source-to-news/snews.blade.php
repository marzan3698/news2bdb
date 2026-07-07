@extends('layouts.admin')

@section('title', 'Cloned News from Source')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <h4 class="page-title">News from jago 1</h4>
                        <button class="btn btn-primary" id="add-new-news-btn">
                            <i class="fas fa-plus mr-2"></i> Add new news
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Cloned At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($articles as $article)
                            <tr>
                                <td>
                                    @if($article->image_url)
                                        <img src="{{ filter_var($article->image_url, FILTER_VALIDATE_URL) ? $article->image_url : asset($article->image_url) }}" alt="image" style="height: 50px; width: 80px; object-fit: cover; border-radius: 4px;">
                                    @else
                                        <span class="badge badge-secondary">No Image</span>
                                    @endif
                                </td>
                                <td>{{ Str::limit($article->title, 50) }}</td>
                                <td>{{ $article->category ? $article->category->name : 'N/A' }}</td>
                                <td>{{ $article->created_at->format('d M, Y h:i A') }}</td>
                                <td>
                                    <a href="{{ route('news.show', $article->slug) }}" target="_blank" class="btn btn-sm btn-info" title="View"><i class="fas fa-eye"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">No news cloned from this source yet.</td>
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

@section('scripts')
<!-- Sweet-Alert  -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    $('#add-new-news-btn').on('click', function() {
        Swal.fire({
            title: 'Clone from jago 1',
            input: 'number',
            inputLabel: 'Number of news to clone (e.g. 2 or 8)',
            inputValue: 2,
            inputAttributes: {
                min: 1,
                max: 20,
                step: 1
            },
            showCancelButton: true,
            confirmButtonText: 'Clone',
            showLoaderOnConfirm: true,
            preConfirm: (num) => {
                if (!num || num <= 0) {
                    Swal.showValidationMessage('Please enter a valid number');
                    return false;
                }
                
                return $.ajax({
                    url: "{{ route('admin.source-to-news.clone') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        number_of_news: num
                    }
                }).then(response => {
                    if (!response.success) {
                        throw new Error(response.message || 'Error fetching news');
                    }
                    return response;
                }).catch(error => {
                    Swal.showValidationMessage(
                        `Request failed: ${error.message || 'Unknown error'}`
                    );
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Success!',
                    text: result.value.message,
                    icon: 'success'
                }).then(() => {
                    location.reload();
                });
            }
        });
    });
});
</script>
@endsection
