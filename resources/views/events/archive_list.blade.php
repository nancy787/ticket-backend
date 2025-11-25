@extends('layouts.main')
@section('title', 'Archive')
@section('content_header')
    <h1>Archive</h1>
@endsection

@section('content')

<a href="{{ route('event.index') }}" class="btn btn-primary float-right"> Back </a>

@if($eventData->isNotEmpty())
<table id="ArchiveEventsTable" class="table table-striped">
    <thead>
        <tr>
            <th>Event Name</th>
            <th>Event Location</th>
            <th>Event Start Date</th>
            <th>Event End Date</th>
            <th>Total Tickets</th>
            <th>Available Tickets</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($eventData as $event)
        <tr>
            <td>{{ strtoupper($event->name) }}</td>
            <td>{{ $event->address }}</td>
            <td>{{ formatDate($event->start_date) }}</td>
            <td>{{ formatDate($event->end_date) }}</td>
            <td>{{ $event->tickets->count() }}</td>
            <td>
                <ul>
                    @foreach($event->ticketCategoryCounts as $ticketCategoryCount)
                    <li>{{ $ticketCategoryCount->ticketCategory->name }}: {{ $ticketCategoryCount->total }}</li>
                    @endforeach
                </ul>
            </td>
            <td>
            <a href="javascript:void(0);"  onclick="unArchive({{ $event->id }});"  class="btn btn-primary btn-sm"><i class="fas fa-archive"></i>UnArchive</a>
            <form id="unarchive-{{ $event->id }}" action="{{ route('event.unarchive', $event->id) }}" method="POST" style="display:none;">
                        @csrf
                        @method('POST')
            </form> 
            </td>
        </tr>
        @endforeach
    </tbody>
</table>



@else 
<div class="text-align-center">Events not found</div>
@endif

@endsection

@push('scripts')

<script>

$(document).ready(function() {
        $('#ArchiveEventsTable').DataTable({
                columnDefs: [
                    { targets: '_all', orderable: true },
                    { targets: [5, 6], orderable: true }
                ],
                order:[
                    [6, 'asc']
                ],
                paging:true,
                pageLength:100,
            });
        });

    function unArchive(eventId) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'Are you sure you want to move  this to unarchive',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            confirmButtonColor: 'primary',
            cancelButtonText: 'Cancel',
            cancelButtonColor: '#007bff',
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('unarchive-' + eventId).submit();
            }
         });
    }
</script>
@endpush