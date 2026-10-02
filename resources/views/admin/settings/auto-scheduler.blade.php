@extends('layouts.admin')

@section('page_title', 'Auto News Posting & Scheduler')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="mt-0 header-title mb-1"><i class="mdi mdi-clock-fast text-primary mr-1"></i> Auto News Posting System (Native)</h4>
                        <p class="text-muted mb-0">No external tools (n8n) required. The website automatically fetches, rewrites with Gemini AI, generates banners, and publishes news articles.</p>
                    </div>
                    <div>
                        <button id="runNowBtn" class="btn btn-gradient-success waves-effect waves-light font-weight-bold">
                            <i class="mdi mdi-play mr-1"></i> Run Autopilot Now (Test)
                        </button>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success border-0 mb-4">
                        <strong><i class="mdi mdi-check-circle mr-1"></i> Success!</strong> {{ session('success') }}
                    </div>
                @endif

                <!-- Status Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card border mb-0" style="background: #f8fafc;">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle p-2 mr-3 {{ $scheduler_enabled == '1' ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger' }}">
                                        <i class="mdi {{ $scheduler_enabled == '1' ? 'mdi-check-circle' : 'mdi-close-circle' }} font-24"></i>
                                    </div>
                                    <div>
                                        <h6 class="m-0 text-muted">Auto-Post Status</h6>
                                        <h5 class="m-0 font-weight-bold {{ $scheduler_enabled == '1' ? 'text-success' : 'text-danger' }}">
                                            {{ $scheduler_enabled == '1' ? 'ACTIVE (Posting automatically)' : 'PAUSED (Manual only)' }}
                                        </h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border mb-0" style="background: #f8fafc;">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle p-2 mr-3 bg-soft-info text-info">
                                        <i class="mdi mdi-timer-sand font-24"></i>
                                    </div>
                                    <div>
                                        <h6 class="m-0 text-muted">Posting Interval</h6>
                                        <h5 class="m-0 font-weight-bold text-dark">Every {{ $scheduler_interval }} Minutes</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border mb-0" style="background: #f8fafc;">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle p-2 mr-3 {{ !empty($gemini_api_key) ? 'bg-soft-success text-success' : 'bg-soft-warning text-warning' }}">
                                        <i class="mdi {{ !empty($gemini_api_key) ? 'mdi-brain' : 'mdi-alert' }} font-24"></i>
                                    </div>
                                    <div>
                                        <h6 class="m-0 text-muted">Gemini AI Engine</h6>
                                        <h5 class="m-0 font-weight-bold {{ !empty($gemini_api_key) ? 'text-success' : 'text-warning' }}">
                                            {{ !empty($gemini_api_key) ? 'Connected & Ready' : 'API Key Missing' }}
                                        </h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nav Tabs -->
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active font-weight-bold text-dark" data-toggle="tab" href="#settings-tab" role="tab">
                            <i class="mdi mdi-tune mr-1"></i> Scheduler Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold text-dark" data-toggle="tab" href="#cpanel-cron-tab" role="tab">
                            <i class="mdi mdi-server mr-1"></i> cPanel Cron Setup
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold text-dark" data-toggle="tab" href="#web-cron-tab" role="tab">
                            <i class="mdi mdi-web mr-1"></i> Web Cron URL (Alternative)
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Tab 1: Settings -->
                    <div class="tab-pane active" id="settings-tab" role="tabpanel">
                        <form action="{{ route('admin.settings.auto-scheduler.save') }}" method="POST">
                            @csrf
                            <div class="form-group mb-4">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="scheduler_enabled" name="scheduler_enabled" value="1" {{ $scheduler_enabled == '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold font-16" for="scheduler_enabled">
                                        Enable Auto News Posting
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">When turned on, the site will automatically fetch latest news, rewrite with Gemini AI, create image banner and publish.</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="scheduler_interval" class="font-weight-bold">Posting Frequency (Interval)</label>
                                        <select class="form-control" id="scheduler_interval" name="scheduler_interval">
                                            <option value="5" {{ $scheduler_interval == '5' ? 'selected' : '' }}>Every 5 Minutes (Ultra Fast)</option>
                                            <option value="10" {{ $scheduler_interval == '10' ? 'selected' : '' }}>Every 10 Minutes</option>
                                            <option value="15" {{ $scheduler_interval == '15' ? 'selected' : '' }}>Every 15 Minutes</option>
                                            <option value="30" {{ $scheduler_interval == '30' ? 'selected' : '' }}>Every 30 Minutes (Recommended)</option>
                                            <option value="60" {{ $scheduler_interval == '60' ? 'selected' : '' }}>Every 1 Hour</option>
                                            <option value="120" {{ $scheduler_interval == '120' ? 'selected' : '' }}>Every 2 Hours</option>
                                        </select>
                                        <small class="form-text text-muted">Select how often the system should attempt to generate and publish an article.</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="cron_secret" class="font-weight-bold">Cron Secret Token</label>
                                        <input type="text" class="form-control font-monospace" id="cron_secret" name="cron_secret" value="{{ $cron_secret }}">
                                        <small class="form-text text-muted">Protects the Web Cron URL from unauthorized requests.</small>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary font-weight-bold mt-2">
                                <i class="mdi mdi-content-save mr-1"></i> Save Scheduler Settings
                            </button>
                        </form>
                    </div>

                    <!-- Tab 2: cPanel Cron -->
                    <div class="tab-pane" id="cpanel-cron-tab" role="tabpanel">
                        <div class="alert border-0" style="background:#e8f4fd; border-left:4px solid #1e88e5 !important;">
                            <h5 class="mt-0 text-primary"><i class="mdi mdi-information-outline mr-1"></i> How to setup cPanel Cron Job</h5>
                            <p class="mb-0 text-dark">
                                You only need to add <strong>ONE</strong> cron command in your live cPanel. cPanel will run Laravel's internal scheduler automatically.
                            </p>
                        </div>

                        <h5 class="font-weight-bold mt-4 mb-2">Recommended cPanel Cron Command:</h5>
                        <p class="text-muted">In your <strong>cPanel &gt; Cron Jobs</strong>, choose <strong>"Once Per Minute (* * * * *)"</strong> and paste this exact command:</p>

                        <div class="position-relative mb-4">
                            <button class="btn btn-sm btn-dark position-absolute copy-code-btn" data-target="cron-cmd-1" style="top: 10px; right: 10px; z-index: 10;">
                                <i class="mdi mdi-content-copy"></i> Copy
                            </button>
                            <pre id="cron-cmd-1" class="p-3 rounded text-white font-monospace" style="background: #1e293b; font-size: 14px; margin: 0;">* * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1</pre>
                        </div>

                        <hr>

                        <h5 class="font-weight-bold mt-4 mb-2">Direct Artisan Command (Alternative):</h5>
                        <p class="text-muted">If you prefer triggering the news generation command directly on specific minute intervals:</p>

                        <div class="position-relative mb-4">
                            <button class="btn btn-sm btn-dark position-absolute copy-code-btn" data-target="cron-cmd-2" style="top: 10px; right: 10px; z-index: 10;">
                                <i class="mdi mdi-content-copy"></i> Copy
                            </button>
                            <pre id="cron-cmd-2" class="p-3 rounded text-white font-monospace" style="background: #1e293b; font-size: 14px; margin: 0;">*/{{ $scheduler_interval }} * * * * cd {{ base_path() }} && php artisan news:generate --count=1 >> /dev/null 2>&1</pre>
                        </div>
                    </div>

                    <!-- Tab 3: Web Cron -->
                    <div class="tab-pane" id="web-cron-tab" role="tabpanel">
                        <div class="alert border-0" style="background:#fef3c7; border-left:4px solid #f59e0b !important;">
                            <h5 class="mt-0 text-warning"><i class="mdi mdi-link-variant mr-1"></i> Web Cron (No Terminal / Shell Access Needed)</h5>
                            <p class="mb-0 text-dark">
                                If your shared hosting doesn't allow CLI commands, you can trigger auto-posting via URL. You can use cPanel's <code>curl</code> or free external services like <strong>cron-job.org</strong>.
                            </p>
                        </div>

                        <h5 class="font-weight-bold mt-4 mb-2">Your Secure Web Cron URL:</h5>
                        <div class="input-group mb-4">
                            <input type="text" class="form-control font-monospace" id="webCronInput" value="{{ $webCronUrl }}" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary copy-input-btn" type="button" data-input="webCronInput">
                                    <i class="mdi mdi-content-copy"></i> Copy URL
                                </button>
                                <a href="{{ $webCronUrl }}" target="_blank" class="btn btn-outline-primary">
                                    <i class="mdi mdi-open-in-new"></i> Open & Test
                                </a>
                            </div>
                        </div>

                        <h5 class="font-weight-bold mt-4 mb-2">cPanel Curl Command for Web Cron:</h5>
                        <div class="position-relative mb-4">
                            <button class="btn btn-sm btn-dark position-absolute copy-code-btn" data-target="cron-cmd-3" style="top: 10px; right: 10px; z-index: 10;">
                                <i class="mdi mdi-content-copy"></i> Copy
                            </button>
                            <pre id="cron-cmd-3" class="p-3 rounded text-white font-monospace" style="background: #1e293b; font-size: 14px; margin: 0;">*/{{ $scheduler_interval }} * * * * curl -s "{{ $webCronUrl }}" > /dev/null 2>&1</pre>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Run Now Result Modal -->
<div class="modal fade" id="runNowModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" id="runNowModalTitle">Autopilot Status</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center p-4" id="runNowModalBody">
                <div class="spinner-border text-primary my-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="sr-only">Processing...</span>
                </div>
                <h5 class="mt-3">Generating News Article...</h5>
                <p class="text-muted">Fetching sources, generating text with Gemini, and creating banner image...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    // Copy code buttons
    document.querySelectorAll('.copy-code-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var text = document.getElementById(targetId).innerText;
            navigator.clipboard.writeText(text);
            var original = this.innerHTML;
            this.innerHTML = '<i class="mdi mdi-check"></i> Copied!';
            var self = this;
            setTimeout(function() { self.innerHTML = original; }, 2000);
        });
    });

    // Copy input button
    document.querySelectorAll('.copy-input-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var inputId = this.getAttribute('data-input');
            var input = document.getElementById(inputId);
            input.select();
            navigator.clipboard.writeText(input.value);
            var original = this.innerHTML;
            this.innerHTML = '<i class="mdi mdi-check"></i> Copied!';
            var self = this;
            setTimeout(function() { self.innerHTML = original; }, 2000);
        });
    });

    // Run Now AJAX
    document.getElementById('runNowBtn').addEventListener('click', function() {
        var modal = $('#runNowModal');
        var body = document.getElementById('runNowModalBody');
        
        body.innerHTML = `
            <div class="spinner-border text-primary my-3" role="status" style="width: 3rem; height: 3rem;">
                <span class="sr-only">Processing...</span>
            </div>
            <h5 class="mt-3">Generating News Article...</h5>
            <p class="text-muted">Fetching sources, querying Gemini, and generating banner image. This takes ~5-10 seconds.</p>
        `;
        modal.modal('show');

        fetch("{{ route('admin.settings.auto-scheduler.run-now') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                var article = data.article || {};
                body.innerHTML = `
                    <div class="text-success mb-3"><i class="mdi mdi-check-circle" style="font-size: 4rem;"></i></div>
                    <h5 class="text-success font-weight-bold">Success!</h5>
                    <p class="font-weight-bold">${article.title || 'Article Created'}</p>
                    <span class="badge badge-soft-primary p-2">${article.category ? article.category.name : 'Category'}</span>
                    <div class="mt-3">
                        <a href="/news/${article.slug}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="mdi mdi-eye mr-1"></i> View Article
                        </a>
                    </div>
                `;
            } else {
                body.innerHTML = `
                    <div class="text-danger mb-3"><i class="mdi mdi-alert-circle" style="font-size: 4rem;"></i></div>
                    <h5 class="text-danger font-weight-bold">Generation Failed</h5>
                    <p class="text-muted">${data.message || 'Unknown error'}</p>
                `;
            }
        })
        .catch(err => {
            body.innerHTML = `
                <div class="text-danger mb-3"><i class="mdi mdi-alert-circle" style="font-size: 4rem;"></i></div>
                <h5 class="text-danger font-weight-bold">Error</h5>
                <p class="text-muted">${err.message}</p>
            `;
        });
    });
</script>
@endpush
