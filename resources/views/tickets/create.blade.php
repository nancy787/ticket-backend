@extends('layouts.main')
@section('title', isset($ticketData) ? 'Edit Ticket' : 'Create Ticket')

@section('content_header')
    <h1>{{ isset($ticketData) ? '#'.($ticketData->ticket_id ?? '') : 'Create Ticket' }}</h1>
@endsection

@section('content')
<a href="{{ route('ticket.index') }}" class="btn btn-primary m-2 float-right"> Back </a>
@if(Request::segment(2) == 'view')
        <button class="btn btn-success m-2">Sell Ticket</button>
@endif

<div class="loader"></div>
@if(isset($ticketData) &&  $ticketData->available_for == 'sold')
        <form id="archive-{{ $ticketData->id }}" action="{{ route('ticket.moveToarchive', $ticketData->id) }}" method="POST" style="display: none;">
            @csrf
        </form>
@endif

@if(isset($ticketData))
        <form id="delete-form-{{ $ticketData->id }}" action="{{ route('ticket.delete', $ticketData->id) }}" method="POST" style="display: none;">
            @csrf
        </form>
@endif

<form id="ticketForm" enctype="multipart/form-data">
    @csrf
    @if(Request::segment(2) != 'view')
        <button type="submit" class="btn btn-primary m-2">{{ isset($ticketData)? 'Update Ticket' : 'Create Ticket' }}</button>
    @endif
    @if(isset($ticketData) && $ticketData->available_for == 'sold')
        <a href="javascript:void(0);" onclick="archiveTicket({{ $ticketData->id }});" class="btn btn-primary">Archive Ticket</a>
    @endif

    @if(isset($ticketData))
        <a href="javascript:void(0);" onclick="deleteTicket({{ $ticketData->id }});" class="btn btn-danger">Delete Ticket</a>
    @endif

    <input type="hidden" name="ticket_id" value="{{ $ticketData->id ?? '' }}">
        <div class="form-group">
            <label for="event_id">Event</label>
            <select class="form-control" id="event_id" name="event_id">
             <option value="">Select Venue</option>
                @foreach($eventDetails as $event)
                <option value="{{ $event->id }}" {{ isset($ticketData) && $ticketData->event_id == $event->id ? 'selected' : '' }}>{{ $event->name }}</option>
                @endforeach
            </select>
            <div class="text text-danger p-1" id="eventError"></div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <label for="category_type">Category (Division)</label>
                    <select class="form-control" name="ticket_category_type" id="category_type">
                        @foreach($ticketCategoryTypes as $ticketCategoryType)
                            <option value="{{ $ticketCategoryType->id }}" {{ isset($ticketData) && $ticketData->ticketCategory->ticket_category_type_id == $ticketCategoryType->id ? 'selected' : '' }}>
                                    {{ ucFirst($ticketCategoryType->name) }}
                            </option>
                        @endforeach
                    </select>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="category_id">Select Category Type</label>
                        <select class="form-control" id="category_id" name="category_id">
                            @if(isset($ticketData))
                                @foreach ($ticketCategories as $category)
                                    <option value="{{ $category->id }}" 
                                        {{ isset($ticketData) && $ticketData->ticketCategory->id == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <div class="text text-danger p-1" id="category_idError"></div>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="text text-danger" id="categoryError"></div>
                <button type="button" class="btn btn-primary float-right" data-toggle="modal" data-target="#addCategoryModal">
                    Add Category
                </button>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ isset($ticketData) ? formatDateForInput($ticketData->start_date) : old('start_date') }}" min="{{ date('Y-m-d') }}" readonly>
                </div>
                <div class="text text-danger p-1" id="start_dateError"></div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ isset($ticketData) ? formatDateForInput($ticketData->end_date) : old('end_date') }}" min="{{ date('Y-m-d') }}" readonly>
                </div>
                <div class="text text-danger p-1" id="end_dateError"></div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="day">Select Day</label>
                    <select class="form-control" id="day" name="day">
                        @isset($ticketData)
                            <option value="{{ $ticketData->day }}">{{ formatDayInput($ticketData->day) }}</option>
                        @endisset
                    </select>
                    <div class="text-danger p-1" id="dayError"></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-1">
                <div class="form-group">
                    <label for="price">Currency Type</label>
                    <input type="text" class="form-control" id="currency_type" name="currency_type" placeholder="Currency" value="{{ isset($ticketData) ? $ticketData->currency_type :  old('currency_type') }}" readonly>
                    <div class="text text-danger p-1" id="currency_typeError"></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="price">Price</label>
                    <input type="number" class="form-control" id="price" name="price" placeholder="Enter price" value="{{ isset($ticketData) ? $ticketData->price ?? 0 :  old('price') }}">
                    <div class="text text-danger p-1" id="priceError"></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="service">Service Charge</label>
                    <input type="number" class="form-control" id="service_charge" name="service_charge" placeholder="Enter price" value="{{ isset($ticketData) ? $ticketData->service ??  0 :  old('service') }}">
                    <div class="text text-danger p-1" id="serviceError"></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="service">Change Fee</label>
                    <input type="number" class="form-control" id="change_fee" name="change_fee" placeholder="Enter price" value="{{ isset($ticketData) ? $ticketData->change_fee ?? 0 :  old('change_fee') }}">
                    <div class="text text-danger p-1" id="change_feeError"></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="total">Total</label>
                    <input type="number" class="form-control" id="total" name="total" placeholder="Enter total" value="{{ isset($ticketData) ? $ticketData->total ?? 0 : old('total') }}" readonly> 
                    <div class="text text-danger p-1" id="totalError"></div>
                </div>
            </div>
        </div>

        <div class="row">
            @if(isset($addons->photo_pack) && $addons->photo_pack)
            <div class="col-md-4">
                    <div class="form-group">
                    <label for="photo_pack">Photo Pack</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <button type="button" class="btn btn-outline-secondary" onclick="changeValue(-1, 'photo_pack')">-</button>
                            </div>
                            <input type="number" class="form-control" id="photo_pack" name="photo_pack" value="{{ isset($ticketData) && $ticketData->photo_pack ? $ticketData->photo_pack : 0 }}" readonly>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="changeValue(1, 'photo_pack')">+</button>
                            </div>
                        </div>
                        <div class="text text-danger p-1" id="photo_packError"></div>
                    </div>
            </div>
            @endif

            @if(isset($addons->race_with_friend) && $addons->race_with_friend)
            <div class="col-md-4">
                    <div class="form-group">
                    <label for="race_with_friend">Race With Friend</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <button type="button" class="btn btn-outline-secondary" onclick="changeValue(-1, 'race_with_friend')">-</button>
                            </div>
                            <input type="number" class="form-control" id="race_with_friend" name="race_with_friend" value="{{ isset($ticketData) && $ticketData->race_with_friend ? $ticketData->race_with_friend : 0 }}" readonly>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="changeValue(1, 'race_with_friend')">+</button>
                            </div>
                        </div>
                        <div class="text text-danger p-1" id="photo_packError"></div>
                    </div>
            </div>
            @endif

            @if(isset($addons->spectator) && $addons->spectator)
            <div class="col-md-4">
                    <div class="form-group">
                        <label for="spectator">Spectator</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <button type="button" class="btn btn-outline-secondary" onclick="changeValue(-1, 'spectator')">-</button>
                            </div>
                            <input type="number" class="form-control" id="spectator" name="spectator" value="{{ isset($ticketData) && $ticketData->spectator ? $ticketData->spectator : 0 }}" readonly>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="changeValue(1, 'spectator')">+</button>
                            </div>
                        </div>
                        <div class="text text-danger p-1" id="photo_packError"></div>
                    </div>
            </div>
            @endif
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="open_for">Ticket Status</label>
                    <select class="form-control" id="available_for" name="available_for">
                        <option value="available" {{ isset($ticketData) && $ticketData->available_for == 'available' ? 'selected' : '' }}>Available</option>
                        <option value="pending" {{ isset($ticketData) && $ticketData->available_for == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="withdrawn" {{ isset($ticketData) && $ticketData->withdrawn == 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                        @if(isset($ticketData) && $ticketData->isverified)
                        <option value="sold" {{ isset($ticketData) && $ticketData->available_for == 'sold' ? 'selected' : '' }}>Sold</option>
                        @endif
                    </select>
                    <input type="hidden" id="hidden_available_for" name="available_for" value="">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="seller">Add Seller</label>
                        <select class="form-control" id="seller" name="seller">
                            <option value="">Select Seller</option>
                            @foreach($userData as $user)
                                <option value="{{ $user->id }}" {{ isset($ticketData) && $ticketData->created_by == $user->id ? 'selected' : '' }} >{{ $user->name }}  ({{ $user->email }})</option>
                            @endforeach
                        </select>
                </div>
            </div>
            <div class="col-md-1">
                <div class="form-group form-check m-4">
                    <input type="hidden" name="isverified" value="0">
                    <input type="checkbox" class="form-check-input" id="isverified" name="isverified" value="1" {{ isset($ticketData) && $ticketData->isverified ? 'checked' : '' }}>
                    <label class="form-check-label" for="isverified">Verified</label>
                </div>
            </div>

            @if(isset($addons->charity_ticket) && $addons->charity_ticket)
                <div class="col-md-1">
                    <div class="form-group form-check m-4"> 
                            <input type="hidden" name="charity_ticket" value="0">
                            <input type="checkbox" class="form-check-input" id="charity_ticket" name="charity_ticket" value="1" {{  isset($ticketData) &&  $ticketData->charity_ticket  ? 'checked' : '' }}>
                            <label class="form-check-label" for="charity_ticket">Charity Ticket</label>
                        </div>
                </div>
            @endif

            <div class="col-md-1">
                <div class="form-group form-check m-4"> 
                        <input type="hidden" name="multiple_tickets" value="0">
                        <input type="checkbox" class="form-check-input" id="multiple_tickets" name="multiple_tickets" value="1" {{  isset($ticketData) &&  $ticketData->multiple_tickets  ? 'checked' : '' }}>
                        <label class="form-check-label" for="multiple_tickets">Multiple Tickets</label>
                    </div>
            </div>
            <div class="col-md-1">
                <div class="form-group form-check m-4">
                        <input type="hidden" name="unpersonalised_ticket" value="0">
                        <input type="checkbox" class="form-check-input" id="unpersonalised_ticket" name="unpersonalised_ticket" value="1" {{  isset($ticketData) &&  $ticketData->unpersonalised_ticket  ? 'checked' : '' }}>
                        <label class="form-check-label" for="unpersonalised_ticket">Unpersonalise Ticket</label>
                    </div>
            </div>

            <div class="col-md-1">
                <div class="form-group form-check m-4">
                        <input type="hidden" name="duplicate_link" value="0">
                        <input type="checkbox" class="form-check-input" id="duplicate_link" name="duplicate_link" value="1" {{  isset($ticketData) &&  $ticketData->dublicate_link  ? 'checked' : '' }} disabled>
                        <label class="form-check-label" for="duplicate_link">Duplicate Link</label>
                    </div>
            </div>
        </div>

        <div id="linkInputs" style="display: block;">
        @if(!empty($ticketLinks))
        @foreach ($ticketLinks as $index => $link)
                <div class="form-group" id="link-input-group-{{ $index + 1 }}">
                        <label for="ticket_link-{{ $index + 1 }}">Add Ticket Link {{ $index + 1 }}</label>
                        <input type="url" class="form-control ticket-link-input" id="ticket_link-{{ $index + 1 }}" name="ticket_links[]" value="{{ $link }}" placeholder="Enter the link">
                        <a class="ticket-link-anchor" href="{{ $link }}" target="_blank">Click here to visit the link</a>
                        <button type="button" class="btn btn-danger remove-attachment" data-attachment-id="link-input-group-{{ $index + 1 }}"><i class="fas fa-trash"></i></button>
                    </div>
        @endforeach
        @else
        <div class="form-group" id="link-input-group-1">
            <label for="ticket_link-1">Add Ticket Link 1</label>
            <input type="url" class="form-control ticket-link-input" id="ticket_link-1" name="ticket_links[]" placeholder="Enter the link">
            <a class="ticket-link-anchor" href="#" target="_blank" style="display:none;">Click here to visit the link</a>
        </div>
        @endif
        </div>
        <button type="button" class="btn btn-secondary" id="addMoreLinks">Add More link</button>

        <br>
        <label for="image">Image</label>
            @if(isset($ticketData) && $ticketData->image)
                <img src="{{ asset($ticketData->image) }}" width="500px" alt="Ticket Image">
            @endif
            <div class="form-group">
                <input type="file" class="form-control" id="image" name="image" >
                <div class="text text-danger p-1" id="imageError"></div>
            </div>
</form>

@include('tickets.ticket_catgories.model')

@endsection

@push('scripts')

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function () {
        var initialStatus = $('#available_for').val();
        $('#hidden_available_for').val(initialStatus);

        $('#available_for').on('change', function () {
            $('#hidden_available_for').val($(this).val());
        });

        $('#ticketForm').on('submit', function (e) {
            e.preventDefault();
        $('#available_for').prop('disabled', false);
        var formData = new FormData(this);
        formData.append('image', $('#image')[0].files[0]);
        var ticket_id = $('input[name="ticket_id"]').val();
        var url = "{{ route('ticket.store') }}";

        if (ticket_id) {
            url = "{{ route('ticket.update', ['id' => ':ticket_id']) }}".replace(':ticket_id', ticket_id);
        }

        $.ajax({
            type: "POST",
            url: url,
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
              $('.loader').show();
            },
            success: function(data, status) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message,
                }).then((result) => {
                    if (result.isConfirmed) {
                       window.location.href = "{{ route('ticket.index') }}";
                    }
                });
            },
            error: function (xhr) {
                var errors = xhr.responseJSON.errors;
                $('.loader').hide();
                $('.text-danger').empty();
                $.each(errors, function (key, value) {
                    $('#' + key + 'Error').text(value[0]);
                });
            }
        });
    });
});

