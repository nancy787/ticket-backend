    @extends('layouts.main')
@section('title', 'Edit Ticket')

@section('content_header')
    <h1>Edit Ticket</h1>
@endsection

@section('content')
<a href="{{ route('ticket.index') }}" class="btn btn-primary m-2 float-right"> Back </a>

<form id="ticketForm" enctype="multipart/form-data">
    @csrf

    <input type="hidden" name="ticket_id" value="{{ $ticketData->id }}">
    
    <div class="form-group">
        <label for="event_id">Event</label>
        <select class="form-control" id="event_id" name="event_id">
            @foreach($eventDetails as $event)
                <option value="{{ $event->id }}" {{ $ticketData->event_id == $event->id ? 'selected' : '' }}>{{ $event->name }}</option>
            @endforeach
        </select>
        <div class="text text-danger p-1" id="eventError"></div>
    </div>


    <div class="form-group">
        <label for="category_type">Select Category Type</label>
                <input type="text" id="category_type" name="category_type" value="{{ $categoryType->id }}" {{ $ticketData->ticketCategory->categoryType->id == $categoryType->id ? 'selected' : '' }}">
                {{ $categoryType->name }}
    </div>

    <div class="form-group">
    <label for="category_id">Category (Division)</label>
        <select class="form-control" id="category_id" name="category_id">
            <option value="">Select Category</option>
            @foreach ($ticketCategories as $category)
                <option value="{{ $category->id }}" {{ $ticketData->category_id == $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <div class="text text-danger p-1" id="categoryError"></div>
            <button type="button" class="btn btn-primary m-2 float-right" data-toggle="modal" data-target="#addCategoryModal">
                Add Category
            </button>
        </div>
        
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" class="form-control" id="name" name="name" placeholder="Enter ticket name" value="{{ $ticketData->name }}">
            <div class="text text-danger p-1" id="nameError"></div>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" id="description" name="description" placeholder="Enter ticket description" rows="3">{{ $ticketData->description }}</textarea>
            <div class="text text-danger p-1" id="descriptionError"></div>
        </div>
        <label for="image">Image</label>
        <div class="form-group">
            <img src="{{ asset($ticketData->image) }}" width="500px" alt="Event Image">
            <input type="file" class="form-control-file" id="image" name="image">
            <div class="text text-danger p-1" id="imageError"></div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ formatDateForInput($ticketData->start_date) }}">
                </div>
                <div class="text text-danger p-1" id="start_dateError"></div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ formatDateForInput($ticketData->end_date) }}">
                </div>
            <div class="text text-danger p-1" id="end_dateError"></div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                <label for="day">Day</label>
                    <select class="form-control" id="day" name="day">
                        <!-- selected date -->
                    </select>
                    <div class="text text-danger p-1" id="dayError"></div>
                </div>
            </div>

        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="price">Price</label>
                    <input type="number" class="form-control" id="price" name="price" placeholder="Enter price" value="{{ $ticketData->price }}">
                    <div class="text text-danger p-1" id="priceError"></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="service">Service Charge</label>
                    <input type="number" class="form-control" id="service" name="service" placeholder="Enter service" value="{{ $ticketData->service }}">
                    <div class="text text-danger p-1" id="serviceError"></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="total">Total</label>
                    <input type="number" class="form-control" id="total" name="total" placeholder="Enter total" value="{{ $ticketData->total }}">
                    <div class="text text-danger p-1" id="totalError"></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="open_for">Open For</label>
                    <select class="form-control" id="avaialble_for" name="avaialble_for" value="{{ $ticketData->available_for }} ">
                        <option value="sale" {{ $ticketData->available_for == 'sale' ? 'selected' : '' }}>Sale</option>
                        <option value="pending" {{ $ticketData->available_for == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="sold" {{ $ticketData->available_for == 'sold' ? 'selected' : '' }}>Sold</option>
                    </select>
                </div>
            </div>
        </div>

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="ticket_link">Add Ticket Link</label>
                <input type="text" class="form-control" id="ticket_link" name="ticket_link" value="{{ $ticketData->ticket_link }}" >
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="form-group"> 
                <input type="checkbox" class="form-group" id="is_verified" name="is_verified" {{ $ticketData->isverified ? 'checked' : '' }}>
                <label class="form-check-label" for="is_verified">Verified</label>
            </div>  
        </div>
        <div class="col-md-4">
            <div class="form-group"> 
                <input type="checkbox" class="form-group" id="charity_ticket" name="charity_ticket" {{ $ticketData->charity_ticket ? 'checked' : '' }}>
                <label class="form-check-label" for="charity_ticket">Charity Ticket</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group"> 
                <input type="checkbox" class="form-group" id="photo_pack" name="photo_pack" {{ $ticketData->photo_pack ? 'checked' : '' }}>
                <label class="form-check-label" for="photo_pack">Photo Pack</label>
            </div>
        </div>
        
    </div>

    <button type="submit" class="btn btn-primary">Update Ticket</button>
</form>

<!-- categry model -->

<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">Add Category</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            <div class="form-group">
                    <label for="category_type">Select Category Type</label>
                    <select class="form-control" id="category_type" name="category_type">
                         <option value="">Select Category Type</option>
                        @foreach($TicketCategoryTypes as $categoryType)
                            <option value="{{ $categoryType->id }}">{{ $categoryType->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="category_name">Name</label>
                    <input type="text" class="form-control" id="category_name" name="category_name" placeholder="Enter category name">
                    <div class="text text-danger p-1" id="category_nameError"></div>
                </div>
                <div class="form-group">
                    <label for="category_description">Description</label>
                    <textarea class="form-control" id="category_description" name="category_description" placeholder="Enter category description" rows="3"></textarea>
                    <div class="text text-danger p-1" id="category_descriptionError"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="addCategoryBtn">Add</button>
            </div>
        </div>
    </div>
</div>


@endsection

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  $(document).ready(function () {
    $('#ticketForm').on('submit', function (e) {

        e.preventDefault();

        var ticket_id = $('input[name="ticket_id"]').val();
        var formData = new FormData(this);
        formData.append('image', $('#image')[0].files[0]);

        
        if (ticket_id) {
            url = "{{ route('ticket.update', ['id' => ':ticket_id']) }}".replace(':ticket_id', ticket_id);
        }

        $.ajax({
            type: "POST",
            url: "{{ route('ticket.update', ['id' => ':ticket_id']) }}".replace(':ticket_id', ticket_id),
            data: formData,
            processData: false,
            contentType: false,
            success: function(data, status) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Ticket Updated successfully.',
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "{{ route('ticket.index') }}";
                    }
                });
            },
            error: function (xhr) {
                var errors = xhr.responseJSON.errors;
                $('.text-danger').empty();
                $.each(errors, function (key, value) {
                    $('#' + key + 'Error').text(value[0]);
                });
            }
        });
    });
});

