@extends('layouts.main')
@section('title', 'Events')

@section('content_header')
    <h1>{{ $eventData->raceInformation->count() > 0 ? 'Update' : 'Add' }} Race Information</h1>
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

<h4> Event : {{ $eventData->name }}</h4>

<a href="{{ route('event.index') }}" class="btn btn-primary m-2 float-right"> Back </a>

<form id="eventForm" method="POST" action="{{ route('event.store-raceinfo', $eventData->id) }}">
    @csrf
    <div>
        <input type="hidden" name="event_id" id="event_id" value="{{ $eventData->id }}">
    </div>
    <table id="raceTable" class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>Title</th>
                <th>Value</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($eventData->raceInformation as $raceInfo)
                <tr>
                    <td><input type="text" name="titles[]" class="form-control" value="{{ $raceInfo->title }}" placeholder="Title" required></td>
                    <td><input type="text" name="values[]" class="form-control" value="{{ $raceInfo->value }}" placeholder="Value" required></td>
                    <td><button type="button" class="btn btn-danger deleteRow"><i class="fas fa-trash"></i></button></td>
                </tr>
            @empty
                <tr>
                    <td><input type="text" name="titles[]" class="form-control" placeholder="Title" required></td>
                    <td><input type="text" name="values[]" class="form-control" placeholder="Value" required></td>
                    <td><button type="button" class="btn btn-danger deleteRow"><i class="fas fa-trash"></i></button></td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <button type="button" id="addRow" class="btn btn-primary mt-3">Add Row</button>
    <button type="submit" class="btn btn-success mt-3">{{ $eventData->raceInformation->count() > 0 ? 'Update' : 'Add' }}</button>
</form>

@endsection

@push('scripts')

<script>
$(document).ready(function () {
    function addNewRow() {
        $('#raceTable tbody').append(`
            <tr>
                <td><input type="text" name="titles[]" class="form-control" placeholder="Title" required></td>
                <div class="text-danger titlesError"></div>
                <td><input type="text" name="values[]" class="form-control" placeholder="Value" required></td>
                <td><button type="button" class="btn btn-danger deleteRow"><i class="fas fa-trash"></i></button></td>
            </tr>
        `);
    }

    $('#addRow').click(function () {
        addNewRow();
    });
    $(document).on('click', '.deleteRow', function () {
    console.log($(this).closest('tr').length > 0);
        if($(this).closest('tr') < 0) {
            alert();
        }
        $(this).closest('tr').remove();
    });
    $(document).on('keypress', 'input', function (e) {
        if (e.which == 13) {
            e.preventDefault();
            addNewRow();
        }
    });
    $('#eventForm').on('submit', function (e) {
        e.preventDefault();
        var formData = new FormData(this);
        var event_id = $('#event_id').val();
        $.ajax({
            type: "POST",
            url: "{{ route('event.store-raceinfo', ['id' => ':event_id']) }}".replace(':event_id', event_id),
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                alert('Race Info successfully.');
                window.location.href = "{{ route('event.index') }}";
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

</script>
@endpush