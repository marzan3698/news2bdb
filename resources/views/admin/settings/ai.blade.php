@extends('layouts.admin')

@section('page_title', 'AI Integration Settings')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="mt-0 header-title">Google AI Studio (Gemini) Integration</h4>
                <p class="text-muted mb-4">Set up your Gemini API key to enable AI-powered features like text generation (e.g., Gemini 2.5 Flash / 3.5 Flash). Image generation uses the Nano Banana model.</p>

                @if(session('success'))
                    <div class="alert alert-success border-0">
                        <strong>Success!</strong> {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('admin.settings.ai.save') }}" method="POST" class="mb-4">
                    @csrf
                    
                    <!-- Nav Tabs -->
                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active font-weight-bold text-dark" data-toggle="tab" href="#gemini-tab" role="tab"><i class="mdi mdi-robot mr-1"></i> Google Gemini Settings</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold text-dark" data-toggle="tab" href="#image-mode-tab" role="tab"><i class="mdi mdi-image-multiple mr-1"></i> Image Mode</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold text-dark" data-toggle="tab" href="#facebook-tab" role="tab"><i class="mdi mdi-facebook mr-1"></i> Facebook Integration</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold text-dark" data-toggle="tab" href="#n8n-tab" role="tab"><i class="mdi mdi-webhook mr-1"></i> n8n Integration</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold text-dark" data-toggle="tab" href="#scheduler-tab" role="tab"><i class="mdi mdi-clock-outline mr-1"></i> Scheduler & Advanced</a>
                        </li>
                    </ul>

                    <!-- Tab Panes -->
                    <div class="tab-content">
                        <!-- Gemini Tab -->
                        <div class="tab-pane active" id="gemini-tab" role="tabpanel">
                            <div class="form-group mb-3">
                                <label for="gemini_api_key" class="font-weight-bold">Gemini API Key</label>
                                <input type="password" class="form-control" id="gemini_api_key" name="gemini_api_key" value="{{ $gemini_api_key }}" placeholder="Enter your Google AI Studio API Key">
                                <small class="form-text text-muted">Generate a key from Google AI Studio to let Gemini rewrite and translate news.</small>
                            </div>
                        </div>

                        <!-- Facebook Tab -->
                        <div class="tab-pane" id="facebook-tab" role="tabpanel">
                            <div class="form-group mb-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="facebook_enabled" name="facebook_enabled" value="1" {{ $facebook_enabled == '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold" for="facebook_enabled">Enable Facebook Page Feed Fetcher</label>
                                </div>
                                <small class="form-text text-muted">Toggle this on to index posts and download images from Facebook Pages as AI Sources.</small>
                            </div>

                            <div class="form-group mb-3">
                                <label for="facebook_app_id" class="font-weight-bold">Facebook App ID</label>
                                <input type="text" class="form-control" id="facebook_app_id" name="facebook_app_id" value="{{ $facebook_app_id }}" placeholder="Enter Facebook App ID">
                            </div>

                            <div class="form-group mb-3">
                                <label for="facebook_app_secret" class="font-weight-bold">Facebook App Secret</label>
                                <input type="password" class="form-control" id="facebook_app_secret" name="facebook_app_secret" value="{{ $facebook_app_secret }}" placeholder="Enter Facebook App Secret">
                            </div>

                            <div class="form-group mb-3">
                                <label for="facebook_page_access_token" class="font-weight-bold">Facebook Page Access Token</label>
                                <input type="password" class="form-control" id="facebook_page_access_token" name="facebook_page_access_token" value="{{ $facebook_page_access_token }}" placeholder="Enter Facebook Page Access Token">
                                <small class="form-text text-muted">A permanent Page Access Token with <code>pages_read_user_content</code> or page feed reading permissions.</small>
                            </div>
                        </div>

                        <!-- n8n Tab -->
                        <div class="tab-pane" id="n8n-tab" role="tabpanel">
                            <div class="form-group mb-3">
                                <label for="n8n_api_key" class="font-weight-bold">n8n API Key (Secret)</label>
                                <input type="password" class="form-control" id="n8n_api_key" name="n8n_api_key" value="{{ $n8n_api_key ?? '' }}" placeholder="Enter a secret key for n8n Webhook">
                                <small class="form-text text-muted">Set a strong secret key here. You will pass this key from your n8n workflow as a Header: <code>X-API-KEY</code> or as a query parameter <code>?api_key=</code>.</small>
                            </div>

                            <div class="alert border-0 mt-3" style="background:#e3f2fd;border-left:4px solid #2196f3 !important;">
                                <strong><i class="mdi mdi-webhook mr-1"></i> n8n Webhook URL:</strong>
                                <p class="mb-1 mt-2">Use this URL in your n8n HTTP Request node (Method: POST):</p>
                                <code class="d-block p-2 rounded" style="background:#263238;color:#80cbc4;font-size:13px;">{{ url('/api/n8n/generate') }}</code>
                                <small class="text-muted mt-1 d-block">You can optionally pass a category slug in the body: <code>{"category": "national"}</code>.</small>
                            </div>
                        </div>

                        <!-- Image Mode Tab -->
                        <div class="tab-pane" id="image-mode-tab" role="tabpanel">
                            <p class="text-muted mb-4">Choose how the AI autopilot handles news images. <strong>Real Image</strong> is the default — uses actual photos from your configured sources.</p>

                            <div class="row" id="image-mode-selector">

                                {{-- Real Image Mode --}}
                                <div class="col-md-4 mb-3">
                                    <label for="mode_real" class="image-mode-card d-block" data-mode="real"
                                        style="cursor:pointer;border-radius:10px;border:2px solid {{ $image_mode=='real' ? '#1da255' : '#dee2e6' }};padding:22px;background:{{ $image_mode=='real' ? '#f0fdf4' : '#fff' }};transition:all .2s;box-shadow:{{ $image_mode=='real' ? '0 0 0 3px rgba(29,162,85,0.2)' : 'none' }};">
                                        <input type="radio" name="image_mode" id="mode_real" value="real" {{ $image_mode=='real' ? 'checked' : '' }} style="display:none;">
                                        <div class="text-center mb-3" style="font-size:52px;">📷</div>
                                        <h5 class="font-weight-bold text-center mb-1" style="color:{{ $image_mode=='real' ? '#1da255' : '#333' }};">Real Image</h5>
                                        <p class="text-muted text-center mb-3" style="font-size:13px;line-height:1.5;">Use the original photo from the news source (RSS/Scraper). Most professional, news-like result.</p>
                                        <div class="text-center">
                                            <span class="badge px-3 py-1" style="background:{{ $image_mode=='real' ? '#1da255' : '#adb5bd' }};color:#fff;border-radius:20px;font-size:11px;">
                                                {{ $image_mode=='real' ? '✓ Active — Default' : 'Default' }}
                                            </span>
                                        </div>
                                    </label>
                                </div>

                                {{-- Auto Mode --}}
                                <div class="col-md-4 mb-3">
                                    <label for="mode_auto" class="image-mode-card d-block" data-mode="auto"
                                        style="cursor:pointer;border-radius:10px;border:2px solid {{ $image_mode=='auto' ? '#1da255' : '#dee2e6' }};padding:22px;background:{{ $image_mode=='auto' ? '#f0fdf4' : '#fff' }};transition:all .2s;box-shadow:{{ $image_mode=='auto' ? '0 0 0 3px rgba(29,162,85,0.2)' : 'none' }};">
                                        <input type="radio" name="image_mode" id="mode_auto" value="auto" {{ $image_mode=='auto' ? 'checked' : '' }} style="display:none;">
                                        <div class="text-center mb-3" style="font-size:52px;">🔄</div>
                                        <h5 class="font-weight-bold text-center mb-1" style="color:{{ $image_mode=='auto' ? '#1da255' : '#333' }};">Auto</h5>
                                        <p class="text-muted text-center mb-3" style="font-size:13px;line-height:1.5;">Try real source image first. If unavailable, automatically generate an AI cartoon illustration as fallback.</p>
                                        <div class="text-center">
                                            <span class="badge px-3 py-1" style="background:{{ $image_mode=='auto' ? '#1da255' : '#adb5bd' }};color:#fff;border-radius:20px;font-size:11px;">
                                                {{ $image_mode=='auto' ? '✓ Active' : 'Real + AI Fallback' }}
                                            </span>
                                        </div>
                                    </label>
                                </div>

                                {{-- Animation Mode --}}
                                <div class="col-md-4 mb-3">
                                    <label for="mode_animation" class="image-mode-card d-block" data-mode="animation"
                                        style="cursor:pointer;border-radius:10px;border:2px solid {{ $image_mode=='animation' ? '#1da255' : '#dee2e6' }};padding:22px;background:{{ $image_mode=='animation' ? '#f0fdf4' : '#fff' }};transition:all .2s;box-shadow:{{ $image_mode=='animation' ? '0 0 0 3px rgba(29,162,85,0.2)' : 'none' }};">
                                        <input type="radio" name="image_mode" id="mode_animation" value="animation" {{ $image_mode=='animation' ? 'checked' : '' }} style="display:none;">
                                        <div class="text-center mb-3" style="font-size:52px;">🎨</div>
                                        <h5 class="font-weight-bold text-center mb-1" style="color:{{ $image_mode=='animation' ? '#1da255' : '#333' }};">Animation</h5>
                                        <p class="text-muted text-center mb-3" style="font-size:13px;line-height:1.5;">Always generate an AI cartoon / vector illustration for each post. No real source images used.</p>
                                        <div class="text-center">
                                            <span class="badge px-3 py-1" style="background:{{ $image_mode=='animation' ? '#1da255' : '#adb5bd' }};color:#fff;border-radius:20px;font-size:11px;">
                                                {{ $image_mode=='animation' ? '✓ Active' : 'AI Generated Only' }}
                                            </span>
                                        </div>
                                    </label>
                                </div>

                            </div>

                            <div class="alert border-0 mt-2" style="background:#e8f5e9;border-left:4px solid #1da255 !important;">
                                <strong><i class="mdi mdi-information-outline mr-1"></i> Current Mode:</strong>
                                @if($image_mode == 'real')
                                    <span class="text-success font-weight-bold">📷 Real Image</span> — Only original source photos will be downloaded and used.
                                @elseif($image_mode == 'auto')
                                    <span class="text-primary font-weight-bold">🔄 Auto</span> — Real photos preferred; AI cartoon illustration used as fallback only.
                                @else
                                    <span style="color:#e67e22;" class="font-weight-bold">🎨 Animation</span> — All images are AI-generated cartoon illustrations.
                                @endif
                            </div>
                        </div>

                        <!-- Scheduler & Advanced Tab -->
                        <div class="tab-pane" id="scheduler-tab" role="tabpanel">
                            <h5 class="font-weight-bold mb-3"><i class="mdi mdi-clock-fast mr-1"></i> Server-Side Scheduler</h5>
                            <p class="text-muted mb-3">Enable automatic news generation without keeping the browser open. Requires a server cron job.</p>

                            <div class="form-group mb-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="scheduler_enabled" name="scheduler_enabled" value="1" {{ ($scheduler_enabled ?? '0') == '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold" for="scheduler_enabled">Enable Server Scheduler</label>
                                </div>
                                <small class="form-text text-muted">When enabled, the system will auto-generate news at the interval below (requires cron job on server).</small>
                            </div>

                            <div class="form-group mb-3">
                                <label for="scheduler_interval" class="font-weight-bold">Generation Interval</label>
                                <select class="form-control" id="scheduler_interval" name="scheduler_interval" style="max-width: 300px;">
                                    <option value="5" {{ ($scheduler_interval ?? '30') == '5' ? 'selected' : '' }}>Every 5 minutes</option>
                                    <option value="10" {{ ($scheduler_interval ?? '30') == '10' ? 'selected' : '' }}>Every 10 minutes</option>
                                    <option value="15" {{ ($scheduler_interval ?? '30') == '15' ? 'selected' : '' }}>Every 15 minutes</option>
                                    <option value="30" {{ ($scheduler_interval ?? '30') == '30' ? 'selected' : '' }}>Every 30 minutes</option>
                                    <option value="60" {{ ($scheduler_interval ?? '30') == '60' ? 'selected' : '' }}>Every 1 hour</option>
                                    <option value="120" {{ ($scheduler_interval ?? '30') == '120' ? 'selected' : '' }}>Every 2 hours</option>
                                </select>
                            </div>

                            <div class="alert border-0 mt-3" style="background:#fff3e0;border-left:4px solid #ff9800 !important;">
                                <strong><i class="mdi mdi-console mr-1"></i> Cron Job Setup:</strong>
                                <p class="mb-1 mt-2">Add this line to your server's crontab (cPanel → Cron Jobs):</p>
                                <code class="d-block p-2 rounded" style="background:#263238;color:#80cbc4;font-size:13px;">* * * * * cd /path/to/bdbnews && php artisan schedule:run >> /dev/null 2>&1</code>
                                <small class="text-muted mt-1 d-block">This runs Laravel's scheduler every minute. The actual generation interval is controlled by the setting above.</small>
                            </div>

                            <hr class="my-4">

                            <h5 class="font-weight-bold mb-3"><i class="mdi mdi-tune mr-1"></i> Advanced Settings</h5>

                            <div class="form-group mb-3">
                                <label for="gemini_model" class="font-weight-bold">Gemini Model for Text Generation</label>
                                <select class="form-control" id="gemini_model" name="gemini_model" style="max-width: 350px;">
                                    <option value="gemini-2.5-flash" {{ ($gemini_model ?? 'gemini-2.5-flash') == 'gemini-2.5-flash' ? 'selected' : '' }}>Gemini 2.5 Flash (Proven, Cost-effective)</option>
                                    <option value="gemini-3.5-flash" {{ ($gemini_model ?? 'gemini-2.5-flash') == 'gemini-3.5-flash' ? 'selected' : '' }}>Gemini 3.5 Flash (Latest, Best Quality)</option>
                                </select>
                                <small class="form-text text-muted">Choose the Gemini model for generating news text. 3.5 Flash is newer but may cost slightly more.</small>
                            </div>

                            <div class="form-group mb-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="use_grounding" name="use_grounding" value="1" {{ ($use_grounding ?? '1') == '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold" for="use_grounding">Enable Google Search Grounding</label>
                                </div>
                                <small class="form-text text-muted">When enabled, Gemini will verify facts using real-time Google Search data — reduces hallucination and improves accuracy.</small>
                            </div>
                        </div>

                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save"></i> Save Settings</button>
                        <button type="button" id="btn-test-gemini" class="btn btn-success ms-2"><i class="mdi mdi-flask"></i> Test Gemini API</button>
                    </div>
                </form>


                <!-- Test Result Modal -->
                <div class="modal fade" id="testResultModal" tabindex="-1" aria-labelledby="testResultModalLabel" aria-hidden="true" data-bs-backdrop="false" style="background-color: rgba(0,0,0,0.1);">
                  <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="testResultModalLabel">Gemini API Test Result</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                          <div id="test-loading" class="text-center py-5">
                              <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                  <span class="visually-hidden">Loading...</span>
                              </div>
                              <h5 class="mt-3 text-muted">Generating response from Gemini...</h5>
                          </div>
                          
                          <div id="test-success" style="display: none;">
                              <div class="card border mb-3 shadow-sm">
                                  <div class="card-body">
                                      <!-- News Heading -->
                                      <h3 id="test-heading" class="fw-bold text-dark mb-3" style="line-height: 1.4;"></h3>
                                      
                                      <!-- News Image -->
                                      <div class="text-center mb-4">
                                          <img id="test-image" src="" alt="News Image" class="img-fluid rounded w-100" style="max-height: 450px; object-fit: cover;">
                                      </div>
                                      
                                      <!-- News Body -->
                                      <div id="test-text" class="fs-5 text-dark" style="line-height: 1.6;"></div>
                                  </div>
                              </div>
                              <div class="alert alert-info border-0 mt-2" id="test-message"></div>
                          </div>
                
                          <div id="test-error" class="alert alert-danger border-0" style="display: none;"></div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      </div>
                    </div>
                  </div>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // --- Image Mode Card Selector ---
    const modeCards = document.querySelectorAll('.image-mode-card');
    modeCards.forEach(function(card) {
        card.addEventListener('click', function() {
            const mode = card.getAttribute('data-mode');
            // Deselect all cards
            modeCards.forEach(function(c) {
                c.style.border = '2px solid #dee2e6';
                c.style.background = '#fff';
                c.style.boxShadow = 'none';
                const h = c.querySelector('h5');
                const badge = c.querySelector('span.badge');
                if (h) h.style.color = '#333';
                if (badge) badge.style.background = '#adb5bd';
            });
            // Highlight selected
            card.style.border = '2px solid #1da255';
            card.style.background = '#f0fdf4';
            card.style.boxShadow = '0 0 0 3px rgba(29,162,85,0.2)';
            const h = card.querySelector('h5');
            const badge = card.querySelector('span.badge');
            if (h) h.style.color = '#1da255';
            if (badge) { badge.style.background = '#1da255'; badge.innerText = '✓ Active'; }
            // Check the hidden radio
            const radio = document.getElementById('mode_' + mode);
            if (radio) radio.checked = true;
        });
    });

    const btnTest = document.getElementById('btn-test-gemini');
    let testModal;
    
    // Check if Bootstrap is loaded and modal exists
    if (typeof bootstrap !== 'undefined') {
        testModal = new bootstrap.Modal(document.getElementById('testResultModal'), {
            backdrop: false
        });
    } else if(typeof window.$ !== 'undefined') {
        // Fallback for older bootstrap (jQuery)
        $('#testResultModal').modal({ backdrop: false, show: false });
        testModal = {
            show: function() { $('#testResultModal').modal('show'); },
            hide: function() { $('#testResultModal').modal('hide'); }
        };
    }

    if(btnTest) {
        btnTest.addEventListener('click', function() {
            if (testModal) {
                testModal.show();
            }
            
            // Show loading state
            document.getElementById('test-loading').style.display = 'block';
            document.getElementById('test-success').style.display = 'none';
            document.getElementById('test-error').style.display = 'none';
            
            fetch("{{ route('admin.settings.ai.test') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('test-loading').style.display = 'none';
                if(data.success) {
                    document.getElementById('test-success').style.display = 'block';
                    document.getElementById('test-heading').innerText = data.heading;
                    document.getElementById('test-text').innerText = data.text;
                    document.getElementById('test-image').src = data.image;
                    document.getElementById('test-message').innerText = data.message;
                } else {
                    document.getElementById('test-error').style.display = 'block';
                    document.getElementById('test-error').innerText = "Error: " + data.error;
                }
            })
            .catch(err => {
                document.getElementById('test-loading').style.display = 'none';
                document.getElementById('test-error').style.display = 'block';
                document.getElementById('test-error').innerText = "Network Error: " + err.message;
            });
        });
    }
});
</script>
@endpush