$(document).ready(function() {
    function loadCategories(categoryTypeId, selectedCategoryId = null) {
        if (categoryTypeId) {
            $.ajax({
                url: "{{ route('ticket.getcategory', ':categoryTypeId') }}".replace(':categoryTypeId', categoryTypeId),
                type: 'GET',
                success: function(response) {
                    $('#category_id').empty();
                    $.each(response, function(key, value) {
                        $('#category_id').append('<option value="' + value.id + '">' + value.name + '</option>');
                    });
                    if (selectedCategoryId) {
                        $('#category_id').val(selectedCategoryId);
                    }
                }
            });
        } else {
            $('#category_id').empty();
            $('#category_id').append('<option value="">Select Category Type</option>');
        }
    }

    $('#category_type').change(function() {
        var categoryTypeId = $(this).val();
        loadCategories(categoryTypeId);
    });

    var initialCategoryTypeId = $('#category_type').val();
    var initialSelectedCategoryId = $('#category_id').val();
    loadCategories(initialCategoryTypeId, initialSelectedCategoryId);
});

$(document).ready(function () {
    var initialStatus = $('#available_for').val();
    $('#hidden_available_for').val(initialStatus); // Sync initial value if using a hidden field
});

$(document).ready(function () {
        $('#event_id').on('change', function () {
            var EventId = $(this).val();
            var TicketCategory = $('#category_id');
            var startDate = $('#start_date');
            var endDate = $('#end_date');
            var countryName  = $('#country_name');
            var Currency  = $('#currency_type');
            var dayDropdown = $('#day');

            if (!EventId) {
                startDate.val('');
                endDate.val('');
                countryName.val('');
                Currency.val('');
                // TicketCategory.empty();
                dayDropdown.empty();
                return;
            }

            $.ajax({
                url: "{{ route('ticket.getEventDetails', ':EventId') }}".replace(':EventId', EventId),
                type: 'GET',
                success: function (data) {

                    if (data) {
                        startDate.val(data.eventDetails.start_date);
                        endDate.val(data.eventDetails.end_date);
                        countryName.val(data.eventDetails.country);
                        Currency.val(data.currencySign);
                        if(data.eventDetails.active == 0) { 
                        $('#available_for').val('pending').attr('disabled', true);
                        $('#hidden_available_for').val('pending');
                    }else{
                        $('#available_for').val('available').removeAttr('disabled');
                        $('#hidden_available_for').val($('#available_for').val());
                    }
                    $('#available_for').on('change', function () {
                          $('#hidden_available_for').val($(this).val());
                     });

                        updateDayOptions(data.eventDetails.start_date, data.eventDetails.end_date);

                        if (data.ticketCategories && data.ticketCategories.length > 0) {
                            // TicketCategory.empty();
                            $.each(data.ticketCategories, function (index, ticketCategory) {
                                TicketCategory.append('<option value="' + ticketCategory.id + '">' + ticketCategory.name + '</option>');
                            });
                        }
                    }
                },
                error: function (error) {   
                    console.error('Error:', error);
                }
            });
        });
});
// Update day options and handle pre-selected day for editing
function updateDayOptions(startDateVal, endDateVal, selectedDay) {
    const dayDropdown = $('#day');
    dayDropdown.empty(); // Clear existing day options

    const startDate = new Date(startDateVal);
    const endDate = new Date(endDateVal);
    if (!startDate || !endDate || isNaN(startDate) || isNaN(endDate)) {
        dayDropdown.val(''); // Clear day value if dates are invalid
        return;
    }

    for (let date = new Date(startDate); date <= endDate; date.setDate(date.getDate() + 1)) {
        const formattedDate = date.toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        const isoDate = date.toISOString().split('T')[0]; // Get date in YYYY-MM-DD format

        const dayOption = $('<option>', {
            value: isoDate,
            text: formattedDate,
        });

        // Check if the current date should be selected (when editing)
        if (isoDate === selectedDay) {
            dayOption.attr('selected', 'selected');
        }

        dayDropdown.append(dayOption);
    }
}

