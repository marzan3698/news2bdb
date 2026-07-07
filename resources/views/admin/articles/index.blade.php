@extends('layouts.admin')

@section('content')
<div class="row pt-4">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="float-right">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">BDB News</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0);">Admin</a></li>
                    <li class="breadcrumb-item active">Articles</li>
                </ol>
            </div>
            <h4 class="page-title">Manage Articles</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="header-title mt-0">All Articles</h5>
                    <div>
                        <a href="{{ route('admin.articles.create') }}" class="btn btn-primary"><i class="mdi mdi-plus-circle mr-2"></i>Add New</a>
                    </div>
                </div>

                <div class="table-responsive" style="overflow-x:auto; width:100%;">
                    <table id="datatable" class="table table-bordered table-hover" style="width:100%; min-width:900px;">
                        <thead>
                            <tr>
                                <th style="width:50px;">ID</th>
                                <th style="width:90px;">Image</th>
                                <th>Title</th>
                                <th style="width:110px;">Category</th>
                                <th style="width:140px;">Source</th>
                                <th style="width:90px;">Status</th>
                                <th style="width:145px;">Created At</th>
                                <th style="width:80px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data populated by DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('modals')

@endpush

@push('css')

    #datatable_wrapper {
        overflow-x: auto;
        width: 100%;
    }
    #datatable th, #datatable td {
        white-space: nowrap;
    }
    #datatable th:nth-child(3), #datatable td:nth-child(3) {
        white-space: normal;
        min-width: 200px;
        max-width: 380px;
    }
</style>
@endpush

@push('js')
<!-- DataTables JS CDN -->
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
    let articlesTable;

    $(document).ready(function() {
        articlesTable = $('#datatable').DataTable({
            processing: true,
            scrollX: true,
            autoWidth: false,
            ajax: {
                url: '{{ route('admin.articles.data') }}',
                dataSrc: 'data'
            },
            columns: [
                { data: 'id', width: '50px' },
                { 
                    data: 'image_url',
                    width: '90px',
                    orderable: false,
                    render: function(data, type, row) {
                        return data ? `<img src="${data}" alt="img" class="img-thumbnail" style="max-width:75px; max-height:48px; object-fit:cover; border-radius:4px;">` : '<span class="text-muted small">No Image</span>';
                    }
                },
                { data: 'title', width: '300px' },
                { 
                    data: 'category.name',
                    width: '110px',
                    render: function(data) { return data || '<span class="text-muted">N/A</span>'; }
                },
                {
                    data: 'source_name',
                    width: '140px',
                    render: function(data) {
                        if (!data) return '<span class="text-muted">—</span>';
                        if (data === 'AI Generated') {
                            return '<span class="badge" style="background:#6c757d;color:#fff;font-size:10px;padding:4px 8px;border-radius:12px;white-space:nowrap;">🤖 AI</span>';
                        }
                        return `<span class="badge" style="background:#1da255;color:#fff;font-size:10px;padding:4px 8px;border-radius:12px;white-space:nowrap;">📰 ${data}</span>`;
                    }
                },
                { 
                    data: 'status',
                    width: '90px',
                    render: function(data) {
                        let badge = data === 'published' ? 'badge-success' : 'badge-warning';
                        return `<span class="badge ${badge}">${data}</span>`;
                    }
                },
                { 
                    data: 'created_at',
                    width: '145px',
                    render: function(data) {
                        if (!data) return '—';
                        const d = new Date(data);
                        return d.toLocaleDateString('bn-BD', {day:'2-digit',month:'2-digit',year:'numeric'}) + ' ' + d.toLocaleTimeString('en', {hour:'2-digit',minute:'2-digit'});
                    }
                },
                {
                    data: 'id',
                    width: '80px',
                    orderable: false,
                    render: function(data, type, row) {
                        return `<button class="btn btn-sm btn-danger" onclick="deleteArticle(${data})" title="Delete"><i class="mdi mdi-delete"></i></button>`;
                    }
                }
            ],
            order: [[0, 'desc']],
            language: {
                processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading...',
                emptyTable: 'No articles found.'
            }
        });
    });

    function deleteArticle(id) {
        Swal.fire({
            title: 'Delete Article?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/articles/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        Swal.fire('Deleted!', 'Article has been deleted.', 'success');
                        articlesTable.ajax.reload();
                    },
                    error: function() {
                        Swal.fire('Error!', 'Could not delete the article.', 'error');
                    }
                });
            }
        });
    }
</script>
@endpush
