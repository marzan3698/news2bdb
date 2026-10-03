@extends('layouts.admin')

@section('title', 'Video Workshop Settings & Diagnostics')
@section('page_header', 'Workshop Setup')
@section('page_title', 'ভিডিও ওয়ার্কশপ সেটিংস ও ডায়াগনস্টিক')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle mr-2"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    <!-- System Diagnostics Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card" style="border-radius: 12px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.06);">
                <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 font-weight-bold text-dark d-flex align-items-center">
                        <i class="mdi mdi-server-network text-primary mr-2" style="font-size: 22px;"></i>
                        <span>সার্ভার ও পরিবেশ ডায়াগনস্টিক (Live Server Diagnostics)</span>
                    </h5>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="retestDiagnosticsBtn">
                        <i class="mdi mdi-refresh mr-1"></i> পুনরায় টেস্ট করুন
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- FFmpeg Status -->
                        <div class="col-md-3 mb-3">
                            <div class="p-3 rounded border h-100 {{ $diagnostics['ffmpeg']['ok'] ? 'bg-soft-success border-success' : 'bg-soft-danger border-danger' }}">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="mdi {{ $diagnostics['ffmpeg']['ok'] ? 'mdi-check-circle text-success' : 'mdi-alert-circle text-danger' }} mr-2" style="font-size: 24px;"></i>
                                    <h6 class="mb-0 font-weight-bold">FFmpeg প্রসেসর</h6>
                                </div>
                                <div class="small">
                                    <strong>স্ট্যাটাস:</strong> 
                                    @if($diagnostics['ffmpeg']['ok'])
                                        <span class="badge badge-success">সক্রিয় ও প্রস্তুত</span>
                                    @else
                                        <span class="badge badge-danger">পাওয়া যায়নি</span>
                                    @endif
                                </div>
                                <div class="small mt-1 text-muted"><strong>পাথ:</strong> <code>{{ $diagnostics['ffmpeg']['path'] }}</code></div>
                                <div class="small text-truncate mt-1 text-muted" title="{{ $diagnostics['ffmpeg']['version'] }}"><strong>ভার্সন:</strong> {{ $diagnostics['ffmpeg']['version'] }}</div>
                            </div>
                        </div>

                        <!-- yt-dlp Status -->
                        <div class="col-md-3 mb-3">
                            <div class="p-3 rounded border h-100 {{ $diagnostics['ytdlp']['ok'] ? 'bg-soft-success border-success' : 'bg-soft-danger border-danger' }}">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="mdi {{ $diagnostics['ytdlp']['ok'] ? 'mdi-check-circle text-success' : 'mdi-alert-circle text-danger' }} mr-2" style="font-size: 24px;"></i>
                                    <h6 class="mb-0 font-weight-bold">yt-dlp ডাউনলোডার</h6>
                                </div>
                                <div class="small">
                                    <strong>স্ট্যাটাস:</strong> 
                                    @if($diagnostics['ytdlp']['ok'])
                                        <span class="badge badge-success">সক্রিয় ও প্রস্তুত</span>
                                    @else
                                        <span class="badge badge-danger">পাওয়া যায়নি</span>
                                    @endif
                                </div>
                                <div class="small mt-1 text-muted"><strong>পাথ:</strong> <code>{{ $diagnostics['ytdlp']['path'] }}</code></div>
                                <div class="small text-truncate mt-1 text-muted" title="{{ $diagnostics['ytdlp']['version'] }}"><strong>ভার্সন:</strong> {{ $diagnostics['ytdlp']['version'] }}</div>
                            </div>
                        </div>

                        <!-- Facebook Status -->
                        <div class="col-md-3 mb-3">
                            <div class="p-3 rounded border h-100 {{ $diagnostics['facebook']['ok'] ? 'bg-soft-success border-success' : 'bg-soft-warning border-warning' }}">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="mdi {{ $diagnostics['facebook']['ok'] ? 'mdi-facebook-box text-primary' : 'mdi-alert text-warning' }} mr-2" style="font-size: 24px;"></i>
                                    <h6 class="mb-0 font-weight-bold">ফেসবুক পেজ API</h6>
                                </div>
                                <div class="small">
                                    <strong>স্ট্যাটাস:</strong> 
                                    @if($diagnostics['facebook']['ok'])
                                        <span class="badge badge-success">সংযুক্ত</span>
                                    @else
                                        <span class="badge badge-warning">টোকেন প্রয়োজন</span>
                                    @endif
                                </div>
                                <div class="small mt-1 text-muted"><strong>পেজ:</strong> {{ $diagnostics['facebook']['page_name'] ?? 'নেই' }}</div>
                                <div class="small mt-1 text-muted"><strong>আইডি:</strong> {{ $diagnostics['facebook']['page_id'] ?? 'নেই' }}</div>
                            </div>
                        </div>

                        <!-- Storage Writable -->
                        <div class="col-md-3 mb-3">
                            <div class="p-3 rounded border h-100 {{ $diagnostics['storage']['writable'] ? 'bg-soft-success border-success' : 'bg-soft-danger border-danger' }}">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="mdi {{ $diagnostics['storage']['writable'] ? 'mdi-folder-check text-success' : 'mdi-folder-alert text-danger' }} mr-2" style="font-size: 24px;"></i>
                                    <h6 class="mb-0 font-weight-bold">স্টোরেজ ডিরেক্টরি</h6>
                                </div>
                                <div class="small">
                                    <strong>স্ট্যাটাস:</strong> 
                                    @if($diagnostics['storage']['writable'])
                                        <span class="badge badge-success">রাইট পারমিশন আছে</span>
                                    @else
                                        <span class="badge badge-danger">পারমিশন নেই</span>
                                    @endif
                                </div>
                                <div class="small mt-1 text-muted text-truncate" title="{{ $diagnostics['storage']['path'] }}"><strong>লোকেশন:</strong> storage/app/public/videos</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Configuration Settings Form -->
        <div class="col-lg-7">
            <div class="card" style="border-radius: 12px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.06);">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h5 class="mb-0 font-weight-bold text-dark">
                        <i class="mdi mdi-tune-vertical text-danger mr-2"></i>
                        ভিডিও ওয়ার্কশপ ডিফল্ট কনফিগারেশন
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.video-workshop.settings.save') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Trimming Defaults -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark">ডিফল্ট শুরুর ট্রিম (Intro Cut)</label>
                                    <div class="input-group">
                                        <input type="number" name="video_workshop_trim_start" class="form-control" value="{{ $trim_start }}" min="0" max="300" required>
                                        <div class="input-group-append"><span class="input-group-text">সেকেন্ড</span></div>
                                    </div>
                                    <small class="text-muted">ভিডিওর শুরুর কত সেকেন্ড স্বয়ংক্রিয়ভাবে বাদ যাবে (ডিফল্ট: ৫ সেকেন্ড)।</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark">ডিফল্ট শেষের ট্রিম (Outro Cut)</label>
                                    <div class="input-group">
                                        <input type="number" name="video_workshop_trim_end" class="form-control" value="{{ $trim_end }}" min="0" max="300" required>
                                        <div class="input-group-append"><span class="input-group-text">সেকেন্ড</span></div>
                                    </div>
                                    <small class="text-muted">ভিডিওর শেষের কত সেকেন্ড স্বয়ংক্রিয়ভাবে বাদ যাবে (ডিফল্ট: ১০ সেকেন্ড)।</small>
                                </div>
                            </div>
                        </div>

                        <!-- Branding Defaults -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark">ডিফল্ট ব্র্যান্ডিং স্টাইল</label>
                                    <select name="video_workshop_branding_type" class="form-control">
                                        <option value="both" {{ $branding_type == 'both' ? 'selected' : '' }}>লোগো ওয়াটারমার্ক + নিচের নিউজ স্ট্র্যাপ (উভয়ই)</option>
                                        <option value="watermark" {{ $branding_type == 'watermark' ? 'selected' : '' }}>শুধু বিডিবি নিউজ লোগো ওয়াটারমার্ক</option>
                                        <option value="banner" {{ $branding_type == 'banner' ? 'selected' : '' }}>শুধু নিচের ব্রেকিং নিউজ স্ট্র্যাপ বার</option>
                                        <option value="none" {{ $branding_type == 'none' ? 'selected' : '' }}>কোনো ব্র্যান্ডিং নয়</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark">লোগো ওয়াটারমার্কের অবস্থান</label>
                                    <select name="video_workshop_watermark_pos" class="form-control">
                                        <option value="top_right" {{ $watermark_pos == 'top_right' ? 'selected' : '' }}>উপরের ডান কোণে (Top-Right)</option>
                                        <option value="top_left" {{ $watermark_pos == 'top_left' ? 'selected' : '' }}>উপরের বাম কোণে (Top-Left)</option>
                                        <option value="bottom_right" {{ $watermark_pos == 'bottom_right' ? 'selected' : '' }}>নিচের ডান কোণে (Bottom-Right)</option>
                                        <option value="bottom_left" {{ $watermark_pos == 'bottom_left' ? 'selected' : '' }}>নিচের বাম কোণে (Bottom-Left)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-dark">নিচের ব্র্যান্ডিং ব্যানার টেক্সট</label>
                            <input type="text" name="video_workshop_branding_text" class="form-control" value="{{ $branding_text }}">
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-dark">কাস্টম ওয়াটারমার্ক লোগো (Upload Transparent PNG)</label>
                            <input type="file" name="watermark_file" class="form-control-file" accept="image/png,image/webp,image/svg+xml">
                            <small class="text-muted">স্বচ্ছ ব্যাকগ্রাউন্ডের (Transparent PNG) লোগো আপলোড করুন। খালি রাখলে সাইটের ডিফল্ট BDB News লোগো ব্যবহৃত হবে।</small>
                        </div>

                        <hr>

                        <!-- Binary Paths (Advanced) -->
                        <h6 class="font-weight-bold text-dark mb-2">
                            <i class="mdi mdi-powershell text-primary mr-1"></i> বাইনারি পাথ কনফিগারেশন (উন্নত)
                        </h6>

                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-dark">FFmpeg পাথ (FFmpeg Binary Path)</label>
                            <input type="text" name="video_workshop_ffmpeg_path" class="form-control" placeholder="অটো-ডিটেক্ট করতে খালি রাখুন (যেমন: /usr/bin/ffmpeg অথবা ffmpeg)" value="{{ $ffmpeg_path }}">
                            <small class="text-muted">খালি রাখলে সিস্টেম স্বয়ংক্রিয়ভাবে <code>/usr/bin/ffmpeg</code> অথবা PATH থেকে ডিটেক্ট করবে।</small>
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-dark">yt-dlp পাথ (yt-dlp / Python Path)</label>
                            <input type="text" name="video_workshop_ytdlp_path" class="form-control" placeholder="অটো-ডিটেক্ট করতে খালি রাখুন (যেমন: /usr/bin/yt-dlp অথবা python3 -m yt_dlp)" value="{{ $ytdlp_path }}">
                            <small class="text-muted">ইউটিউব ভিডিও ডাউনলোডের জন্য। খালি রাখলে স্বয়ংক্রিয়ভাবে ডিটেক্ট হবে।</small>
                        </div>

                        <div class="custom-control custom-checkbox mb-4">
                            <input type="checkbox" name="video_workshop_auto_fb" class="custom-control-input" id="autoFbCheck" value="1" {{ $auto_fb == '1' ? 'checked' : '' }}>
                            <label class="custom-control-label font-weight-bold text-primary" for="autoFbCheck">
                                সর্বদা ডিফল্টভাবে ফেসবুকে অটো-পোস্ট সক্রিয় রাখুন
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block py-2 font-weight-bold">
                            <i class="mdi mdi-content-save mr-1"></i> সেটিংস সংরক্ষণ করুন
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- cPanel Server Setup Instructions Card -->
        <div class="col-lg-5">
            <div class="card" style="border-radius: 12px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.06); background: #fdfdfe;">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h5 class="mb-0 font-weight-bold text-dark">
                        <i class="mdi mdi-server text-info mr-2"></i>
                        cPanel লাইভ সার্ভার সেটআপ গাইড
                    </h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        বিডিবি নিউজের লাইভ cPanel সার্ভারে ভিডিও প্রসেসিং সর্বোচ্চ গতিতে কার্যকর করতে নিচের নির্দেশিকা অনুসরণ করুন:
                    </p>

                    <div class="accordion" id="cpanelGuideAccordion">
                        <!-- Step 1: php.ini limits -->
                        <div class="card border mb-2" style="border-radius: 8px;">
                            <div class="card-header p-2 bg-light" id="headingOne">
                                <h6 class="mb-0">
                                    <button class="btn btn-link btn-block text-left text-dark font-weight-bold py-1" type="button" data-toggle="collapse" data-target="#collapseOne">
                                        ১. cPanel-এ PHP Time & Upload Limit বৃদ্ধি
                                    </button>
                                </h6>
                            </div>
                            <div id="collapseOne" class="collapse show" data-parent="#cpanelGuideAccordion">
                                <div class="card-body p-3 small text-muted">
                                    cPanel-এর <strong>MultiPHP INI Editor</strong>-এ গিয়ে আপনার ডোমেইনের জন্য নিচের মানগুলো নিশ্চিত করুন:
                                    <ul class="mb-0 mt-2 pl-3">
                                        <li><code>max_execution_time = 300</code></li>
                                        <li><code>memory_limit = 512M</code></li>
                                        <li><code>upload_max_filesize = 128M</code></li>
                                        <li><code>post_max_size = 128M</code></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: FFmpeg on cPanel -->
                        <div class="card border mb-2" style="border-radius: 8px;">
                            <div class="card-header p-2 bg-light" id="headingTwo">
                                <h6 class="mb-0">
                                    <button class="btn btn-link btn-block text-left text-dark font-weight-bold py-1 collapsed" type="button" data-toggle="collapse" data-target="#collapseTwo">
                                        ২. cPanel-এ FFmpeg সেটআপ (Root ছাড়া)
                                    </button>
                                </h6>
                            </div>
                            <div id="collapseTwo" class="collapse" data-parent="#cpanelGuideAccordion">
                                <div class="card-body p-3 small text-muted">
                                    বেশিরভাগ হোস্টিংয়ে <code>/usr/bin/ffmpeg</code> আগে থেকেই থাকে। যদি না থাকে, রুট পারমিশন ছাড়াই cPanel Terminal-এ নিচের ২টি কমান্ড দিয়ে স্ট্যাটিক লিনাক্স FFmpeg ইনস্টল করা যায়:
                                    <pre class="bg-dark text-white p-2 rounded mt-2 mb-0" style="font-size: 11px;">