// Call updateDayOptions and pass the saved day (if editing)
$(document).ready(function () {
    $('#event_id').on('change', function () {
        var EventId = $(this).val();
        var TicketCategory = $('#category_id');
        var startDate = $('#start_date');
        var endDate = $('#end_date');
        var countryName = $('#country_name');
        var Currency = $('#currency_type');
        var dayDropdown = $('#day');

        if (!EventId) {
            startDate.val('');
            endDate.val('');
            countryName.val('');
            Currency.val('');
            dayDropdown.empty();
            return;
        }

        $.ajax({
            url: "{{ route('ticket.getEventDetails', ':EventId') }}".replace(':EventId', EventId),
            type: 'GET',
            success: function (data) {
                if (data) {
                    startDate.val(data.eventDetails.start_date);
                    endDate.val(data.eventDetails.end_date);
                    countryName.val(data.eventDetails.country);
                    Currency.val(data.currencySign);

                    if(data.eventDetails.active == 0) { 
                        $('#available_for').val('pending').attr('disabled', true);
                        $('#hidden_available_for').val('pending');
                    }else{
                        $('#available_for').val('available').removeAttr('disabled');
                        $('#hidden_available_for').val($('#available_for').val());
                    }
                    $('#available_for').on('change', function () {
                          $('#hidden_available_for').val($(this).val());
                     });

                    const savedDay = "{{ isset($ticketData) ? $ticketData->day : '' }}";
                    updateDayOptions(data.eventDetails.start_date, data.eventDetails.end_date, savedDay);

                    if (data.ticketCategories && data.ticketCategories.length > 0) {
                        $.each(data.ticketCategories, function (index, ticketCategory) {
                            TicketCategory.append('<option value="' + ticketCategory.id + '">' + ticketCategory.name + '</option>');
                        });
                    }
                }
            },
            error: function (error) {
                console.error('Error:', error);
            }
        });
    });

    // If editing, call the updateDayOptions with saved data on page load
    const savedStartDate = "{{ isset($ticketData) ? $ticketData->start_date : '' }}";
    const savedEndDate = "{{ isset($ticketData) ? $ticketData->end_date : '' }}";
    const savedDay = "{{ isset($ticketData) ? $ticketData->day : '' }}";

    if (savedStartDate && savedEndDate) {
        updateDayOptions(savedStartDate, savedEndDate, savedDay);
    }
});

