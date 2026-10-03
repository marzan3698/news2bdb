@extends('layouts.admin')

@section('title', 'Video Workshop Tool - ভিডিও এডিটিং ও ব্র্যান্ডিং')
@section('page_header', 'Video Workshop Studio')
@section('page_title', 'ভিডিও ওয়ার্কশপ টুল')

@push('css')
<style>
    .workshop-card {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .workshop-card:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.09);
    }
    .stat-gradient-1 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }
    .stat-gradient-2 { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: #fff; }
    .stat-gradient-3 { background: linear-gradient(135deg, #1877F2 0%, #0c4a96 100%); color: #fff; }
    .stat-gradient-4 { background: linear-gradient(135deg, #ff9966 0%, #ff5e62 100%); color: #fff; }

    .trim-visual-bar {
        height: 24px;
        background: #e2e8f0;
        border-radius: 6px;
        overflow: hidden;
        display: flex;
        position: relative;
        margin: 12px 0;
    }
    .trim-cut-start {
        background: repeating-linear-gradient(45deg, #f87171, #f87171 8px, #ef4444 8px, #ef4444 16px);
        color: white;
        font-size: 11px;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: width 0.3s;
    }
    .trim-keep-area {
        background: linear-gradient(90deg, #10b981, #059669);
        color: white;
        font-size: 11px;
        font-weight: bold;
        flex-grow: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .trim-cut-end {
        background: repeating-linear-gradient(45deg, #f87171, #f87171 8px, #ef4444 8px, #ef4444 16px);
        color: white;
        font-size: 11px;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: width 0.3s;
    }

    .video-thumb-preview {
        width: 100%;
        max-height: 210px;
        object-fit: cover;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        background: #0f172a;
    }

    .bulletin-pill {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 14px;
        background: #fff;
        transition: all 0.2s;
        cursor: pointer;
    }
    .bulletin-pill:hover {
        border-color: #e94057;
        background: #fff5f6;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(233, 64, 87, 0.15);
    }
    .badge-brand-bdb {
        background: linear-gradient(135deg, #8A2387, #E94057, #F27121);
        color: white;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
    }
    .badge-fb-posted {
        background: #1877F2;
        color: white;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Notifications -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle mr-2"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert mr-2"></i> {{ session('warning') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle mr-2"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    <!-- Top Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card workshop-card stat-gradient-1">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1" style="opacity: 0.85; font-size: 12px;">মোট ভিডিও টাস্ক</h6>
                        <h3 class="mb-0 font-weight-bold">{{ $totalCount }}</h3>
                    </div>
                    <i class="mdi mdi-movie-filter" style="font-size: 40px; opacity: 0.4;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card workshop-card stat-gradient-2">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1" style="opacity: 0.85; font-size: 12px;">ব্র্যান্ডিং সম্পন্ন</h6>
                        <h3 class="mb-0 font-weight-bold">{{ $completedCount }}</h3>
                    </div>
                    <i class="mdi mdi-check-decagram" style="font-size: 40px; opacity: 0.4;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card workshop-card stat-gradient-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1" style="opacity: 0.85; font-size: 12px;">ফেসবুকে আপলোড</h6>
                        <h3 class="mb-0 font-weight-bold">{{ $facebookCount }}</h3>
                    </div>
                    <i class="mdi mdi-facebook" style="font-size: 40px; opacity: 0.4;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card workshop-card stat-gradient-4">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1" style="opacity: 0.85; font-size: 12px;">প্রসেসিং চলছে</h6>
                        <h3 class="mb-0 font-weight-bold">{{ $processingCount }}</h3>
                    </div>
                    <i class="mdi mdi-progress-clock" style="font-size: 40px; opacity: 0.4;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Workshop Studio Card -->
    <div class="row">
        <div class="col-12">
            <div class="card workshop-card mb-4" style="background: #ffffff; border-top: 4px solid #E94057;">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center border-bottom py-3">
                    <h5 class="mb-0 font-weight-bold text-dark d-flex align-items-center">
                        <i class="mdi mdi-movie-edit text-danger mr-2" style="font-size: 22px;"></i>
                        <span>ভিডিও ওয়ার্কশপ স্টুডিও (YouTube Download &bull; Trim &bull; BDB News Branding &bull; Facebook Auto-Post)</span>
                    </h5>
                    <a href="{{ route('admin.video-workshop.settings') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="mdi mdi-cog mr-1"></i> ওয়ার্কশপ সেটিংস
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.video-workshop.store') }}" method="POST" id="workshopForm">
                        @csrf
                        <input type="hidden" name="duration_seconds" id="durationSecondsInput" value="180">
                        <input type="hidden" name="thumbnail_url" id="thumbnailUrlInput" value="">
                        <input type="hidden" name="channel_name" id="channelNameInput" value="">

                        <!-- Step 1: YouTube URL Input -->
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold text-dark">
                                        <i class="mdi mdi-youtube text-danger mr-1" style="font-size: 18px;"></i>
                                        ইউটিউব ভিডিও লিংক (YouTube Video URL) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="url" name="source_url" id="sourceUrlInput" class="form-control form-control-lg" placeholder="https://www.youtube.com/watch?v=... অথবা https://youtu.be/..." required>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-danger px-4" id="fetchInfoBtn">
                                                <i class="mdi mdi-magnify mr-1"></i> তথ্য আনুন
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">যেকোনো ইউটিউব ভিডিও বা বুলেটিনের লিংক পেস্ট করলেই অটোমেটিক টাইটেল, থাম্বনেইল ও সময় লোড হবে।</small>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="font-weight-bold text-dark">ভিডিও টাইটেল / শিরোনাম <span class="text-danger">*</span></label>
                                    <input type="text" name="title" id="videoTitleInput" class="form-control font-weight-bold text-dark" placeholder="ভিডিওর শিরোনাম লোড হবে..." required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold text-dark">সংবাদ ক্যাটাগরি</label>
                                            <select name="category" class="form-control">
                                                @foreach($categories as $cat)
                                                    <option value="{{ $cat->name }}" {{ $cat->name == 'জাতীয়' ? 'selected' : '' }}>{{ $cat->name }}</option>
                                                @endforeach
                                                <option value="ভিডিও">ভিডিও</option>
                                                <option value="রাজনীতি">রাজনীতি</option>
                                                <option value="আন্তর্জাতিক">আন্তর্জাতিক</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold text-dark">ব্র্যান্ডিং স্টাইল</label>
                                            <select name="branding_type" id="brandingTypeSelect" class="form-control">
                                                <option value="both" {{ $defaultBrandingType == 'both' ? 'selected' : '' }}>লোগো ওয়াটারমার্ক + নিচের নিউজ স্ট্র্যাপ (উভয়ই - প্রস্তাবিত)</option>
                                                <option value="watermark" {{ $defaultBrandingType == 'watermark' ? 'selected' : '' }}>শুধু বিডিবি নিউজ লোগো ওয়াটারমার্ক</option>
                                                <option value="banner" {{ $defaultBrandingType == 'banner' ? 'selected' : '' }}>শুধু নিচের ব্রেকিং নিউজ স্ট্র্যাপ বার</option>
                                                <option value="none">কোনো ব্র্যান্ডিং নয় (শুধু ট্রিম)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Live Preview Column -->
                            <div class="col-lg-4">
                                <div class="card bg-light border-0 p-3 text-center" style="border-radius: 12px;">
                                    <h6 class="font-weight-bold text-muted mb-2 text-left">
                                        <i class="mdi mdi-play-box-outline mr-1"></i> ভিডিও প্রিভিউ
                                    </h6>
                                    <div id="previewContainer" style="position: relative;">
                                        <img id="previewImage" src="{{ asset('admin-assets/images/bg-1.jpg') }}" alt="Preview" class="video-thumb-preview">
                                        <!-- Simulated Watermark Overlay -->
                                        <div id="simulatedWatermark" style="position: absolute; top: 12px; right: 12px; background: rgba(15,23,42,0.85); padding: 4px 8px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.2); display: flex; align-items: center;">
                                            <span style="color: #ef4444; font-weight: bold; margin-right: 4px;">●</span>
                                            <span style="color: #fff; font-size: 11px; font-weight: bold; letter-spacing: 0.5px;">BDB NEWS</span>
                                        </div>
                                        <!-- Simulated Bottom Banner -->
                                        <div id="simulatedBanner" style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.8); border-top: 2px solid #ef4444; padding: 4px 8px; text-align: left;">
                                            <span style="color: #fff; font-size: 10px; font-weight: bold;">BDB NEWS &bull; সত্যের সন্ধানে সার্বক্ষণিক</span>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-left">
                                        <small class="text-muted d-block" id="channelInfoText">চ্যানেল: তথ্য নেই</small>
                                        <small class="text-muted d-block" id="durationInfoText">সময়কাল: --:--</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Trimming Studio (Cut Intro & Outro) -->
                        <div class="card mt-2 p-3" style="background: #f8fafc; border-radius: 10px; border: 1px dashed #cbd5e1;">
                            <h6 class="font-weight-bold text-dark mb-2 d-flex align-items-center">
                                <i class="mdi mdi-content-cut text-danger mr-2" style="font-size: 20px;"></i>
                                <span>ভিডিও ট্রিম ও অবাঞ্ছিত অংশ ছাঁটাই (Trimming Studio)</span>
                            </h6>
                            <p class="text-muted small mb-2">
                                ইউটিউব ভিডিওর শুরুর ইন্ট্রো এবং শেষের আউট্রো/ক্রেডিট স্বয়ংক্রিয়ভাবে বাদ দিতে সেকেন্ড নির্ধারণ করুন:
                            </p>

                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <label class="font-weight-bold small text-danger">
                                        <i class="mdi mdi-skip-forward mr-1"></i> শুরুর অংশ বাদ দিন (Intro Cut)
                                    </label>
                                    <div class="input-group">
                                        <input type="number" name="trim_start" id="trimStartInput" class="form-control font-weight-bold text-danger text-center" min="0" max="300" value="{{ $defaultTrimStart }}" required>
                                        <div class="input-group-append"><span class="input-group-text">সেকেন্ড</span></div>
                                    </div>
                                    <small class="text-muted">প্রথম {{ $defaultTrimStart }} সেকেন্ড বাদ যাবে</small>
                                </div>

                                <div class="col-md-4">
                                    <label class="font-weight-bold small text-danger">
                                        <i class="mdi mdi-skip-backward mr-1"></i> শেষের অংশ বাদ দিন (Outro Cut)
                                    </label>
                                    <div class="input-group">
                                        <input type="number" name="trim_end" id="trimEndInput" class="form-control font-weight-bold text-danger text-center" min="0" max="300" value="{{ $defaultTrimEnd }}" required>
                                        <div class="input-group-append"><span class="input-group-text">সেকেন্ড</span></div>
                                    </div>
                                    <small class="text-muted">শেষের {{ $defaultTrimEnd }} সেকেন্ড বাদ যাবে</small>
                                </div>

                                <div class="col-md-4">
                                    <div class="p-2 text-center" style="background: #fff; border-radius: 8px; border: 1px solid #e2e8f0;">
                                        <div class="small text-muted font-weight-bold">এডিটের পর মূল ভিডিওর দৈর্ঘ্য</div>
                                        <div class="h5 mb-0 font-weight-bold text-success" id="netDurationDisplay">--:--</div>
                                        <small class="text-muted" id="trimCalculationText">হিসাব করা হচ্ছে...</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Visual Timeline Bar -->
                            <div class="trim-visual-bar mt-3">
                                <div class="trim-cut-start" id="trimVisualStart" style="width: 8%;">কাটা অংশ</div>
                                <div class="trim-keep-area" id="trimVisualKeep">বিডিবি নিউজ ভিডিও থাকবে (মূল কনটেন্ট)</div>
                                <div class="trim-cut-end" id="trimVisualEnd" style="width: 12%;">কাটা অংশ</div>
                            </div>
                        </div>

                        <!-- Step 3: Branding & Facebook Auto-Post Options -->
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label class="font-weight-bold small text-dark">লোগো ওয়াটারমার্কের অবস্থান</label>
                                    <select name="watermark_position" id="watermarkPosSelect" class="form-control">
                                        <option value="top_right" selected>উপরের ডান কোণে (Top-Right - প্রস্তাবিত)</option>
                                        <option value="top_left">উপরের বাম কোণে (Top-Left)</option>
                                        <option value="bottom_right">নিচের ডান কোণে (Bottom-Right)</option>
                                        <option value="bottom_left">নিচের বাম কোণে (Bottom-Left)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label class="font-weight-bold small text-dark">নিচের ব্যানার স্ট্র্যাপ লেখা</label>
                                    <input type="text" name="branding_text" class="form-control" value="BDB NEWS • সত্যের সন্ধানে সার্বক্ষণিক">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card p-3 mb-2" style="background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 8px;">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="auto_post_facebook" class="custom-control-input" id="autoPostFbCheck" value="1" {{ $defaultAutoFb ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-bold text-primary" for="autoPostFbCheck">
                                            <i class="mdi mdi-facebook mr-1"></i> প্রসেসিং শেষে ফেসবুকে অটো-পোস্ট করুন
                                        </label>
                                    </div>
                                    <small class="text-muted">টিক দেওয়া থাকলে ভিডিও তৈরি হওয়ামাত্র ফেসবুক পেজে স্বয়ংক্রিয়ভাবে ভিডিও আপলোড হয়ে যাবে।</small>
                                </div>
                            </div>
                        </div>

                        <!-- Action Submit Button -->
                        <div class="mt-4 d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                <i class="mdi mdi-information-outline mr-1"></i> ক্লিক করলে স্বয়ংক্রিয়ভাবে ব্যাকগ্রাউন্ডে ভিডিও ডাউনলোড, ট্রিম ও ব্র্যান্ডিং এক্সপোর্ট শুরু হবে।
                            </div>
                            <button type="submit" class="btn btn-lg font-weight-bold px-4 text-white" id="submitBtn" style="background: linear-gradient(135deg, #8A2387 0%, #E94057 50%, #F27121 100%); border: none; box-shadow: 0 4px 15px rgba(233, 64, 87, 0.4);">
                                <i class="mdi mdi-play-circle-outline mr-1"></i> ডাউনলোড, ট্রিম ও ব্র্যান্ডিং শুরু করুন
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick 1-Click Import from Live TV News Bulletins -->
    @if(count($liveNewsBulletins) > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card workshop-card">
                <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 font-weight-bold text-dark d-flex align-items-center">
                        <i class="mdi mdi-television-guide text-danger mr-2" style="font-size: 20px;"></i>
                        <span>শীর্ষ টিভি চ্যানেলের লাইভ বুলেটিন থেকে সহজে নির্বাচন করুন (Quick 1-Click Import)</span>
                    </h6>
                    <span class="badge badge-soft-danger font-weight-bold">Live YouTube News</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($liveNewsBulletins as $bulletin)
                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="bulletin-pill h-100 d-flex flex-column justify-content-between" onclick="pickBulletin('{{ addslashes($bulletin['url']) }}', '{{ addslashes($bulletin['title']) }}', '{{ addslashes($bulletin['thumbnail']) }}', '{{ addslashes($bulletin['channel']) }}')">
                                    <div>
                                        <div style="position: relative;">
                                            <img src="{{ $bulletin['thumbnail'] }}" alt="Thumb" class="w-100 rounded mb-2" style="height: 120px; object-fit: cover;">
                                            <span class="badge badge-dark" style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.8);">{{ $bulletin['channel'] }}</span>
                                        </div>
                                        <h6 class="font-weight-bold text-dark mb-1" style="font-size: 13px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            {{ $bulletin['title'] }}
                                        </h6>
                                    </div>
                                    <button type="button" class="btn btn-outline-danger btn-sm btn-block mt-2 font-weight-bold">
                                        <i class="mdi mdi-arrow-up-bold mr-1"></i> এই ভিডিওটি স্টুডিওতে নিন
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Processed Videos Library Table -->
    <div class="row">
        <div class="col-12">
            <div class="card workshop-card">
                <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 font-weight-bold text-dark">
                        <i class="mdi mdi-movie-roll text-primary mr-2" style="font-size: 22px;"></i>
                        <span>ওয়ার্কশপ ভিডিও হিস্টোরি ও লাইব্রেরি (Workshop Video Library)</span>
                    </h5>
                    <div class="d-flex align-items-center">
                        <a href="{{ route('admin.video-workshop.index') }}" class="btn btn-outline-secondary btn-sm mr-2">
                            <i class="mdi mdi-refresh"></i> রিফ্রেশ
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>ভিডিও তথ্য</th>
                                    <th>ক্যাটাগরি</th>
                                    <th>ট্রিম বিবরণ</th>
                                    <th>প্রসেসিং স্ট্যাটাস</th>
                                    <th>ফেসবুক পোস্ট</th>
                                    <th class="text-right" style="width: 220px;">অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($videos as $vid)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($vid->thumbnail_url)
                                                    <img src="{{ $vid->thumbnail_url }}" alt="Thumb" class="rounded mr-3" style="width: 70px; height: 45px; object-fit: cover; background: #000;">
                                                @else
                                                    <div class="rounded mr-3 bg-secondary d-flex align-items-center justify-content-center" style="width: 70px; height: 45px; color: white;">
                                                        <i class="mdi mdi-video"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <a href="{{ $vid->source_url }}" target="_blank" class="font-weight-bold text-dark" style="font-size: 14px; text-decoration: none;">
                                                        {{ Str::limit($vid->title, 50) }}
                                                    </a>
                                                    <div class="text-muted small">
                                                        <span><i class="mdi mdi-youtube text-danger"></i> {{ $vid->channel_name ?: 'YouTube' }}</span>
                                                        <span class="mx-1">&bull;</span>
                                                        <span><i class="mdi mdi-clock-outline"></i> {{ $vid->formatted_duration }}</span>
                                                        <span class="mx-1">&bull;</span>
                                                        <span>{{ $vid->created_at->diffForHumans() }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-primary font-weight-bold">{{ $vid->category }}</span>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <span class="text-danger font-weight-bold">-{{ $vid->trim_start }}s (ইন্ট্রো)</span> /
                                                <span class="text-danger font-weight-bold">-{{ $vid->trim_end }}s (আউট্রো)</span>
                                            </div>
                                            <small class="text-success font-weight-bold">
                                                চূড়ান্ত: {{ $vid->formatted_net_duration }}
                                            </small>
                                        </td>
                                        <td>
                                            @if($vid->status === 'completed')
                                                <span class="badge badge-success px-2 py-1"><i class="mdi mdi-check"></i> ব্র্যান্ডিং সম্পন্ন</span>
                                            @elseif($vid->status === 'downloading')
                                                <span class="badge badge-info px-2 py-1"><i class="mdi mdi-loading mdi-spin"></i> ডাউনলোড হচ্ছে</span>
                                            @elseif($vid->status === 'processing')
                                                <span class="badge badge-warning px-2 py-1"><i class="mdi mdi-loading mdi-spin"></i> প্রসেসিং চলছে</span>
                                            @elseif($vid->status === 'failed')
                                                <span class="badge badge-danger px-2 py-1" title="{{ $vid->error_message }}"><i class="mdi mdi-alert-circle"></i> ব্যর্থ</span>
                                            @else
                                                <span class="badge badge-secondary px-2 py-1">অপেক্ষমাণ</span>
                                            @endif

                                            @if($vid->error_message)
                                                <div class="text-danger small mt-1" style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $vid->error_message }}">
                                                    {{ $vid->error_message }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($vid->facebook_status === 'posted')
                                                <a href="{{ $vid->facebook_video_url }}" target="_blank" class="badge badge-fb-posted">
                                                    <i class="mdi mdi-facebook"></i> লাইভ ফেসবুকে
                                                </a>
                                            @elseif($vid->facebook_status === 'posting')
                                                <span class="badge badge-warning"><i class="mdi mdi-loading mdi-spin"></i> আপলোড হচ্ছে</span>
                                            @elseif($vid->facebook_status === 'failed')
                                                <span class="badge badge-danger" title="{{ $vid->facebook_error }}"><i class="mdi mdi-alert"></i> পোস্ট ব্যর্থ</span>
                                            @else
                                                <form action="{{ route('admin.video-workshop.publish-fb', $vid->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-primary btn-sm py-0 px-2" {{ $vid->status !== 'completed' ? 'disabled' : '' }}>
                                                        <i class="mdi mdi-facebook"></i> পোস্ট করুন
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @if($vid->processed_video_path)
                                                <!-- Play Modal Button -->
                                                <button type="button" class="btn btn-sm btn-info" onclick="openPlayerModal('{{ $vid->processed_video_url }}', '{{ addslashes($vid->title) }}')" title="ভিডিও দেখুন">
                                                    <i class="mdi mdi-play"></i>
                                                </button>
                                                <!-- Download MP4 -->
                                                <a href="{{ $vid->processed_video_url }}" download class="btn btn-sm btn-secondary" title="ডাউনলোড MP4">
                                                    <i class="mdi mdi-download"></i>
                                                </a>
                                            @endif

                                            @if($vid->status !== 'completed')
                                                <form action="{{ route('admin.video-workshop.process', $vid->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-warning" title="পুনরায় প্রসেস করুন">
                                                        <i class="mdi mdi-autorenew"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if(!$vid->article_id && $vid->status === 'completed')
                                                <form action="{{ route('admin.video-workshop.create-article', $vid->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="সাইটে নিউজ আর্টিকেল তৈরি করুন">
                                                        <i class="mdi mdi-newspaper"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            <form action="{{ route('admin.video-workshop.destroy', $vid->id) }}" method="POST" class="d-inline" onsubmit="return confirm('আপনি কি নিশ্চিত যে এই ভিডিওটি মুছে ফেলতে চান?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="মুছে ফেলুন">
                                                    <i class="mdi mdi-trash-can-outline"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="mdi mdi-movie-outline" style="font-size: 48px; opacity: 0.3;"></i>
                                            <h6 class="mt-3">এখনও কোনো ভিডিও টাস্ক নেই।</h6>
                                            <p class="small">উপরের ইনপুট বক্সে ইউটিউব ভিডিও লিংক পেস্ট করে ব্র্যান্ডিং শুরু করুন!</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($videos->hasPages())
                        <div class="p-3">
                            {{ $videos->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Video Player -->
<div class="modal fade" id="videoPlayerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content" style="background: #0f172a; border-radius: 12px; overflow: hidden; border: none;">
            <div class="modal-header border-0 py-2 px-3 d-flex justify-content-between align-items-center">
                <h6 class="modal-title text-white font-weight-bold" id="modalVideoTitle">ভিডিও প্রিভিউ</h6>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" onclick="closePlayerModal()">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <video id="modalVideoElement" controls autoplay style="width: 100%; max-height: 480px; display: block; background: #000;">
                    <source src="" type="video/mp4" id="modalVideoSource">
                    আপনার ব্রাউজারটি ভিডিও প্লেয়ার সমর্থন করে না।
                </video>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentDurationSeconds = 180;

    // Pick bulletin helper
    function pickBulletin(url, title, thumb, channel) {
        document.getElementById('sourceUrlInput').value = url;
        document.getElementById('videoTitleInput').value = title;
        document.getElementById('thumbnailUrlInput').value = thumb;
        document.getElementById('channelNameInput').value = channel;
        document.getElementById('previewImage').src = thumb;
        document.getElementById('channelInfoText').innerText = 'চ্যানেল: ' + channel;

        // Auto trigger fetch for accurate duration
        fetchVideoInfo(url);

        // Scroll up smoothly to form
        window.scrollTo({ top: 100, behavior: 'smooth' });
    }

    // Fetch video info via AJAX
    function fetchVideoInfo(url) {
        if (!url) return;

        const btn = document.getElementById('fetchInfoBtn');
        const origText = btn.innerHTML;
        btn.innerHTML = '<i class="mdi mdi-loading mdi-spin mr-1"></i> আনছি...';
        btn.disabled = true;

        fetch('{{ route("admin.video-workshop.fetch-info") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ url: url })
        })
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = origText;
            btn.disabled = false;

            if (data.success && data.data) {
                const item = data.data;
                if (!document.getElementById('videoTitleInput').value || document.getElementById('videoTitleInput').value === 'ইউটিউব ভিডিও') {
                    document.getElementById('videoTitleInput').value = item.title || '';
                }
                if (item.thumbnail_url) {
                    document.getElementById('previewImage').src = item.thumbnail_url;
                    document.getElementById('thumbnailUrlInput').value = item.thumbnail_url;
                }
                if (item.channel_name) {
                    document.getElementById('channelNameInput').value = item.channel_name;
                    document.getElementById('channelInfoText').innerText = 'চ্যানেল: ' + item.channel_name;
                }
                if (item.duration) {
                    currentDurationSeconds = item.duration;
                    document.getElementById('durationSecondsInput').value = item.duration;
                    document.getElementById('durationInfoText').innerText = 'সময়কাল: ' + (item.formatted_duration || formatTime(item.duration));
                    recalculateTrimVisuals();
                }
            }
        })
        .catch(err => {
            btn.innerHTML = origText;
            btn.disabled = false;
            console.error('Error fetching video metadata:', err);
        });
    }

    document.getElementById('fetchInfoBtn').addEventListener('click', function() {
        const url = document.getElementById('sourceUrlInput').value.trim();
        fetchVideoInfo(url);
    });

    // Auto fetch when pasting into URL input
    document.getElementById('sourceUrlInput').addEventListener('paste', function(e) {
        setTimeout(() => {
            const url = this.value.trim();
            if (url) fetchVideoInfo(url);
        }, 100);
    });

    // Trim recalculations
    function recalculateTrimVisuals() {
        const startCut = parseInt(document.getElementById('trimStartInput').value) || 0;
        const endCut = parseInt(document.getElementById('trimEndInput').value) || 0;
        const total = currentDurationSeconds || 180;

        let net = total - startCut - endCut;
        if (net < 1) net = 1;

        document.getElementById('netDurationDisplay').innerText = formatTime(net);
        document.getElementById('trimCalculationText').innerText = `মূল (${formatTime(total)}) - ${startCut}s - ${endCut}s`;

        // Update visual bar percentage
        let startPct = Math.min(30, (startCut / total) * 100);
        let endPct = Math.min(30, (endCut / total) * 100);

        document.getElementById('trimVisualStart').style.width = startPct + '%';
        document.getElementById('trimVisualEnd').style.width = endPct + '%';
        document.getElementById('trimVisualStart').innerText = startCut > 0 ? `-${startCut}s` : '';
        document.getElementById('trimVisualEnd').innerText = endCut > 0 ? `-${endCut}s` : '';
    }

    document.getElementById('trimStartInput').addEventListener('input', recalculateTrimVisuals);
    document.getElementById('trimEndInput').addEventListener('input', recalculateTrimVisuals);

    function formatTime(seconds) {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    }

    // Modal player
    function openPlayerModal(videoUrl, title) {
        document.getElementById('modalVideoTitle').innerText = title;
        const video = document.getElementById('modalVideoElement');
        const source = document.getElementById('modalVideoSource');
        source.src = videoUrl;
        video.load();
        video.play();
        $('#videoPlayerModal').modal('show');
    }

    function closePlayerModal() {
        const video = document.getElementById('modalVideoElement');
        video.pause();
        $('#videoPlayerModal').modal('hide');
    }

    $('#videoPlayerModal').on('hidden.bs.modal', function () {
        document.getElementById('modalVideoElement').pause();
    });

    // Watermark position simulator
    document.getElementById('watermarkPosSelect').addEventListener('change', function() {
        const sim = document.getElementById('simulatedWatermark');
        sim.style.top = '';
        sim.style.bottom = '';
        sim.style.left = '';
        sim.style.right = '';

        switch (this.value) {
            case 'top_left':
                sim.style.top = '12px'; sim.style.left = '12px'; break;
            case 'bottom_right':
                sim.style.bottom = '35px'; sim.style.right = '12px'; break;
            case 'bottom_left':
                sim.style.bottom = '35px'; sim.style.left = '12px'; break;
            default: // top_right
                sim.style.top = '12px'; sim.style.right = '12px'; break;
        }
    });

    // Branding type simulator
    document.getElementById('brandingTypeSelect').addEventListener('change', function() {
        const simWatermark = document.getElementById('simulatedWatermark');
        const simBanner = document.getElementById('simulatedBanner');

        if (this.value === 'both') {
            simWatermark.style.display = 'flex';
            simBanner.style.display = 'block';
        } else if (this.value === 'watermark') {
            simWatermark.style.display = 'flex';
            simBanner.style.display = 'none';
        } else if (this.value === 'banner') {
            simWatermark.style.display = 'none';
            simBanner.style.display = 'block';
        } else {
            simWatermark.style.display = 'none';
            simBanner.style.display = 'none';
        }
    });

    // Initialize visuals on page load
    recalculateTrimVisuals();
</script>
@endpush
