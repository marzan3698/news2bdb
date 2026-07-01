@extends('layouts.admin')

@section('content')
<div class="row pt-4">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="float-right">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">BDB News</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.ai-sources.index') }}">AI Sources</a></li>
                    <li class="breadcrumb-item active">Add Source</li>
                </ol>
            </div>
            <h4 class="page-title">Add AI Source</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title mt-0 mb-4">Configure New AI News Source</h4>
                
                @if ($errors->any())
                    <div class="alert alert-danger border-0">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.ai-sources.store') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="name" class="font-weight-bold">Source Name</label>
                        <input type="text" name="name" class="form-control" id="name" placeholder="e.g. Prothom Alo RSS" value="{{ old('name') }}" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="type" class="font-weight-bold">Source Type</label>
                        <select name="type" id="type" class="form-control" required>
                            <option value="rss" {{ old('type') == 'rss' ? 'selected' : '' }}>RSS Feed Link (Recommended)</option>
                            <option value="facebook" {{ old('type') == 'facebook' ? 'selected' : '' }}>Facebook Page ID/Username</option>
                            <option value="scraping" {{ old('type') == 'scraping' ? 'selected' : '' }}>Web Page HTML Scraping</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="url" class="font-weight-bold">URL / Address / Page identifier</label>
                        <input type="text" name="url" class="form-control" id="url" placeholder="e.g. https://www.prothomalo.com/feed or facebook-page-username" value="{{ old('url') }}" required>
                        <small class="form-text text-muted" id="url-help">Provide the full RSS XML feed URL.</small>
                    </div>

                    <div class="form-group mb-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="status" name="status" value="1" checked>
                            <label class="custom-control-label font-weight-bold" for="status">Active & Enabled</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3"><i class="mdi mdi-content-save"></i> Save Source</button>
                    <a href="{{ route('admin.ai-sources.index') }}" class="btn btn-secondary mt-3 ml-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('type');
        const urlHelp = document.getElementById('url-help');
        const urlInput = document.getElementById('url');

        function updateHelpText() {
            if(typeSelect.value === 'rss') {
                urlHelp.innerHTML = 'Provide the full RSS XML feed URL (e.g., <code>https://www.prothomalo.com/feed</code>)';
                urlInput.placeholder = 'e.g. https://www.prothomalo.com/feed';
            } else if(typeSelect.value === 'facebook') {
                urlHelp.innerHTML = 'Provide the Facebook Page username or numeric ID (e.g., <code>ProthomAlo</code> or <code>12345678901</code>). Make sure Facebook credentials are saved in settings.';
                urlInput.placeholder = 'e.g. ProthomAlo';
            } else {
                urlHelp.innerHTML = 'Provide the website web page URL to parse (e.g., <code>https://www.thedailystar.net/news-sports</code>)';
                urlInput.placeholder = 'e.g. https://www.thedailystar.net/news-sports';
            }
        }

        typeSelect.addEventListener('change', updateHelpText);
        updateHelpText(); // run once on load
    });
</script>
@endpush
