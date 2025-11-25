@extends('layouts.main')
@section('title', 'Add Ticket')

@section('content_header')
    <h1>Ticket Catgories</h1>
@endsection

@section('content')

<a href="{{ route('ticket.ticket_categories') }}" class="btn btn-primary m-2 float-right ">Back</a>

<form action="">

<input type="hidden" name="ticket_category_id" value="{{ $ticketCategoryData->id ?? '' }}">

    <div class="form-group">
        <label for="category_type">Select Category Type</label>
        <select class="form-control" id="category_type" name="category_type">
            @foreach($ticketCategoryTypes as $categoryType)
                <option value="{{ $categoryType->id }}"
                    {{ isset($ticketCategoryData) && $ticketCategoryData->categoryType->id == $categoryType->id ? 'selected' : '' }}>
                    {{ ucfirst($categoryType->name) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label for="category_name">Name</label>
        <input type="text" class="form-control" id="category_name" name="category_name" placeholder="Enter category name" value="{{ isset($ticketCategoryData) ? $ticketCategoryData->name : old('category_name') }}">
        <div class="text text-danger p-1" id="category_nameError"></div>
    </div>
    <div class="form-group">
        <label for="category_description">Description</label>
        <textarea class="form-control" id="category_description" name="category_description" placeholder="Enter category description" rows="3" value="{{ isset($ticketCategoryData) ? $ticketCategoryData->description : old('description') }}" >{{ isset($ticketCategoryData) ? $ticketCategoryData->description : old('description') }}</textarea>
        <div class="text text-danger p-1" id="category_descriptionError"></div>
    </div>
    </div>
    <button type="button" class="btn btn-primary" id="addCategoryBtn">{{ isset($ticketCategoryData) ?  'Update Category' : 'Add Category' }}</button>
</form>
@endsection
@push('scripts')
<script>
    $(document).ready(function() {
    $('#addCategoryBtn').on('click', function(e) {
        e.preventDefault();
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var categoryId =  $('input[name="ticket_category_id"]').val();

        var formData = {
            category_name: $('#category_name').val(),
            category_description: $('#category_description').val(),
            category_type : $('#category_type').val(),
        };

        var url =  "{{ route('ticket.add-ticket-category') }}";

        if (categoryId) {
            url = "{{ route('ticket.update-category', ['categoryId' => ':categoryId']) }}".replace(':categoryId', categoryId);
        }

        $.ajax({
            url: url,
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
                    if (result.isConfirmed) {
                        window.location.href = "{{ route('ticket.ticket_categories') }}";
                    }
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
    });
});

</script>
@endpush