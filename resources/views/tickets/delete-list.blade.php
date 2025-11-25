@extends('layouts.main')
@section('title', 'Tickets')
@section('content_header')
    <h1>Tickets</h1>
@endsection

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissable">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissable">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        {{ session('error') }}
</div>
@endif

@if(session('message'))
    <div class="alert alert-danger">
        {{ session('message') }}
    </div>
@endif
<div class="loader" style="display: none;"></div>
<div class="row" style="margin-bottom:10px; important">
    <div class="col-md-4">
    <label for="filterCity">City:</label>
        <select id="filterCity" class="form-control">
            <option value="">All</option>
        </select>
    </div>
    <div class="col-md-4">
    <label for="filterDivision">Division:</label>
        <select id="filterDivision" class="form-control">
            <option value="">All</option>
        </select>
    </div>
    <div class="col-md-3">
    <label for="filterDay">Day:</label>
        <select id="filterDay" class="form-control">
            <option value="">All</option>
        </select>
    </div>
    <div class="col-md-1">
        <a href="{{ route('ticket.deleted-tickets') }}"  class="btn btn-danger btn-sm form-control-plaintext" style="margin-top: 33px;">Clear</a>
    </div>
</div>

<div class="table-responsive">
    <a href="{{ route('ticket.index') }}" class="btn btn-primary btn-sm float-right ml-2">Back</a>
<table id="ticketTable" class="table table-hover">
    <thead>
        <tr>
            <th colspan="col">Ticket Id</th>
            <th colspan="col">Ticket Status</th>
            <th scope="col">Sold Date</th>
            <th scope="col">City Name</th>
            <th scope="col">Dates</th>
            <th scope="col">Division</th>
            <th scope="col">Day</th>
            <th scope="col">Cha</th>
            <th scope="col">Pho</th>
            <th scope="col">Fri</th>
            <th scope="col">Spe</th>
            <th scope="col">MT</th>
            <th scope="col">UT</th>
            <th scope="col">Total</th>
            <th scope="col">Seller</th>
            <th scope="col">Buyer</th>
            <th scope="col">Verified</th>
            <th scope="col">Deleted At</th>
            <th scope="col">Restore Tickets</th>
        </tr>
    </thead>
    <tbody>

    </tbody>
</table>
</div>
@endsection

@push('scripts')
<script>

$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const ticketId = urlParams.get('ticketId');
    const eventId = urlParams.get('eventId');

    var table = $('#ticketTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('ticket.get-deleted-tickets') }}",
            type: 'GET',
            data: function(d) {
                d.city = $('#filterCity').val();
                d.division = $('#filterDivision').val();
                d.day = $('#filterDay').val();
                if(ticketId) {
                    d.ticketId = ticketId;
                }
                if(eventId) {
                    d.eventId = eventId;
                }
            },
            dataSrc: function(json) {
                return json.data;
            },
            error: function(xhr, error, thrown) {
                console.log('Ajax error: ' + error);
            }
        },
        order: [[0, 'desc']],
        columns: [
            { data: 'ticket_id' },
            { data: 'ticket_status' },
            { data: 'sold_date' },
            { data: 'city_name' },
            { data: 'dates' },
            { data: 'division' },
            { data: 'day' },
            { data: 'cha' },
            { data: 'pho' },
            { data: 'fri' },
            { data: 'spe' },
            { data: 'mt' },
            { data: 'ut' },
            { data: 'total' },
            { data: 'seller' },
            { data: 'buyer' },
            { data: 'verified' },
            { data: 'deleted_at' },
            { data: 'restore_tickets' },
        ],
        pageLength: 100,
        language: {
            processing: "<span>Loading tickets...</span>"
        },
    });

    $('#filterCity, #filterDivision, #filterDay').on('change', function () {
        table.ajax.reload(); 
    });
});

