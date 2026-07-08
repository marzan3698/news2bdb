@extends('layouts.admin')

@section('title', 'Cloned News from Source')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <h4 class="page-title">News from Fixed Sources</h4>
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

@push('js')
<!-- Sweet-Alert  -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    const activeSources = @json($activeSources);

    $('#add-new-news-btn').on('click', function() {
        if (activeSources.length === 0) {
            Swal.fire('No Active Sources', 'Please enable at least one source from the All Source List.', 'error');
            return;
        }

        let sourcesHtml = '<strong>Active Sources:</strong> ' + activeSources.join(', ');

        Swal.fire({
            title: 'Clone from Fixed Sources',
            html: `
                <div class="mb-3 text-left" style="font-size: 14px;">
                    ${sourcesHtml}
                </div>
                <div class="d-flex justify-content-center flex-wrap mb-3">
                    <button type="button" class="btn btn-outline-primary clone-num-btn m-1" data-num="2">2 News</button>
                    <button type="button" class="btn btn-outline-primary clone-num-btn m-1" data-num="5">5 News</button>
                    <button type="button" class="btn btn-outline-primary clone-num-btn m-1" data-num="10">10 News</button>
                    <button type="button" class="btn btn-outline-primary clone-num-btn m-1" data-num="30">30 News</button>
                </div>
                <div class="form-group text-left">
                    <label>Or enter manual amount:</label>
                    <input type="number" id="clone-manual-num" class="form-control" min="1" max="100" placeholder="e.g. 8">
                </div>
                <div id="clone-distribution-info" class="text-info text-left mt-2" style="font-size: 13.5px; min-height: 20px;"></div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Start Cloning',
            showLoaderOnConfirm: true,
            didOpen: () => {
                const updateDistribution = (num) => {
                    if (!num || num <= 0) {
                        $('#clone-distribution-info').text('');
                        return;
                    }
                    if (activeSources.length === 1) {
                        $('#clone-distribution-info').html(`Will fetch <strong>${num}</strong> news from <strong>${activeSources[0]}</strong>.`);
                    } else if (activeSources.length === 2) {
                        let p = Math.ceil(num / 2); // prothom is first in controller logic
                        let j = Math.floor(num / 2);
                        $('#clone-distribution-info').html(`Will fetch <strong>${p}</strong> from <strong>prothom 1</strong>, and <strong>${j}</strong> from <strong>jago 1</strong>.`);
                    }
                };

                $('.clone-num-btn').on('click', function() {
                    $('.clone-num-btn').removeClass('active');
                    $(this).addClass('active');
                    let num = $(this).data('num');
                    $('#clone-manual-num').val(num);
                    updateDistribution(num);
                });

                $('#clone-manual-num').on('input', function() {
                    $('.clone-num-btn').removeClass('active');
                    let num = parseInt($(this).val());
                    updateDistribution(num);
                });

                // Set default to 2
                $('.clone-num-btn[data-num="2"]').click();
            },
            preConfirm: () => {
                const num = parseInt($('#clone-manual-num').val());
                if (!num || num <= 0) {
                    Swal.showValidationMessage('Please select or enter a valid number');
                    return false;
                }
                
                return $.ajax({
                    url: "{{ route('admin.source-to-news.clone.fetch') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        number_of_news: num
                    }
                }).then(response => {
                    if (!response.success || !response.items || response.items.length === 0) {
                        throw new Error(response.message || 'No new articles found to clone.');
                    }
                    return response.items;
                }).catch(error => {
                    Swal.showValidationMessage(
                        `Failed: ${error.message || 'Unknown error'}`
                    );
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then(async (result) => {
            if (result.isConfirmed && result.value) {
                const items = result.value;
                const total = items.length;
                let successCount = 0;
                let failCount = 0;

                // Open a new Swal for progress
                Swal.fire({
                    title: 'Cloning News...',
                    html: `
                        <div class="mb-3 text-left">
                            <strong>Status:</strong> <span id="clone-status">Initializing...</span><br>
                            <small class="text-muted" id="clone-headline"></small>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div id="clone-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                        <div class="mt-2">
                            <small><span id="clone-count">0</span> of ${total} processed</small>
                        </div>
                    `,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false
                });

                // Loop through items sequentially
                for (let i = 0; i < total; i++) {
                    const item = items[i];
                    $('#clone-status').text(`Generating AI content for item ${i + 1}...`);
                    $('#clone-headline').text(item.headline);

                    try {
                        const processResp = await $.ajax({
                            url: "{{ route('admin.source-to-news.clone.process') }}",
                            type: "POST",
                            data: {
                                _token: "{{ csrf_token() }}",
                                item: item
                            }
                        });

                        if (processResp.success) {
                            successCount++;
                        } else {
                            failCount++;
                            console.error("Item processing failed:", processResp.message);
                        }
                    } catch (err) {
                        failCount++;
                        console.error("AJAX error during processing:", err);
                    }

                    const percent = Math.round(((i + 1) / total) * 100);
                    $('#clone-progress-bar').css('width', `${percent}%`).text(`${percent}%`).attr('aria-valuenow', percent);
                    $('#clone-count').text(i + 1);
                }

                // Complete
                Swal.fire({
                    title: 'Completed!',
                    html: `Successfully cloned <b>${successCount}</b> news items.<br>${failCount > 0 ? `<b>${failCount}</b> items failed.` : ''}`,
                    icon: successCount > 0 ? 'success' : (failCount > 0 ? 'error' : 'info'),
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            }
        });
    });
});
</script>
@endpush
