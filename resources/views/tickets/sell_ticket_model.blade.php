<div class="modal fade" id="ticketModal" tabindex="-1" role="dialog" aria-labelledby="ticketModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ticketModalLabel">Sell Ticket <span id="modalTicketId"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="loader"></div>
                <input type="hidden" class="form-control" id="event_value_id" name="event_value_id" value="{{ isset($ticketData) ? $ticketData->event->id : (isset($ticket) ? $ticket->event->id : '') }}" readonly>
                <div class="form-group">
                    <label for="category_type">Buyer Name</label>
                    <div id="selector">
                        <button id="buyer_select" class="w-100 form-control"><b>Select Buyer</b></button>
                        <input type="text" id="buyer_name" class="form-control" placeholder="Enter Buyer Name..." style="display:none;">
                        <ul id="select_buyer" class="list-group" style="display:none; max-height: 200px; overflow-y: auto;">
                            @foreach($userData as $user)
                                <li class="list-group-item cursor-pointer" id="user-data" data-user-id="{{ $user->id }}">{{ ucfirst($user->name) }} ({{ $user->email }})</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="text text-danger p-1" id="buyer_nameError"></div>
                </div>
                <div class="form-group">
                    <label for="attachmentType">Attachment Type</label>
                    <select id="attachmentType" class="form-control">
                        <option value="">Select Attachment Type</option>
                        <option value="pdf">PDF</option>
                        <option value="link">Link</option>
                    </select>
                </div>
                <div id="pdfUploads" style="display: none;">
                    <div class="form-group">
                        <label for="pdf-file">Upload PDF 1</label>
                        <input type="file" class="form-control" id="pdf-file" name="pdf-files[]" accept="application/pdf">
                    </div>
                </div>
              <div id="linkInputs" style="display: none;">
                  <div class="form-group">
                      <label for="link-url">Enter Link 1</label>
                      <input type="url" class="form-control" id="link-url" name="links[]" placeholder="Enter the link">
                      <a class="ticket-link-anchor" href="#" target="_blank" style="display:none;">Click here to visit the link</a>
                  </div>
              </div>
                <div id="attachmentsContainer">
                    <!-- Container for dynamically added attachments -->
                </div>
                <button type="button" class="btn btn-secondary" id="addMoreAttachments">Add Another Attachment</button>
                <div class="text text-danger p-1" id="attachmentTypeError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" aria-label="Close">Close</button>
                <button type="button" class="btn btn-primary" id="sellTicket">Sell Ticket</button>
            </div>
        </div>
    </div>
</div>

<style>
  #select_buyer {
  max-height: 200px;
  overflow-y: auto;
}

.cursor-pointer {
  cursor: pointer;
};

.attachment-group {
        margin-bottom: 15px;
    }

