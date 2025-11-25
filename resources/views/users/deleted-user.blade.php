@extends('layouts.main')
@section('title', 'Users')

@section('content_header')
    Users
@endsection

@section('content')

<a href="{{ route('users.index') }}" class="btn btn-primary float-right btn-sm">Back</a>
<button id="bulkRestoreBtn" class="btn btn-success btn-sm float-right mr-2">Bulk restore</button>

@if(session('success'))
<div class="alert alert-success alert-dismissable" id="success-alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    {{ session('success') }}
</div>
@endif

@if(session('message'))
<div class="alert alert-danger alert-dismissable" id="message-alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    {{ session('message') }}
</div>
@endif

    @if($users->isNotEmpty())
        <table id="UserTable" class="table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Gender</th>
                    <th scope="col">Country</th>
                    <th scope="col">Nationality</th>
                    <th scope="col">Address</th>
                    <th colspan="col">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td><input type="checkbox" name="bulkRestore[]" class="bulkRestore " value="{{ $user->id }}"></td>
                        <td>{{ ucFirst($user->name) }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ ucFirst($user->gender) ?? '__' }}</td>
                        <td>{{ $user->country ?  ucFirst($user->country) : '__' }}</td>
                        <td>{{ $user->nationality ? ucFirst($user->nationality) : '__' }}</td>
                        <td>{{ $user->address ?? '__' }}</td>
                        <td>
                        <a href="javascript:void(0);"  onclick="restoreUser({{ $user->id }});" class="btn text-primary"><i class="fas fa-trash-restore text-success mr-2"></i>restore account</a>
                        <form id="restore-form-{{ $user->id }}"  action="{{ route('users.restore', ['id' => $user->id]) }}" method="POST"  style="display: none;">
                            @csrf
                            @method('POST')
                        </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No users found.</p>
    @endif
@endsection

@push('scripts')
<script>

$(document).ready(function() {
        $('#UserTable').DataTable({
            paging:true,
            pageLength:100,
            columns: [
                { data: 'bulkRestore'},
                { data: 'name' },
                { data: 'email' },
                { data: 'gender' },
                { data: 'country' },
                { data: 'nationality' },
                { data: 'address' },
                { data:  'action' }
            ]
        });
    });


     function restoreUser(userId) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'Are you sure you want to restore this user account ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            confirmButtonColor: '#4CAD49',
            cancelButtonText: 'Cancel',
            cancelButtonColor: '#007bff',
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('restore-form-' + userId).submit();
            }
        });
    }

    $(document).ready(function(){
    $('#selectAll').on('click', function(){
        $('.bulkRestore').prop('checked', this.checked);
        $('.bulkRestore').trigger('change');
    });

    $('#bulkRestoreBtn').on('click', function(){
        var selectedIds = [];
        $('.bulkRestore:checked').each(function(){
            selectedIds.push($(this).val());
        });

        if(selectedIds.length > 0){
            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you want to restore these user accounts?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Restore',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#007bff',
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('users.bulk-restore') }}",
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            ids: selectedIds
                        },
                        success: function(response) {
                            Swal.fire({
                                title: 'Users Restored successfully',
                                text: 'Users have been restored successfully.',
                                icon: 'success',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#28a745',
                            }).then(() => {
                                window.location.href = "{{ route('users.index') }}";
                            });
                        },
                        error: function(response) {
                            Swal.fire({
                                title: 'Error',
                                text: 'An error occurred while restoring users.',
                                icon: 'error',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#dc3545',
                            });
                        }
                    });
                }
            });
        } else {
            Swal.fire({
                title: 'No users selected',
                text: 'Please select at least one user to restore.',
                icon: 'info',
                confirmButtonText: 'OK',
                confirmButtonColor: '#007bff',
            });
        }
    });
});
</script>
@endpush