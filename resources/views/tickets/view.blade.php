@extends('layouts.main')
@section('title', 'View Ticket')

@section('content_header')
    <h1>{{ isset($ticketData) ? '#'.($ticketData->ticket_id ?? '') : 'View Ticket' }}</h1>
@endsection

@section('content')

<a href="{{ route('ticket.archive') }}" class="btn btn-primary m-2 float-right"> Back </a>

@if(isset($ticketData) && $ticketData->available_for != 'sold')
<button type="button" class="btn btn-success btn-sm" onclick="openModal({{ $ticketData->id }})">
    Sell Ticket
</button>
@endif

<form id="ticketForm" enctype="multipart/form-data">
    @csrf  
    <input type="hidden" name="ticket_id" value="{{ $ticketData->id ?? '' }}">
        <div class="form-group">
            <label for="event_id">Event</label>
                <input type="text" class="form-control" id="event_id" name="event_id" value="{{$ticketData->event->name}}" readonly>
        </div>
        <div class="row">
            <div class="col-md-6">
                <label for="category_type">Category (Division)</label>
                    <select class="form-control" name="ticket_category_type" id="category_type" disabled>
                        @foreach($ticketCategoryTypes as $ticketCategoryType)
                            <option value="{{ $ticketCategoryType->id }}" disabled {{ isset($ticketData) && $ticketData->ticketCategory->ticket_category_type_id == $ticketCategoryType->id ? 'selected' : '' }} >
                                    {{ ucFirst($ticketCategoryType->name) }}
                            </option>
                        @endforeach
                    </select>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="category_id">Ticket Category Type</label>
                        <input type="text" class="form-control" id="category_id" name="category_id" value=" {{ $ticketData->ticketCategory->name }}" readonly>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ isset($ticketData) ? formatDateForInput($ticketData->start_date) : old('start_date') }}" min="{{ date('Y-m-d') }}" readonly>
                </div>
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
                    <input type="" class="form-control" id="day" name="day" value="{{ formatDayInput($ticketData->day) }}" readonly>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-1">
                <div class="form-group">
                    <label for="price">Currecy Type</label>
                    <input type="text" class="form-control" id="currency_type" name="currency_type" placeholder="Currecy Type" value="{{ isset($ticketData) ? $ticketData->currency_type :  old('currency_type') }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="price">Price</label>
                    <input type="number" class="form-control" id="price" name="price" placeholder="Enter price" value="{{ isset($ticketData) ? $ticketData->price :  old('price') }}" readonly>
                    <div class="text text-danger p-1" id="priceError"></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="service">Service Charge</label>
                    <input type="number" class="form-control" id="service_charge" name="service_charge" placeholder="Enter price" value="{{ isset($ticketData) ? $ticketData->service :  old('service') }}" readonly>
                    <div class="text text-danger p-1" id="serviceError"></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="service">Change Fee</label>
                    <input type="number" class="form-control" id="change_fee" name="change_fee" placeholder="Enter price" value="{{ isset($ticketData) ? $ticketData->change_fee :  old('change_fee') }}" readonly>
                    <div class="text text-danger p-1" id="change_feeError"></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="total">Total</label>
                    <input type="number" class="form-control" id="total" name="total" placeholder="Enter total" value="{{ isset($ticketData) ? $ticketData->total : old('total') }}" readonly> 
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
                                <button type="button" class="btn btn-outline-secondary" readonly>-</button>
                            </div>
                            <input type="number" class="form-control" id="photo_pack" name="photo_pack" value="{{ isset($ticketData) && $ticketData->photo_pack ? $ticketData->photo_pack : old('photo_pack') }}" readonly>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary">+</button>
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
                                <button type="button" class="btn btn-outline-secondary">-</button>
                            </div>
                            <input type="number" class="form-control" id="race_with_friend" name="race_with_friend" value="{{ isset($ticketData) && $ticketData->race_with_friend ? $ticketData->race_with_friend : old('race_with_friend') }}" readonly>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary">+</button>
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
                                <button type="button" class="btn btn-outline-secondary" >-</button>
                            </div>
                            <input type="number" class="form-control" id="spectator" name="spectator" value="{{ isset($ticketData) && $ticketData->spectator ? $ticketData->spectator : old('spectator') }}" readonly>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary">+</button>
                            </div>
                        </div>
                        <div class="text text-danger p-1" id="photo_packError"></div>
                    </div>
            </div>
            @endif
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="open_for">Ticket Status</label>
                    <input type="text" class="form-control" id="available_for" name="available_for" value="{{ isset($ticketData) && $ticketData->available_for ? ucFirst($ticketData->available_for) : old('available_for') }}" readonly>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group form-check m-4">
                    <input type="hidden" name="isverified" value="0">
                    <input type="checkbox" class="form-check-input" id="isverified" name="isverified" disabled value="1" {{ isset($ticketData) && $ticketData->isverified ? 'checked' : '' }}>
                    <label class="form-check-label" for="isverified">Verified</label>
                </div>
            </div>
            @if(isset($addons->charity_ticket) && $addons->charity_ticket)
                <div class="col-md-2">
                    <div class="form-group form-check m-4"> 
                            <input type="hidden" name="charity_ticket" value="0">
                            <input type="checkbox" class="form-check-input" id="charity_ticket" name="charity_ticket" disabled value="1" {{  isset($ticketData) &&  $ticketData->charity_ticket  ? 'checked' : '' }}>
                            <label class="form-check-label" for="charity_ticket">Charity Ticket</label>
                        </div>
                </div>
            @endif
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="ticket_link">Add Ticket Link</label>
                    <input type="text" class="form-control" id="ticket_link" name="ticket_link" value="{{ isset($ticketData) ? $ticketData->ticket_link : old('ticket_link') }}" readonly>
                </div>
            </div>
        </div>

        <label for="image">Image</label>
        <div>
            <img src="{{ asset($ticketData->image) }}" width="500px" alt="Ticket Image">
        </div>
</form>

<!-- sell my ticket model -->
    @include('tickets.sell_ticket_model')
<!-- end sell my ticket -->

@endsection

@push('scripts')

<script>
</script>
@endpush