</style>
@push('scripts')
<script>
$(document).ready(function() {
    let pdfCount = 0;
    let linkCount = 0;

    // Handle attachment type change
    $('#attachmentType').on('change', function() {
        const selectedType = $(this).val();
        if (selectedType === 'pdf') {
            $('#pdfUploads').show();
            $('#linkInputs').hide();
            // Reset link inputs if PDF is selected
            // $('input[name="links[]"]').val('');
        } else if (selectedType === 'link') {
            $('#linkInputs').show();
            $('#pdfUploads').hide();
            // Reset PDF inputs if Link is selected
            // $('input[type="file"][name="pdf-files[]"]').val('');
        } else {
            $('#pdfUploads').hide();
            $('#linkInputs').hide();
            // Reset both if none selected
            $('input[name="links[]"]').val('');
            $('input[type="file"][name="pdf-files[]"]').val('');
        }
    });

    // Add more attachments
    $('#addMoreAttachments').on('click', function() {
        const selectedType = $('#attachmentType').val();
        if (!selectedType) {
            $('#attachmentTypeError').html('Please select an attachment');
            return;
        }
        if (selectedType === 'pdf') {
            pdfCount++;
            $('#pdfUploads').append(`
              <div class="form-group" id="pdf-file-group-${pdfCount}">
                    <label for="pdf-file-${pdfCount}">Upload PDF ${pdfCount}</label>
                    <input type="file" class="form-control" id="pdf-file-${pdfCount}" name="pdf-files[]" accept="application/pdf">
                    <button type="button" class="btn btn-danger remove-attachment" data-attachment-id="pdf-file-group-${pdfCount}"><i class="fas fa-trash"></i></button>
                </div>
            `);
        } else if (selectedType === 'link') {
            linkCount++;
            $('#linkInputs').append(`
               <div class="form-group" id="link-input-group-${linkCount}">
                    <label for="link-url-${linkCount}">Enter Link ${linkCount}</label>
                    <input type="url" class="form-control" id="link-url-${linkCount}" name="links[]" placeholder="Enter the link">
                    <button type="button" class="btn btn-danger remove-attachment" data-attachment-id="link-input-group-${linkCount}"><i class="fas fa-trash"></i></button>
                    <a class="ticket-link-anchor" href="#" target="_blank" style="display:none;">Click here to visit the link</a>
                </div>
            `);
        }
    });

    // Remove attachment
    $(document).on('click', '.remove-attachment', function() {
        const attachmentId = $(this).data('attachment-id');
        $(`#${attachmentId}`).remove();
    });

    // Link validation
    $(document).on('input', 'input[name="links[]"]', function() {
        const input = $(this);
        const url = input.val().trim();
        const anchor = input.siblings('.ticket-link-anchor');
        if (url && (url.startsWith('http://') || url.startsWith('https://'))) {
            anchor.attr('href', url).text('Click here to visit the link').show();
        } else {
            anchor.hide();
        }
    });

    // Open modal and load ticket data
    window.openModal = function openModal(ticketId, status) {
        $('#ticketModal').modal('show');
        $('#ticketModal').data('status', status);
        $('#ticketModal').data('ticketId', ticketId);

        if (status === 'pending') {
            $('#sellTicket').text('Add Buyer');
        } else if (status === 'available') {
            $('#sellTicket').text('Sell Ticket');
        }else {
            $('#sellTicket').text('Resell Ticket');
        }

        const url = "{{ route('ticket.sold-ticket', ['id' => 'ticketId']) }}".replace('ticketId', ticketId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
                if (response.ticket_link.length > 0 && Array.isArray(response.ticket_link)) {
                    $('#linkInputs').empty();
                    $('#attachmentType').val('link');
                    response.ticket_link.forEach((link, index) => {
                        const currentIndex = index + 1;
                        $('#linkInputs').append(`
                        <div class="form-group" id="link-input-group-${currentIndex}">
                                <label for="link-url-${currentIndex}">Enter Link ${currentIndex}</label>
                                <input type="url" class="form-control" id="link-url-${currentIndex}" name="links[]" value="${link}" placeholder="Enter the link">
                                <a class="ticket-link-anchor" href="${link}" target="_blank">Click here to visit the link</a>
                                <button type="button" class="btn btn-danger remove-attachment" data-attachment-id="link-input-group-${currentIndex}"><i class="fas fa-trash"></i></button>
                            </div>
                        `);
                        linkCount = currentIndex;
                    });
                    $('#linkInputs').show();
                }
            },
            error: function(xhr, status, error) {
                console.error("An error occurred: " + error);
            }
        });
    };

    var selectedUserId;

    $("#select_buyer li").on("click", function() {
        selectedUserId = $(this).data('user-id');
        var buyerName = $(this).text();
        $("#buyer_name").val(buyerName).show();
    });

    $("#buyer_select").on("click", function() {
        $("#buyer_name").toggle();
        $("#select_buyer").toggle();
    });

    $("#buyer_name").on("keyup", function() {
        var query = $(this).val().toLowerCase();
        $("#select_buyer li").each(function() {
            var listItem = $(this).text().toLowerCase();
            $(this).toggle(listItem.includes(query));
        });
    });

    $("#select_buyer li").on("click", function() {
        $("#buyer_select").html("<b>" + $(this).html() + "</b>");
        $("#buyer_name").hide();
        $("#select_buyer").hide();
    });

    // Handle ticket selling
    $('#sellTicket').on('click', function(e) {
        e.preventDefault();
        const formData = new FormData();
        formData.append('buyer_name', $('#buyer_name').val());
        formData.append('user_id', selectedUserId);
        formData.append('ticket_id', $('#ticketModal').data('ticketId'));
        formData.append('event_id', $('input[name="event_value_id"]').val());
        formData.append('status', $('#ticketModal').data('status'));

        // Append each PDF file
        $('input[type="file"][name="pdf-files[]"]').each(function() {
            formData.append('pdf-files[]', $(this)[0].files[0]);
        });

        // Append each link
        $('input[type="url"][name="links[]"]').each(function() {
            formData.append('links[]', $(this).val());
        });

        // Perform AJAX request
        $.ajax({
            url: "{{ route('ticket.sell-ticket') }}",
            type: "POST",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('.loader').show();
            },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Ticket Sold',
                }).then((result) => {
                   window.location.href = "{{ route('ticket.index') }}";
                });
            },
            error: function(xhr, status, error) {
                const errors = xhr.responseJSON.errors;
                $('.loader').hide();
                $('.text-danger').empty();
                $.each(errors, function(key, value) {
                    $('#' + key + 'Error').text(value[0]);
                });
            }
        });
    });

    // Reset modal when hidden
    $('#ticketModal').on('hidden.bs.modal', function () {
        selectedUserId = null;
        $('#buyer_name').hide().val('');
        $('#select_buyer').hide();
        $('#buyer_select').html('<b>Select Buyer</b>').show();
        $('#attachmentType').val('');
        $('#pdfUploads').hide();
        $('#linkInputs').hide();
        $('#pdfUploads').empty();
        $('#linkInputs').empty();
        pdfCount = 0;
        linkCount = 0;
    });
});
</script>

@endpush
