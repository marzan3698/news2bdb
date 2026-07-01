@extends('layouts.admin')

@section('content')
<div class="row pt-4">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="float-right">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">BDB News</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0);">Admin</a></li>
                    <li class="breadcrumb-item active">Articles</li>
                </ol>
            </div>
            <h4 class="page-title">Manage Articles</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- Autopilot Modern Controller Card (Hidden by default) -->
        <div id="autopilot-controller" class="card shadow-lg border-0 mb-4" style="display: none; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; border-radius: 15px;">
            <div class="card-body d-flex justify-content-between align-items-center p-4">
                <div class="d-flex align-items-center">
                    <div class="spinner-grow text-danger mr-3" role="status" style="width: 1.5rem; height: 1.5rem;"></div>
                    <div>
                        <h4 class="text-white mb-1 fw-bold">AI Autopilot is Running</h4>
                        <p class="mb-0 text-light opacity-75" id="autopilot-status-text">Generating next post in 30s...</p>
                    </div>
                </div>
                <div class="text-center mx-4">
                    <h2 class="text-white mb-0 fw-bold" id="autopilot-post-count">0</h2>
                    <span class="text-light text-uppercase" style="letter-spacing: 1px; font-size: 12px;">Posts Generated</span>
                </div>
                <div>
                    <button type="button" class="btn btn-danger btn-lg shadow-sm" onclick="stopAiLoop()" style="border-radius: 30px; padding: 10px 30px;">
                        <i class="mdi mdi-stop-circle-outline mr-2"></i> Stop Autopilot
                    </button>
                </div>
            </div>
            <!-- Error Log Section inside Controller (Hidden by default) -->
            <div id="autopilot-error-log" class="card-footer bg-dark text-danger font-monospace" style="display: none; max-height: 200px; overflow-y: auto; font-size: 0.85rem; text-align: left;">
                <div class="font-weight-bold mb-1"><i class="mdi mdi-alert-circle-outline mr-1"></i>Error Log:</div>
                <div id="autopilot-error-messages"></div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="header-title mt-0">All Articles</h5>
                    <div>
                        <a href="{{ route('admin.articles.create') }}" class="btn btn-primary"><i class="mdi mdi-plus-circle mr-2"></i>Add New</a>
                        <button type="button" class="btn btn-warning ml-2 text-dark font-weight-bold" data-toggle="modal" data-target="#aiSetupModal">
                            <i class="mdi mdi-robot mr-2"></i> AI Power On
                        </button>
                    </div>
                </div>

                <div class="table-responsive" style="overflow-x:auto; width:100%;">
                    <table id="datatable" class="table table-bordered table-hover" style="width:100%; min-width:900px;">
                        <thead>
                            <tr>
                                <th style="width:50px;">ID</th>
                                <th style="width:90px;">Image</th>
                                <th>Title</th>
                                <th style="width:110px;">Category</th>
                                <th style="width:140px;">Source</th>
                                <th style="width:90px;">Status</th>
                                <th style="width:145px;">Created At</th>
                                <th style="width:80px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data populated by DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('modals')
