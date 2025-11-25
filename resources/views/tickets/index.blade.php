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
<!-- Filters Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom">
        <h6 class="mb-0 d-flex align-items-center">
            <i class="fas fa-filter text-primary me-2"></i>
            Filter Tickets
        </h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label for="filterCity" class="form-label fw-semibold">
                    <i class="fas fa-city text-muted me-1"></i> City
                </label>
                <select id="filterCity" class="form-select">
                    <option value="">All Cities</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filterDivision" class="form-label fw-semibold">
                    <i class="fas fa-layer-group text-muted me-1"></i> Division
                </label>
                <select id="filterDivision" class="form-select">
                    <option value="">All Divisions</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="filterDay" class="form-label fw-semibold">
                    <i class="fas fa-calendar-day text-muted me-1"></i> Day
                </label>
                <select id="filterDay" class="form-select">
                    <option value="">All Days</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="filterStatus" class="form-label fw-semibold">
                    <i class="fas fa-info-circle text-muted me-1"></i> Status
                </label>
                <select id="filterStatus" class="form-select">
                    <option value="">All Status</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('ticket.index') }}" class="btn btn-outline-danger w-100" onclick="clearFilters()">
                    <i class="fas fa-times me-1"></i> Clear Filters
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Tickets Table Card -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-bottom py-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">
                    <i class="fas fa-ticket-alt text-primary"></i> All Tickets
                </h5>
            </div>
            <div class="btn-toolbar" role="toolbar">
                <div class="btn-group me-2" role="group">
                    <button id="bulkArchiveBtn" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-archive"></i> Bulk Archive
                    </button>
                </div>
                <div class="btn-group" role="group">
                    <a href="{{ route('ticket.archive') }}" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-box-archive"></i> Archived
                    </a>
                    <a href="{{ route('ticket.deleted-tickets') }}" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-trash"></i> Deleted
                    </a>
                    <a href="{{ route('ticket.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Add Ticket
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="ticketTable" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="selectAllTickets">
                            </div>
                        </th>
                        <th>Ticket ID</th>
                        <th>Sale/Resell</th>
                        <th>Status</th>
                        <th>Sold Date</th>
                        <th>City</th>
                        <th>Dates</th>
                        <th>Division</th>
                        <th>Day</th>
                        <th class="text-center">Cha</th>
                        <th class="text-center">Pho</th>
                        <th class="text-center">Fri</th>
                        <th class="text-center">Spe</th>
                        <th class="text-center">MT</th>
                        <th class="text-center">UT</th>
                        <th class="text-center">DL</th>
                        <th class="text-end">Total</th>
                        <th>Seller</th>
                        <th>Buyer</th>
                        <th class="text-center">Verified</th>
                        <th class="text-center" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Table content will be populated by DataTables -->
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('tickets.sell_ticket_model')

@endsection

@push('scripts')
<script>

$(document).ready(function () {
    let table;

    // Restore filter values from sessionStorage
    const savedCity = sessionStorage.getItem('filterCity');
    const savedDivision = sessionStorage.getItem('filterDivision');
    const savedDay = sessionStorage.getItem('filterDay');
    const savedStatus = sessionStorage.getItem('filterStatus');
    
    // Set filter values if they exist
    if (savedCity) $('#filterCity').val(savedCity);
    if (savedDivision) $('#filterDivision').val(savedDivision);
    if (savedDay) $('#filterDay').val(savedDay);
    if (savedStatus) $('#filterStatus').val(savedStatus);

    // Fetch filter data and populate dropdowns
    fetchFilterData();

    function fetchFilterData() {
        $.ajax({
            url: "{{ route('ticket.filter') }}",
            method: 'GET',
            success: function (data) {
                
                populateFilter('#filterCity', data.cities, savedCity);
                populateFilter('#filterDivision', data.divisions, savedDivision);
                populateFilter('#filterDay', data.days, savedDay);
                populateFilter('#filterStatus', data.status, savedStatus);

                // Initialize DataTable after filters are populated
                initDataTable();
            },
            error: function (xhr, status, error) {
                console.error("Failed to fetch filter data:", error);
            }
        });
    }

    function populateFilter(filterId, data, selectedValue) {
        const select = $(filterId);
        select.empty().append('<option value="">All</option>');
        data.forEach(function (item) {
            const isSelected = item === selectedValue ? 'selected' : '';
            select.append(`<option value="${item}" ${isSelected}>${item}</option>`);
        });
    }

    function initDataTable() {
        const urlParams = new URLSearchParams(window.location.search);
        const ticketId = urlParams.get('ticketId');
        const eventId = urlParams.get('eventId');

        table = $('#ticketTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('ticket.data') }}",
                type: 'GET',
                data: function (d) {  
                    d.city = $('#filterCity').val();
                    d.division = $('#filterDivision').val();
                    d.day = $('#filterDay').val();
                    d.status = $('#filterStatus').val();
                    if (ticketId) {
                        d.ticketId = ticketId;
                    }
                    if (eventId) {
                        d.eventId = eventId;
                    }
                },
                dataSrc: function (json) {
                    return json.data;
                },
                error: function (xhr, error, thrown) {
                    console.log('Ajax error: ' + error);
                }
            },
            order: [[0, 'desc']],
            columns: [
                { data: 'checkbox', searchable: false },
                { data: 'ticket_id' },
                { data: 'sale/resell' },
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
                { data: 'dl' },
                { data: 'total' },
                { data: 'seller' },
                { data: 'buyer' },
                { data: 'verified' },
                { data: 'actions', orderable: false, searchable: false }
            ],
            pageLength: 100,
            language: {
                processing: "<span>Loading tickets...</span>"
            },
        });

        // Save filter values to sessionStorage on change and reload table
        $('#filterCity, #filterDivision, #filterDay, #filterStatus').on('change', function () {
            sessionStorage.setItem('filterCity', $('#filterCity').val());
            sessionStorage.setItem('filterDivision', $('#filterDivision').val());
            sessionStorage.setItem('filterDay', $('#filterDay').val());
            sessionStorage.setItem('filterStatus', $('#filterStatus').val());
            table.ajax.reload();
        });
    }
});

