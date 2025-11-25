@extends('layouts.main')
@section('title', 'Users')

@section('content_header')
   <h3 class="">{{ ucFirst($user->name) }}</h3>
   <p>{{ $user->email }}</p>
@endsection
@section('content')

<a href="{{ route('users.index') }}" class="btn btn-primary m-2 float-right">Back</a>

<div class="row">
    <div class="col-md-12">
        <input type="hidden" name="user_id" id="user_id" value="{{ $user->id }}">
        <ul class="nav nav-tabs" id="myTabs">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" data-status="soldTickets">Sold Tickets</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" data-status="purchaseHistory">Purchase History</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" data-status="wishlistItems">WishList Items</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" data-status="wishlistsubscriptionItems">WishList Subcription Items</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" data-status="transactionHistory">Transaction History</a>
            </li>
        </ul>
    </div>
</div>

<div id="usersTableWrapper">
    <table id="UsersTable" class="table table-hover">
        <thead>
            <tr>
                <th>Ticket Id</th>
                <th>Dates</th>
                <th>Day</th>
                <th>Charity Ticket</th>
                <th>Photo Pack</th>
                <th>Race With Friend</th>
                <th>Spectator</th>
                <th>Price</th>
                <th>Change</th>
                <th>Service Charge</th>
                <th>Total</th>
                <th>Attachment</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>

<div id="transactionTableWrapper" style="display: none;">
    <table id="transactionTable" class="table table-hover">
        <thead>
            <tr>
                <th>Amount</th>
                <th>Status</th>
                <th>Type</th>
                <th>Transaction Date</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>


<div id="wishlistSubcriptionWrapper" style="display: none;">
    <table id="wishlistSubscriptionTable" class="table table-hover">
        <thead>
            <tr>
                <th>Subscribed continent</th>
                <th>Subscribed country</th>
                <th>Subscribed events</th>
                <th>Subscribed categroies</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

<script>

$(document).ready(function() {
    function loadTableData(status) {
        var userId = $('#user_id').val();
        if(status === 'transactionHistory') {
            $('#transactionTableWrapper').show();
            $('#usersTableWrapper').hide();
            $('#wishlistSubcriptionWrapper').hide();
            loadTransactionHistory(userId);
        } else if(status === 'wishlistsubscriptionItems') {
            $('#wishlistSubcriptionWrapper').show();
            $('#transactionTableWrapper').hide();
            $('#usersTableWrapper').hide();
            loadWishlistSubscription(userId);
        }else {
            $('#transactionTableWrapper').hide();
            $('#usersTableWrapper').show();
            $('#wishlistSubcriptionWrapper').hide();
            loadUserDetails(userId, status);
        }
    }

    function loadTransactionHistory(userId) {
        $.ajax({
            url: '/users/transaction-history/' + userId,
            method: 'GET',
            success: function(response) {
                updateTransactionTable(response.transactionHistory);
            },
            error: function(xhr, status, error) {
                console.error('AJAX request failed. Error:', error);
            }
        });
    }

    function loadWishlistSubscription(userId) {
            $.ajax({
                url : '/users/subscribed-wishlist/' + userId,
                method : 'GET',
                success: function(response) {
                    updateWishlistSubscription(response.subscribedWishlist);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX request failed. Error:', error);
                }
            })
    }

    function loadUserDetails(userId, status) {
        $.ajax({
            url: '/users/ticket-status/' + userId,
            method: 'GET',
            data: { status: status },
            success: function(response) {
                updateTable(response.userDetail);
            },
            error: function(xhr, status, error) {
                console.error('AJAX request failed. Error:', error);
            }
        });
    }

    $('#myTabs a').on('click', function(e) {
        e.preventDefault();
        $(this).tab('show');
        var status = $(this).data('status');
        loadTableData(status);
    });

    loadTableData('soldTickets');
    $('#myTabs a[data-status="soldTickets"]').tab('show');
});

