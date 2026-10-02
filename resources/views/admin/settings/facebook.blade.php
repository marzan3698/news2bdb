@extends('layouts.admin')

@section('page_title', 'Facebook Auto-Post Settings')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="mt-0 header-title mb-1"><i class="mdi mdi-facebook text-primary mr-1"></i> Direct Facebook Auto-Post (Native)</h4>
                        <p class="text-muted mb-0">Whenever an article is published (automatically or manually), it is instantly posted to your Facebook Page with high-res banner, title, summary, and link — without n8n.</p>
                    </div>
                    <div>
                        <button id="testConnectionBtn" class="btn btn-outline-primary waves-effect waves-light font-weight-bold">
                            <i class="mdi mdi-check-network mr-1"></i> Test Facebook Connection
                        </button>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success border-0 mb-4">
                        <strong><i class="mdi mdi-check-circle mr-1"></i> Success!</strong> {{ session('success') }}
                    </div>
                @endif

                <div id="testResultBox" style="display:none;" class="mb-4"></div>

                <!-- Nav Tabs -->
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active font-weight-bold text-dark" data-toggle="tab" href="#config-tab" role="tab">
                            <i class="mdi mdi-cog mr-1"></i> Credentials & Configuration
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold text-dark" data-toggle="tab" href="#guide-tab" role="tab">
                            <i class="mdi mdi-book-open-page-variant mr-1"></i> Setup Guide (Bangla)
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Tab 1: Configuration -->
                    <div class="tab-pane active" id="config-tab" role="tabpanel">
                        <form action="{{ route('admin.settings.facebook.save') }}" method="POST">
                            @csrf
                            
                            <div class="form-group mb-4">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="facebook_enabled" name="facebook_enabled" value="1" {{ $facebook_enabled == '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold font-16" for="facebook_enabled">
                                        Enable Direct Facebook Auto-Posting
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">When active, newly published news articles will be automatically posted to your Facebook Page via Facebook Graph API.</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="facebook_page_id" class="font-weight-bold">Facebook Page ID</label>
                                        <input type="text" class="form-control" id="facebook_page_id" name="facebook_page_id" value="{{ $facebook_page_id ?? '' }}" placeholder="e.g. 100085938472910 or me">
                                        <small class="form-text text-muted">Your numeric Facebook Page ID. (If left blank, 'me' will be used with Page token).</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="facebook_page_access_token" class="font-weight-bold">Page Access Token (Permanent / Long-lived)</label>
                                        <input type="password" class="form-control" id="facebook_page_access_token" name="facebook_page_access_token" value="{{ $facebook_page_access_token ?? '' }}" placeholder="EAABw...">
                                        <small class="form-text text-muted">A valid Facebook Page Access Token with <code>pages_manage_posts</code> and <code>pages_read_engagement</code> permissions.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="facebook_app_id" class="font-weight-bold">Facebook App ID <span class="text-muted font-weight-normal">(Optional)</span></label>
                                        <input type="text" class="form-control" id="facebook_app_id" name="facebook_app_id" value="{{ $facebook_app_id ?? '' }}" placeholder="e.g. 123456789012345">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="facebook_app_secret" class="font-weight-bold">Facebook App Secret <span class="text-muted font-weight-normal">(Optional)</span></label>
                                        <input type="password" class="form-control" id="facebook_app_secret" name="facebook_app_secret" value="{{ $facebook_app_secret ?? '' }}" placeholder="Meta App Secret">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary font-weight-bold mt-2">
                                <i class="mdi mdi-content-save mr-1"></i> Save Facebook Settings
                            </button>
                        </form>
                    </div>

                    <!-- Tab 2: Guide -->
                    <div class="tab-pane" id="guide-tab" role="tabpanel">
                        <div class="p-3 mb-4 rounded" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                            <h4 class="text-primary"><i class="mdi mdi-facebook-box mr-2"></i> কিভাবে ফেসবুক পেজ কানেক্ট করবেন?</h4>
                            <p class="font-16 text-dark">
                                লারাভেল থেকে সরাসরি কোনো থার্ড-পার্টি টুল (n8n) ছাড়া ফেসবুক পেজে পোস্ট করার জন্য কেবল আপনার পেজের একটি <strong>Page Access Token</strong> প্রয়োজন। নিচে ধাপে ধাপে নির্দেশনা দেওয়া হলো:
                            </p>
                        </div>

                        <div class="timeline">
                            <div class="card mb-3 border">
                                <div class="card-body">
                                    <h5 class="text-primary font-weight-bold"><span class="badge badge-primary mr-2">ধাপ ১</span> Meta Developers এ যান</h5>
                                    <p class="mb-0 text-muted">
                                        <a href="https://developers.facebook.com" target="_blank" class="text-primary font-weight-bold">developers.facebook.com</a> এ গিয়ে একটি App তৈরি করুন (Type: "Business" বা "None" সিলেক্ট করুন)।
                                    </p>
                                </div>
                            </div>

                            <div class="card mb-3 border">
                                <div class="card-body">
                                    <h5 class="text-primary font-weight-bold"><span class="badge badge-info mr-2">ধাপ ২</span> Graph API Explorer ওপেন করুন</h5>
                                    <p class="mb-0 text-muted">
                                        <a href="https://developers.facebook.com/tools/explorer/" target="_blank" class="text-primary font-weight-bold">Meta Graph API Explorer</a> টুলসে ঢুকুন। আপনার তৈরি করা Meta App টি নির্বাচন করুন।
                                    </p>
                                </div>
                            </div>

                            <div class="card mb-3 border">
                                <div class="card-body">
                                    <h5 class="text-primary font-weight-bold"><span class="badge badge-warning mr-2">ধাপ ৩</span> পারমিশন (Permissions) সিলেক্ট করুন</h5>
                                    <p class="text-muted mb-1">ডানদিকের "Permissions" ড্রপডাউন থেকে নিচের পারমিশনগুলো অ্যাড করুন:</p>
                                    <ul class="font-monospace text-dark mb-0">
                                        <li><code>pages_show_list</code></li>
                                        <li><code>pages_read_engagement</code></li>
                                        <li><code>pages_manage_posts</code></li>
                                    </ul>
                                </div>
                            </div>

                            <div class="card mb-3 border">
                                <div class="card-body">
                                    <h5 class="text-primary font-weight-bold"><span class="badge badge-success mr-2">ধাপ ৪</span> Page Access Token জেনারেট করুন</h5>
                                    <p class="mb-0 text-muted">
                                        <strong>User or Page</strong> ড্রপডাউনে ক্লিক করে আপনার <strong>Facebook Page</strong> টি নির্বাচন করুন। এরপর <strong>Generate Access Token</strong> এ ক্লিক করুন এবং টোকেনটি কপি করে এই সেটিংস পেজের <strong>Page Access Token</strong> ঘরে পেস্ট করে সেভ করুন।
                                    </p>
                                </div>
                            </div>

                            <div class="card border">
                                <div class="card-body">
                                    <h5 class="text-primary font-weight-bold"><span class="badge badge-danger mr-2">ধাপ ৫</span> টেস্ট করুন</h5>
                                    <p class="mb-0 text-muted">
                                        সেভ করার পর উপরের <strong>"Test Facebook Connection"</strong> বাটনে ক্লিক করুন। যদি পেজের নাম ও আইডি সবুজ রঙে প্রদর্শিত হয়, তাহলে আপনার সেটআপ ১০০% সম্পন্ন!
                                    </p>
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

@push('js')
<script>
    document.getElementById('testConnectionBtn').addEventListener('click', function() {
        var btn = this;
        var originalText = btn.innerHTML;
        var resultBox = document.getElementById('testResultBox');
        
        btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Checking...';
        btn.disabled = true;
        resultBox.style.display = 'none';

        fetch("{{ route('admin.settings.facebook.test') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            resultBox.style.display = 'block';

            if (data.success) {
                resultBox.innerHTML = `
                    <div class="alert alert-success border-0">
                        <h5 class="mt-0 font-weight-bold"><i class="mdi mdi-check-circle mr-1"></i> Connected to Facebook!</h5>
                        <p class="mb-0"><strong>Page Name:</strong> ${data.page_name} | <strong>Page ID:</strong> ${data.page_id}</p>
                        <small class="text-muted">Your Page Access Token is valid and ready to post.</small>
                    </div>
                `;
            } else {
                resultBox.innerHTML = `
                    <div class="alert alert-danger border-0">
                        <h5 class="mt-0 font-weight-bold"><i class="mdi mdi-alert-circle mr-1"></i> Facebook Connection Failed</h5>
                        <p class="mb-0">${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(err => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            resultBox.style.display = 'block';
            resultBox.innerHTML = `
                <div class="alert alert-danger border-0">
                    <strong>Error:</strong> ${err.message}
                </div>
            `;
        });
    });
</script>
@endpush
