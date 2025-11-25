@extends('layouts.main')
@section('title', 'Users Management')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Users</h2>
        </div>
        <div class="btn-group" role="group">
            <form action="{{ route('users.chat-disable') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-danger" title="Disable all chats">
                    <i class="fas fa-comment-slash"></i> Disable Chats
                </button>
            </form>
            <form action="{{ route('users.chat-enable') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-success" title="Enable all chats">
                    <i class="fas fa-comment"></i> Enable Chats
                </button>
            </form>
        </div>
    </div>
@endsection

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show shadow-sm" id="success-alert" role="alert">
    <i class="fas fa-check-circle me-2"></i>
    <strong>Success!</strong> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('message'))
<div class="alert alert-danger alert-dismissible fade show shadow-sm" id="message-alert" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i>
    <strong>Error!</strong> {{ session('message') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-bottom py-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0"><i class="fas fa-users text-primary"></i> All Users</h5>
            </div>
            <div class="btn-toolbar" role="toolbar">
                <div class="btn-group me-2" role="group">
                    <button id="bulkDeleteBtn" class="btn btn-danger btn-sm" disabled>
                        <i class="fas fa-trash-alt"></i> Delete Selected (<span id="selectedCount">0</span>)
                    </button>
                </div>
                <div class="btn-group" role="group">
                    <a href="{{ route('users.view-deleted-user') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-archive"></i> Deleted Users
                    </a>
                    <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-user-plus"></i> Add User
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="UserTable" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </div>
                        </th>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Gender</th>
                        <th scope="col">Country</th>
                        <th scope="col">Nationality</th>
                        <th scope="col">Address</th>
                        <th scope="col">Device</th>
                        <th scope="col">Version</th>
                        <th scope="col" class="text-center" style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Custom styles for modern look */
.card {
    border-radius: 12px;
    overflow: hidden;
}

.card-header {
    border-top-left-radius: 12px !important;
    border-top-right-radius: 12px !important;
}

.table-responsive {
    border-radius: 0 0 12px 12px;
}

#UserTable thead th {
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    border-bottom: 2px solid #dee2e6;
    padding: 1rem 0.75rem;
}

#UserTable tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
}

#UserTable tbody tr {
    transition: all 0.2s ease;
}

#UserTable tbody tr:hover {
    background-color: #f8f9fa;
    transform: translateX(2px);
}

.btn-sm {
    padding: 0.375rem 0.875rem;
    font-size: 0.875rem;
    border-radius: 6px;
    font-weight: 500;
}

.btn-group .btn {
    margin-left: 0 !important;
}

.alert {
    border-radius: 8px;
    border: none;
    padding: 1rem 1.25rem;
}

.form-check-input {
    cursor: pointer;
    width: 1.125rem;
    height: 1.125rem;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

/* Loading spinner */
.dataTables_processing {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    padding: 1.5rem !important;
}

/* Pagination styling */
.dataTables_wrapper .dataTables_paginate .paginate_button {
    border-radius: 6px !important;
    margin: 0 2px;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #0d6efd !important;
    border-color: #0d6efd !important;
}

/* Search box styling */
.dataTables_wrapper .dataTables_filter input {
    border-radius: 6px;
    border: 1px solid #ced4da;
    padding: 0.5rem 0.75rem;
}

.dataTables_wrapper .dataTables_filter input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25);
    outline: none;
}

/* Info text styling */
.dataTables_wrapper .dataTables_info {
    font-size: 0.875rem;
    color: #6c757d;
}

/* Length menu styling */
.dataTables_wrapper .dataTables_length select {
    border-radius: 6px;
    border: 1px solid #ced4da;
    padding: 0.375rem 2rem 0.375rem 0.75rem;
}
</style>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#UserTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('users.data') }}",
            type: 'GET',
            dataSrc: function(json) {
                return json.data;
            },
            error: function(xhr, error, thrown) {
                console.log('Ajax error: ' + error);
            }
        },
        order: [[1, 'asc']],
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'name' },
            { data: 'email' },
            { data: 'gender' },
            { data: 'country' },
            { data: 'nationality' },
            { data: 'address' },
            { data: 'device' },
            { data: 'current_version' },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        pageLength: 100,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><div class="mt-2">Loading users...</div>',
            search: "_INPUT_",
            searchPlaceholder: "Search users...",
            lengthMenu: "Show _MENU_ users",
            info: "Showing _START_ to _END_ of _TOTAL_ users",
            infoEmpty: "No users found",
            infoFiltered: "(filtered from _MAX_ total users)",
            zeroRecords: "No matching users found",
            emptyTable: "No users available"
        },
        dom: '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
    });

    // Update selected count
    function updateSelectedCount() {
        var count = $('.bulkDelete:checked').length;
        $('#selectedCount').text(count);
        $('#bulkDeleteBtn').prop('disabled', count === 0);
    }

    // Select all checkbox
    $('#selectAll').on('click', function(){
        $('.bulkDelete').prop('checked', this.checked);
        updateSelectedCount();
    });

    // Individual checkbox change
    $(document).on('change', '.bulkDelete', function(){
        var allChecked = $('.bulkDelete:checked').length === $('.bulkDelete').length;
        $('#selectAll').prop('checked', allChecked);
        updateSelectedCount();
    });

    // Bulk delete
    $('#bulkDeleteBtn').on('click', function(){
        var selectedIds = [];
        $('.bulkDelete:checked').each(function(){
            selectedIds.push($(this).val());
        });

        if(selectedIds.length > 0){
            Swal.fire({
                title: 'Delete Users?',
                text: `You are about to delete ${selectedIds.length} user account${selectedIds.length > 1 ? 's' : ''}. This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-trash"></i> Delete',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-danger me-2',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('users.bulk-delete') }}",
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            ids: selectedIds
                        },
                        success: function(response) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: 'Users have been deleted successfully.',
                                icon: 'success',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#28a745',
                                customClass: {
                                    confirmButton: 'btn btn-success'
                                },
                                buttonsStyling: false
                            }).then(() => {
                                window.location.href = "{{ route('users.index') }}";
                            });
                        },
                        error: function(response) {
                            Swal.fire({
                                title: 'Error',
                                text: 'An error occurred while deleting users.',
                                icon: 'error',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#dc3545',
                                customClass: {
                                    confirmButton: 'btn btn-danger'
                                },
                                buttonsStyling: false
                            });
                        }
                    });
                }
            });
        }
    });
});

// Auto-dismiss alerts
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        let successAlert = document.getElementById('success-alert');
        if (successAlert) {
            successAlert.classList.remove('show');
            setTimeout(function() {
                successAlert.remove();
            }, 150);
        }
    }, 3000);

    setTimeout(function () {
        let messageAlert = document.getElementById('message-alert');
        if (messageAlert) {
            messageAlert.classList.remove('show');
            setTimeout(function() {
                messageAlert.remove();
            }, 150);
        }
    }, 3000);
});

// Delete single user
function deleteUser(userId) {
    Swal.fire({
        title: 'Delete User?',
        text: 'This action cannot be undone. Are you sure you want to delete this user account?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-trash"></i> Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        reverseButtons: true,
        customClass: {
            confirmButton: 'btn btn-danger me-2',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-form-' + userId).submit();
        }
    });
}

// Delete connected account
function deleteConnectedAccount(userId) {
    Swal.fire({
        title: 'Delete Stripe Account?',
        text: 'This will remove the Stripe connected account for this user. Continue?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-trash"></i> Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        reverseButtons: true,
        customClass: {
            confirmButton: 'btn btn-danger me-2',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-connected-form-' + userId).submit();
        }
    });
}
</script>
@endpush