mkdir -p ~/bin && cd ~/bin
curl -s https://johnvansickle.com/ffmpeg/releases/ffmpeg-release-amd64-static.tar.xz | tar -xJ --strip-components=1
chmod +x ~/bin/ffmpeg
                                    </pre>
                                    এরপর ওয়ার্কশপ সেটিংসে FFmpeg পাথ দিন: <code>/home/YOUR_CPANEL_USER/bin/ffmpeg</code>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: yt-dlp on cPanel -->
                        <div class="card border mb-2" style="border-radius: 8px;">
                            <div class="card-header p-2 bg-light" id="headingThree">
                                <h6 class="mb-0">
                                    <button class="btn btn-link btn-block text-left text-dark font-weight-bold py-1 collapsed" type="button" data-toggle="collapse" data-target="#collapseThree">
                                        ৩. cPanel-এ yt-dlp সেটআপ
                                    </button>
                                </h6>
                            </div>
                            <div id="collapseThree" class="collapse" data-parent="#cpanelGuideAccordion">
                                <div class="card-body p-3 small text-muted">
                                    cPanel Terminal বা SSH-এ মাত্র ১টি কমান্ড দিয়ে yt-dlp ডাউনলোড করা যায়:
                                    <pre class="bg-dark text-white p-2 rounded mt-2 mb-0" style="font-size: 11px;">
