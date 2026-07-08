@extends('layouts.admin')

@section('title', 'Post Video News')
@section('page_header', 'Post AI Video News')
@section('page_title', 'Video News')

@section('content')
<div class="row">
    <div class="col-lg-6 mx-auto">
        <div class="card">
            <div class="card-body">
                <h4 class="mt-0 header-title">Trigger n8n Video Generation</h4>
                <p class="text-muted mb-4">Select a category to automatically generate a unique video script, voiceover, and compile the video via your n8n workflow. The final video will be posted to your Facebook Page and embedded as a news article.</p>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <form action="{{ route('admin.video-news.trigger') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="category">Select Video Category</label>
                        <select name="category" id="category" class="form-control" required>
                            <option value="">-- Choose Category --</option>
                            <option value="Sports">Sports (স্পোর্টস)</option>
                            <option value="Politics">Politics (রাজনীতি)</option>
                            <option value="Science">Science (বিজ্ঞান ও প্রযুক্তি)</option>
                            <option value="History">History (ইতিহাস)</option>
                            <option value="Entertainment">Entertainment (বিনোদন)</option>
                            <option value="Business">Business (ব্যবসা-বাণিজ্য)</option>
                        </select>
                        <small class="form-text text-muted">The AI will generate an explanation or scientific video based on this category (e.g. Physics of Football for Sports).</small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg mt-4" id="generateBtn">
                        <i class="mdi mdi-video-vintage mr-2"></i> Generate & Post Video
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    $('form').on('submit', function() {
        let btn = $('#generateBtn');
        btn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Sending to n8n...');
        btn.prop('disabled', true);
    });
</script>
@endpush
