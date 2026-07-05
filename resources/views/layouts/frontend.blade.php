<!DOCTYPE html>
<html lang="bn">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $siteLogo = \App\Models\Setting::where('key', 'site_logo')->value('value');
            $siteTitle = \App\Models\Setting::where('key', 'site_title')->value('value') ?? 'বিডিবি নিউজ';
            $siteSub = \App\Models\Setting::where('key', 'site_description')->value('value') ?? 'সত্যের সন্ধানে সার্বক্ষণিক';
        @endphp
        <title>{{ $siteTitle }} - {{ $siteSub }}</title>

        <!-- Google Fonts & Adorsho Lipi -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
        <link href="https://fonts.maateen.me/adorsho-lipi/font.css" rel="stylesheet">
        
        <!-- FontAwesome for Icons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- Bootstrap CSS for basic grid structures -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

        <!-- Custom Styling for News Portal Layout -->
        <style>
            :root {
                --primary-green: #1da255;
                --primary-red: #d92323;
                --text-dark: #222222;
                --text-grey: #666666;
                --bg-light: #f5f6f8;
                --border-color: #e5e7eb;
            }

            body {
                font-family: 'Adorsho Lipi', 'Roboto', sans-serif;
                background-color: var(--bg-light);
                color: var(--text-dark);
                overflow-x: hidden;
            }

            a {
                text-decoration: none;
                color: inherit;
            }
            a:hover {
                color: var(--primary-red);
            }

            /* --- Typography --- */
            h1, h2, h3, h4, h5, h6 {
                font-weight: 700;
                margin-bottom: 0.5rem;
            }

            /* --- 1. Top Utility Navigation Bar --- */
            .top-utility-bar {
                background-color: #ffffff;
                border-bottom: 1px solid var(--border-color);
                font-size: 13px;
                padding: 6px 0;
                color: var(--text-grey);
            }
            .date-time {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .auth-links a {
                margin-left: 15px;
                font-weight: 500;
                transition: color 0.2s;
            }
            .auth-links a:hover {
                color: var(--primary-green) !important;
            }

            /* --- 2. Header Main --- */
            .header-main {
                background-color: #ffffff;
                padding: 20px 0;
                border-bottom: 1px solid var(--border-color);
            }
            .header-logo-text {
                font-size: 38px;
                font-weight: 800;
                color: var(--primary-green);
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .header-logo-text span.red {
                color: var(--primary-red);
            }
            .header-logo-sub {
                font-size: 13px;
                color: var(--text-grey);
                letter-spacing: 1px;
                margin-top: -5px;
            }
            .ads-banner-donate {
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                border: 1px solid var(--border-color);
                padding: 15px 25px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            }
            .ads-banner-donate h5 {
                color: var(--primary-green);
                margin-bottom: 5px;
                font-size: 16px;
            }
            .ads-banner-donate p {
                margin: 0;
                font-size: 12px;
                color: var(--text-grey);
            }
            .btn-donate {
                background-color: var(--primary-red);
                color: #fff;
                font-weight: 600;
                padding: 8px 20px;
                border-radius: 4px;
                border: none;
                transition: all 0.3s;
                white-space: nowrap;
            }
            .btn-donate:hover {
                background-color: #b51c1c;
                color: #fff;
                transform: translateY(-2px);
            }

            /* --- 3. Main Navigation Bar --- */
            .main-navbar-container {
                background-color: #ffffff;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                position: sticky;
                top: 0;
                z-index: 1000;
                border-top: 2px solid var(--primary-green);
            }
            .navbar-nav {
                flex-direction: row;
                flex-wrap: nowrap;
                overflow-x: auto;
                overflow-y: hidden;
                -ms-overflow-style: none;  /* IE and Edge */
                scrollbar-width: none;  /* Firefox */
            }
            .navbar-nav::-webkit-scrollbar {
                display: none; /* Chrome, Safari and Opera */
            }
            .navbar-nav .nav-item {
                border-right: 1px solid var(--border-color);
            }
            .navbar-nav .nav-item:last-child {
                border-right: none;
            }
            .navbar-nav .nav-link {
                color: var(--text-dark) !important;
                font-weight: 600;
                font-size: 16px;
                padding: 15px 20px !important;
                transition: all 0.3s;
                position: relative;
                white-space: nowrap;
            }
            .navbar-nav .nav-link:hover, .navbar-nav .nav-link.active {
                color: var(--primary-green) !important;
                background-color: #f8f9fa;
            }
            .navbar-nav .nav-link::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                width: 100%;
                height: 3px;
                background-color: var(--primary-green);
                transform: scaleX(0);
                transition: transform 0.3s ease;
            }
            .navbar-nav .nav-link:hover::after, .navbar-nav .nav-link.active::after {
                transform: scaleX(1);
            }
            .nav-home-btn {
                background-color: var(--primary-green);
                color: #ffffff !important;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 50px;
            }
            .nav-home-btn:hover {
                background-color: #15803d !important;
                color: #ffffff !important;
            }
            .nav-home-btn::after { display: none; }

            /* --- 4. Breaking News Ticker --- */
            body {
                padding-bottom: 50px; /* Space for the fixed ticker */
            }
            .news-ticker-bar {
                background-color: #ffffff;
                border-top: 2px solid var(--primary-red);
                margin: 0;
                display: flex;
                align-items: center;
                overflow: hidden;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                z-index: 1050;
            }
            .ticker-title {
                background-color: var(--primary-red);
                color: #fff;
                padding: 8px 20px;
                font-weight: 700;
                display: flex;
                align-items: center;
                gap: 8px;
                position: relative;
                z-index: 2;
            }
            .ticker-title::after {
                content: '';
                position: absolute;
                right: -10px;
                top: 0;
                border-top: 20px solid transparent;
                border-bottom: 20px solid transparent;
                border-left: 10px solid var(--primary-red);
            }
            .ticker-content {
                flex-grow: 1;
                padding: 8px 20px;
                background: #f8f9fa;
            }
            .ticker-content marquee a {
                margin-right: 30px;
                font-weight: 500;
                color: var(--text-dark);
            }
            .ticker-content marquee a:hover {
                color: var(--primary-red);
                text-decoration: underline;
            }

            /* --- 5. Ad Banner Space --- */
            .top-ad-banner {
                margin-bottom: 30px;
                text-align: center;
            }
            .top-ad-banner img {
                max-width: 100%;
                border: 1px solid var(--border-color);
                border-radius: 4px;
            }

            /* --- Common Layout Elements --- */
            .section-title-bar {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
                border-bottom: 2px solid var(--primary-green);
                padding-bottom: 10px;
            }
            .section-title-bar h3 {
                margin: 0;
                color: var(--primary-green);
                font-size: 24px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .section-title-bar h3 i {
                color: var(--primary-red);
            }
            
            /* --- Cards --- */
            .news-card {
                background: #ffffff;
                border: 1px solid var(--border-color);
                border-radius: 6px;
                overflow: hidden;
                margin-bottom: 25px;
                transition: transform 0.3s, box-shadow 0.3s;
            }
            .news-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            }
            .news-image-wrapper {
                position: relative;
                overflow: hidden;
                aspect-ratio: 16/9;
            }
            .news-image-wrapper img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform 0.5s ease;
            }
            .news-card:hover .news-image-wrapper img {
                transform: scale(1.05);
            }
            
            /* --- Featured Lead News --- */
            .lead-news-card {
                border: none;
                background: transparent;
                margin-bottom: 30px;
            }
            .lead-news-image-wrapper {
                position: relative;
                border-radius: 8px;
                overflow: hidden;
                aspect-ratio: 16/9;
                margin-bottom: 15px;
            }
            .lead-news-image-wrapper img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform 0.5s ease;
            }
            .lead-news-card:hover .lead-news-image-wrapper img {
                transform: scale(1.03);
            }
            .lead-news-title {
                font-size: 32px;
                font-weight: 800;
                line-height: 1.3;
                margin-bottom: 12px;
            }
            .lead-news-title a {
                color: var(--text-dark);
            }
            .lead-news-title a:hover {
                color: var(--primary-red);
            }
            .lead-news-summary {
                font-size: 16px;
                color: var(--text-grey);
                line-height: 1.6;
                margin-bottom: 15px;
            }
            .lead-news-card .card-body {
                padding: 0;
            }

            /* --- List items --- */
            .news-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            .news-list li {
                padding: 12px 0;
                border-bottom: 1px dashed var(--border-color);
                display: flex;
                align-items: flex-start;
                gap: 12px;
            }
            .news-list li:last-child {
                border-bottom: none;
            }
            .news-list li i {
                color: var(--primary-red);
                margin-top: 4px;
                font-size: 12px;
            }
            .news-list li a {
                font-weight: 500;
                font-size: 16px;
                line-height: 1.4;
            }

            /* --- Thumb list --- */
            .thumb-list li {
                display: flex;
                gap: 15px;
                padding: 15px 0;
                border-bottom: 1px solid var(--border-color);
            }
            .thumb-list li:last-child { border-bottom: none; }
            .thumb-list .thumb {
                width: 100px;
                height: 75px;
                border-radius: 4px;
                overflow: hidden;
                flex-shrink: 0;
            }
            .thumb-list .thumb img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .thumb-list h4 {
                font-size: 15px;
                font-weight: 600;
                line-height: 1.4;
                margin-bottom: 5px;
            }
            .news-meta-info {
                font-size: 12px;
                color: #888;
                display: flex;
                gap: 12px;
                align-items: center;
            }

            /* --- Tabs --- */
            .custom-tabs .nav-link {
                color: var(--text-dark);
                font-weight: 600;
                border: none;
                border-bottom: 3px solid transparent;
                padding: 10px 15px;
                background: #f8f9fa;
                border-radius: 0;
            }
            .custom-tabs .nav-link.active {
                color: var(--primary-green);
                background: #ffffff;
                border-bottom: 3px solid var(--primary-green);
            }

            /* --- Category Section Cards --- */
            .category-news-card .card-body {
                padding: 15px;
            }
            .category-news-card h4 {
                font-size: 18px;
                font-weight: 700;
                line-height: 1.4;
                margin-bottom: 10px;
            }

            /* --- Photo Gallery --- */
            .gallery-section {
                background: #111827;
                padding: 40px 0;
                color: white;
                margin-top: 40px;
            }
            .gallery-section .section-title-bar {
                border-color: #374151;
            }
            .gallery-section .section-title-bar h3 {
                color: #ffffff;
            }
            .gallery-item {
                position: relative;
                border-radius: 6px;
                overflow: hidden;
                margin-bottom: 20px;
            }
            .gallery-item img {
                width: 100%;
                aspect-ratio: 4/3;
                object-fit: cover;
                transition: transform 0.5s;
            }
            .gallery-item:hover img {
                transform: scale(1.1);
            }
            .gallery-overlay {
                position: absolute;
                bottom: 0;
                left: 0;
                width: 100%;
                padding: 20px 15px 15px;
                background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
            }
            .gallery-overlay h5 {
                color: white;
                font-size: 15px;
                margin: 0;
                line-height: 1.4;
            }
            .gallery-icon {
                position: absolute;
                top: 15px;
                right: 15px;
                background: rgba(0,0,0,0.6);
                color: white;
                width: 36px;
                height: 36px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                font-size: 14px;
            }

            /* --- Footer Area --- */
            footer {
                background-color: #111827;
                color: #9ca3af;
                padding: 50px 0 20px 0;
                border-top: 4px solid var(--primary-green);
                font-size: 14px;
            }
            footer h5 {
                color: #ffffff;
                font-weight: 700;
                margin-bottom: 20px;
                font-size: 16px;
            }
            footer p {
                line-height: 1.8;
            }
            .footer-links-list {
                padding: 0;
                list-style: none;
            }
            .footer-links-list li {
                margin-bottom: 10px;
            }
            .footer-links-list li a {
                color: #9ca3af;
            }
            .footer-links-list li a:hover {
                color: #ffffff;
            }
            .footer-social-icons {
                display: flex;
                gap: 12px;
                margin-top: 15px;
            }
            .footer-social-icons a {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
                background-color: #374151;
                color: #ffffff;
                border-radius: 50%;
                font-size: 16px;
                transition: background-color 0.2s;
            }
            .footer-social-icons a:hover {
                background-color: var(--primary-green);
            }
            .footer-bottom-bar {
                border-top: 1px solid #374151;
                padding-top: 20px;
                margin-top: 40px;
                font-size: 12px;
                text-align: center;
            }

            /* --- Mobile Header (JagoNews Style) --- */
            .mobile-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 12px 15px;
                background: #fff;
                border-bottom: 1px solid #e0e0e0;
            }
            .mobile-header .hamburger {
                font-size: 24px;
                color: var(--primary-red);
                cursor: pointer;
            }
            .mobile-header .logo img {
                max-height: 40px;
            }
            .mobile-header .logo-text {
                font-size: 24px;
                font-weight: 700;
                color: var(--primary-green);
                display: flex;
                align-items: center;
                margin-bottom: 0;
            }
            .mobile-header .logo-text .red {
                color: var(--primary-red);
                margin-left: 5px;
            }
            .mobile-header .right-actions {
                display: flex;
                align-items: center;
                gap: 15px;
            }
            .mobile-header .eng-btn {
                color: var(--primary-red);
                font-weight: 600;
                font-size: 15px;
                text-decoration: none;
            }
            .mobile-header .search-icon {
                font-size: 20px;
                color: #222;
                cursor: pointer;
            }
            .mobile-navbar-container {
                border-bottom: 1px solid #eaeaea;
                background: #fff;
            }
            .mobile-navbar {
                display: flex;
                overflow-x: auto;
                white-space: nowrap;
                padding: 12px 15px;
                gap: 20px;
                -ms-overflow-style: none; /* IE and Edge */
                scrollbar-width: none; /* Firefox */
            }
            .mobile-navbar::-webkit-scrollbar {
                display: none; /* Chrome, Safari and Opera */
            }
            .mobile-navbar a {
                color: #222;
                font-size: 16px;
                font-weight: 600;
                text-decoration: none;
            }
            .mobile-navbar a:hover {
                color: var(--primary-red);
            }
        </style>
        @stack('css')
    </head>
    
    <body>
        <!-- 1. Top Utility Navigation Bar -->
        <div class="top-utility-bar d-none d-lg-block">
            <div class="container d-flex justify-content-between align-items-center">
                <div class="date-time d-flex align-items-center">
                    <span><i class="far fa-calendar-alt me-1"></i> বুধবার, ১ জুলাই, ২০২৬ইং</span>
                    <div class="ms-3 d-none d-md-flex gap-3">
                        <a href="#" class="text-secondary hover-primary"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-secondary hover-primary"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-secondary hover-primary"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="auth-links">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-dark"><i class="fas fa-desktop me-1"></i> ড্যাশবোর্ড</a>
                        @else
                            <a href="{{ route('login') }}" class="text-dark"><i class="fas fa-sign-in-alt me-1"></i> লগইন</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="text-dark"><i class="fas fa-user-plus me-1"></i> নিবন্ধন</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </div>

        <!-- 2. Header Main with Logo & Advertisement Banner -->
        <header class="header-main d-none d-lg-block">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-4 col-md-5 mb-3 mb-md-0 text-center text-md-start">
                        <a href="{{ route('home') }}">
                            @if($siteLogo)
                                <img src="{{ $siteLogo }}" alt="{{ $siteTitle }}" style="max-height: 60px; object-fit: contain; max-width: 100%; display: block; margin: 0 auto 5px auto;" class="img-fluid d-inline-block">
                            @else
                                <div class="header-logo-text justify-content-center justify-content-md-start">
                                    বিডিবি <span class="red">নিউজ</span>
                                </div>
                            @endif
                            <div class="header-logo-sub">{{ $siteSub }}</div>
                        </a>
                    </div>
                    <div class="col-lg-8 col-md-7">
                        <div class="ads-banner-donate">
                            <div>
                                <h5>আসুন অসহায় মানুষের পাশে দাঁড়াই</h5>
                                <p>আপনার ক্ষুদ্র অনুদান আমাদের সংবাদমাধ্যমকে স্বাধীন ও সোচ্চার রাখতে সাহায্য করবে।</p>
                            </div>
                            <div>
                                <a href="#" class="btn-donate btn">ডোনেট করুন <i class="fas fa-heart ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- 3. Navigation Bar Category Links -->
        <div class="main-navbar-container d-none d-lg-block">
            <nav class="navbar navbar-expand p-0">
                <div class="container d-flex flex-nowrap">
                    <a href="{{ route('home') }}" class="nav-home-btn nav-link active py-3 flex-shrink-0">
                        <i class="fas fa-home"></i>
                    </a>
                    
                    <div class="w-100" id="newsNavbar">
                        <ul class="navbar-nav mb-0 w-100">
                            @foreach($categories as $cat)
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->is('category/' . $cat->slug) ? 'active' : '' }}" href="{{ route('news.category', $cat->slug) }}">{{ $cat->name }}</a>
                                </li>
                            @endforeach
                            <li class="nav-item">
                                <a class="nav-link" href="#">ভিডিও</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#">পরিবার</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>

        <!-- 4. Mobile Header & Navbar (Visible only on small screens) -->
        <div class="d-block d-lg-none">
            <div class="mobile-header">
                <div class="hamburger" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebarOffcanvas" aria-controls="mobileSidebarOffcanvas">
                    <i class="fas fa-bars"></i>
                </div>
                <div class="logo">
                    <a href="{{ route('home') }}" class="text-decoration-none">
                        @if($siteLogo)
                            <img src="{{ $siteLogo }}" alt="{{ $siteTitle }}">
                        @else
                            <div class="logo-text">বিডিবি <span class="red">নিউজ</span></div>
                        @endif
                    </a>
                </div>
                <div class="right-actions">
                    <a href="#" class="eng-btn">ENG</a>
                    <div class="search-icon" data-bs-toggle="offcanvas" data-bs-target="#mobileSearchOffcanvas" aria-controls="mobileSearchOffcanvas">
                        <i class="fas fa-search"></i>
                    </div>
                </div>
            </div>
            <div class="mobile-navbar-container">
                <div class="mobile-navbar">
                    <a href="{{ route('home') }}">প্রচ্ছদ</a>
                    <a href="#">সর্বশেষ</a>
                    @foreach($categories as $cat)
                        <a href="{{ route('news.category', $cat->slug) }}">{{ $cat->name }}</a>
                    @endforeach
                    <a href="#">ভিডিও</a>
                    <a href="#">পরিবার</a>
                </div>
            </div>
        </div>

        <div class="container">
            <!-- 4. Breaking News Ticker -->
            <div class="news-ticker-bar">
                <div class="ticker-title">
                    <i class="fas fa-bolt"></i> সর্বশেষ
                </div>
                <div class="ticker-content">
                    <marquee behavior="scroll" direction="left" onmouseover="this.stop();" onmouseout="this.start();">
                        @foreach($latest_articles as $article)
                            <a href="{{ route('news.show', $article->slug) }}"><i class="fas fa-circle" style="font-size: 8px; margin-right:5px; vertical-align: middle;"></i> {{ $article->title }}</a>
                        @endforeach
                    </marquee>
                </div>
            </div>

            <!-- 5. Top Advertisement Banner -->
            <div class="top-ad-banner">
                <img src="https://via.placeholder.com/970x90/f1f5f9/94a3b8?text=Advertisement+Space+970x90" alt="Ad">
            </div>

            @yield('content')
            
        </div> <!-- /container -->

        <!-- 9. Footer Area -->
        <footer>
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 mb-4 mb-lg-0">
                        <h5>আমাদের কথা</h5>
                        <p>বিডিবি নিউজ বাংলাদেশের অন্যতম জনপ্রিয় অনলাইন নিউজ পোর্টাল। আমরা সত্য, বস্তুনিষ্ঠ এবং দায়িত্বশীল সাংবাদিকতায় বিশ্বাসী। প্রতিদিনের সর্বশেষ দেশী-বিদেশী খবরাখবর পড়তে আমাদের সাথেই থাকুন।</p>
                    </div>
                    <div class="col-lg-4 mb-4 mb-lg-0">
                        <h5>গুরুত্বপূর্ণ লিংক</h5>
                        <ul class="footer-links-list">
                            <li><a href="#"><i class="fas fa-angle-right me-1"></i> আমাদের সম্পর্কে</a></li>
                            <li><a href="#"><i class="fas fa-angle-right me-1"></i> বিজ্ঞাপনের মূল্য তালিকা</a></li>
                            <li><a href="#"><i class="fas fa-angle-right me-1"></i> গোপনীয়তার নীতিমালা</a></li>
                            <li><a href="#"><i class="fas fa-angle-right me-1"></i> যোগাযোগ করুন</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-4">
                        <h5>যোগাযোগ ও সোশাল মিডিয়া</h5>
                        <p><i class="fas fa-map-marker-alt me-2"></i> পান্থপথ, ঢাকা-১২১৫, বাংলাদেশ</p>
                        <p><i class="fas fa-phone-alt me-2"></i> +৮৮০ ২-১২৩৪৫৬৭</p>
                        <p><i class="fas fa-envelope me-2"></i> info@bdbnews.com</p>
                        <div class="footer-social-icons">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-youtube"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="footer-bottom-bar">
                    <p>&copy; ২০২৬ বিডিবি নিউজ - সর্বস্বত্ব সংরক্ষিত।</p>
                </div>
            </div>
        </footer>

        <!-- Mobile Sidebar Offcanvas -->
        <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebarOffcanvas" aria-labelledby="mobileSidebarLabel">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title" id="mobileSidebarLabel">
                    @if($siteLogo)
                        <img src="{{ $siteLogo }}" alt="{{ $siteTitle }}" style="max-height: 35px;">
                    @else
                        <div style="font-size: 24px; font-weight: 700; color: var(--primary-green);">বিডিবি <span style="color: var(--primary-red);">নিউজ</span></div>
                    @endif
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-0">
                <ul class="list-group list-group-flush">
                    <a href="{{ route('home') }}" class="list-group-item list-group-item-action py-3"><i class="fas fa-home me-2 text-secondary"></i> প্রচ্ছদ</a>
                    @foreach($categories as $cat)
                        <a href="{{ route('news.category', $cat->slug) }}" class="list-group-item list-group-item-action py-3 {{ request()->is('category/' . $cat->slug) ? 'active' : '' }}"><i class="fas fa-chevron-right me-2 text-secondary" style="font-size:12px;"></i> {{ $cat->name }}</a>
                    @endforeach
                    <a href="#" class="list-group-item list-group-item-action py-3"><i class="fas fa-video me-2 text-secondary"></i> ভিডিও</a>
                    <a href="#" class="list-group-item list-group-item-action py-3"><i class="fas fa-users me-2 text-secondary"></i> পরিবার</a>
                </ul>
                <div class="p-3 mt-2">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn btn-outline-success w-100 mb-2"><i class="fas fa-desktop me-1"></i> ড্যাশবোর্ড</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-outline-dark w-100 mb-2"><i class="fas fa-sign-in-alt me-1"></i> লগইন</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn btn-outline-danger w-100"><i class="fas fa-user-plus me-1"></i> নিবন্ধন</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </div>

        <!-- Mobile Search Offcanvas -->
        <div class="offcanvas offcanvas-top" tabindex="-1" id="mobileSearchOffcanvas" aria-labelledby="mobileSearchLabel" style="height: auto;">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title" id="mobileSearchLabel">অনুসন্ধান করুন</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <form action="#" method="GET" class="d-flex">
                    <input class="form-control me-2" type="search" placeholder="খবর খুঁজুন..." aria-label="Search" name="q">
                    <button class="btn btn-danger" type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </div>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>