mkdir -p ~/bin
curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o ~/bin/yt-dlp
chmod +x ~/bin/yt-dlp
                                    </pre>
                                    এরপর ওয়ার্কশপ সেটিংসে yt-dlp পাথ দিন: <code>/home/YOUR_CPANEL_USER/bin/yt-dlp</code>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Facebook Page Token -->
                        <div class="card border mb-2" style="border-radius: 8px;">
                            <div class="card-header p-2 bg-light" id="headingFour">
                                <h6 class="mb-0">
                                    <button class="btn btn-link btn-block text-left text-dark font-weight-bold py-1 collapsed" type="button" data-toggle="collapse" data-target="#collapseFour">
                                        ৪. ফেসবুক পেজ ভিডিও আপলোড পারমিশন
                                    </button>
                                </h6>
                            </div>
                            <div id="collapseFour" class="collapse" data-parent="#cpanelGuideAccordion">
                                <div class="card-body p-3 small text-muted">
                                    ফেসবুকে ভিডিও পোস্ট হতে আপনার Facebook Page Access Token-এ নিচের পারমিশনগুলো থাকতে হবে:
                                    <ul class="mb-2 mt-1 pl-3">
                                        <li><code>pages_manage_posts</code></li>
                                        <li><code>pages_read_engagement</code></li>
                                    </ul>
                                    <a href="{{ route('admin.settings.facebook') }}" class="btn btn-sm btn-outline-primary font-weight-bold">
                                        <i class="mdi mdi-facebook mr-1"></i> ফেসবুক সেটিংস পেজে যান
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('retestDiagnosticsBtn').addEventListener('click', function() {
        const btn = this;
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="mdi mdi-loading mdi-spin mr-1"></i> টেস্ট হচ্ছে...';
        btn.disabled = true;

        fetch('{{ route("admin.video-workshop.diagnostics") }}')
            .then(res => res.json())
            .then(data => {
                location.reload();
            })
            .catch(err => {
                btn.innerHTML = orig;
                btn.disabled = false;
                alert('ডায়াগনস্টিক টেস্টে ত্রুটি: ' + err);
            });
    });
</script>
@endpush
