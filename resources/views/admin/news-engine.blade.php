@extends('layouts.admin')

@section('content')
<div class="row pt-4">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="float-right">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">BDB News</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0);">Admin</a></li>
                    <li class="breadcrumb-item active">News Engine</li>
                </ol>
            </div>
            <h4 class="page-title">AI News Engine</h4>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        
        <!-- Autopilot Modern Controller Card -->
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

        <!-- Setup Card -->
        <div class="card border-warning" id="setup-card">
            <div class="card-header bg-warning text-dark font-weight-bold" style="font-size: 1.2rem;">
                <i class="mdi mdi-robot mr-2"></i> AI Autopilot Setup
            </div>
            <div class="card-body p-4">
                <p class="text-muted mb-4">Set the interval (in seconds) for the AI to automatically pick hot news and post it. Minimum is 30 seconds.</p>
                
                <div class="form-group">
                    <label for="aiInterval" class="font-weight-bold">Post every (seconds):</label>
                    <input type="number" class="form-control form-control-lg" id="aiInterval" value="30" min="30">
                </div>

                <div class="form-group">
                    <label for="aiPostLimit" class="font-weight-bold">Number of posts to generate (0 for unlimited):</label>
                    <input type="number" class="form-control form-control-lg" id="aiPostLimit" value="0" min="0">
                </div>

                <div class="form-group">
                    <label for="aiCategories" class="font-weight-bold">Target Categories (Leave empty to rotate all):</label>
                    <select class="form-control select2" id="aiCategories" name="categories[]" multiple="multiple" style="width: 100%;">
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div id="ai-status" class="alert alert-info mt-4" style="display: none;">
                    <div class="spinner-border spinner-border-sm text-info mr-2" role="status"></div>
                    <span id="ai-status-text">AI is working...</span>
                </div>
                
                <div class="mt-4 text-center">
                    <button type="button" class="btn btn-success btn-lg px-5 shadow" id="btn-start-ai" onclick="startAiLoop()">
                        <i class="mdi mdi-power mr-2"></i> Start Autopilot
                    </button>
                </div>
            </div>
        </div>
        
    </div>
</div>
@endsection

@push('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Styling to make Select2 look beautiful and match the admin theme */
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #e3ebf6 !important;
        border-radius: 0.25rem !important;
        min-height: 48px !important;
        padding: 5px 6px !important;
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
        padding: 2px 10px !important;
        margin-top: 6px !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: rgba(255,255,255,0.7) !important;
        margin-right: 5px !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: white !important;
    }
</style>
@endpush

@push('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    let aiIntervalTimer = null;
    let isAiRunning = false;
    let generatedCount = 0;

    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "Select Categories (Leave empty to rotate all)",
            allowClear: true
        });
    });

    function logAiError(message) {
        const errorLog = $('#autopilot-error-log');
        const errorMessages = $('#autopilot-error-messages');
        const time = new Date().toLocaleTimeString();
        errorMessages.prepend(`<div>[${time}] ${message}</div>`);
        errorLog.show();
    }

    function triggerAiGeneration() {
        $('#ai-status').show();
        $('#ai-status-text').text('AI is generating news... Please wait.');
        
        const categories = $('#aiCategories').val();
        
        $.ajax({
            url: "{{ route('admin.articles.autoGenerate') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                categories: categories
            },
            success: function(response) {
                if(response.success) {
                    generatedCount++;
                    $('#autopilot-post-count').text(generatedCount);
                } else {
                    logAiError(response.message || 'Failed to generate article.');
                }
            },
            error: function(xhr) {
                let msg = 'An error occurred while generating AI content.';
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                logAiError(msg);
            },
            complete: function() {
                if(isAiRunning) {
                    let interval = parseInt($('#aiInterval').val()) || 30;
                    if(interval < 30) interval = 30;
                    
                    let limit = parseInt($('#aiPostLimit').val()) || 0;
                    if(limit > 0 && generatedCount >= limit) {
                        stopAiLoop();
                        Swal.fire('Completed', `Successfully generated ${generatedCount} articles limit.`, 'success');
                        return;
                    }

                    $('#ai-status-text').text(`Waiting... Next generation in ${interval}s`);
                    let secondsLeft = interval;
                    
                    aiIntervalTimer = setInterval(function() {
                        secondsLeft--;
                        $('#ai-status-text').text(`Waiting... Next generation in ${secondsLeft}s`);
                        $('#autopilot-status-text').text(`Generating next post in ${secondsLeft}s...`);
                        
                        if(secondsLeft <= 0) {
                            clearInterval(aiIntervalTimer);
                            if(isAiRunning) {
                                triggerAiGeneration();
                            }
                        }
                    }, 1000);
                }
            }
        });
    }

    function startAiLoop() {
        let interval = parseInt($('#aiInterval').val());
        if(isNaN(interval) || interval < 30) {
            Swal.fire('Error', 'Minimum interval must be 30 seconds to prevent rate limiting.', 'error');
            return;
        }

        isAiRunning = true;
        generatedCount = 0;
        
        // UI Updates
        $('#btn-start-ai').prop('disabled', true);
        $('#aiInterval').prop('disabled', true);
        $('#aiPostLimit').prop('disabled', true);
        $('#aiCategories').prop('disabled', true);
        $('#autopilot-error-log').hide();
        $('#autopilot-error-messages').empty();
        $('#autopilot-post-count').text('0');
        
        // Show the cool controller card, hide setup card
        $('#setup-card').slideUp();
        $('#autopilot-controller').slideDown();
        
        triggerAiGeneration();
    }

    function stopAiLoop() {
        isAiRunning = false;
        if(aiIntervalTimer) {
            clearInterval(aiIntervalTimer);
            aiIntervalTimer = null;
        }
        
        // UI Updates
        $('#btn-start-ai').prop('disabled', false);
        $('#aiInterval').prop('disabled', false);
        $('#aiPostLimit').prop('disabled', false);
        $('#aiCategories').prop('disabled', false);
        $('#ai-status').hide();
        
        // Show setup card, hide controller card
        $('#autopilot-controller').slideUp();
        $('#setup-card').slideDown();
    }
</script>
@endpush
