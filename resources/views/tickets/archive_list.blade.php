@extends('layouts.main')
@section('title', 'Archive')
@section('content_header')
    <h1>Archive Tickets</h1>
@endsection

@section('content')
<a href="{{ route('ticket.index') }}" class="btn btn-primary float-right"> Back </a>

<table id="ticketTable" class="table table-striped">
    <thead>
        <tr>
            <th>Ticket Id</th>
            <th>Seller Account</th>
            <th>Seller Paid</th>
            <th>Ticket Status</th>
            <th>Sold Date</th>
            <th>City Name</th>
            <th>Dates</th>
            <th>Division</th>
            <th>Day</th>
            <th>Cha</th>
            <th>Pho</th>
            <th>Fri</th>
            <th>Spe</th>
            <th>MT</th>
            <th>UP</th>
            <th>Price</th>
            <th>Seller</th>
            <th>Buyer</th>
            <th>Verified</th>
            <th>Action</th>
        </tr>
    </thead>
</table>

@include('tickets.payout')
@endsection

@push('scripts')
<script>

$(document).ready(function () {
    table = $('#ticketTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('ticket.getarchive') }}",
            type: 'GET',
            dataSrc: function (json) {
                return json.data;
            },
            error: function (xhr, error, thrown) {
                console.log('Ajax error: ' + error);
            }
        },
        order: [[4, 'desc']],
        columns: [
            { data: 'ticket_id' },
            { data: 'seller_account' },
            { data: 'seller_paid' },
            { data: 'ticket_status' },
            { data: 'sold_date' },
            { data: 'event_name' },
            { data: 'formatted_date' },
            { data: 'category_name' },
            { data: 'Day' },
            { data: 'charity_ticket' },
            { data: 'photo_pack' },
            { data: 'race_with_friend' },
            { data: 'spectator' },
            { data: 'multiple_tickets' },
            { data: 'unpersonalised_ticket' },
            { data: 'price' },
            { data: 'seller_name' },
            { data: 'buyer_name' },
            { data: 'isverified' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        pageLength: 100,
        language: {
            processing: "<span>Loading tickets...</span>"
        },
    });
});


function updateSellerPaid(ticketId, element) {
    let value = element.value;
    $.ajax({
        url: '{{ route("ticket.update-seller-paid") }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            ticket_id: ticketId,
            seller_paid: value
        },
        success: function(response) {
            let color = value === "yes" ? "#28a745" : "#dc3545";
            $(element).css("border", `2px solid ${color}`);
            $(element).css("background-color", color);
        },
        error: function() {
            alert("Error updating seller paid status.");
        }
    });
}

function unArchive(ticketId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'Are you sure you want to move this to unarchive?',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        confirmButtonColor: 'primary',
        cancelButtonText: 'Cancel',
        cancelButtonColor: '#007bff',
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('unarchive-' + ticketId).submit();
        }
    });
}
var STRIPE_DASHBOARD_URL = "{{ env('STRIPE_DASHBOARD_URL', 'https://dashboard.stripe.com/test/connect') }}";
function showPayoutModal(ticketData) {
    $('#payoutModalLabel').text(`Payout Confirmation for ${ticketData.seller_name}`);
    $('#modalTicketId').val(ticketData.id);
    $('#currency').val(ticketData.currency);
    $('#amount').val(ticketData.price);
    $('#stripe_account_id').val(ticketData.stripe_account_id);

    let stripeLink = `${STRIPE_DASHBOARD_URL}/${ticketData.stripe_account_id}/activity`;
    $('#stripeAccountLink').attr('href', stripeLink);

    var payoutModal = new bootstrap.Modal(document.getElementById('payoutModal'));
    payoutModal.show();
    document.getElementById('buttonCancel').addEventListener('click', function () {
        payoutModal.hide();
    });
}
</script>
@endpush
