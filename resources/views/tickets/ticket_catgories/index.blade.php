@extends('layouts.main')
@section('title', 'Ticket Catgories')

@section('content_header')
    <h1 class="m-4">Ticket Catgories</h1>
@endsection

@section('content')
<a href="{{ route('ticket.add-new-ticket-category') }}" class="btn btn-primary float-right ">Add New Category</a>

@if($ticketCategories->isNotEmpty())
<table class="table table-hover" id="ticketCategories">
    <thead>
        <tr>
        <th scope="col">Category Type</th>
        <th scope="col">Catgories Name</th>
        <th colspan="2">Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ticketCategories as $key => $ticketCategory)
        <tr>
            <td>{{ isset($ticketCategory) && $ticketCategory->categoryType ? ucfirst($ticketCategory->categoryType->name) : '' }}</td>
            <td>{{ ucfirst($ticketCategory->name) }}</td>
            <td>
                <a href="{{ route('ticket.edit-category', $ticketCategory->id) }}" class="btn btn-primary"><i class="fas fa-edit"></i></a>
                <a href="javascript:void(0);" onclick="deleteTicketCategory({{ $ticketCategory->id }});" class="btn btn-danger"><i class="fas fa-trash"></i></a>
                    <form id="delete-category-{{ $ticketCategory->id }}" action="{{ route('ticket.delete-category', $ticketCategory->id) }}" method="POST" style="display: none;">
                            @csrf
                            @method('POST')
                    </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@endsection

@push('scripts')
<script>
$(document).ready(function() {
            $('#ticketCategories').DataTable({
                columnDefs: [
                    { targets: '_all', orderable: true },
                    { targets: [1, 2], orderable: true }
                ],
                order:[
                    [2, 'asc']
                ],
                paging:true,
                pageLength:100,
            });
        });

      function deleteTicketCategory(ticketCategoryId) {
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
                document.getElementById('delete-category-' + ticketCategoryId).submit();
            }
        });
    }

</script>
@endpush