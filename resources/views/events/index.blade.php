@extends('layouts.main')
@section('title', 'Events')
@section('content_header')
    <h1>Events</h1>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('message'))
    <div class="alert alert-danger">
        {{ session('message') }}
    </div>
@endif

<!-- Search & Filters Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom">
        <h6 class="mb-0 d-flex align-items-center">
            <i class="fas fa-search text-primary me-2"></i>
            Search & Filter Events
        </h6>
    </div>
    <div class="card-body">
        <form action="{{ route('event.index') }}" method="GET">
            <div class="row g-3">
                <!-- Search Input -->
                <div class="col-lg-4 col-md-6">
                    <label for="searchInput" class="form-label fw-semibold">
                        <i class="fas fa-magnifying-glass text-muted me-1"></i> Search
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" 
                               id="searchInput" 
                               name="searchInput" 
                               class="form-control border-start-0 ps-0" 
                               placeholder="Search by name or location..." 
                               value="{{ old('searchInput', request('searchInput')) }}">
                    </div>
                </div>

                <!-- Category Type -->
                <div class="col-lg-4 col-md-6">
                    <label for="category_type" class="form-label fw-semibold">
                        <i class="fas fa-tag text-muted me-1"></i> Category Type
                    </label>
                    <select class="form-select" id="category_type" name="category_type">
                        <option value="">All Categories</option>
                        @foreach($ticketCategoryType as $categoryType)
                            <option value="{{ $categoryType->id }}" 
                                    {{ old('category_type', request('category_type')) == $categoryType->id ? 'selected' : '' }}>
                                {{ ucfirst($categoryType->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="col-lg-4 col-md-12">
                    <label class="form-label fw-semibold d-none d-md-block">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" id="searchButton" class="btn btn-primary flex-fill">
                            <i class="fas fa-search me-1"></i> Search
                        </button>
                        <a href="{{ route('event.index') }}" class="btn btn-outline-secondary flex-fill">
                            <i class="fas fa-times me-1"></i> Clear
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Quick Actions & Management Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-0">
                    <i class="fas fa-calendar-alt text-primary"></i> Event Management
                </h5>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <!-- Bulk Actions -->
                <div class="btn-group" role="group">
                    <a href="javascript:void(0);" 
                       id="inactive-event" 
                       class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-pause-circle me-1"></i> Deactivate All
                    </a>
                    <a href="javascript:void(0);" 
                       id="activate-event" 
                       class="btn btn-outline-success btn-sm">
                        <i class="fas fa-play-circle me-1"></i> Activate All
                    </a>
                </div>
                
                <!-- Navigation Actions -->
                <div class="btn-group" role="group">
                    <a href="{{ route('event.archive') }}" 
                       class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-box-archive me-1"></i> Archived
                    </a>
                    <a href="{{ route('event.create') }}" 
                       class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> Add Event
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Card Styling */
.card {
    border-radius: 12px;
    overflow: hidden;
}

.card-header {
    border-top-left-radius: 12px !important;
    border-top-right-radius: 12px !important;
}

/* Form Controls */
.form-select,
.form-control {
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 0.5rem 0.75rem;
    font-size: 0.9375rem;
    transition: all 0.2s ease;
}

.form-select:focus,
.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
}

/* Input Group Styling */
.input-group-text {
    border-radius: 8px 0 0 8px;
    border: 1px solid #ced4da;
    transition: all 0.2s ease;
}

.input-group .form-control:focus + .input-group-text,
.input-group:focus-within .input-group-text {
    border-color: #0d6efd;
    background-color: #e7f1ff;
}

.input-group .form-control {
    border-radius: 0 8px 8px 0;
}

/* Form Label */
.form-label {
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    color: #495057;
}

/* Button Styling */
.btn-sm {
    padding: 0.375rem 0.875rem;
    font-size: 0.875rem;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #0a58ca 0%, #084298 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
}

.btn-outline-danger:hover {
    background-color: #dc3545;
    border-color: #dc3545;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
}

.btn-outline-success:hover {
    background-color: #198754;
    border-color: #198754;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(25, 135, 84, 0.3);
}

.btn-outline-secondary:hover {
    background-color: #6c757d;
    border-color: #6c757d;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(108, 117, 125, 0.3);
}

/* Button Group */
.btn-group {
    border-radius: 8px;
    overflow: hidden;
}

.btn-group .btn {
    border-radius: 0;
}

.btn-group .btn:first-child {
    border-top-left-radius: 6px;
    border-bottom-left-radius: 6px;
}

.btn-group .btn:last-child {
    border-top-right-radius: 6px;
    border-bottom-right-radius: 6px;
}

/* Icon Styling */
.fas, .far {
    font-size: 0.875rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .d-flex.flex-wrap.gap-2 {
        flex-direction: column;
    }
    
    .btn-group {
        width: 100%;
    }
    
    .btn-group .btn {
        flex: 1;
    }
}

/* Gap utility for older Bootstrap versions */
.gap-2 {
    gap: 0.5rem;
}

.gap-3 {
    gap: 1rem;
}

/* Hover Effects */
.card {
    transition: all 0.3s ease;
}

/* Focus styles for accessibility */
.form-control:focus,
.form-select:focus,
.btn:focus {
    outline: none;
}

/* Custom scrollbar for select */
.form-select {
    background-position: right 0.75rem center;
    background-size: 16px 12px;
}

/* Loading state */
.btn:disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

/* Success and Error States */
.is-invalid {
    border-color: #dc3545;
}

.is-valid {
    border-color: #198754;
}
</style>

@if($eventData->isNotEmpty())
<div class="row">
    @foreach($eventData as $event)
        <div class="col-md-4 mb-4">
            <div class="card m-4">
                <div class="banner">
                    <img class="card-img-top" src="{{ asset($event->image) }}" alt="Ticket image">
                        <div class="card-overlay">
                            <p class="category-text">{{ strtoupper($event->name) }}</p>
                        </div>
                        <div class="card-upper">
                            <p class="category-text">{{ strtoupper($event->city_code) }}</p>
                        </div>
                </div>
                <div class="card-body">
                    <p class="card-text"><strong>Event Location :</strong> {{ $event->address }} </p>
                    <p class="card-text"><strong>Event Start Date : </strong> {{ formatDate($event->start_date ) }}</p>
                    <p class="card-text"><strong>Event End Date  : </strong>{{ formatDate($event->end_date) }} </p>
                    @if($event->tickets->count() > 0)
                        <p class="card-text"><strong>Total Tickets: </strong>{{ $event->tickets->count() }}</p>
                    @endif
                    <p class="card-text"><strong>Ticket Availability :</strong></p>
                    <ul>
                    @foreach($event->ticketCategoryCounts as $ticketCategoryCount)
                        @if ($ticketCategoryCount->ticketCategory)
                            <li>
                                <a href="javascript:void(0);"
                                onclick="availableTicket({{ $ticketCategoryCount->ticketCategory->id }}, {{ $event->id }});">
                                 {{ $ticketCategoryCount->ticketCategory->name }}: {{ $ticketCategoryCount->total }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                    </ul>
                    <p class="card-text">
                        @if ($event->active)
                            <span style="color: green;">&#9679; Active</span>
                        @else
                            <span style="color: red;">&#9679; Inactive</span>
                        @endif
                    </p>
                    <p class="card-text"><strong>Event Status : </strong>
                    @if($event->open_for == 'sale' && $event->tickets->count()  > 0)
                    <a href="javascript:void(0);" onclick="availablefor({{ $event->id }});" class="btn btn-success m-2">{{ $event->open_for }}</a>    </p>
                    @elseif($event->open_for == 'pending')
                         <a href="{{ route('event.edit', $event->id) }}" class="btn btn-primary btn-sm">pending</i></a></p>
                    @else
                    <p class="card-text"><button type="button" class="btn btn-danger disabled">Sold</button></p></p>
                    @endif
                    <a href="{{ route('event.edit', $event->id) }}" class="btn text-primary"><i class="fas fa-edit"></i></a>
                    <a href="javascript:void(0);" onclick="deleteEvent({{ $event->id }});" class="btn text-danger"><i class="fas fa-trash"></i></a>
                    <form id="delete-form-{{ $event->id }}" action="{{ route('event.delete', $event->id) }}" method="POST" style="display: none;">
                        @csrf
                        @method('POST')
                    </form>
                    <!-- <a href="{{ route('event.view', $event->id) }}" class="btn text-primary"><i class="fas fa-eye">Ticket Status</i></a> -->
                    <a href="javascript:void(0);" onclick="archiveEvent({{ $event->id }});"  class="btn text-primary"><i class="fas fa-archive">archive</i></a>
                    <form id="archive-{{ $event->id }}" action="{{ route('event.moveToarchive', $event->id) }}" method="POST">
                        @csrf
                        @method('POST')
                    </form>
                </div>
            </div>
        </div>  
    @endforeach
</div>

<div class="float-right">
    {{ $eventData->links() }}
</div>
@else
    <div class="text-align-center">Events not found</div>
@endif
@endsection

@push('scripts')

<script>

function deleteEvent(eventId) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'Are you sure you want to delete this event?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            confirmButtonColor: '#dc3545',
            cancelButtonText: 'Cancel',
            cancelButtonColor: '#007bff',
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + eventId).submit();
            }
         });
    }

