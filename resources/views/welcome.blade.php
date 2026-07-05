@extends('layouts.frontend')

@push('css')
<style>
/* Homepage Specific Styling */
.secondary-news-item {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
}
.secondary-news-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}
.secondary-news-img {
    width: 120px;
    height: 85px;
    object-fit: cover;
    border-radius: 4px;
}
.secondary-news-title {
    font-size: 16px;
    font-weight: 700;
    line-height: 1.4;
    margin-bottom: 5px;
}
.secondary-news-summary {
    font-size: 13px;
    color: var(--text-grey);
    margin: 0;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* PM Corner */
.pm-corner-widget {
    background: #fff;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    overflow: hidden;
}
.pm-corner-header {
    background: var(--primary-green);
    color: #fff;
    padding: 12px 15px;
    font-weight: 700;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.pm-corner-body {
    padding: 15px;
}
.pm-lead-img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 4px;
    margin-bottom: 10px;
}
.pm-lead-title {
    font-size: 17px;
    font-weight: 700;
    margin-bottom: 15px;
    line-height: 1.4;
}
.pm-sub-item {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px dashed var(--border-color);
}
.pm-sub-item:last-child {
    border: none; margin: 0; padding: 0;
}
.pm-sub-img {
    width: 70px;
    height: 50px;
    object-fit: cover;
    border-radius: 4px;
}
.pm-sub-title {
    font-size: 14px;
    font-weight: 600;
    line-height: 1.4;
    margin: 0;
}

/* Location Filter */
.location-filter-card {
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-top: 3px solid var(--primary-green);
    border-radius: 4px;
    padding: 20px;
    margin: 30px 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.02);
}
.location-filter-card h6 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 15px;
}
.btn-search {
    background: var(--primary-green);
    color: #fff;
    width: 100%;
}
.btn-search:hover { background: #b51c1c; color: #fff; }

/* Category Sections */
.category-section-row {
    margin-bottom: 40px;
}
.section-title-text {
    font-size: 22px;
    font-weight: 700;
    color: #ffffff;
    background-color: var(--primary-green);
    padding: 6px 15px;
    border-radius: 4px 4px 0 0;
    display: inline-block;
}
.section-more-link {
    font-size: 14px;
    font-weight: 600;
    color: var(--primary-green);
    margin-left: auto;
}
.category-news-card {
    background: #fff;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: box-shadow 0.2s;
}
.category-news-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.category-card-img {
    width: 100%;
    height: 160px;
    object-fit: cover;
}
.category-card-body {
    padding: 15px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}
.category-card-title {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 10px;
    line-height: 1.4;
}
.category-card-summary {
    font-size: 13px;
    color: var(--text-grey);
    margin-bottom: 10px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Gallery */
.gallery-title-text {
    font-size: 24px;
    font-weight: 700;
    color: #fff;
    border-bottom: 2px solid #374151;
    padding-bottom: 10px;
    margin-bottom: 25px;
}
.gallery-card {
    position: relative;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 20px;
    aspect-ratio: 16/9;
}
.gallery-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}
.gallery-card:hover img {
    transform: scale(1.05);
}
.gallery-overlay-play {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: rgba(255,255,255,0.8);
    font-size: 40px;
}
.gallery-card-title {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    padding: 30px 15px 15px;
    background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
    color: #fff;
    margin: 0;
    font-size: 14px;
    line-height: 1.4;
}

/* Image watermark overlay effects */
.lead-news-image-wrapper {
    position: relative;
    overflow: hidden;
}
.lead-news-image-wrapper::before {
    content: "বিডিবি নিউজ চিত্র";
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(229, 29, 32, 0.95);
    color: white;
    font-size: 11px;
    font-weight: bold;
    padding: 4px 10px;
    border-radius: 4px;
    z-index: 10;
    pointer-events: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    letter-spacing: 0.5px;
}

.category-news-card {
    position: relative;
}
.category-news-card::before {
    content: "বিডিবি নিউজ চিত্র";
    position: absolute;
    top: 10px;
    left: 10px;
    background: rgba(229, 29, 32, 0.95);
    color: white;
    font-size: 10px;
    font-weight: bold;
    padding: 2px 8px;
    border-radius: 3px;
    z-index: 10;
    pointer-events: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}
</style>
@endpush

@section('content')
            <!-- 6. Public Lead News / Main Section -->
            <div class="news-main-section">
                <div class="row">
                    <!-- Left Column: Large Featured Lead Card -->
                    <div class="col-lg-5 col-md-12">
                        @if($featured_article)
                            <div class="lead-news-card">
                                <div class="lead-news-image-wrapper">
                                    <img src="{{ $featured_article->image_url }}" alt="{{ $featured_article->title }}">
                                </div>
                                <div class="card-body">
                                    <h2 class="lead-news-title">
                                        <a href="{{ route('news.show', $featured_article->slug) }}">{{ $featured_article->title }}</a>
                                    </h2>
                                    <p class="lead-news-summary">{{ $featured_article->summary }}</p>
                                    <div class="news-meta-info">
                                        <span><i class="fas fa-folder text-danger"></i> {{ $featured_article->category->name }}</span>
                                        <span><i class="far fa-clock"></i> {{ $featured_article->created_at->diffForHumans() }}</span>
                                        <span><i class="far fa-eye"></i> {{ $featured_article->views }} বার পঠিত</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-muted py-5 text-center">
                                কোনো মূল সংবাদ পাওয়া যায়নি।
                            </div>
                        @endif
                    </div>

                    <!-- Center Column: Secondary News List -->
                    <div class="col-lg-4 col-md-7">
                        @foreach($middle_articles as $article)
                            <div class="secondary-news-item">
                                @if($article->image_url)
                                    <img src="{{ $article->image_url }}" alt="{{ $article->title }}" class="secondary-news-img">
                                @endif
                                <div>
                                    <h3 class="secondary-news-title">
                                        <a href="{{ route('news.show', $article->slug) }}">{{ $article->title }}</a>
                                    </h3>
                                    <p class="secondary-news-summary">{{ $article->summary }}</p>
                                    <div class="news-meta-info mt-2">
                                        <span><i class="far fa-clock"></i> {{ $article->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Right Column: Prime Minister Corner Widget -->
                    <div class="col-lg-3 col-md-5">
                        <div class="pm-corner-widget">
                            <div class="pm-corner-header">
                                <span>প্রধানমন্ত্রীর কর্নার</span>
                                <i class="fas fa-bookmark text-white"></i>
                            </div>
                            <div class="pm-corner-body">
                                @if($pm_corner_lead)
                                    <div class="pm-lead-article">
                                        <img src="{{ $pm_corner_lead->image_url }}" alt="{{ $pm_corner_lead->title }}" class="pm-lead-img">
                                        <h4 class="pm-lead-title">
                                            <a href="{{ route('news.show', $pm_corner_lead->slug) }}">{{ $pm_corner_lead->title }}</a>
                                        </h4>
                                    </div>
                                @endif

                                <div class="pm-sub-list">
                                    @foreach($pm_corner_subs as $article)
                                        <div class="pm-sub-item">
                                            <img src="{{ $article->image_url }}" alt="{{ $article->title }}" class="pm-sub-img">
                                            <h5 class="pm-sub-title">
                                                <a href="{{ route('news.show', $article->slug) }}">{{ $article->title }}</a>
                                            </h5>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Search by Location Filter Widget -->
            <div class="location-filter-card">
                <h6><i class="fas fa-map-marked-alt text-danger"></i> সারাবাংলা সংবাদ অনুসন্ধান</h6>
                <form action="{{ route('home') }}" method="GET">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-5 col-sm-6">
                            <select name="division" class="form-select">
                                <option value="">-- বিভাগ নির্বাচন করুন --</option>
                                @foreach($divisions as $div)
                                    <option value="{{ $div }}" {{ $selected_division == $div ? 'selected' : '' }}>{{ $div }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5 col-sm-6">
                            <select name="district" class="form-select">
                                <option value="">-- জেলা নির্বাচন করুন --</option>
                                @foreach($districts as $dis)
                                    <option value="{{ $dis }}" {{ $selected_district == $dis ? 'selected' : '' }}>{{ $dis }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-12">
                            <button type="submit" class="btn-search btn">খুঁজুন <i class="fas fa-search ms-1"></i></button>
                        </div>
                    </div>
                </form>

                @if($filtered_articles !== null)
                    <div class="mt-3 border-top pt-3 d-flex justify-content-between align-items-center">
                        <div>
                            <strong>অনুসন্ধানের ফলাফল:</strong> 
                            <span>{{ $selected_division ? 'বিভাগ: ' . $selected_division : '' }}</span>
                            <span>{{ $selected_district ? ($selected_division ? ', ' : '') . 'জেলা: ' . $selected_district : '' }}</span>
                            <span class="badge bg-secondary ms-2">{{ $filtered_articles->count() }}টি সংবাদ</span>
                        </div>
                        <a href="{{ route('home') }}" class="btn btn-outline-danger btn-sm">রিসেট করুন <i class="fas fa-undo ms-1"></i></a>
                    </div>
                    
                    <!-- Filtered Articles Grid -->
                    <div class="row mt-3">
                        @forelse($filtered_articles as $article)
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                <div class="category-news-card">
                                    <img src="{{ $article->image_url }}" alt="{{ $article->title }}" class="category-card-img">
                                    <div class="category-card-body">
                                        <h5 class="category-card-title"><a href="{{ route('news.show', $article->slug) }}">{{ $article->title }}</a></h5>
                                        <p class="category-card-summary">{{ $article->summary }}</p>
                                        <div class="news-meta-info mt-auto">
                                            <span><i class="far fa-clock"></i> {{ $article->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 py-3 text-center text-muted">
                                <i class="fas fa-info-circle me-1"></i> এই এলাকায় কোনো সংবাদ পাওয়া যায়নি।
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>

            <!-- সারাবাংলা (All Bangladesh) Section -->
            <div class="category-section-row mt-4">
                <div class="section-title-bar">
                    <div class="section-title-text">সারাবাংলা</div>
                    <a href="#" class="section-more-link">আরও পড়ুন <i class="fas fa-angle-double-right"></i></a>
                </div>
                <div class="row">
                    @forelse($sarabangla_articles as $article)
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                            <div class="category-news-card">
                                <img src="{{ $article->image_url }}" alt="{{ $article->title }}" class="category-card-img">
                                <div class="category-card-body">
                                    <h5 class="category-card-title"><a href="{{ route('news.show', $article->slug) }}">{{ $article->title }}</a></h5>
                                    <p class="category-card-summary">{{ $article->summary }}</p>
                                    <div class="news-meta-info mt-auto">
                                        <span><i class="far fa-clock"></i> {{ $article->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 py-3 text-muted text-center">
                            সারাবাংলা বিভাগে এখনো কোনো সংবাদ নেই।
                        </div>
                    @endforelse
                </div>
            </div>

            <hr class="my-4" style="color: var(--border-color);">

            <!-- 7. Category grid sections (Politics, Sports, Entertainment, Tech, Lifestyle) -->
            
            <!-- Category Row: রাজনীতি & আন্তর্জাতিক (Combined layout) -->
            <div class="category-section-row">
                <div class="row">
                    <!-- Column 1: রাজনীতি -->
                    <div class="col-lg-6 mb-4">
                        <div class="section-title-bar">
                            <div class="section-title-text">রাজনীতি</div>
                            <a href="#" class="section-more-link">আরও পড়ুন <i class="fas fa-angle-double-right"></i></a>
                        </div>
                        <div class="row">
                            @foreach($politics_articles as $article)
                                <div class="col-sm-6 mb-3">
                                    <div class="category-news-card">
                                        <img src="{{ $article->image_url }}" alt="{{ $article->title }}" class="category-card-img">
                                        <div class="category-card-body">
                                            <h5 class="category-card-title"><a href="{{ route('news.show', $article->slug) }}">{{ $article->title }}</a></h5>
                                            <p class="category-card-summary">{{ $article->summary }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Column 2: আন্তর্জাতিক & অর্থনীতি (Mock grid) -->
                    <div class="col-lg-6 mb-4">
                        <div class="section-title-bar">
                            <div class="section-title-text">খেলাধুলা</div>
                            <a href="#" class="section-more-link">আরও পড়ুন <i class="fas fa-angle-double-right"></i></a>
                        </div>
                        <div class="row">
                            @foreach($sports_articles as $article)
                                <div class="col-sm-6 mb-3">
                                    <div class="category-news-card">
                                        <img src="{{ $article->image_url }}" alt="{{ $article->title }}" class="category-card-img">
                                        <div class="category-card-body">
                                            <h5 class="category-card-title"><a href="{{ route('news.show', $article->slug) }}">{{ $article->title }}</a></h5>
                                            <p class="category-card-summary">{{ $article->summary }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- FIFA 26 Live Section -->
            <div class="category-section-row fifa-26-section mb-5 mt-2">
                <div class="section-title-bar" style="border-bottom: 2px solid var(--primary-red);">
                    <div class="section-title-text" style="background-color: var(--primary-red);">ফিফা বিশ্বকাপ ২০২৬</div>
                    <a href="#" class="section-more-link text-danger">সকল আপডেট <i class="fas fa-angle-double-right"></i></a>
                </div>
                
                <!-- Banner -->
                <div class="fifa-banner-wrapper position-relative rounded overflow-hidden mb-4 shadow-sm" style="max-height: 250px;">
                    <img src="{{ asset('images/fifa_26_banner.png') }}" class="img-fluid w-100" alt="FIFA World Cup 26" style="object-fit: cover;">
                    <div class="position-absolute w-100 h-100 top-0 start-0 d-flex flex-column justify-content-end p-3" style="background: linear-gradient(0deg, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0) 100%);">
                        <h3 class="text-white fw-bold mb-1"><i class="fas fa-futbol text-danger"></i> বিশ্বকাপ লাইভ আপডেট</h3>
                        <p class="text-light mb-1" style="font-size:14px; opacity: 0.9;">সর্বশেষ স্কোর এবং সময়সূচি</p>
                    </div>
                </div>

                <!-- Live Scoreboard Widget -->
                <div class="row" id="fifa-scoreboard-container">
                    <div class="col-12 text-center py-4">
                        <div class="spinner-border text-danger" role="status">
                            <span class="visually-hidden">লোড হচ্ছে...</span>
                        </div>
                        <p class="mt-2 text-muted">লাইভ ডেটা ফেচ করা হচ্ছে...</p>
                    </div>
                </div>
            </div>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const container = document.getElementById('fifa-scoreboard-container');
                    
                    fetch('https://site.api.espn.com/apis/site/v2/sports/soccer/fifa.world/scoreboard')
                        .then(response => response.json())
                        .then(data => {
                            if(data.events && data.events.length > 0) {
                                let html = '';
                                data.events.slice(0, 4).forEach(event => {
                                    const comp = event.competitions[0];
                                    const team1 = comp.competitors[0];
                                    const team2 = comp.competitors[1];
                                    const status = event.status.type.shortDetail; 
                                    const isLive = event.status.type.state === 'in';
                                    
                                    html += `
                                        <div class="col-lg-6 col-md-6 mb-3">
                                            <div class="card shadow-sm border-0 h-100" style="background: #f8fafc;">
                                                <div class="card-body p-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                                        <span class="badge ${isLive ? 'bg-danger blink-badge' : 'bg-dark'}">${status}</span>
                                                        <small class="text-muted fw-bold"><i class="far fa-calendar-alt"></i> ${new Date(event.date).toLocaleDateString('bn-BD', {month: 'short', day: 'numeric', hour: '2-digit', minute:'2-digit'})}</small>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="text-center w-40">
                                                            <img src="${team1.team.logo}" width="48" height="48" class="mb-2 rounded-circle border p-1 bg-white" onerror="this.src='https://via.placeholder.com/48'">
                                                            <div class="fw-bold fs-6">${team1.team.shortDisplayName || team1.team.name}</div>
                                                        </div>
                                                        <div class="text-center w-20 px-1">
                                                            <div class="display-6 fw-bolder text-dark">${team1.score} - ${team2.score}</div>
                                                        </div>
                                                        <div class="text-center w-40">
                                                            <img src="${team2.team.logo}" width="48" height="48" class="mb-2 rounded-circle border p-1 bg-white" onerror="this.src='https://via.placeholder.com/48'">
                                                            <div class="fw-bold fs-6">${team2.team.shortDisplayName || team2.team.name}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                });
                                container.innerHTML = html;
                            } else {
                                // Fallback mock data if API returns empty
                                throw new Error('No events');
                            }
                        })
                        .catch(err => {
                            // Fallback mock data if API fails or no active matches
                            container.innerHTML = `
                                <div class="col-lg-6 mb-3">
                                    <div class="card shadow-sm border-0" style="background: #f8fafc;">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                                                <span class="badge bg-danger blink-badge">LIVE 75'</span>
                                                <small class="text-muted fw-bold">কোয়ার্টার ফাইনাল</small>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="text-center w-40"><h5 class="fw-bold mb-0">আর্জেন্টিনা</h5></div>
                                                <div class="w-20 text-center"><div class="display-6 fw-bolder text-dark">2 - 1</div></div>
                                                <div class="text-center w-40"><h5 class="fw-bold mb-0">ব্রাজিল</h5></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <div class="card shadow-sm border-0" style="background: #f8fafc;">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                                                <span class="badge bg-dark">আজ রাত ১০:০০</span>
                                                <small class="text-muted fw-bold">কোয়ার্টার ফাইনাল</small>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="text-center w-40"><h5 class="fw-bold mb-0">ফ্রান্স</h5></div>
                                                <div class="w-20 text-center"><div class="display-6 fw-bolder text-dark">- : -</div></div>
                                                <div class="text-center w-40"><h5 class="fw-bold mb-0">পর্তুগাল</h5></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                });
            </script>

            <!-- Category Row 2: বিনোদন & তথ্যপ্রযুক্তি -->
            <div class="category-section-row">
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="section-title-bar">
                            <div class="section-title-text">বিনোদন</div>
                            <a href="#" class="section-more-link">আরও পড়ুন <i class="fas fa-angle-double-right"></i></a>
                        </div>
                        <div class="row">
                            @foreach($entertainment_articles as $article)
                                <div class="col-sm-6 mb-3">
                                    <div class="category-news-card">
                                        <img src="{{ $article->image_url }}" alt="{{ $article->title }}" class="category-card-img">
                                        <div class="category-card-body">
                                            <h5 class="category-card-title"><a href="{{ route('news.show', $article->slug) }}">{{ $article->title }}</a></h5>
                                            <p class="category-card-summary">{{ $article->summary }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="col-lg-6 mb-4">
                        <div class="section-title-bar">
                            <div class="section-title-text">তথ্য ও প্রযুক্তি</div>
                            <a href="#" class="section-more-link">আরও পড়ুন <i class="fas fa-angle-double-right"></i></a>
                        </div>
                        <div class="row">
                            @foreach($tech_articles as $article)
                                <div class="col-sm-6 mb-3">
                                    <div class="category-news-card">
                                        <img src="{{ $article->image_url }}" alt="{{ $article->title }}" class="category-card-img">
                                        <div class="category-card-body">
                                            <h5 class="category-card-title"><a href="{{ route('news.show', $article->slug) }}">{{ $article->title }}</a></h5>
                                            <p class="category-card-summary">{{ $article->summary }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- /container -->

        <!-- 8. Multimedia Gallery Section -->
        <div class="gallery-section">
            <div class="container">
                <div class="gallery-title">
                    <div class="gallery-title-text"><i class="fas fa-video me-1"></i> ভিডিও গ্যালারি</div>
                </div>
                
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <div class="gallery-card">
                            <img src="{{ asset('images/pm_visit.png') }}" alt="Video">
                            <div class="gallery-overlay-play">
                                <i class="far fa-play-circle"></i>
                            </div>
                            <h5 class="gallery-card-title">বাংলাদেশ ও চীনের দ্বিপাক্ষিক সম্পর্কের নতুন দিগন্ত</h5>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="gallery-card">
                            <img src="{{ asset('images/sports_brazil.png') }}" alt="Video">
                            <div class="gallery-overlay-play">
                                <i class="far fa-play-circle"></i>
                            </div>
                            <h5 class="gallery-card-title">রোমাঞ্চকর জয়ে শেষ ষোলোতে ব্রাজিলের আনন্দ উদযাপন</h5>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="gallery-card">
                            <img src="{{ asset('images/earthquake.png') }}" alt="Video">
                            <div class="gallery-overlay-play">
                                <i class="far fa-play-circle"></i>
                            </div>
                            <h5 class="gallery-card-title">ভেনিজুয়েলায় ভূমিকম্প কবলিত এলাকার ড্রোন চিত্র</h5>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="gallery-card">
                            <img src="{{ asset('images/lead_national.png') }}" alt="Video">
                            <div class="gallery-overlay-play">
                                <i class="far fa-play-circle"></i>
                            </div>
                            <h5 class="gallery-card-title">কৃত্রিম বুদ্ধিমত্তা ও বিজ্ঞান নিয়ে নতুন তথ্যচিত্র</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

@endsection
