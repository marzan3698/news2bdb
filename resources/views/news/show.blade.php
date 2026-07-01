@extends('layouts.frontend')

@push('css')
<style>
    /* Drop Cap Styling */
    .article-content > p:first-of-type::first-letter {
        float: left;
        font-size: 4rem;
        line-height: 1;
        padding: 5px 15px;
        margin-right: 10px;
        background-color: #e51d20; /* Red color matching the screenshot */
        color: white;
        font-weight: bold;
        border-radius: 2px;
        margin-top: 5px;
    }
    
    .article-content {
        font-size: 1.15rem;
        line-height: 1.8;
        color: #333;
    }

    /* Left Sidebar Styling */
    .left-sidebar-meta {
        border: 1px solid #eee;
        padding: 20px;
        text-align: center;
        background: #fff;
    }
    
    .category-badge-large {
        color: #28a745;
        font-weight: bold;
        font-size: 1.2rem;
        border-bottom: 2px solid #28a745;
        padding-bottom: 5px;
        display: inline-block;
        margin-bottom: 20px;
    }

    .meta-logo {
        width: 80px;
        margin: 0 auto 15px auto;
        display: block;
    }

    .news-desk-text {
        font-weight: 600;
        font-size: 1.1rem;
        color: #333;
        margin-bottom: 20px;
        border-bottom: 1px solid #ddd;
        padding-bottom: 15px;
    }

    .publish-time {
        font-size: 0.95rem;
        color: #666;
        margin-bottom: 20px;
    }

    .share-text {
        font-size: 0.9rem;
        color: #555;
        margin-bottom: 10px;
    }

    .social-share-buttons {
        display: flex;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .social-share-buttons a {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-decoration: none;
    }
    
    .btn-fb { background: #3b5998; }
    .btn-tw { background: #1da1f2; }
    .btn-wa { background: #25d366; }
    .btn-pr { background: #d9534f; }
    .btn-dl { background: #555; }

    /* Main Content Styling */
    .article-title {
        font-size: 2.5rem;
        font-weight: bold;
        color: #111;
        margin-bottom: 20px;
        line-height: 1.3;
    }
    
    .article-image-container {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
        border: 4px solid #f0f3f8;
    }
    
    .article-image-container img.article-image {
        width: 100%;
        display: block;
        border: none;
        padding: 0;
        margin-bottom: 0;
    }

    .article-image-watermark {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.4) 60%, rgba(0,0,0,0) 100%);
        color: white;
        padding: 25px 15px 12px 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.8rem;
        pointer-events: none;
    }

    .watermark-brand {
        font-weight: bold;
        background: rgba(229, 29, 32, 0.95); /* Red brand color */
        padding: 4px 10px;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        font-size: 0.75rem;
    }

    .watermark-location {
        font-weight: 500;
        background: rgba(42, 82, 152, 0.95); /* Blue theme color */
        padding: 4px 10px;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        font-size: 0.75rem;
    }

    .google-news-banner {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        padding: 10px;
        text-align: center;
        font-weight: bold;
        color: #333;
        margin-bottom: 20px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .google-news-banner img {
        height: 24px;
    }

    .ai-disclaimer {
        background-color: #fff3cd;
        border-left: 5px solid #ffc107;
        padding: 15px;
        margin-bottom: 25px;
        border-radius: 0 4px 4px 0;
        font-size: 0.95rem;
        color: #856404;
    }

    /* Right Sidebar Styling */
    .right-sidebar-ad {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: center;
        margin-bottom: 20px;
        background: #fff;
    }

    .right-sidebar-ad img {
        max-width: 100%;
        height: auto;
    }

    .latest-news-widget .widget-title {
        background: #e51d20;
        color: white;
        padding: 5px 10px;
        font-size: 1.1rem;
        font-weight: bold;
        display: inline-block;
        margin-bottom: 15px;
    }

    .latest-news-widget ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .latest-news-widget ul li {
        border-bottom: 1px dotted #ccc;
        padding-bottom: 10px;
        margin-bottom: 10px;
    }
    
    .latest-news-widget ul li:last-child {
        border-bottom: none;
    }

    .latest-news-widget ul li a {
        color: #333;
        text-decoration: none;
        font-size: 1.05rem;
        font-weight: 500;
        line-height: 1.4;
        display: block;
    }

    .latest-news-widget ul li a:hover {
        color: #e51d20;
    }
</style>
@endpush

@section('content')
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-white border">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-success"><i class="fas fa-home"></i> প্রচ্ছদ</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $article->category->name ?? 'সংবাদ' }}</li>
        </ol>
    </nav>

    <div class="row mt-4">
        <!-- Left Sidebar (Meta Info) -->
        <div class="col-md-2">
            <div class="left-sidebar-meta">
                <div class="category-badge-large">{{ $article->category->name ?? 'সংবাদ' }}</div>
                
                <!-- Site Logo/Name -->
                <div class="bg-dark text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; font-weight: bold; font-size: 1.2rem; border: 3px solid #e51d20;">
                    বিডিবি
                </div>

                <div class="news-desk-text" style="line-height: 1.2;">
                    <strong>বিডিবি নিউজ</strong><br>
                    <small class="text-muted" style="font-size: 0.85rem; font-weight: normal;">AI Powered News</small>
                </div>
                
                <div class="publish-time">
                    প্রকাশিত: {{ \Carbon\Carbon::parse($article->created_at)->locale('bn')->translatedFormat('j F, Y') }}
                </div>

                <div class="share-text">সংবাদটি শেয়ার করে সাথে থাকুন</div>
                
                <div class="social-share-buttons">
                    <a href="#" class="btn-fb"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="btn-tw"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="btn-wa"><i class="fab fa-whatsapp"></i></a>
                    <a href="#" class="btn-pr"><i class="fas fa-print"></i></a>
                    <a href="#" class="btn-dl"><i class="fas fa-download"></i></a>
                </div>
            </div>
        </div>

        <!-- Middle Column (Main Content) -->
        <div class="col-md-7">
            <h1 class="article-title">{{ $article->title }}</h1>
            
            @if($article->image_url)
                <div class="article-image-container">
                    <img src="{{ $article->image_url }}" alt="{{ $article->title }}" class="article-image">
                    <div class="article-image-watermark">
                        <span class="watermark-brand"><i class="fas fa-robot mr-1"></i> বিডিবি নিউজ চিত্র</span>
                        @if($article->district)
                            <span class="watermark-location"><i class="fas fa-map-marker-alt mr-1"></i> {{ $article->district }}</span>
                        @endif
                    </div>
                </div>
            @endif

            <a href="#" class="google-news-banner text-decoration-none">
                <i class="fab fa-google" style="color: #4285F4; font-size: 1.2rem;"></i>
                বিডিবি নিউজ এর সর্বশেষ খবর পেতে গুগল নিউজ (Google News) ফিডটি অনুসরণ করুন
            </a>

            <!-- AI Generated Disclaimer -->
            <div class="ai-disclaimer shadow-sm">
                <strong><i class="fas fa-robot text-warning mr-2"></i> বিশেষ দ্রষ্টব্য:</strong> এই সংবাদটি এবং এর সাথে ব্যবহৃত ছবিটি আর্টিফিশিয়াল ইন্টেলিজেন্স (AI) দ্বারা স্বয়ংক্রিয়ভাবে জেনারেট করা হয়েছে। 
            </div>

            <div class="article-content mt-4">
                {!! $article->content !!}
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="col-md-3">
            <!-- Ad Placeholder -->
            <div class="right-sidebar-ad">
                <span class="badge badge-danger mb-2">বিজ্ঞাপন</span><br>
                <div style="background: #000080; color: white; padding: 20px; font-weight: bold;">
                    মায়ের আঁচল কোচিং সেন্টার<br>
                    <small>ভর্তি চলছে!</small>
                </div>
            </div>

            <div class="latest-news-widget mt-4">
                <span class="widget-title">সর্বশেষ সংবাদ</span>
                <ul>
                    @foreach($latest_articles as $news)
                        <li><a href="{{ route('news.show', $news->slug) }}">{{ $news->title }}</a></li>
                    @endforeach
                </ul>
            </div>
            
            <!-- Another Ad Placeholder -->
            <div class="right-sidebar-ad mt-4">
                <span class="badge badge-danger mb-2">বিজ্ঞাপন</span><br>
                <div style="background: #111; color: white; padding: 20px; font-weight: bold;">
                    পাবলিক ল্যাবরেটরী স্কুল<br>
                    <small>ভর্তি চলছে!</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