$(document).ready(function() {
    function calculateTotal() {

        let price = parseFloat($('#price').val()); 
        let serviceCharge = parseFloat($('#service_charge').val());
        let changeFee = parseFloat($('#change_fee').val());

        price = isNaN(price) ? 0 : price;
        serviceCharge = isNaN(serviceCharge) ? 0 : serviceCharge;
        changeFee = isNaN(changeFee) ? 0 : changeFee;

        let total = price + serviceCharge + changeFee;

        $('#total').val(total);

        return total;
    }

    $('#price, #service_charge, #change_fee').on('input', calculateTotal);

});

function changeValue(delta, elementId) {
        var input = document.getElementById(elementId);
        var value = parseInt(input.value) || 0;
        value += delta;
        if (value > 5) value = 5;
        if (value < 0) value = 0;
        input.value = value;
}

$(document).ready(function() {
        $('#seller').select2({
            placeholder: "Select Seller",
            allowClear: true
        });
    });

function updateLink() {
    $('input[name="ticket_links[]"]').each(function() {
        const input = $(this);
        const url = input.val().trim();
        const anchor = input.siblings('.ticket-link-anchor');
        if (url && (url.startsWith('http://') || url.startsWith('https://'))) {
            anchor.attr('href', url).text('Click here to visit the link').show();
        } else {
            anchor.hide();
        }
    });
}