function updateTable(userDetail) {
    var table = $('#UsersTable').DataTable();
    table.clear().draw();
    let links = [];
    let pdfs = []

    $.each(userDetail, function(index, user) {
        let startDate = moment(user.start_date);
        let endDate = moment(user.end_date);
        let formattedDate = startDate.format('D MMMM') + ' - ' + endDate.format('D MMMM') + ', ' + startDate.format('YYYY');
        let dayOfWeek = moment(user.day).format('dddd');

        if (user.link && typeof user.link === 'string') {
            try {
                links = JSON.parse(user.link);
            } catch (e) {
                console.error('Error parsing user.link:', e);
            }
        }

        if (user.pdf && typeof user.pdf === 'string') {
                try {
                    pdfs = JSON.parse(user.pdf);
                } catch (e) {
                    console.error('Error parsing user.pdf:', e);
                }
        }

        const pdfHtml = pdfs.map(pdf =>
            `<a href="/storage/${pdf}" target="_blank" class="btn btn-primary btn-sm">Download PDF</a>`
        ).join(' ');

        const linkHtml = links.map(link =>
            `<a href="${link}" target="_blank" class="btn btn-secondary m-2">Visit Link</a>`
        ).join(' ');

        const attachmentsHtml = `<div>${linkHtml}${pdfHtml}</div>`;

        table.row.add([
            '<a href="#" class="user-link" data-ticket-id="' + user.id + '">'+ user.ticket_id +' </a>',
            formattedDate,
            dayOfWeek,
            user.charity_ticket,
            user.photo_pack,
            user.race_with_friend,
            user.spectator,
            user.price ? parseFloat(user.price).toFixed(2) : '0.00',
            user.change_fee ? parseFloat(user.change_fee).toFixed(2) : '0.00',
            user.service ? parseFloat(user.service).toFixed(2) : '0.00',
            user.total ? parseFloat(user.total).toFixed(2) : '0.00',
            attachmentsHtml
        ]).draw();
    });
}

function updateTransactionTable(transactionHistory) {
    var table = $('#transactionTable').DataTable();
    table.clear().draw();

    $.each(transactionHistory, function(index, transaction) {
        let amount = transaction.amount ? parseFloat(transaction.amount).toFixed(2) : '0.00';
        let status = transaction.status;
        let createdAt = moment(transaction.created_at).format('D MMMM YYYY');

        table.row.add([
            amount,
            status,
            transaction.type,
            createdAt
        ]).draw();
    });
}

$(document).ready(function() {
    $('#UsersTable').DataTable({
        columnDefs: [
            { targets: '_all', orderable: true },
            { targets: [9, 10], orderable: true }
        ],
        order: [[10, 'asc']],
        paging: true,
        pageLength: 10,
    });

    $('#transactionTable').DataTable({
        columnDefs: [
            { targets: '_all', orderable: true }
        ],
        order: [[3, 'asc']],
        paging: true,
        pageLength: 10,
    });
});

$(document).on('click', '.user-link', function(e) {
    e.preventDefault();
    var ticketId = $(this).data('ticket-id');
    ticketDetails(ticketId);
});

function ticketDetails(ticketId) {
    window.location.href = "{{ route('ticket.index') }}?ticketId=" + ticketId;
}

function updateWishlistSubscription(subscribedWishlist) {
    var table = $('#wishlistSubscriptionTable').DataTable();
    table.clear().draw();

    $.each(subscribedWishlist, function(index, wishlist) {
        let continent = wishlist.continent_name ?? '__';
        let country = wishlist.country_name ?? '__';
        let event = wishlist.event_name ?? '__';

        // Map categories to a string, handling the case where there might be none
        let categories = wishlist.categories.length > 0 
            ? wishlist.categories.map(category => category.name).join(', ') 
            : '__';

        table.row.add([
            continent,
            country,
            event,
            categories,
        ]).draw();
    });
}


</script>

@endpush
