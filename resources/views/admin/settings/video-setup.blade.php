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
                        <input type="url" class="form-control" id="n8n_video_webhook_url" name="n8n_video_webhook_url" value="{{  }}" placeholder="https://n8n.yourdomain.com/webhook/video-generate">
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
                    <input type="text" class="form-control" id="callbackUrl" value="{{  }}" readonly>
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('callbackUrl')">Copy</button>
                    </div>
                </div>
                
                <h5 class="mt-4">Quick Start: n8n Workflow JSON</h5>
                <p class="text-muted">Copy this JSON and paste it directly into your n8n canvas. It contains the trigger webhook and the callback HTTP request pre-configured.</p>
                
                <div class="position-relative">
                    <textarea id="n8nJson" class="form-control bg-dark text-light" rows="6" readonly style="font-family: monospace; font-size: 12px;">{{  }}</textarea>
                    <button class="btn btn-sm btn-success position-absolute" style="top: 10px; right: 10px;" onclick="copyToClipboard('n8nJson')">Copy JSON</button>
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