<!-- AI Setup Modal -->
<div class="modal fade" id="aiSetupModal" role="dialog" aria-labelledby="aiSetupModalLabel" aria-hidden="true" data-backdrop="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content border-warning">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-dark font-weight-bold" id="aiSetupModalLabel"><i class="mdi mdi-robot mr-2"></i> AI Autopilot Setup</h5>
                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close" onclick="stopAiLoop()">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Set the interval (in seconds) for the AI to automatically pick hot news and post it. Minimum is 30 seconds.</p>
                
                <div class="form-group">
                    <label for="aiInterval" class="font-weight-bold">Post every (seconds):</label>
                    <input type="number" class="form-control" id="aiInterval" value="30" min="30">
                </div>

                <div class="form-group">
                    <label for="aiPostLimit" class="font-weight-bold">Number of posts to generate (0 for unlimited):</label>
                    <input type="number" class="form-control" id="aiPostLimit" value="0" min="0">
                </div>

                <div class="form-group">
                    <label for="aiCategories" class="font-weight-bold">Target Categories (Leave empty to rotate all):</label>
                    <select class="form-control select2" id="aiCategories" name="categories[]" multiple="multiple" style="width: 100%;">
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div id="ai-status" class="alert alert-info mt-3" style="display: none;">
                    <div class="spinner-border spinner-border-sm text-info mr-2" role="status"></div>
                    <span id="ai-status-text">AI is working...</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="stopAiLoop()">Close & Stop</button>
                <button type="button" class="btn btn-success" id="btn-start-ai" onclick="startAiLoop()">Start Autopilot</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Styling to make Select2 look beautiful and match the admin theme */
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #e3ebf6 !important;
        border-radius: 0.25rem !important;
        min-height: 38px !important;
        padding: 2px 6px !important;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #2a5298 !important;
        box-shadow: 0 0 0 0.2rem rgba(42, 82, 152, 0.15) !important;
    }
    .select2-dropdown {
        border-color: #e3ebf6 !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
        z-index: 99999 !important;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #2a5298 !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #2a5298 !important;
        border: 1px solid #1e3c72 !important;
        color: white !important;
        border-radius: 3px !important;
        padding: 1px 8px !important;
        margin-top: 4px !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: rgba(255,255,255,0.7) !important;
        margin-right: 5px !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: white !important;
    }
    /* Fix DataTable overflow */
    #datatable_wrapper {
        overflow-x: auto;
        width: 100%;
    }
    #datatable th, #datatable td {
        white-space: nowrap;
    }
    #datatable th:nth-child(3), #datatable td:nth-child(3) {
        white-space: normal;
        min-width: 200px;
        max-width: 380px;
    }
</style>
@endpush