function deleteTicket(ticketId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'Are you sure you want to delete this ticket?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        confirmButtonColor: '#dc3545',
        cancelButtonText: 'Cancel',
        cancelButtonColor: '#007bff',
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-form-' + ticketId).submit();
        }
    });
}

function removeBuyer(ticketId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'Are you sure you want to remove buyer from this ticket?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        confirmButtonColor: '#dc3545',
        cancelButtonText: 'Cancel',
        cancelButtonColor: '#007bff',
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('remove-buyer-' + ticketId).submit();
        }
    });
}

function verifyTicket(ticketId) {
    document.getElementById('verify-ticket-' + ticketId).submit();
}

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

function archiveTicket(ticketId) {
    Swal.fire({
            title: 'Are you sure?',
            text: 'Are you sure you want to move  this to archive',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            confirmButtonColor: 'primary',
            cancelButtonText: 'Cancel',
            cancelButtonColor: '#007bff',
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('archive-' + ticketId).submit();
            }
        });
}

function userDetails(userId) {
    var url = "{{ route('users.data') }}?userId=" + userId;
    window.open(url, '_blank');
}

$(document).ready(function() {
    $('#bulkArchiveBtn').on('click', function(){
        var selectedIds = [];
        $('.bulkarchive:checked').each(function(){
            selectedIds.push($(this).val());
        });

        if(selectedIds.length > 0) {
            $('.loader').show();
            $.ajax({
                url: "{{ route('ticket.bulk-archive') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    ids: selectedIds
                },
                success: function(response) {
                    $('.loader').hide();
                    Swal.fire({
                        title: 'Tickets Moved to archive',
                        text: 'Tickets Moved to archive successfully.',
                        icon: 'info',
                        cancelButtonText: 'Cancel',
                        cancelButtonColor: '#007bff',
                    }).then((
                        window.location.href = "{{ route('ticket.index') }}"
                    ));
                },
                error: function(response) {
                    console.log(response);
                }
            });
        } else {
            Swal.fire({
                title: 'Tickets not selcted',
                text: 'Please select at least one ticket to archive.',
                icon: 'info',
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#007bff',
            });
        }
    });
});

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

function clearFilters() {
    sessionStorage.removeItem('filterCity');
    sessionStorage.removeItem('filterDivision');
    sessionStorage.removeItem('filterDay');
    sessionStorage.removeItem('filterStatus');
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
/* 
.filter-container {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    margin: 20px 0; 
}

.form-group {
    margin: 10px;
    flex: 1;
    min-width: 200px;
}

label {
    font-weight: bold;
    display: block;
    margin-bottom: 5px;
}

select.form-control {
    width: 100%;
    padding: 10px;
    font-size: 14px;
    border-radius: 4px;
    border: 1px solid #ccc;
    box-sizing: border-box;
}

select.form-control:focus {
    border-color: #007bff;
    outline: none;
} */

</style>
