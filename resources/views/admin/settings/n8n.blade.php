@extends('layouts.admin')

@section('page_title', 'n8n Setup & Documentation')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="mt-0 header-title">n8n Workflow Setup & Documentation</h4>
                <p class="text-muted mb-4">Manage your n8n integration settings and read the documentation on how to auto-fetch viral district news to your news portal.</p>

                <!-- Nav Tabs -->
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active font-weight-bold text-dark" data-toggle="tab" href="#connection-tab" role="tab"><i class="mdi mdi-connection mr-1"></i> Connection Details</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold text-dark" data-toggle="tab" href="#docs-tab" role="tab"><i class="mdi mdi-book-open-page-variant mr-1"></i> Workflow Documentation</a>
                    </li>
                </ul>

                <!-- Tab Panes -->
                <div class="tab-content">
                    
                    <!-- Connection Details Tab -->
                    <div class="tab-pane active" id="connection-tab" role="tabpanel">
                        <form action="{{ route('admin.settings.ai.save') }}" method="POST">
                            @csrf
                            <div class="form-group mb-3">
                                <label for="n8n_api_key" class="font-weight-bold">n8n Secret API Key</label>
                                <input type="password" class="form-control" id="n8n_api_key" name="n8n_api_key" value="{{ $n8n_api_key ?? '' }}" placeholder="Create a strong secret key">
                                <small class="form-text text-muted">This secret key will authenticate requests coming from n8n to your website.</small>
                            </div>

                            <div class="alert border-0 mt-3" style="background:#e3f2fd;border-left:4px solid #2196f3 !important;">
                                <strong><i class="mdi mdi-webhook mr-1"></i> n8n Webhook Target URL:</strong>
                                <p class="mb-1 mt-2">Use this URL in your n8n HTTP Request node to send data to BDB News:</p>
                                <code class="d-block p-2 rounded" style="background:#263238;color:#80cbc4;font-size:14px;">{{ url('/api/n8n/generate') }}</code>
                            </div>

                            <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save"></i> Save Key</button>
                        </form>
                    </div>

                    <!-- Workflow Documentation Tab -->
                    <div class="tab-pane" id="docs-tab" role="tabpanel">
                        <div class="p-3 mb-4 rounded" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                            <h4 class="text-primary"><i class="mdi mdi-lightbulb-on mr-2"></i> 목적 (Goal): বাংলাদেশের সব জেলার ভাইরাল খবর সংগ্রহ</h4>
                            <p class="font-16">
                                আমাদের লক্ষ্য হলো n8n ব্যবহার করে বাংলাদেশের শীর্ষস্থানীয় সংবাদমাধ্যম (যেমন: প্রথম আলো, বিডিনিউজ) থেকে <strong>"সারাদেশ"</strong> বা <strong>"জেলা"</strong> ক্যাটাগরির লেটেস্ট এবং ভাইরাল খবরগুলো ছবিসহ কালেক্ট করা এবং আমাদের সাইটে পাঠানো। আমাদের সাইটের AI (Gemini) সেই খবরটিকে ফাইন-টিউন করে (রিরাইট ও নতুন ফরম্যাটে) পাবলিশ করবে।
                            </p>
                        </div>

                        <h5 class="font-weight-bold mt-4 mb-3">Best Setup Architecture</h5>
                        <ul class="list-group mb-4">
                            <li class="list-group-item d-flex align-items-center">
                                <span class="badge badge-primary badge-pill mr-3 p-2">Step 1</span>
                                <div>
                                    <strong>Schedule Trigger:</strong> n8n-এ একটি Schedule নোড দিয়ে প্রতি ১ ঘণ্টা পরপর ফ্লো রান করানো হবে।
                                </div>
                            </li>
                            <li class="list-group-item d-flex align-items-center">
                                <span class="badge badge-info badge-pill mr-3 p-2">Step 2</span>
                                <div>
                                    <strong>RSS Feed Read:</strong> প্রথম আলো বা বিডিনিউজ-এর "সারাদেশ" (All districts) ফিড কল করা হবে।<br>
                                    <small class="text-muted">উদাহরণ: <code>https://www.prothomalo.com/bangladesh/feed</code></small>
                                </div>
                            </li>
                            <li class="list-group-item d-flex align-items-center">
                                <span class="badge badge-warning badge-pill mr-3 p-2">Step 3</span>
                                <div>
                                    <strong>Item Lists (Filter):</strong> গত ১ ঘণ্টার ভেতরের লেটেস্ট আইটেমগুলো ফিল্টার করা হবে যেন পুরোনো খবর না আসে।
                                </div>
                            </li>
                            <li class="list-group-item d-flex align-items-center">
                                <span class="badge badge-success badge-pill mr-3 p-2">Step 4</span>
                                <div>
                                    <strong>HTTP Request (Send to Site):</strong> আপনার সাইটের <code>/api/n8n/generate</code> রাউটে ডাটা পাঠানো হবে।
                                </div>
                            </li>
                        </ul>

                        <hr class="my-5">

                        <h5 class="font-weight-bold mb-3"><i class="mdi mdi-code-json text-warning mr-1"></i> Ready-made n8n Workflow JSON</h5>
                        <p>নিচের কোডটি কপি করে আপনার n8n এর ক্যানভাসে পেস্ট করুন (Ctrl+V)। এটি স্বয়ংক্রিয়ভাবে প্রয়োজনীয় নোডগুলো তৈরি করে দেবে। <br><strong class="text-danger">নোট:</strong> পেস্ট করার পর HTTP Request নোডে গিয়ে আপনার <code>X-API-KEY</code> বসাতে ভুলবেন না।</p>
                        
                        <div class="position-relative">
                            <button id="copyJsonBtn" class="btn btn-sm btn-dark position-absolute" style="top: 10px; right: 10px; z-index: 10;">
                                <i class="mdi mdi-content-copy"></i> Copy JSON
                            </button>
                            <textarea id="n8nJsonData" class="form-control" style="background:#263238; color:#a3f7bf; font-family: monospace; height: 350px; font-size:13px;" readonly>
{
  "nodes": [
    {
      "parameters": {
        "rule": {
          "interval": [
            {
              "field": "hours"
            }
          ]
        }
      },
      "name": "Schedule",
      "type": "n8n-nodes-base.scheduleTrigger",
      "typeVersion": 1,
      "position": [ 250, 300 ]
    },
    {
      "parameters": {
        "url": "https://www.prothomalo.com/bangladesh/feed"
      },
      "name": "RSS - All Districts",
      "type": "n8n-nodes-base.rssFeedRead",
      "typeVersion": 1,
      "position": [ 450, 300 ]
    },
    {
      "parameters": {
        "method": "POST",
        "url": "{{ url('/api/n8n/generate') }}",
        "sendHeaders": true,
        "headerParameters": {
          "parameters": [
            {
              "name": "X-API-KEY",
              "value": "REPLACE_WITH_YOUR_SECRET_KEY"
            }
          ]
        },
        "sendBody": true,
        "bodyParameters": {
          "parameters": [
            {
              "name": "category",
              "value": "sarabangla"
            },
            {
              "name": "title",
              "value": "={{ $json.title }}"
            },
            {
              "name": "url",
              "value": "={{ $json.link }}"
            },
            {
              "name": "content",
              "value": "={{ $json.contentSnippet }}"
            }
          ]
        },
        "options": {}
      },
      "name": "Trigger AI Generation on BDB News",
      "type": "n8n-nodes-base.httpRequest",
      "typeVersion": 3,
      "position": [ 650, 300 ]
    }
  ],
  "connections": {
    "Schedule": {
      "main": [
        [
          {
            "node": "RSS - All Districts",
            "type": "main",
            "index": 0
          }
        ]
      ]
    },
    "RSS - All Districts": {
      "main": [
        [
          {
            "node": "Trigger AI Generation on BDB News",
            "type": "main",
            "index": 0
          }
        ]
      ]
    }
  }
}
                            </textarea>
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
    document.getElementById('copyJsonBtn').addEventListener('click', function() {
        var copyText = document.getElementById("n8nJsonData");
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value);
        
        var btn = this;
        var originalText = btn.innerHTML;
        btn.innerHTML = '<i class="mdi mdi-check"></i> Copied!';
        btn.classList.replace('btn-dark', 'btn-success');
        
        setTimeout(function() {
            btn.innerHTML = originalText;
            btn.classList.replace('btn-success', 'btn-dark');
        }, 2000);
    });
</script>
@endpush
