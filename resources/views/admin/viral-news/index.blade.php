@extends('layouts.admin')

@section('title', 'ভাইরাল ও ট্রেন্ডিং সংবাদ ইঞ্জিন')
@section('page_header', 'Viral & Trending Hub')
@section('page_title', 'লাইভ ভাইরাল ট্রেন্ডস')

@push('css')
<style>
    .trend-card-stat {
        border-radius: 12px;
        transition: transform 0.2s, box-shadow 0.2s;
        border: none;
    }
    .trend-card-stat:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }
    .pulse-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #ff4757;
        box-shadow: 0 0 0 0 rgba(255, 71, 87, 0.7);
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 71, 87, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(255, 71, 87, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 71, 87, 0); }
    }
    .badge-viral {
        background: linear-gradient(135deg, #ff416c, #ff4b2b);
        color: #fff;
        font-weight: 600;
        font-size: 11px;
        padding: 5px 9px;
        border-radius: 6px;
    }
    .badge-top-story {
        background: linear-gradient(135deg, #2193b0, #6dd5ed);
        color: #fff;
        font-weight: 600;
        font-size: 11px;
        padding: 5px 9px;
        border-radius: 6px;
    }
    .badge-lead {
        background: linear-gradient(135deg, #f7971e, #ffd200);
        color: #222;
        font-weight: 600;
        font-size: 11px;
        padding: 5px 9px;
        border-radius: 6px;
    }
    .custom-switch .custom-control-label::before {
        height: 1.5rem;
        width: 2.75rem;
        border-radius: 1rem;
    }
    .custom-switch .custom-control-label::after {
        width: calc(1.5rem - 4px);
        height: calc(1.5rem - 4px);
        border-radius: calc(1rem - (1.5rem / 2));
    }
    .table-hover tbody tr:hover {
        background-color: rgba(255, 75, 43, 0.03);
    }
    .trend-title-text {
        font-size: 14px;
        font-weight: 600;
        color: #2c3e50;
        line-height: 1.4;
    }
    .trend-snippet {
        font-size: 12px;
        color: #7f8c8d;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .btn-generate {
        background: linear-gradient(135deg, #ff416c, #ff4b2b);
        color: white;
        border: none;
        font-weight: 600;
        border-radius: 6px;
        padding: 6px 14px;
        transition: all 0.2s;
    }
    .btn-generate:hover {
        background: linear-gradient(135deg, #ff4b2b, #ff416c);
        color: white;
        box-shadow: 0 4px 12px rgba(255, 65, 108, 0.4);
    }
</style>
@endpush

@section('content')
<div class="container-fluid pt-3">
    
    <!-- Top Header Banner -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #1f1c2c 0%, #928dab 100%); color: white; border-radius: 12px;">
                <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                    <div>
                        <div class="d-flex align-items-center mb-1">
                            <span class="pulse-dot mr-2"></span>
                            <span class="badge badge-danger text-uppercase font-weight-bold px-2 py-1 mr-2" style="letter-spacing: 1px; font-size: 10px;">লাইভ ইঞ্জিন</span>
                            <h3 class="mb-0 text-white font-weight-bold" style="font-size: 1.6rem;">🔥 ভাইরাল ও ট্রেন্ডিং সংবাদ হাব</h3>
                        </div>
                        <p class="mb-0 text-light opacity-80" style="font-size: 13px;">
                            বাংলাদেশে এই মুহূর্তে গুগল ট্রেন্ডস, সোশ্যাল মিডিয়া এবং শীর্ষ সংবাদমাধ্যমে সবচেয়ে আলোচিত বিষয়গুলো রিয়েল-টাইমে সংগ্রহ করা হচ্ছে।
                        </p>
                    </div>
                    <div class="mt-3 mt-md-0 d-flex align-items-center">
                        <button id="btn-refresh" class="btn btn-light shadow-sm font-weight-bold mr-2 text-dark px-3 py-2" onclick="loadTrends(true)">
                            <i class="mdi mdi-refresh mr-1 text-primary"></i> রিফ্রেশ করুন
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat Cards & Auto-Viral Toggle -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card trend-card-stat shadow-sm p-3 bg-white h-100 border-left border-danger" style="border-left-width: 4px !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted text-uppercase mb-1 font-weight-bold" style="font-size: 11px;">মোট সক্রিয় ট্রেন্ড</p>
                        <h3 class="mb-0 font-weight-bold text-dark" id="stat-total-trends">--</h3>
                    </div>
                    <div class="rounded-circle bg-light p-3 text-danger">
                        <i class="mdi mdi-fire" style="font-size: 24px;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card trend-card-stat shadow-sm p-3 bg-white h-100 border-left border-warning" style="border-left-width: 4px !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted text-uppercase mb-1 font-weight-bold" style="font-size: 11px;">হট সার্চেস (গুগল)</p>
                        <h3 class="mb-0 font-weight-bold text-warning" id="stat-hot-trends">--</h3>
                    </div>
                    <div class="rounded-circle bg-light p-3 text-warning">
                        <i class="mdi mdi-trending-up" style="font-size: 24px;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card trend-card-stat shadow-sm p-3 bg-white h-100 border-left border-success" style="border-left-width: 4px !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted text-uppercase mb-1 font-weight-bold" style="font-size: 11px;">ইতিমধ্যে পোস্ট হয়েছে</p>
                        <h3 class="mb-0 font-weight-bold text-success" id="stat-posted-trends">--</h3>
                    </div>
                    <div class="rounded-circle bg-light p-3 text-success">
                        <i class="mdi mdi-check-decagram" style="font-size: 24px;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card trend-card-stat shadow-sm p-3 bg-white h-100 border-left border-primary" style="border-left-width: 4px !important;">
                <div class="d-flex flex-column justify-content-between h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted text-uppercase font-weight-bold" style="font-size: 11px;">স্বয়ংক্রিয় ভাইরাল মোড</span>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="autoViralSwitch" {{ $viralModeEnabled ? 'checked' : '' }} onchange="toggleAutoViral(this.checked)">
                            <label class="custom-control-label" for="autoViralSwitch" style="cursor: pointer;"></label>
                        </div>
                    </div>
                    <p class="mb-0 text-muted" style="font-size: 11px; line-height: 1.3;" id="viral-mode-desc">
                        {{ $viralModeEnabled ? 'সক্রিয়: অটো শিডিউলার সাধারণ সংবাদের বদলে আগে ভাইরাল ট্রেন্ড পোস্ট করবে।' : 'নিষ্ক্রিয়: অটো শিডিউলার সাধারণ রোটেশনে সংবাদ তৈরি করছে।' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Trends Filter & Table Container -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div class="d-flex align-items-center mb-2 mb-md-0">
                <i class="mdi mdi-format-list-bulleted-type mr-2 text-danger" style="font-size: 20px;"></i>
                <h5 class="mb-0 font-weight-bold text-dark">চলমান ট্রেন্ডের তালিকা (Live Trends List)</h5>
                <span class="badge badge-light border ml-2 text-muted" id="last-updated-text">হালনাগাদ: লোড হচ্ছে...</span>
            </div>
            <div class="d-flex flex-wrap align-items-center">
                <div class="mr-2 mb-2 mb-md-0">
                    <select id="filter-source" class="form-control form-control-sm" onchange="applyFilters()">
                        <option value="all">সব উৎস (All Sources)</option>
                        <option value="Google Trends">গুগল ট্রেন্ডস (সার্চ)</option>
                        <option value="Google Top Stories">গুগল টপ স্টোরিজ</option>
                        <option value="জাতীয় লিড">জাতীয় লিড নিউজ</option>
                    </select>
                </div>
                <div class="mr-2 mb-2 mb-md-0">
                    <select id="filter-status" class="form-control form-control-sm" onchange="applyFilters()">
                        <option value="all">সব স্ট্যাটাস</option>
                        <option value="unposted">নতুন (পোস্ট হয়নি)</option>
                        <option value="posted">পোস্ট সম্পন্ন</option>
                    </select>
                </div>
                <div>
                    <input type="text" id="search-box" class="form-control form-control-sm" placeholder="খুঁজুন (যেমন: ইরান, ক্রিকেট)..." onkeyup="applyFilters()">
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="trends-table">
                    <thead class="bg-light text-muted text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                        <tr>
                            <th style="width: 50px;" class="text-center">#</th>
                            <th style="min-width: 320px;">ট্রেন্ডিং বিষয় ও শিরোনাম</th>
                            <th style="width: 140px;">উৎস / প্ল্যাটফর্ম</th>
                            <th style="width: 140px;">জনপ্রিয়তা মাত্রা</th>
                            <th style="width: 120px;">ক্যাটাগরি</th>
                            <th style="width: 110px;">সময়</th>
                            <th style="width: 130px;" class="text-center">স্ট্যাটাস</th>
                            <th style="width: 160px;" class="text-center">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody id="trends-table-body">
                        <!-- Loading skeleton row -->
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="spinner-border text-danger" role="status" style="width: 2.5rem; height: 2.5rem;"></div>
                                <p class="mt-2 text-muted mb-0 font-weight-bold">লাইভ ট্রেন্ড ডাটা লোড হচ্ছে, অনুগ্রহ করে অপেক্ষা করুন...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
            <span class="text-muted" style="font-size: 12px;" id="table-showing-text">০টি ট্রেন্ড দেখানো হচ্ছে</span>
            <small class="text-muted">
                <i class="mdi mdi-information-outline mr-1"></i>প্রতিটি ট্রেন্ড আইটেমের ডানদিকের <b>Generate</b> বাটনে ক্লিক করলে এআই সাথে সাথে পোস্ট বানিয়ে ড্রাফট বা পাবলিশ করবে।
            </small>
        </div>
    </div>

</div>

<!-- Modal for generation progress -->
<div class="modal fade" id="genProgressModal" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-body text-center p-4">
                <div class="spinner-grow text-danger mb-3" role="status" style="width: 3.5rem; height: 3.5rem;"></div>
                <h4 class="font-weight-bold text-dark mb-1" id="genModalTitle">এআই নিউজ তৈরি হচ্ছে...</h4>
                <p class="text-muted mb-3" id="genModalSubtitle">জেমিনি এআই লাইভ ফ্যাক্ট-চেক করে বিস্তারিত প্রতিবেদন সাজাচ্ছে।</p>
                <div class="progress mb-3" style="height: 6px; border-radius: 3px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger w-100"></div>
                </div>
                <small class="text-muted" id="genModalFootnote">অনুগ্রহ করে ১০-১৫ সেকেন্ড সময় দিন...</small>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Toastify JS -->
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<script>
let allTrendsData = [];

function showToast(text, isError = false) {
    Toastify({
        text: text,
        duration: 4000,
        gravity: "top",
        position: "right",
        backgroundColor: isError ? "linear-gradient(to right, #e74c3c, #c0392b)" : "linear-gradient(to right, #00b09b, #96c93d)",
        stopOnFocus: true
    }).showToast();
}

function loadTrends(forceRefresh = false) {
    const btn = document.getElementById('btn-refresh');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm mr-1"></span> লোড হচ্ছে...`;

    const url = `{{ route('admin.viral-news.data') }}${forceRefresh ? '?refresh=1' : ''}`;

    fetch(url)
        .then(response => response.json())
        .then(result => {
            btn.disabled = false;
            btn.innerHTML = originalText;

            if (result.success) {
                allTrendsData = result.data;
                document.getElementById('stat-total-trends').innerText = result.count;
                document.getElementById('stat-hot-trends').innerText = result.hot_count;
                document.getElementById('stat-posted-trends').innerText = result.posted_count;
                document.getElementById('last-updated-text').innerText = `হালনাগাদ: ${result.fetched_at}`;
                
                applyFilters();

                if (forceRefresh) {
                    showToast('সর্বশেষ লাইভ ট্রেন্ডসমূহ সফলভাবে রিফ্রেশ করা হয়েছে!');
                }
            } else {
                showToast('ট্রেন্ড ডাটা লোড করতে সমস্যা হয়েছে।', true);
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            console.error('Error fetching trends:', error);
            showToast('সার্ভার থেকে ট্রেন্ড ফেচ করা যায়নি।', true);
        });
}

function applyFilters() {
    const sourceFilter = document.getElementById('filter-source').value;
    const statusFilter = document.getElementById('filter-status').value;
    const searchFilter = document.getElementById('search-box').value.trim().toLowerCase();

    const filtered = allTrendsData.filter(item => {
        // Source match
        if (sourceFilter !== 'all' && item.source_type !== sourceFilter) {
            return false;
        }
        // Status match
        if (statusFilter === 'posted' && !item.is_posted) {
            return false;
        }
        if (statusFilter === 'unposted' && item.is_posted) {
            return false;
        }
        // Search match
        if (searchFilter) {
            const combined = ((item.title || '') + ' ' + (item.query || '') + ' ' + (item.snippet || '') + ' ' + (item.source_name || '')).toLowerCase();
            if (!combined.includes(searchFilter)) {
                return false;
            }
        }
        return true;
    });

    renderTable(filtered);
}

function renderTable(items) {
    const tbody = document.getElementById('trends-table-body');
    document.getElementById('table-showing-text').innerText = `${items.length}টি ট্রেন্ড দেখানো হচ্ছে`;

    if (items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    <i class="mdi mdi-alert-circle-outline text-warning" style="font-size: 32px;"></i>
                    <p class="mb-0 mt-2 font-weight-bold">কোনো ট্রেন্ড তথ্য পাওয়া যায়নি। ফিল্টার পরিবর্তন করুন বা রিফ্রেশ দিন।</p>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    items.forEach((item, index) => {
        let badgeHtml = '';
        if (item.source_type === 'Google Trends') {
            badgeHtml = `<span class="badge-viral mr-1">${item.badge}</span>`;
        } else if (item.source_type === 'Google Top Stories') {
            badgeHtml = `<span class="badge-top-story mr-1">${item.badge}</span>`;
        } else {
            badgeHtml = `<span class="badge-lead mr-1">${item.badge}</span>`;
        }

        let statusHtml = '';
        let actionBtnHtml = '';

        if (item.is_posted) {
            statusHtml = `
                <span class="badge badge-success px-2 py-1 font-weight-bold" style="font-size: 11px;">
                    <i class="mdi mdi-check-circle mr-1"></i>পোস্ট হয়েছে
                </span>
            `;
            actionBtnHtml = `
                <a href="${item.article_url}" target="_blank" class="btn btn-sm btn-outline-success font-weight-bold">
                    <i class="mdi mdi-eye mr-1"></i>পোস্ট দেখুন
                </a>
            `;
        } else {
            statusHtml = `
                <span class="badge badge-light border text-muted px-2 py-1 font-weight-bold" style="font-size: 11px;">
                    <i class="mdi mdi-clock-outline mr-1"></i>পোস্ট হয়নি
                </span>
            `;
            actionBtnHtml = `
                <button type="button" class="btn-generate btn-sm" onclick="generateFromItem('${item.id}')">
                    <i class="mdi mdi-creation mr-1"></i>পোস্ট তৈরি
                </button>
            `;
        }

        let portalLink = item.source_url ? `<a href="${item.source_url}" target="_blank" class="text-muted ml-1" title="মূল উৎস দেখুন"><i class="mdi mdi-open-in-new" style="font-size: 12px;"></i></a>` : '';

        html += `
            <tr id="row-${item.id}">
                <td class="text-center font-weight-bold text-muted">${index + 1}</td>
                <td>
                    <div class="mb-1">
                        ${badgeHtml}
                        <span class="trend-title-text">${escapeHtml(item.title)}</span>
                    </div>
                    <p class="trend-snippet mb-0">${escapeHtml(item.snippet)}</p>
                </td>
                <td>
                    <div class="font-weight-bold text-dark" style="font-size: 13px;">${escapeHtml(item.source_name)} ${portalLink}</div>
                    <small class="text-muted">${escapeHtml(item.source_type)}</small>
                </td>
                <td>
                    <span class="badge badge-danger-light text-danger font-weight-bold" style="font-size: 12px; background: rgba(255, 75, 43, 0.1); padding: 4px 8px; border-radius: 4px;">
                        ${escapeHtml(item.traffic)}
                    </span>
                </td>
                <td>
                    <span class="badge badge-info px-2 py-1 font-weight-bold" style="font-size: 11px;">
                        ${escapeHtml(item.category_guess || 'জাতীয়')}
                    </span>
                </td>
                <td class="text-muted" style="font-size: 12px;">
                    ${escapeHtml(item.time_ago)}
                </td>
                <td class="text-center" id="status-cell-${item.id}">
                    ${statusHtml}
                </td>
                <td class="text-center" id="action-cell-${item.id}">
                    ${actionBtnHtml}
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function generateFromItem(itemId) {
    const trend = allTrendsData.find(t => t.id === itemId);
    if (!trend) {
        showToast('ট্রেন্ড ডাটা পাওয়া যায়নি।', true);
        return;
    }

    // Show Progress Modal
    document.getElementById('genModalTitle').innerText = `"${trend.title.substring(0, 45)}..."`;
    document.getElementById('genModalSubtitle').innerText = `জেমিনি এআই খবরটি বিস্তারিত বিশ্লেষণ করে প্রতিবেদন রচনা করছে...`;
    $('#genProgressModal').modal('show');

    // Update Action Button
    const actionCell = document.getElementById(`action-cell-${itemId}`);
    if (actionCell) {
        actionCell.innerHTML = `<span class="spinner-border spinner-border-sm text-danger"></span> <small class="text-danger font-weight-bold">লিখছে...</small>`;
    }

    fetch(`{{ route('admin.viral-news.generate') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ trend: trend })
    })
    .then(response => response.json())
    .then(data => {
        $('#genProgressModal').modal('hide');

        if (data.success && data.article) {
            showToast('✅ সফল! ভাইরাল নিউজ তৈরি ও পাবলিশ করা হয়েছে!');

            // Update item in local array
            trend.is_posted = true;
            trend.article_id = data.article.id;
            trend.article_slug = data.article.slug;
            trend.article_url = `/news/${data.article.slug}`;

            // Update row UI
            const statusCell = document.getElementById(`status-cell-${itemId}`);
            if (statusCell) {
                statusCell.innerHTML = `
                    <span class="badge badge-success px-2 py-1 font-weight-bold" style="font-size: 11px;">
                        <i class="mdi mdi-check-circle mr-1"></i>পোস্ট হয়েছে
                    </span>
                `;
            }
            if (actionCell) {
                actionCell.innerHTML = `
                    <a href="/news/${data.article.slug}" target="_blank" class="btn btn-sm btn-outline-success font-weight-bold">
                        <i class="mdi mdi-eye mr-1"></i>পোস্ট দেখুন
                    </a>
                `;
            }

            // Update stat counter
            const postedElem = document.getElementById('stat-posted-trends');
            postedElem.innerText = parseInt(postedElem.innerText || 0) + 1;
        } else {
            showToast('ব্যর্থ হয়েছে: ' + (data.message || 'অজানা ত্রুটি'), true);
            if (actionCell) {
                actionCell.innerHTML = `
                    <button type="button" class="btn-generate btn-sm" onclick="generateFromItem('${itemId}')">
                        <i class="mdi mdi-reload mr-1"></i>আবার চেষ্টা
                    </button>
                `;
            }
        }
    })
    .catch(err => {
        $('#genProgressModal').modal('hide');
        console.error('Generation error:', err);
        showToast('সার্ভার থেকে কোনো রেসপন্স পাওয়া যায়নি।', true);
        if (actionCell) {
            actionCell.innerHTML = `
                <button type="button" class="btn-generate btn-sm" onclick="generateFromItem('${itemId}')">
                    <i class="mdi mdi-reload mr-1"></i>আবার চেষ্টা
                </button>
            `;
        }
    });
}

function toggleAutoViral(enabled) {
    fetch(`{{ route('admin.viral-news.toggle-auto') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ enabled: enabled })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message);
            document.getElementById('viral-mode-desc').innerText = enabled
                ? 'সক্রিয়: অটো শিডিউলার সাধারণ সংবাদের বদলে আগে ভাইরাল ট্রেন্ড পোস্ট করবে।'
                : 'নিষ্ক্রিয়: অটো শিডিউলার সাধারণ রোটেশনে সংবাদ তৈরি করছে।';
        } else {
            showToast('সেটিংস পরিবর্তন ব্যর্থ হয়েছে।', true);
        }
    })
    .catch(err => {
        console.error('Toggle error:', err);
        showToast('সার্ভার কানেকশন ত্রুটি।', true);
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}

// Expose functions to global window scope so HTML onclick/onchange attributes always find them
window.loadTrends = loadTrends;
window.applyFilters = applyFilters;
window.generateFromItem = generateFromItem;
window.toggleAutoViral = toggleAutoViral;

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        loadTrends(false);
    });
} else {
    loadTrends(false);
}
</script>
@endpush
