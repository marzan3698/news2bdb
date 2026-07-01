@extends('layouts.admin')

@section('content')
<div class="row pt-4">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="float-right">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">BDB News</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.articles.index') }}">Articles</a></li>
                    <li class="breadcrumb-item active">Add New</li>
                </ol>
            </div>
            <h4 class="page-title">Add New Article</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.articles.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="title">Article Title</label>
                        <input type="text" name="title" class="form-control" id="title" required>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select name="category_id" id="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="content">Article Content</label>
                        <textarea name="content" class="form-control" id="content" rows="10" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3">Save Article</button>
                    <a href="{{ route('admin.articles.index') }}" class="btn btn-secondary mt-3 ml-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
