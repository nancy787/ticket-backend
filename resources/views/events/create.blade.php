@extends('layouts.main')
@section('title', isset($eventData) ? 'Edit Event' : 'Create Event')

@section('content_header')
<h1>{{ isset($eventData) ? 'Edit Event' : 'Create Event' }}</h1>
@endsection

@section('content')
<a href="{{ route('event.index') }}" class="btn btn-primary m-2 float-right"> Back </a>

<div class="loader"></div>
<form id="eventForm"  enctype="multipart/form-data">
    @csrf

    <input type="hidden" name="event_id" value="{{ $eventData->id  ?? '' }}">

    <div class="form-group">
        <label for="name">Event Name</label>
        <input type="text" class="form-control" id="name" name="name" placeholder="Enter event name" value="{{ isset($eventData) ? $eventData->name : old('name') }}">
        <div class="text text-danger p-1" id="nameError"></div>
    </div>

    <div class="row">
        <div class="col-md-5">
        <div class="form-group">
            <label for="continent">Select Continent</label>
            <select class="form-control" id="continent" name="continent">
                @foreach($allContinent as $continent)  
                <option value="{{ $continent->id }}" {{ isset($eventData) && $eventData->continent_id == $continent->id ? 'selected' : '' }}>{{ $continent->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="text text-danger p-1" id="continentError"></div>
        </div>

        <div class="col-md-5">
            <div class="form-group">
            <label for="country">Select Country</label>
            <select class="form-control" id="country" name="country">
                @foreach($countries as $country)
                    <option value="{{ $country->id }}" {{ isset($eventData) && $eventData->country_id == $country->id ? 'selected' : '' }}>{{ $country->name }} </option>
                @endforeach
            </select>  
            </div>
            <div class="text text-danger p-1" id="countryError"></div>
        </div>

        <div class="col-md-2">
            <div class="form-group">
                <label for="country">Currency</label>
                    @if(isset($eventData) && $eventData->currency)
                      <input class="form-control" type="text"  name="currency" value="{{ isset($eventData) ? $eventData->currency : old('currency') }}" readonly> 
                    @else
                        <input class="form-control" type="text" id="currency" name="currency" value=" " readonly> 
                    @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
                <div class="form-group">
                <label for="location">Event Address</label>
                <input type="text" class="form-control" id="address" name="address" placeholder="Enter location" value="{{ isset($eventData) ? $eventData->address : old('address') }}">
                </div>
                <div class="text text-danger p-1" id="addressError"></div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="location1">City Code</label>
                <input type="text" class="form-control" id="city_code" name="city_code" placeholder="ABC" value="{{ isset($eventData) ? $eventData->city_code : old('city_code') }}"  maxlength="3" onkeypress="isLetter(event)" oninput="convertToUpper(this)">
            </div>
            <div class="text text-danger p-1" id="city_codeError"></div>
        </div>

    </div>

    <div class="form-group">
    <label for="location">Event Subtitle</label>
        <textarea id="subtitle" class="form-control" name="subtitle" placeholder="Enter Subtitle" value="{{ isset($eventData) ? $eventData->subtitle : old('subtitle') }}"  rows="2" cols="10">{{ isset($eventData) ? $eventData->subtitle : old('subtitle')}}</textarea>
    </div>
    <div class="text text-danger p-1" id="subtitleError"></div>

    <div class="form-group">
        <label for="location">Event Description</label>
      
        <textarea id="description" class="form-control" name="description" placeholder="Enter Description" value="{{ isset($eventData) ? $eventData->description : old('description') }}" rows="4" cols="10">{{ isset($eventData) ? $eventData->description : old('description') }}</textarea>
    </div>
    <div class="text text-danger p-1" id="descriptionError"></div>

    <div class="form-group">
        <label for="start_date">Start Date </label>
         <input type="date" class="form-control" id="start_date" name="start_date" value="{{ isset($eventData) ? $eventData->start_date : old('start_date') }}">
    </div>
    <div class="text text-danger p-1" id="start_dateError"></div>

    <div class="form-group">
        <label for="end_date">End Date</label>
        <input type="date" class="form-control" id="end_date" name="end_date" value="{{ isset($eventData) ? $eventData->end_date : old('end_date') }}">
    </div>
    <div class="text text-danger p-1" id="end_dateError"></div>

    <label for="image">Image</label>
    <div class="form-group">
        @if(isset($eventData) && $eventData->image)
            <img src="{{ asset($eventData->image) }}" width="500px" alt="Event Image">
        @else 
        <p>No selected image<p>
        @endif
        <input type="file" class="form-control-file" id="image" name="image" value="{{ old('image') }}">
    </div>
    <div class="text text-danger p-1" id="imageError"></div>
    
    <div class="form-group">
        <label for="open_for">Open For</label>
        <select class="form-control" id="open_for" name="open_for" value="{{ isset($eventData) ? $eventData->open_for : '' }}">
            <option value="sale" {{ isset($eventData) && $eventData->open_for == 'sale' ? 'selected' : '' }}>Sale</option> 
            <option value="pending" {{ isset($eventData) && $eventData->open_for == 'pending' ? 'selected' : '' }}>Pending</option> 
            <option value="sold" {{ isset($eventData) && $eventData->open_for == 'sold' ? 'selected' : '' }}>Sold</option>
        </select>
    </div>
    <div class="text text-danger p-1" id="open_forError"></div>

    <div class="form-check">
        <input type="hidden" name="active" value="0">
        <input type="checkbox" class="form-check-input" id="active" name="active" value="1" {{ isset($eventData) && $eventData->active ? 'checked' : '' }}>
        <label class="form-check-label" for="active">Active</label>
    </div>

    <button type="submit" class="btn btn-primary">{{ isset($eventData) ? 'Update Event' : 'Create Event' }}</button>
</form>

@endsection
@push('scripts')
<script>
    $(document).ready(function() {
        $('#code-snippet').on('input', function() {
            var codeSnippet = $(this).val();
            $('#preview-section').html(codeSnippet);
        });
    });

  $(document).ready(function () {
    $('#eventForm').on('submit', function (e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('image', $('#image')[0].files[0]);
        var event_id = $('input[name="event_id"]').val();
    
        var url = "{{ route('event.store') }}";

        if (event_id) {
            url = "{{ route('event.update', ['id' => ':event_id']) }}".replace(':event_id', event_id);
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
                    text: 'Event created successfully.',
                }).then((result) => {
                         if (result.isConfirmed) {
                            $('.loader').show();
                             window.location.href = "{{ route('event.index') }}";
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

    // $(document).ready(function(){
    //     var today = new Date().toISOString().split('T')[0];
    //         $('#start_date').attr('min', today);
    //         $('#end_date').attr('min', today);
    // });

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

    $(document).ready(function () {
        function loadCountries(continentId, selectedCountryId = null) {
            var countrySelect = $('#country');
            var currencyInput = $('#currency');
            var event_id = $('input[name="event_id"]').val();

            if(!event_id) {
                countrySelect.empty().append('<option value="">Select Country</option>');
                currencyInput.val('');
            }

            if (continentId) {
                var url = "{{ route('event.getCountries', ['continent' => ':continentId']) }}".replace(':continentId', continentId);

                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function (data) {
                        $.each(data, function (index, country) {
                            countrySelect.append('<option value="' + country.id + '">' + country.name + '</option>');
                        });
                        if (selectedCountryId) {
                            countrySelect.val(selectedCountryId).trigger('change');
                        }
                    },
                    error: function (error) {
                        console.error('Error:', error);
                    }
                });
            }
        }

        function loadCurrency(countryId) {
            var currencyInput = $('#currency');
            currencyInput.val('');
            if (countryId) {
                var url = "{{ route('event.getCountryCurrency', ['countryId' => ':countryId']) }}".replace(':countryId', countryId);
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function (data) {
                        if (data.length > 0) {
                            var country = data[0];
                            currencyInput.val(country.currency_sign);
                        }
                    },
                    error: function (error) {
                        console.error('Error:', error);
                    }
                });
            }
        }
        $('#continent').on('change', function () {
            var continentId = $(this).val();
            loadCountries(continentId);
        });
        $('#country').on('change', function () {
            var countryId = $(this).val();
            loadCurrency(countryId);
        });
        var initialContinentId = $('#continent').val();
        var initialCountryId = $('#country').data('selected');

        if (initialContinentId) {
            loadCountries(initialContinentId, initialCountryId);
        }
    });

</script>
@endpush
