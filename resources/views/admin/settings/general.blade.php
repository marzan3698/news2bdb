@extends('layouts.admin')

@section('page_title', 'General Settings')

@section('content')
<div class="row pt-4">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="float-right">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">BDB News</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0);">Settings</a></li>
                    <li class="breadcrumb-item active">General</li>
                </ol>
            </div>
            <h4 class="page-title">General Settings</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="mt-0 header-title">Site Settings & Branding</h4>
                <p class="text-muted mb-4">Manage your site title, description, and branding assets here. The logo uploaded here is also used as a watermark on crawled news images.</p>

                @if(session('success'))
                    <div class="alert alert-success border-0">
                        <strong>Success!</strong> {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger border-0">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.settings.general.save') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="site_title" class="font-weight-bold">Site Title</label>
                        <input type="text" class="form-control" id="site_title" name="site_title" value="{{ $site_title }}" placeholder="e.g. বিডিবি নিউজ">
                    </div>

                    <div class="form-group mb-3">
                        <label for="site_description" class="font-weight-bold">Site Subtitle / Description</label>
                        <input type="text" class="form-control" id="site_description" name="site_description" value="{{ $site_description }}" placeholder="e.g. সত্যের সন্ধানে সার্বক্ষণিক">
                    </div>

                    <div class="form-group mb-3">
                        <label for="site_logo" class="font-weight-bold">Site Logo (Upload)</label>
                        <input type="file" class="form-control-file" id="site_logo" name="site_logo" accept="image/*">
                        <small class="form-text text-muted">Upload a transparent PNG/WebP logo. Recommended height: 60px. This logo is overlaid at all four corners of automatically fetched news images.</small>
                    </div>

                    @if($site_logo)
                        <div class="mb-4">
                            <label class="font-weight-bold d-block">Current Logo Preview</label>
                            <div class="p-3 bg-light d-inline-block rounded border">
                                <img src="{{ $site_logo }}" alt="Site Logo" style="max-height: 60px; object-fit: contain;">
                            </div>
                        </div>
                    @endif

                    <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save"></i> Save General Settings</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