$(document).on('input', 'input[name="ticket_links[]"]', updateLink);

let linkCount = {{ !empty($ticketData->ticket_links) ? count($ticketData->ticket_links) : 1 }};

function updateLabels() {
    $('#linkInputs .form-group').each(function(index) {
        $(this).find('label').text(`Add Ticket Link ${index + 1}`);
        $(this).find('input').attr('id', `ticket_link-${index + 1}`);
        $(this).attr('id', `link-input-group-${index + 1}`);
    });

    linkCount = $('#linkInputs .form-group').length; // Update linkCount based on current number of groups
}

$('#addMoreLinks').click(function(){
    linkCount++;
    $('#linkInputs').append(`
        <div class="form-group" id="link-input-group-${linkCount}">
            <label for="ticket_link-${linkCount}">Add Ticket Link ${linkCount}</label>
            <input type="url" class="form-control" id="ticket_link-${linkCount}" name="ticket_links[]" placeholder="Enter the link">
            <button type="button" class="btn btn-danger remove-attachment" data-attachment-id="link-input-group-${linkCount}"><i class="fas fa-trash"></i></button>
            <a class="ticket-link-anchor" href="#" target="_blank" style="display:none;">Click here to visit the link</a>
        </div>
    `);
})


$(document).on('click', '.remove-attachment', function() {
    const attachmentId = $(this).data('attachment-id');
    $(`#${attachmentId}`).remove();
});

updateLabels();

function archiveTicket(ticketId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'Are you sure you want to move this to the archive?',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        confirmButtonColor: '#007bff', // Use a valid hex color
        cancelButtonText: 'Cancel',
        cancelButtonColor: '#d33',
        beforeSend: function() {
              $('.loader').show();
            },
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('archive-' + ticketId).submit();
        }
    });
}

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

</script>
@endpush

<style>
.select2-selection.select2-selection--single {
    display: block !important;
        width: 100%  !important;
        height: calc(2.25rem + 2px)  !important;
        padding: .375rem .75rem  !important;
        font-size: 1rem  !important;
        font-weight: 400  !important;
        line-height: 1.5  !important;
        color: #495057  !important;
        background-color: #fff  !important;
        background-clip: padding-box  !important; 
        border: 1px solid #ced4da  !important;
        border-radius: .25rem  !important;
        box-shadow: inset 0 0 0 transparent  !important;
        transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out  !important;
}
</style>