function availablefor(eventId) {
    window.location.href = "{{ route('ticket.index') }}?eventId=" + eventId;
}

function availableTicket(ticketId, eventId) {
    var url = "{{ route('ticket.index') }}?ticketId=" + ticketId + '&eventId=' + eventId;
    window.open(url, '_blank');
}

function archiveEvent(eventId) {
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
                document.getElementById('archive-' + eventId).submit();
            }
         });
}

$('#inactive-event').on('click', function() {
    Swal.fire({
            title: 'Are you sure?',
            text: 'Are you sure you want to inactivate all the events',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            confirmButtonColor: 'primary',
            cancelButtonText: 'Cancel',
            cancelButtonColor: '#dc3545',
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "POST",
                        url: "{{ route('event.inactive') }}",
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        success: function(data, status) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: 'Events inactive successfully.',
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = "{{ route('event.index') }}";
                                    }
                                });
                            },
                            error: function (xhr) {
                                console.log('Failed to inactive events');
                            }
                        });
                }
            });
});

$('#activate-event').on('click', function() {
    Swal.fire({
            title: 'Are you sure?',
            text: 'Are you sure you want to activate  all the events',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            confirmButtonColor: '#28a745',
            cancelButtonText: 'Cancel',
            cancelButtonColor: '#dc3545',
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "POST",
                        url: "{{ route('event.active') }}",
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        success: function(data, status) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: 'Events activated successfully.',
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                       window.location.href = "{{ route('event.index') }}";
                                    }
                                });
                            },
                            error: function (xhr) {
                                console.log('Failed to inactive events');
                            }
                        });
                }
            });
});

</script>
@endpush

<style>

.banner {
    position: relative;
}

.card-img-top {
    display: block;
    width: 100%;
    height: auto;
}

.card-overlay {
    position: absolute;
    top: 0;
    left: 0;
    height: 193%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 10px;
    pointer-events: none;
}

.category-text {
    color: #ffed00;
    font-size: 1.5em;
    font-weight: bold;
    text-align: center;
}

.card-upper {
    position: absolute;
    top: 0;
    left: 85%;
    display: flex;
    align-items: center;
    justify-content: right;
    margin-left: 10px;
    pointer-events: none;
}
</style>