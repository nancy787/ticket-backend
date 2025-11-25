<div class="modal fade" id="payoutModal" tabindex="-1" aria-labelledby="payoutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="payoutModalLabel">Payout Confirmation</h5>
            </div>
            <div id="validationErrors"></div>
            <div id="transferStatus"></div>
            <div class="modal-body">
                <form id="payoutForm">
                    <input type="hidden" name="ticket_id" id="modalTicketId">
                    <div class="mb-3">
                        <label for="currency" class="form-label">Enter Currency</label>
                        <input type="text" name="currency" id="currency" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Enter Amount to Transfer</label>
                        <input type="number" name="amount" id="amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="stripe_account_id" class="form-label">Stripe Connected Account ID</label>
                        <div class="input-group">
                            <input type="text" name="stripe_account_id" id="stripe_account_id" class="form-control" required readonly>
                            <a id="stripeAccountLink" href="#" target="_blank" class="btn btn-primary">View in Stripe</a>
                        </div>
                    </div>
                    <p>Are you sure you want to process the payout?</p>
                    <div class="modal-footer">
                        <button type="button" id="buttonCancel" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="confirmPayout" class="btn btn-primary">Confirm Payout</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function () {
    $('#confirmPayout').on('click', function (e) {
        e.preventDefault();
        $('#validationErrors').html('');

        let formData = {
            _token: "{{ csrf_token() }}",
            ticket_id: $('#modalTicketId').val(),
            currency: $('#currency').val(),
            amount: $('#amount').val(),
            stripe_account_id: $('#stripe_account_id').val()
        };

        $.ajax({
            url: "{{ route('tickets.transfer-funds', ':id') }}".replace(':id', $('#modalTicketId').val()),
            type: "POST",
            data: formData,
            dataType: "json",
            beforeSend: function () {
                $('#confirmPayout').prop('disabled', true); // Disable button while processing
            },
            success: function (response) {
                $('#confirmPayout').prop('disabled', false); // Enable button

                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        $('#payoutModal').modal('hide');
                        $('#transferStatus').html(`<div class="alert alert-success">${response.message}</div>`);
                        location.reload();
                    });
                } else {
                    $('#validationErrors').html(`<div class="alert alert-danger">${response.message}</div>`);
                }
            },
            error: function (xhr) {
                $('#confirmPayout').prop('disabled', false);
                let responseJSON = xhr.responseJSON || {};
                let errors = responseJSON.errors || {};
                let errorMessage = responseJSON.errors || 'Something went wrong.';

                if (!$.isEmptyObject(errors)) {
                    let errorMessages = '<div class="alert alert-danger"><ul>';
                    $.each(errors, function (key, value) {
                        errorMessages += `<li>${value[0]}</li>`;
                    });
                    errorMessages += '</ul></div>';
                    $('#validationErrors').html(errorMessages);
                } else {
                    $('#validationErrors').html(`<div class="alert alert-danger">${errorMessage}</div>`);
                }
            }
        });
    });
});
</script>

@endpush