$(document).ready(function () {
    let table; 
    fetchFilterData();

    function fetchFilterData() {
        $.ajax({
            url: "{{ route('ticket.filter') }}",
            method: 'GET',
            success: function (data) {
                populateFilter('#filterCity', data.cities);
                populateFilter('#filterDivision', data.divisions);
                populateFilter('#filterDay', data.days);
            },
            error: function (xhr, status, error) {
                console.error("Failed to fetch filter data:", error);
            }
        });
    }

    function populateFilter(filterId, data) {
        const select = $(filterId);
        select.empty().append('<option value="">All</option>');
        data.forEach(function (item) {
            select.append(`<option value="${item}">${item}</option>`);
        });
    }
});

$(document).ready(function() {
    $('.dropdown-toggle').dropdown();

    // Attach the click event only to the status dropdown items
    $('.dropdown-item.status-change').on('click', function() {
        var newValue = $(this).attr('data-value');
        var ticketId = $(this).attr('data-ticket-id');
        var statusDropdown = $('#statusDropdown' + ticketId);

        statusDropdown.text(newValue);

        var url = '{{ route('ticket.change-status', ['id' => ':ticketId']) }}';
        url = url.replace(':ticketId', ticketId);

        // Update button class based on newValue
        switch(newValue) {
            case 'pending':
                statusDropdown.removeClass('btn-danger btn-success btn-warning').addClass('btn-primary');
                break;
            case 'sold':
                statusDropdown.removeClass('btn-primary btn-success btn-warning').addClass('btn-danger');
                break;
            case 'available':
                statusDropdown.removeClass('btn-primary btn-danger btn-warning').addClass('btn-success');
                break;
            case 'withdrawn':
                statusDropdown.removeClass('btn-primary btn-danger btn-success').addClass('btn-warning');
                break;
            default:
                break;
        }

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                status: newValue
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                window.location.href = "{{ route('ticket.index') }}";
            },
            error: function(xhr) {
                console.error('Error updating status:', xhr.responseText);
            }
        });
    });
});

function userDetails(userId) {
    var url = "{{ route('users.data') }}?userId=" + userId;
    window.open(url, '_blank');
}

$(document).ready(function() {
    $('.dropdown-submenu > a').on('click', function(e) {
        var $subMenu = $(this).next('.dropdown-menu');
        $subMenu.toggleClass('show');
        e.stopPropagation();
        e.preventDefault();
    });
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown-menu').length) {
            $('.dropdown-menu').removeClass('show');
        }
    });
    $('.ticket-link').on('click', function() {
        $('.ticket-link').removeClass('active');
        $(this).addClass('active');
    });
});

function confirmRestore(ticketId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to restore this ticket?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, restore it!'
        }).then((result) => {
            if (result.isConfirmed) {
                let form = document.createElement('form');
                form.method = 'POST';
                form.action = '/tickets/restore/' + ticketId;

                let csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';

                form.appendChild(csrfToken);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
@endpush

<style>
.btn-orange {
background-color: #fd7e14 !important;
}

/* Style for the dropdown submenu */
.dropdown-menu .dropdown-submenu {
position: relative;
}

.dropdown-menu .dropdown-submenu .dropdown-menu {
display: none; /* Hide by default */
position: absolute;
top: 0;
right: 100%; /* Position it to the left of the parent */
margin-right: 0;
border-radius: 0.25rem;
z-index: 1000; /* Ensure it appears above other content */
white-space: nowrap; /* Prevent text from wrapping */
}

/* Show on hover */
.dropdown-menu .dropdown-submenu:hover > .dropdown-menu {
display: block;
}

/* Ensure dropdown does not go outside the viewport */
.dropdown-menu .dropdown-submenu .dropdown-menu {
max-width: 200px; /* Set a max width to prevent overflow */
}

/* Adjust dropdown if it's too close to the viewport edge */
.dropdown-menu .dropdown-submenu {
position: relative;
}

.dropdown-menu .dropdown-submenu .dropdown-menu {
/* Ensure dropdown stays within viewport */
left: auto;
right: calc(100% - 15px); /* Adjust for margin and padding */
}
</style>