$(document).ready(function() {
    $('#addCategoryBtn').on('click', function(e) {
        e.preventDefault();
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var formData = {
            category_name: $('#category_name').val(),
            category_description: $('#category_description').val()
        };
        var ticket_id = $('input[name="ticket_id"]').val();

        $.ajax({
            url: "{{ route('ticket.add-ticket-category') }}",
            type: "POST",
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            data: formData,
            success: function(data, status) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Category added successfully.',
                }).then((result) => {
                    if (result.isConfirmed) {
                        var editUrl = "{{ route('ticket.edit', ['id' => ':ticket_id']) }}".replace(':ticket_id', ticket_id);
                        window.location.href = editUrl;
                    }
                });
            },
            error: function(xhr, status, error) {
                var errors = xhr.responseJSON.errors;
                $('.text-danger').empty();
                $.each(errors, function (key, value) {
                    $('#' + key + 'Error').text(value[0]);
                });
            }
        });
    });
});

$(document).ready(function() {
    $('#category_type').change(function() {
        var categoryTypeId = $(this).val();
        if (categoryTypeId) {
            $.ajax({
                url: "{{ route('ticket.getcategory', ':categoryTypeId') }}".replace(':categoryTypeId', categoryTypeId),
                type: 'GET',
                success: function(response) {
                    $('#category_id').empty();
                    $.each(response, function(key, value) {
                        $('#category_id').append('<option value="' + value.id + '">' + value.name + '</option>');
                    });
                }
            });
        } else {
            $('#category_id').empty();
            $('#category_id').append('<option value="">Select Category</option>');
        }
    });
});

$(document).ready(function() {
        $('#category_type').change(function() {
            var categoryTypeId = $(this).val();
            if (categoryTypeId) {
                $.ajax({
                    url: "{{ route('ticket.getcategory', ':categoryTypeId') }}".replace(':categoryTypeId', categoryTypeId),
                    type: 'GET',
                    success: function(response) {
                        $('#category_id').empty();
                        $.each(response, function(key, value) {
                            $('#category_id').append('<option value="' + value.id + '">' + value.name + '</option>');
                        });
                    }
                });
            } else {
                $('#category_id').empty();
                $('#category_id').append('<option value="">Select Category</option>');
            }
        });
    });

    $(document).ready(function() {
    function updateDayOptions() {
        const startDate = new Date($('#start_date').val());
        const endDate = new Date($('#end_date').val());
        const selectedDay = '{{ $ticketData->day }}';
        $('#day').empty();

        if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
            return;
        }

        for (let date = new Date(startDate); date <= endDate; date.setDate(date.getDate() + 1)) {
            const formattedDate = date.toISOString().split('T')[0];
            const dayOption = $('<option>', {
                value: formattedDate,
                text: date.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }),
                selected: formattedDate === selectedDay
            });
            $('#day').append(dayOption);
        }
    }

    $('#start_date, #end_date').on('input', updateDayOptions);
    updateDayOptions();
});

</script>
