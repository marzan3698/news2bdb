@extends('layouts.frontend')

@section('content')
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-white border">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-success"><i class="fas fa-home"></i> প্রচ্ছদ</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
        </ol>
    </nav>

    <div class="section-title-bar mb-4 mt-4">
        <h3><i class="fas fa-list"></i> {{ $category->name }}</h3>
    </div>

    <div class="row">
        <!-- Main Content Area -->
        <div class="col-lg-8">
            <div class="row">
                @forelse($articles as $article)
                    <div class="col-md-6 mb-4">
                        <div class="news-card h-100 d-flex flex-column">
                            @if($article->image_url)
                            <div class="news-image-wrapper">
                                <a href="{{ route('news.show', $article->slug) }}">
                                    <img src="{{ $article->image_url }}" alt="{{ $article->title }}">
                                </a>
                            </div>
                            @endif
                            <div class="card-body flex-grow-1 p-3">
                                <a href="{{ route('news.category', $category->slug) }}" class="text-danger fw-bold text-decoration-none" style="font-size: 14px;">{{ $category->name }}</a>
                                <h4 class="mt-2" style="font-size: 18px; line-height: 1.4; font-weight: 700;">
                                    <a href="{{ route('news.show', $article->slug) }}" class="text-dark text-decoration-none">{{ $article->title }}</a>
                                </h4>
                                <div class="text-muted mt-2" style="font-size: 13px;">
                                    <i class="far fa-clock"></i> {{ \Carbon\Carbon::parse($article->created_at)->locale('bn')->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <p class="text-muted" style="font-size: 18px;">এই ক্যাটাগরিতে কোনো সংবাদ পাওয়া যায়নি।</p>
                    </div>
                @endforelse
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $articles->links() }}
            </div>
        </div>
        
        <!-- Right Sidebar Area -->
        <div class="col-lg-4 mt-4 mt-lg-0">
            <!-- Sidebar with Latest News -->
            <div class="section-title-bar mb-3">
                <h3 style="font-size: 20px;"><i class="fas fa-bolt"></i> সর্বশেষ সংবাদ</h3>
            </div>
            <ul class="thumb-list list-unstyled">
                @foreach($latest_articles as $latest)
                    <li>
                        @if($latest->image_url)
                        <div class="thumb">
                            <a href="{{ route('news.show', $latest->slug) }}">
                                <img src="{{ $latest->image_url }}" alt="{{ $latest->title }}">
                            </a>
                        </div>
                        @endif
                        <div class="info">
                            <h4><a href="{{ route('news.show', $latest->slug) }}" class="text-dark text-decoration-none">{{ $latest->title }}</a></h4>
                            <div class="news-meta-info mt-1">
                                <span><i class="far fa-clock"></i> {{ \Carbon\Carbon::parse($latest->created_at)->locale('bn')->diffForHumans() }}</span>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <!-- Advertisement Placeholder -->
            <div class="right-sidebar-ad mt-4 border p-3 text-center bg-white">
                <span class="badge bg-danger mb-2">বিজ্ঞাপন</span><br>
                <div style="background: #f8f9fa; color: #6c757d; padding: 40px 20px; font-weight: bold; border: 1px dashed #ced4da;">
                    ৩০০ x ২৫০ <br>
                    <small>বিজ্ঞাপন স্পেস</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
