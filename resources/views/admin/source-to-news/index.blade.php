@extends('layouts.admin')

@section('title', 'All Source List')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col-12">
                        <h4 class="page-title">All Source List</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="mt-0 header-title mb-4">Manage News Sources</h4>
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>jago 1</td>
                                <td>
                                    <div class="custom-control custom-switch switch-success">
                                        <input type="checkbox" class="custom-control-input source-status-toggle" id="source-toggle" {{ $jagoStatus == '1' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="source-toggle"></label>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(document).ready(function() {
    $('.source-status-toggle').on('change', function() {
        var status = $(this).prop('checked') ? 1 : 0;
        
        $.ajax({
            url: "{{ route('admin.source-to-news.toggle-status') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                status: status
            },
            success: function(response) {
                if(response.success) {
                    Swal.fire('Updated!', response.message, 'success');
                } else {
                    Swal.fire('Error!', 'Could not update status.', 'error');
                }
            },
            error: function() {
                Swal.fire('Error!', 'Something went wrong.', 'error');
            }
        });
    });
});
</script>
@endpush