@push('js')
<!-- DataTables JS CDN -->
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Select2 JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    let articlesTable;
    let aiIntervalTimer = null;
    let isAiRunning = false;
    let generatedCount = 0;

    $(document).ready(function() {
        articlesTable = $('#datatable').DataTable({
            processing: true,
            scrollX: true,
            autoWidth: false,
            ajax: {
                url: '{{ route('admin.articles.data') }}',
                dataSrc: 'data'
            },
            columns: [
                { data: 'id', width: '50px' },
                { 
                    data: 'image_url',
                    width: '90px',
                    orderable: false,
                    render: function(data, type, row) {
                        return data ? `<img src="${data}" alt="img" class="img-thumbnail" style="max-width:75px; max-height:48px; object-fit:cover; border-radius:4px;">` : '<span class="text-muted small">No Image</span>';
                    }
                },
                { data: 'title', width: '300px' },
                { 
                    data: 'category.name',
                    width: '110px',
                    render: function(data) { return data || '<span class="text-muted">N/A</span>'; }
                },
                {
                    data: 'source_name',
                    width: '140px',
                    render: function(data) {
                        if (!data) return '<span class="text-muted">—</span>';
                        if (data === 'AI Generated') {
                            return '<span class="badge" style="background:#6c757d;color:#fff;font-size:10px;padding:4px 8px;border-radius:12px;white-space:nowrap;">🤖 AI</span>';
                        }
                        return `<span class="badge" style="background:#1da255;color:#fff;font-size:10px;padding:4px 8px;border-radius:12px;white-space:nowrap;">📰 ${data}</span>`;
                    }
                },
                { 
                    data: 'status',
                    width: '90px',
                    render: function(data) {
                        let badge = data === 'published' ? 'badge-success' : 'badge-warning';
                        return `<span class="badge ${badge}">${data}</span>`;
                    }
                },
                { 
                    data: 'created_at',
                    width: '145px',
                    render: function(data) {
                        if (!data) return '—';
                        const d = new Date(data);
                        return d.toLocaleDateString('bn-BD', {day:'2-digit',month:'2-digit',year:'numeric'}) + ' ' + d.toLocaleTimeString('en', {hour:'2-digit',minute:'2-digit'});
                    }
                },
                {
                    data: 'id',
                    width: '80px',
                    orderable: false,
                    render: function(data, type, row) {
                        return `<button class="btn btn-sm btn-danger" onclick="deleteArticle(${data})" title="Delete"><i class="mdi mdi-delete"></i></button>`;
                    }
                }
            ],
            order: [[0, 'desc']],
            language: {
                processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading...',
                emptyTable: 'No articles found.'
            }
        });

        // Initialize Select2 on the Categories selectbox in the modal
        $('#aiCategories').select2({
            placeholder: "Select Categories (Leave empty to rotate all)",
            allowClear: true,
            dropdownParent: $('#aiSetupModal')
        });
    });

    function deleteArticle(id) {
        Swal.fire({
            title: 'Delete Article?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/articles/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        Swal.fire('Deleted!', 'Article has been deleted.', 'success');
                        articlesTable.ajax.reload();
                    },
                    error: function() {
                        Swal.fire('Error!', 'Could not delete the article.', 'error');
                    }
                });
            }
        });
    }


    function startAiLoop() {
        let seconds = parseInt($('#aiInterval').val());
        if(seconds < 30) seconds = 30;

        if (isAiRunning) return;

        isAiRunning = true;
        generatedCount = 0;
        $('#autopilot-post-count').text(generatedCount);
        
        let selectedCategories = $('#aiCategories').val() || [];
        let postLimit = parseInt($('#aiPostLimit').val()) || 0;

        // Hide the modal
        $('#aiSetupModal').modal('hide');
        
        // Show the modern controller and clear errors
        $('#autopilot-controller').fadeIn();
        $('#autopilot-error-log').hide();
        $('#autopilot-error-messages').empty();

        let categoryText = selectedCategories.length > 0 ? `${selectedCategories.length} category/ies selected` : 'all rotating';
        let limitText = postLimit > 0 ? `Limit: ${postLimit}` : 'no limit';
        $('#autopilot-status-text').text(`AI is running! Next post in ${seconds}s... (${categoryText}, ${limitText})`);

        // Disable modal inputs while running
        $('#aiInterval').prop('disabled', true);
        $('#aiPostLimit').prop('disabled', true);
        $('#aiCategories').prop('disabled', true);

        // Run immediately first
        triggerAiGeneration(seconds, selectedCategories, postLimit);
        
        aiIntervalTimer = setInterval(function() {
            triggerAiGeneration(seconds, selectedCategories, postLimit);
        }, seconds * 1000);
    }

    function stopAiLoop() {
        if(aiIntervalTimer) clearInterval(aiIntervalTimer);
        isAiRunning = false;
        
        // Hide controller and reset
        $('#autopilot-controller').fadeOut();
        $('#btn-start-ai').prop('disabled', false).text('Start Autopilot');
        $('#aiInterval').prop('disabled', false);
        $('#aiPostLimit').prop('disabled', false);
        $('#aiCategories').prop('disabled', false);
    }

    function triggerAiGeneration(intervalSeconds, selectedCategories, postLimit) {
        $('#autopilot-status-text').text('AI is generating news right now...');
        
        $.ajax({
            url: '{{ route('admin.articles.autoGenerate') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                category_ids: selectedCategories
            },
            success: function(response) {
                if(response.success) {
                    articlesTable.ajax.reload(null, false);
                    generatedCount++;
                    $('#autopilot-post-count').text(generatedCount);
                    
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Auto-posted: ' + response.article.title,
                        showConfirmButton: false,
                        timer: 3000
                    });

                    // Check if limit is reached
                    if (postLimit > 0 && generatedCount >= postLimit) {
                        stopAiLoop();
                        Swal.fire({
                            icon: 'success',
                            title: 'Autopilot Completed',
                            text: `Successfully generated ${generatedCount} posts as requested!`,
                            confirmButtonColor: '#2a5298'
                        });
                        return;
                    }
                } else {
                    console.error("AI Error: ", response.message);
                    logError(response.message || JSON.stringify(response));
                }
            },
            error: function(xhr, status, error) {
                console.error("AI Post Failed: ", error);
                let errDetails = xhr.responseJSON ? xhr.responseJSON.message : error;
                logError(errDetails);
            },
            complete: function() {
                if(isAiRunning) {
                    let categoryText = selectedCategories.length > 0 ? `${selectedCategories.length} category/ies selected` : 'all rotating';
                    let limitText = postLimit > 0 ? `Limit: ${postLimit}` : 'no limit';
                    $('#autopilot-status-text').text(`Waiting... Next post in ${intervalSeconds}s. (${categoryText}, ${limitText})`);
                }
            }
        });
    }

    function logError(message) {
        let timestamp = new Date().toLocaleTimeString();
        $('#autopilot-error-log').show();
        $('#autopilot-error-messages').prepend(`<div>[${timestamp}] ${message}</div>`);
    }
</script>
@endpush
