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
                    <li><strong>ওয়ার্কফ্লো তৈরি:</strong> প্রথমে উপরে দেওয়া <span class="badge badge-success">Copy JSON</span> বাটনে ক্লিক করে JSON কোডটি কপি করুন। এরপর আপনার <a href="https://n8n.io/" target="_blank" class="text-primary font-weight-bold">n8n.io</a> ড্যাশবোর্ডে গিয়ে একটি নতুন ওয়ার্কফ্লো তৈরি করুন এবং ক্যানভাসের উপর মাউস রেখে <code>Ctrl+V</code> (বা <code>Cmd+V</code>) চেপে কোডটি পেস্ট করুন।</li>
                    
                    <li><strong>Webhook URL সেভ করা:</strong> পেস্ট করার পর আপনি "Webhook (From Laravel)" নামে একটি নোড দেখতে পাবেন। সেটিতে ডাবল-ক্লিক করে ওপেন করুন এবং এর <strong>Test URL</strong> অথবা <strong>Production URL</strong> টি কপি করে এই পেজের উপরের "n8n Video Webhook URL" বক্সে দিয়ে <span class="badge badge-primary">Save Settings</span> এ ক্লিক করুন।</li>
                    
                    <li><strong>ভিডিও জেনারেট করা:</strong> n8n এ আপনার ওয়েববুক নোডের পর, আপনাকে অন্যান্য নোডগুলো (যেমন: Gemini/ChatGPT নোড স্ক্রিপ্ট লেখার জন্য, Text-to-Speech নোড ভয়েসওভারের জন্য, এবং Video Generation API/Node) বসাতে হবে। <br>
                    লারাভেল থেকে আপনি ক্যাটাগরি পেয়ে যাবেন (যেমন: Sports), সেটি দিয়ে আপনি Gemini কে প্রম্পট দিতে পারেন: <em>"Generate a 30-second explanation video script about the physics of football in Bengali."</em></li>
                    
                    <li><strong>ফেসবুকে ভিডিও আপলোড:</strong> ভিডিও জেনারেট হয়ে গেলে, n8n এর Facebook Graph API নোড ব্যবহার করে আপনার ফেসবুক পেজে ভিডিওটি আপলোড করুন। আপলোড শেষে আপনি ফেসবুক থেকে একটি ভিডিও আইডি বা লিংক পাবেন। </li>
                    
                    <li><strong>লারাভেলে ডাটা ফেরত পাঠানো:</strong> ওয়ার্কফ্লো এর একদম শেষে "Send Data Back to Laravel" নামের HTTP Request নোডটি ব্যবহার করুন। এই নোডে লারাভেলকে জানাতে হবে যে ভিডিওটি তৈরি হয়ে গেছে।<br>
                    এই নোডের Body Parameters এর ভেতরে আপনাকে ৩টি জিনিস ডাইনামিকভাবে সেট করে দিতে হবে:
                        <ul>
                            <li><code>concept_title</code>: (যেমন: "ফুটবলের ফিজিক্স")</li>
                            <li><code>facebook_video_url</code>: (আপনার ফেসবুক পেজে আপলোড হওয়া ভিডিওর লিংক বা এমবেড লিংক)</li>
                            <li><code>banner_image_url</code>: (নিউজ আর্টিকেলের জন্য একটি থাম্বনেইল ইমেজের লিংক)</li>
                        </ul>
                    </li>
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
