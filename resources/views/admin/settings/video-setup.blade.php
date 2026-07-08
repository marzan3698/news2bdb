@extends('layouts.admin')

@section('title', 'AI Video Setup')
@section('page_header', 'AI Video Setup')
@section('page_title', 'Settings')

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h4 class="mt-0 header-title">n8n Integration Details</h4>
                <p class="text-muted mb-4">Configure where Laravel will send the trigger for video generation.</p>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <form action="{{ route('admin.settings.video-setup.save') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="n8n_video_webhook_url">n8n Video Webhook URL</label>
                        <input type="url" class="form-control" id="n8n_video_webhook_url" name="n8n_video_webhook_url" value="{{ $n8n_video_webhook_url }}" placeholder="https://n8n.yourdomain.com/webhook/video-generate">
                        <small class="form-text text-muted">The URL of the Webhook node in your n8n workflow.</small>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h4 class="mt-0 header-title">Laravel to n8n Callback URL</h4>
                <p class="text-muted mb-4">Your n8n workflow MUST send a POST request to this URL when the video is generated and uploaded to Facebook.</p>
                
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="callbackUrl" value="{{ $callback_url }}" readonly>
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('callbackUrl')">Copy</button>
                    </div>
                </div>
                
                <h5 class="mt-4">Quick Start: n8n Workflow JSON</h5>
                <p class="text-muted">Copy this JSON and paste it directly into your n8n canvas. It contains the trigger webhook and the callback HTTP request pre-configured.</p>
                
                <div class="position-relative">
                    <textarea id="n8nJson" class="form-control bg-dark text-light" rows="6" readonly style="font-family: monospace; font-size: 12px;">{{ $n8n_template_json }}</textarea>
                    <button class="btn btn-sm btn-success position-absolute" style="top: 10px; right: 10px;" onclick="copyToClipboard('n8nJson')">Copy JSON</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card bg-light border-info">
            <div class="card-body">
                <h4 class="mt-0 header-title text-info"><i class="mdi mdi-information-outline"></i> n8n এ Video News সেটআপ করার সম্পূর্ণ গাইডলাইন</h4>
                <p class="text-muted mb-4">নিচের ধাপগুলো অনুসরণ করে আপনি সহজেই আপনার n8n ওয়ার্কফ্লো সেটআপ করতে পারবেন:</p>
                
                <ol class="font-14" style="line-height: 1.8;">
                    <li><strong>ওয়ার্কফ্লো তৈরি:</strong> প্রথমে উপরে দেওয়া <span class="badge badge-success">Copy JSON</span> বাটনে ক্লিক করে JSON কোডটি কপি করুন। এরপর আপনার <a href="https://n8n.io/" target="_blank" class="text-primary font-weight-bold">n8n.io</a> ড্যাশবোর্ডে গিয়ে একটি নতুন ওয়ার্কফ্লো তৈরি করুন এবং ক্যানভাসের উপর মাউস রেখে <code>Ctrl+V</code> (বা <code>Cmd+V</code>) চেপে কোডটি পেস্ট করুন। পেস্ট করার পর আপনি ৬টি নোড দেখতে পাবেন।</li>
                    
                    <li><strong>1. Webhook (Trigger):</strong> এই নোডে ডাবল-ক্লিক করে এর <strong>Test URL</strong> অথবা <strong>Production URL</strong> টি কপি করে এই পেজের উপরের "n8n Video Webhook URL" বক্সে দিয়ে <span class="badge badge-primary">Save Settings</span> এ ক্লিক করুন। লারাভেল এখান থেকেই ক্যাটাগরি ডেটা পাঠাবে।</li>
                    
                    <li><strong>2. Write Script (OpenAI):</strong> এই নোডটি লারাভেল থেকে পাওয়া ক্যাটাগরি (যেমন: Sports) অনুযায়ী বাংলায় ৩০ সেকেন্ডের একটি আকর্ষণীয় সায়েন্টিফিক স্ক্রিপ্ট লিখবে। আপনার যদি OpenAI API Key সেভ করা না থাকে, তাহলে Credentials থেকে যুক্ত করে নেবেন। (আপনার যদি OpenAI এর বদলে Gemini ব্যবহার করতে ইচ্ছে হয়, তাহলে এই নোডটি ডিলিট করে Gemini নোড যুক্ত করে নিতে পারেন)।</li>

                    <li><strong>3. Generate Voiceover (ElevenLabs / TTS):</strong> এই নোডটি OpenAI থেকে পাওয়া টেক্সটকে অডিওতে রূপান্তর করবে। JSON এ আমরা ElevenLabs এর ডেমো দিয়েছি, আপনি চাইলে Google TTS বা OpenAI এর TTS ব্যবহার করতে পারেন। নোডটিতে আপনার API Key বসিয়ে নেবেন।</li>

                    <li><strong>4. Create Video (Your API):</strong> এই নোডটি আপনার পছন্দের Video Generation API (যেমন: HeyGen, D-ID, Runway) এ রিকোয়েস্ট পাঠিয়ে অডিও এবং ছবি থেকে ভিডিও তৈরি করবে। এই নোডটিতে আপনার ভিডিও সার্ভিসের URL এবং API Key বসিয়ে নেবেন।</li>
                    
                    <li><strong>5. Upload to Facebook:</strong> ভিডিও জেনারেট হয়ে গেলে এই নোডটি আপনার ফেসবুক পেজে ভিডিওটি আপলোড করবে। যেহেতু পেজের <strong>Graph API Access Token</strong> আগে থেকেই লারাভেলে সেটআপ করা আছে, তাই n8n এ শুধুমাত্র Credentials এ সেই টোকেনটি কানেক্ট করতে হবে এবং আপনার <code>Page ID</code> নোডটিতে বসিয়ে দিতে হবে। আপলোড শেষে ফেসবুক একটি ভিডিও আইডি রিটার্ন করবে।</li>
                    
                    <li><strong>6. Send Data Back to Laravel:</strong> এটি ওয়ার্কফ্লো এর একদম শেষ নোড। এটি স্বয়ংক্রিয়ভাবে ফেসবুকের ভিডিও আইডিটি দিয়ে একটি লিংক তৈরি করবে এবং টাইটেলসহ লারাভেলে পাঠিয়ে দেবে। এর ফলে আপনার ওয়েবসাইটে নিউজটি পাবলিশ হয়ে যাবে। এই নোডের সব ডেটা ম্যাপিং আগে থেকেই করা আছে, আপনাকে কিছু করতে হবে না।</li>
                </ol>

                <div class="alert alert-warning mt-3 mb-0">
                    <strong>গুরুত্বপূর্ণ:</strong> n8n এর Webhook নোডটি সবসময় Active বা "Listen for Test Event" মোডে থাকতে হবে। তা না হলে "Post Video" পেজ থেকে জেনারেট রিকোয়েস্ট দিলে তা কাজ করবে না।
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    function copyToClipboard(elementId) {
        var copyText = document.getElementById(elementId);
        copyText.select();
        copyText.setSelectionRange(0, 99999); // For mobile devices
        document.execCommand("copy");
        
        Toastify({
            text: "Copied to clipboard!",
            duration: 3000,
            close: true,
            gravity: "top",
            position: "right",
            style: { background: "linear-gradient(to right, #00b09b, #96c93d)" }
        }).showToast();
    }
</script>
@endpush
