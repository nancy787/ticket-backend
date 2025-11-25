<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">Add Category</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="category_type">Category (Division)</label>
                    <select class="form-control" name="ticket_category_type" id="ticket_category_type">
                        @foreach($ticketCategoryTypes as $ticketCategoryType)
                        <option value="{{ $ticketCategoryType->id }}">{{ ucFirst($ticketCategoryType->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="category_name">Name</label>
                    <input type="text" class="form-control" id="category_name" name="category_name" placeholder="Enter category name">
                    <div class="text text-danger p-1" id="category_nameError"></div>
                </div>
                <div class="form-group">
                    <label for="category_description">Description</label>
                    <textarea class="form-control" id="category_description" name="category_description" placeholder="Enter category description" rows="3"></textarea>
                    <div class="text text-danger p-1" id="category_descriptionError"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="addCategoryBtn">Add</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
    $('#addCategoryBtn').on('click', function(e) {
        e.preventDefault();
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var ticket_id = $('input[name="ticket_id"]').val();
        var link = "{{ route('ticket.create') }}";
        if(ticket_id) {
            link = "{{ route('ticket.edit', ['id' => ':ticket_id']) }}".replace(':ticket_id', ticket_id);
        }

        var formData = {
            category_name: $('#category_name').val(),
            category_description: $('#category_description').val(),
            category_type : $('#ticket_category_type').val()
        };

        $.ajax({
            url: "{{ route('ticket.add-ticket-category') }}",
            type: "POST",
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            data: formData,
            success: function(data, status) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Category added successfully.',
                }).then((result) => {
                    setTimeout(function() {
                        window.location.href = link;
                    }, 1000);
                });
            },
            error: function(xhr, status, error) {
                var errors = xhr.responseJSON.errors;
                $('.text-danger').empty();
                $.each(errors, function (key, value) {
                    $('#' + key + 'Error').text(value[0]);
                });
            }
        });

        $('#addCategoryModal').on('hidden.bs.modal', function (e) {
            $('#category_name').val('');
            $('#category_description').val('');
            $('#ticket_category_type').val('');
            $('.text-danger').empty(); // Clear error messages
        });
    });
});

</script>
@endpush