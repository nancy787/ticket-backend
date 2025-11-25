@extends('layouts.main')
@section('title', 'Reports')

@section('content_header')
    Reports
@endsection

@section('content')

<div class="row">
    <div class="col-md-12">
        <ul class="nav nav-tabs" id="myTabs">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" data-status="AppUsersReport">App users</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" data-status="WishlistReport">WishList</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" data-status="TicketSoldReport">Archived/Ticket sold</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" data-status="ArchivedReports">Sales</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" data-status="SubscriptionReports">WishList Subscription</a>
            </li>
        </ul>
    </div>
</div>

<div class="loader" style="display:none;">Loading...</div>
<div id="successMessage" class="alert alert-success" style="display:none; margin-top: 20px;"></div>
<div id="errorMessage" class="alert alert-danger" style="display:none; margin-top: 20px;"></div>

<form id="reportForm" method="GET">
    @csrf
    <div class="">
        <div class="row">
            <div class="col-md-8">
                <label for="">Start Date</label>
            <input type="date" name="start_date" id="start_date" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label for="start_time">Start Time</label>
                <input type="time" name="start_time" id="start_time" class="form-control" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-8">
                <label for="">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label for="start_time">End Time</label>
                <input type="time" name="end_time" id="end_time" class="form-control" required>
            </div>
        </div>
    </div>
    <div id="eventDropdown" style="display: none;">
        <div class="form-group">
            <label for="event_id">Select Venue</label>
            <select class="form-control" id="event_id" name="event_id">
                <option value="">Select all event</option>
                @foreach($getEventName as $event)
                <option value="{{ $event->id }}">{{ $event->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <input type="submit" class="btn btn-primary mt-4" value="Generate CSV report">
</form>

@endsection

<style>
    #successMessage {
        display: none;
        margin-top: 20px;
    }

    #errorMessage {
        display: none;
        margin-top: 20px;
    }
</style>

@push('scripts')
<script>
$(document).ready(function() {
    $('#start_date, #end_date').on('focus', function() {
        if (this.type === 'date') {
            this.showPicker(); // This may not be necessary for most modern browsers
        }
    });

    $('#start_time, #end_time').on('focus', function() {
        if (this.type === 'time') {
            this.showPicker(); // This may not be necessary for most modern browsers
        }
    });

    var routes = {
        'AppUsersReport': '{{ route('report.app-users') }}',
        'WishlistReport': '{{ route('report.wislist-reports') }}',
        'TicketSoldReport': '{{ route('report.ticket-sold-reports') }}',
        'ArchivedReports': '{{ route('report.sales-reports') }}',
        'SubscriptionReports': '{{ route('reports.wishlist-subscription-reports') }}',
    };

    function setFormAction(reportType) {
        let formAction = routes[reportType];
        $('#reportForm').attr('action', formAction);
    }

    function toggleFields(reportType) {
        if (reportType === 'WishlistReport') {
            $('#eventDropdown').show();
            $('#start_date, #end_date, #start_time, #end_time').parent().hide();
            $('#start_date, #end_date').prop('required', false);
            $('#start_time, #end_time').prop('required', false);
        } else if (reportType === 'AppUsersReport') {
            $('#eventDropdown').hide();
            $('#start_date, #end_date, #start_time, #end_time').parent().show();
            $('#start_date, #end_date').prop('required', false);
            $('#start_time, #end_time').prop('required', false);
        } else {
            $('#eventDropdown').hide();
            $('#start_date, #end_date').parent().show();
            $('#start_date, #end_date').prop('required', false);
            $('#start_time, #end_time').parent().hide();
            $('#start_time, #end_time').prop('required', false);
        }
    }

    setFormAction('AppUsersReport');
    toggleFields('AppUsersReport');

    $('#myTabs .nav-link').on('click', function(e) {
        e.preventDefault();
        $('.loader').show();
        let reportType = $(this).data('status');
        setFormAction(reportType);
        toggleFields(reportType);
        $('#reportForm').trigger('reset'); // Clear form fields
        $('#myTabs .nav-link').removeClass('active');
        $(this).addClass('active');
        $('.loader').hide();
    });

    $('#reportForm').on('submit', function(e) {
        e.preventDefault();
        $('.loader').show();
        $('#successMessage').hide();
        $('#errorMessage').hide();

        let form = $(this);
        let actionUrl = form.attr('action');

        $.ajax({
            url: actionUrl,
            type: 'GET',
            data: form.serialize(),
            success: function(response) {
                $('.loader').hide();
                if (response.success) {
                    $('#successMessage').html(`
                        CSV Report generated successfully!
                        <a href="${response.reportUrl}" class="btn btn-success ml-2" download>Download Report</a>
                    `).show();
                    setTimeout(function() {
                        $('#successMessage').fadeOut('slow');
                    }, 5000);
                } else {
                    $('#errorMessage').html('Failed to generate the report. Please try again.').show();
                }
            },
            error: function(xhr) {
                $('.loader').hide();
                let errorMsg = 'An error occurred while generating the report.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMsg = xhr.responseJSON.error;
                }
                $('#errorMessage').html(errorMsg).show();
            }
        });
    });
});

</script>
@endpush
