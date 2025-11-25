@extends('layouts.main')
@section('title', 'Events')

@section('content_header')
    <h1>Edit Event</h1>
@endsection

@section('content')

<a href="{{ route('event.index') }}" class="btn btn-primary m-2 float-right"> Back </a>
<div>
<form id="eventForm" enctype="multipart/form-data">
    @csrf

    <input type="hidden" name="event_id" value="{{ $eventData->id }}">

    <div class="form-group">
        <label for="name">Event Name</label>
        <input type="text" class="form-control" id="name" name="name" placeholder="Enter event name" value="{{ $eventData->name }}">
        <div class="text text-danger p-1" id="nameError"></div>
    </div>

    <div class="row">
        <div class="col-md-6">
        <div class="form-group">
            <label for="continent">Select Continent</label>
                <select class="form-control" id="continent" name="continent" value="{{ $eventData->continent }} ">
                <option value="asia" {{ $eventData->continent == 'asia' ? 'selected' : '' }}>Asia</option>
                <option value="europe" {{ $eventData->open_for == 'europe' ? 'selected' : '' }}>Europe</option>
                <option value="north_america" {{ $eventData->continent == 'north_america' ? 'selected' : '' }}>North America</option>
                <option value="africa" {{ $eventData->continent == 'africa' ? 'selected' : '' }}>Africa</option>
                <option value="south_america" {{ $eventData->continent == 'south_america' ? 'selected' : '' }}>South America</option>
                <option value="antartica" {{ $eventData->continent == 'antartica' ? 'selected' : '' }}>Antartica</option>
                <option value="oceania"{{ $eventData->continent == 'oceania' ? 'selected' : '' }}  >Oceania</option>
            </select>
        </div>
        <div class="text text-danger p-1" id="continentError"></div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="location">Event Address</label>
                <input type="text" class="form-control" id="address" name="address" placeholder="Enter location" value="{{ $eventData->address }}">
            </div>
            <div class="text text-danger p-1" id="addressError"></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
                <div class="form-group">
                    <label for="country">Country</label>
                    <input type="text" class="form-control" id="country" name="country" placeholder="Enter Country" value="{{ $eventData->country }}">
                </div>
                <div class="text text-danger p-1" id="countryError"></div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label for="location2">State</label>
                <input type="text" class="form-control" id="state" name="state" placeholder="Enter your state" value="{{ $eventData->state }}">
            </div>
            <div class="text text-danger p-1" id="stateError"></div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label for="location1">City</label>
                <input type="text" class="form-control" id="city" name="city" placeholder="Enter your city" value="{{ $eventData->city }}" >
            </div>
            <div class="text text-danger p-1" id="cityError"></div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label for="location1">City Code</label>
                <input type="text" class="form-control" id="city_code" name="city_code" placeholder="ABC" value="{{ $eventData->city_code }}" maxlength="3" onkeypress="isLetter(event)" oninput="convertToUpper(this)">
            </div>
            <div class="text text-danger p-1" id="city_codeError"></div>
        </div>
    </div>

    <div class="form-group">
    <label for="location">Event Subtitle</label>
        <textarea id="subtitle" class="form-control" name="subtitle" placeholder="Enter Subtitle" value="{{ $eventData->subtitle }}" rows="2" cols="10">{{ $eventData->subtitle }}</textarea>
    </div>
    <div class="text text-danger p-1" id="subtitleError"></div>

    <div class="form-group">
        <label for="location">Event Description</label>
        <textarea id="description" class="form-control" name="description" placeholder="Enter Description" value="{{ $eventData->description }}" rows="4" cols="10">{{ $eventData->description }}</textarea>
    </div>
    <div class="text text-danger p-1" id="descriptionError"></div>

    <div class="form-group">
    <label for="start_date">Start Date & Time</label>
    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ formatDateForInput($eventData->start_date) }}">
</div>
<div class="text text-danger p-1" id="start_dateError"></div>

    <div class="form-group">
    <label for="end_date">End Date & Time</label>
    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ formatDateForInput($eventData->end_date) }}">
    </div>
    <div class="text text-danger p-1" id="end_dateError"></div>

    <label for="image">Image</label>
    <div class="form-group">
        <img src="{{ asset($eventData->image) }}" width="500px" alt="Event Image">
            <input type="file" class="form-control-file" id="image" name="image">
    </div>

    <div class="form-group">
        <label for="open_for">Open For</label>
        <select class="form-control" id="open_for" name="open_for" value="{{ $eventData->open_for }} ">
            <option value="sale" {{ $eventData->open_for == 'sale' ? 'selected' : '' }}>Sale</option>
            <option value="pending" {{ $eventData->open_for == 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="sold" {{ $eventData->open_for == 'sold' ? 'selected' : '' }}>Sold</option>
        </select>
    </div>
    <div class="text text-danger p-1" id="open_forError"></div>

    <div class="form-check">
        <input type="checkbox" class="form-check-input" id="active" name="active" {{ $eventData->active == 1 ? 'checked' : '' }}>
        <label class="form-check-label" for="active">Active</label>
    </div>
    <button type="submit" class="btn btn-primary">Update Event</button>
</form>
</div>

@endsection
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  $(document).ready(function () {
    $('#eventForm').on('submit', function (e) {
        e.preventDefault();

        var formData = new FormData(this);
        var event_id = $('input[name="event_id"]').val();

        $.ajax({
            type: "POST",
            url: "{{ route('event.update', ['id' => ':event_id']) }}".replace(':event_id', event_id),
            data: formData,
            processData: false,
            contentType: false,
            success: function(data, status) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Events Updated successfully.',
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "{{ route('event.index') }}";
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


function isLetter(evt) {
        var charCode = (evt.which) ? evt.which : evt.keyCode;
        if ((charCode < 65 || charCode > 90) && (charCode < 97 || charCode > 122)) {
            evt.preventDefault();
            return false;
        }
        return true;
    }

    function convertToUpper(input) {
        input.value = input.value.toUpperCase();
    }
    
</script>
