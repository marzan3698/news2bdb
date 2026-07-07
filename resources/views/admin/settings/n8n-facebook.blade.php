@extends('layouts.admin')

@section('page_title', 'n8n + Facebook Auto Post')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="mt-0 header-title">n8n + Facebook Workflow Setup</h4>
                <p class="text-muted mb-4">Set up an automated workflow to post newly published news from your site directly to your Facebook Page using n8n.</p>

                <!-- Nav Tabs -->
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active font-weight-bold text-dark" data-toggle="tab" href="#connection-tab" role="tab"><i class="mdi mdi-connection mr-1"></i> Webhook Configuration</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold text-dark" data-toggle="tab" href="#docs-tab" role="tab"><i class="mdi mdi-book-open-page-variant mr-1"></i> Setup Guide (Bangla)</a>
                    </li>
                </ul>

                <!-- Tab Panes -->
                <div class="tab-content">
                    
                    <!-- Connection Details Tab -->
                    <div class="tab-pane active" id="connection-tab" role="tabpanel">
                        <form action="{{ route('admin.settings.n8n-facebook.save') }}" method="POST">
                            @csrf
                            <div class="form-group mb-3">
                                <label for="n8n_facebook_webhook_url" class="font-weight-bold">n8n Webhook URL (For Facebook Post)</label>
                                <input type="url" class="form-control" id="n8n_facebook_webhook_url" name="n8n_facebook_webhook_url" value="{{ $n8n_facebook_webhook_url ?? '' }}" placeholder="e.g. https://n8n.yourdomain.com/webhook/facebook-post">
                                <small class="form-text text-muted">Paste the Production Webhook URL from your n8n workflow here. Whenever a news article is published, we will send data to this URL.</small>
                            </div>

                            <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save"></i> Save Webhook URL</button>
                        </form>
                    </div>

                    <!-- Workflow Documentation Tab -->
                    <div class="tab-pane" id="docs-tab" role="tabpanel">
                        <div class="p-3 mb-4 rounded" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                            <h4 class="text-primary"><i class="mdi mdi-facebook mr-2"></i> 목적 (Goal): স্বয়ংক্রিয়ভাবে ফেসবুকে নিউজ পোস্ট করা</h4>
                            <p class="font-16">
                                আমাদের লক্ষ্য হলো— যখনই এই সাইটে কোনো নতুন নিউজ পাবলিশ হবে, সেটি স্বয়ংক্রিয়ভাবে n8n এর মাধ্যমে আপনার ফেসবুক পেজে ছবি, টাইটেল, সাবটাইটেল এবং হ্যাশট্যাগ সহ পোস্ট হয়ে যাবে। 
                            </p>
                        </div>

                        <h5 class="font-weight-bold mt-4 mb-3">কিভাবে সেটআপ করবেন? (Step-by-step Guide)</h5>
                        <ul class="list-group mb-4">
                            <li class="list-group-item d-flex align-items-center">
                                <span class="badge badge-primary badge-pill mr-3 p-2">Step 1</span>
                                <div>
                                    <strong>n8n এ JSON ইম্পোর্ট:</strong> নিচের JSON কোডটি কপি করুন এবং আপনার n8n এর ওয়ার্কফ্লো ক্যানভাসে পেস্ট (Ctrl+V) করুন। এটি স্বয়ংক্রিয়ভাবে Webhook এবং Facebook Graph API নোড তৈরি করে দেবে।
                                </div>
                            </li>
                            <li class="list-group-item d-flex align-items-center">
                                <span class="badge badge-info badge-pill mr-3 p-2">Step 2</span>
                                <div>
                                    <strong>Facebook Credentials অ্যাড করা:</strong> Facebook Graph API নোডে ডাবল ক্লিক করুন। "Credential for Facebook Graph API" অংশ থেকে আপনার ফেসবুক পেজের এক্সেস টোকেন (Page Access Token) দিয়ে নতুন ক্রেডেনশিয়াল তৈরি করুন।
                                </div>
                            </li>
                            <li class="list-group-item d-flex align-items-center">
                                <span class="badge badge-warning badge-pill mr-3 p-2">Step 3</span>
                                <div>
                                    <strong>Webhook URL কপি করা:</strong> Webhook নোডে ক্লিক করুন। সেখান থেকে <strong>Production URL</strong> টি কপি করুন।
                                </div>
                            </li>
                            <li class="list-group-item d-flex align-items-center">
                                <span class="badge badge-success badge-pill mr-3 p-2">Step 4</span>
                                <div>
                                    <strong>সাইটে URL সেভ করা:</strong> এই পেজের <strong>Webhook Configuration</strong> ট্যাবে গিয়ে কপি করা URL টি পেস্ট করুন এবং সেভ করুন।
                                </div>
                            </li>
                            <li class="list-group-item d-flex align-items-center">
                                <span class="badge badge-danger badge-pill mr-3 p-2">Step 5</span>
                                <div>
                                    <strong>n8n ওয়ার্কফ্লো Active করা:</strong> n8n এর উপরের ডান পাশ থেকে ওয়ার্কফ্লোটি <strong>Active</strong> করে দিন। ব্যাস! এখন থেকে সাইটে নিউজ পাবলিশ হলে তা ফেসবুকে পোস্ট হয়ে যাবে।
                                </div>
                            </li>
                        </ul>

                        <hr class="my-5">

                        <h5 class="font-weight-bold mb-3"><i class="mdi mdi-code-json text-warning mr-1"></i> Ready-made n8n Workflow JSON</h5>
                        <p>নিচের কোডটি কপি করে আপনার n8n এর ক্যানভাসে পেস্ট করুন (Ctrl+V)।</p>
                        
                        <div class="position-relative">
                            <button id="copyJsonBtn" class="btn btn-sm btn-dark position-absolute" style="top: 10px; right: 10px; z-index: 10;">
                                <i class="mdi mdi-content-copy"></i> Copy JSON
                            </button>
                            <textarea id="n8nJsonData" class="form-control" style="background:#263238; color:#a3f7bf; font-family: monospace; height: 350px; font-size:13px;" readonly>
{
  "nodes": [
    {
      "parameters": {
        "httpMethod": "POST",
        "path": "facebook-post",
        "options": {}
      },
      "name": "Webhook",
      "type": "n8n-nodes-base.webhook",
      "typeVersion": 1,
      "position": [
        250,
        300
      ],
      "webhookId": "facebook-post-webhook-id"
    },
    {
      "parameters": {
        "httpRequestMethod": "POST",
        "node": "me",
        "edge": "photos",
        "options": {
          "queryParameters": {
            "parameter": [
              {
                "name": "url",
                "value": "=@{{ $('Webhook').item.json.body.image }}"
              },
              {
                "name": "message",
                "value": "=@{{ $('Webhook').item.json.body.title }}\n\n@{{ $('Webhook').item.json.body.subtitle }}\n\nবিস্তারিত পড়ুন: @{{ $('Webhook').item.json.body.url }}\n\n@{{ $('Webhook').item.json.body.tags }}"
              }
            ]
          }
        }
      },
      "name": "Facebook Graph API",
      "type": "n8n-nodes-base.facebookGraphApi",
      "typeVersion": 1,
      "position": [
        500,
        300
      ],
      "credentials": {
        "facebookGraphApi": {
          "id": "1",
          "name": "Facebook Page Account"
        }
      }
    }
  ],
  "connections": {
    "Webhook": {
      "main": [
        [
          {
            "node": "Facebook Graph API",
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
