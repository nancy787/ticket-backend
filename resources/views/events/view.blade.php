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
        <input type="text" class="form-control" id="name" name="name" placeholder="Enter event name" value="{{ $eventData->name }}" readonly>
    </div>

    <div class="form-group">
        <label for="location">Event Address</label>
        <input type="text" class="form-control" id="address" name="address" placeholder="Enter location" value="{{ $eventData->address }}" readonly>
    </div>
    </div>
    <div class="text text-danger p-1" id="addressError"></div>

    <div class="col-md-3">
            <div class="form-group">
                <label for="location2">State</label>
                <input type="text" class="form-control" id="state" name="state" placeholder="Enter your state" value="{{ $eventData->state }}" readonly>
            </div>
            <div class="text text-danger p-1" id="stateError"></div>
        </div>

    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label for="location1">City</label>
                <input type="text" class="form-control" id="city" name="city" placeholder="Enter your city" value="{{ $eventData->city }}" readonly>
            </div>
            <div class="text text-danger p-1" id="cityError"></div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label for="location3">Zip Code</label>
                <input type="text" class="form-control" id="zip_code" name="zip_code" placeholder="Enter Zip" value="{{ $eventData->zip_code }}"  oninput="this.value = this.value.replace(/[^0-9]/g, '')"  maxlength="5" readonly>
            </div>
            <div class="text text-danger p-1" id="zip_codeError"></div>
        </div>
        <div class="col-md-3">
        <label for="continent">Selected Continent</label>
        <input type="text" class="form-control" value="{{ $eventData->continent  }}" readonly>
    </div>
    <div class="text text-danger p-1" id="continentError"></div>
    </div>

    <div class="form-group">
    <label for="location">Event Subtitle</label>
        <textarea id="subtitle" class="form-control" name="subtitle" placeholder="Enter Subtitle" value="{{ $eventData->subtitle }}" rows="2" cols="10" readonly>{{ $eventData->subtitle }}</textarea>
    </div>

    <div class="form-group">
        <label for="location">Event Description</label>
        <textarea id="description" class="form-control" name="description" placeholder="Enter Description" value="{{ $eventData->description }}" rows="4" cols="10" readonly>{{ $eventData->description }}</textarea>
    </div>

    <div class="form-group">
    <label for="start_date">Start Date & Time</label>
    <input type="datetime-local" class="form-control" id="start_date" name="start_date" value="{{ \Carbon\Carbon::parse($eventData->start_date)->format('Y-m-d\TH:i') }}" readonly>
</div>
<div class="text text-danger p-1" id="start_dateError"></div>

    <div class="form-group">
    <label for="end_date">End Date & Time</label>
    <input type="datetime-local" class="form-control" id="end_date" name="end_date" value="{{ \Carbon\Carbon::parse($eventData->end_date)->format('Y-m-d\TH:i') }}" readonly>
    </div>
    <div class="text text-danger p-1" id="end_dateError"></div>

    <label for="image">Image</label>
    <div class="form-group">
        <img src="{{ asset($eventData->image) }}" width="500px" alt="Event Image">
    </div>

    <div class="text text-danger p-1" id="imageError"></div>
    <div class="form-group">
        <label for="open_for">Open For</label>
        <input type="text" class="form-control" value="{{ $eventData->open_for  }}" readonly>
    </div>

    <div class="form-check">
        <input type="checkbox" disabled class="form-check-input" id="active" name="active" {{ $eventData->active == 1 ? 'checked' : '' }} >
        <label class="form-check-label" for="active">Active</label>
    </div>
</form>
</div>

@endsection
