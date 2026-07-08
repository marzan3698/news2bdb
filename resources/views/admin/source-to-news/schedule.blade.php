@extends('layouts.admin')

@section('title', 'Schedule Snews (Auto Cloning)')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col-12">
                        <h4 class="page-title">Schedule Snews (Cron Auto-Clone)</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="mt-0 header-title mb-4">Cron Configuration</h4>
                    
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('admin.source-to-news.schedule.save') }}" method="POST">
                        @csrf
                        
                        <div class="form-group mb-4">
                            <label class="d-block">Status</label>
                            <div class="custom-control custom-switch switch-success">
                                <input type="checkbox" class="custom-control-input" id="snews_schedule_status" name="snews_schedule_status" {{ $scheduleStatus == '1' ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold" for="snews_schedule_status" id="status-label">
                                    {{ $scheduleStatus == '1' ? 'Active' : 'Inactive' }}
                                </label>
                            </div>
                            <small class="text-muted">Turn on to enable auto-cloning via cron job.</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="snews_schedule_interval">Time Interval (Minutes)</label>
                            <input type="number" class="form-control" id="snews_schedule_interval" name="snews_schedule_interval" value="{{ $scheduleInterval }}" min="1" required>
                            <small class="text-muted">How often should the cron job run? (e.g. 10 for every 10 minutes)</small>
                        </div>

                        <div class="form-group mb-4">
                            <label for="snews_schedule_count">Number of News per Run</label>
                            <input type="number" class="form-control" id="snews_schedule_count" name="snews_schedule_count" value="{{ $scheduleCount }}" min="1" max="50" required>
                            <small class="text-muted">How many news items to clone every time the cron runs? (e.g. 4)</small>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h4 class="mt-0 header-title mb-4 text-dark"><i class="fas fa-terminal mr-2"></i>cPanel Cron Job Setup</h4>
                    <p class="text-muted">
                        To make this work automatically, you must set up a Cron Job in your cPanel. <br>
                        Copy the exact command below and paste it into the <strong>Command</strong> field in your cPanel Cron Jobs section. Set the time interval to match your settings.
                    </p>
                    
                    <div class="form-group">
                        <label>Dynamic Command:</label>
                        <div class="input-group">
                            <textarea id="cron_command" class="form-control bg-dark text-warning font-weight-bold" rows="3" readonly style="font-family: monospace; resize: none;"></textarea>
                            <div class="input-group-append">
                                <button class="btn btn-success" type="button" id="copy-btn" title="Copy to clipboard">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
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
    const baseUrl = "{{ url('/') }}";
    const cronSecret = "{{ $cronSecret }}";

    function updateCronCommand() {
        let interval = $('#snews_schedule_interval').val();
        if(!interval || interval < 1) interval = 10;
        
        // Ensure interval is formatted for standard cron
        let cronTime = `*/${interval} * * * *`;
        if (interval >= 60) {
            let hours = Math.floor(interval / 60);
            cronTime = `0 */${hours} * * *`; // approximate if hours
        }
        
        let url = `${baseUrl}/cron/snews?key=${cronSecret}`;
        let command = `${cronTime} /usr/bin/wget -q -O - "${url}" >/dev/null 2>&1`;
        
        $('#cron_command').val(command);
    }

    $(document).ready(function() {
        // Initial setup
        updateCronCommand();

        // Update on input
        $('#snews_schedule_interval').on('input', updateCronCommand);

        // Status label toggle
        $('#snews_schedule_status').on('change', function() {
            if($(this).is(':checked')) {
                $('#status-label').text('Active');
            } else {
                $('#status-label').text('Inactive');
            }
        });

        // Copy button
        $('#copy-btn').on('click', function() {
            var copyText = document.getElementById("cron_command");
            copyText.select();
            copyText.setSelectionRange(0, 99999); /* For mobile devices */
            document.execCommand("copy");
            
            let btn = $(this);
            let originalHtml = btn.html();
            btn.html('<i class="fas fa-check"></i> Copied');
            setTimeout(function() {
                btn.html(originalHtml);
            }, 2000);
        });
    });
</script>
@endpush
