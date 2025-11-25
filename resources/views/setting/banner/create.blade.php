@extends('layouts.main')
@section('title', 'Events')

@section('content_header')
    <h1>{{ isset($banner) ? 'Edit Banner' : 'Create Banner' }}</h1>
@endsection

@section('content')
<a href="{{ route('banner.index') }}" class="btn btn-primary m-2 float-right ">Back</a>

    <form action="{{ isset($banner) ? route('banner.update', $banner->id) : route('banner.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if(isset($banner))
            @method('POST')
        @endif

        <div class="form-group">
            <label for="tittle">Page Title</label>
            <input type="text" name="page_tittle" class="form-control" value="{{ isset($banner) ? $banner->page_tittle : '' }}" required>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" class="form-control" required>{{ isset($banner) ? $banner->description : '' }}</textarea>
        </div>

        <div class="form-group">
            <label for="image">Image</label>
            <input type="file" name="image" class="form-control">
        </div>

        <div class="form-group">
            <label for="additional_info">Additional Information</label>
            <textarea name="additional_info" class="form-control">{{ isset($banner) ? $banner->additional_info : '' }}</textarea>
        </div>


        <label for="image">Action</label>
        <table id="bannerTable" class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>Target</th>
                <th>Link</th>
            </tr>
        </thead>
            <tbody>
            @if(isset($banner))
            @forelse($banner->bannerAction as $bannerData)
                <tr>
                    <td><input type="text" name="targets[]" class="form-control" value="{{ $bannerData->target }}" placeholder="Target" required></td>
                    <td><input type="text" name="links[]" class="form-control" value="{{ $bannerData->link }}" placeholder="Link" required></td>
                    <td><button type="button" class="btn btn-danger deleteRow"><i class="fas fa-trash"></i></button></td>
                </tr>
                @empty
                @endif
                <tr>
                    <td><input type="text" name="targets[]" class="form-control" placeholder="Target" required></td>
                    <td><input type="text" name="links[]" class="form-control" placeholder="Link" required></td>
                    <td><button type="button" class="btn btn-danger deleteRow"><i class="fas fa-trash"></i></button></td>
                </tr>
            @endforelse
            </tbody>
    </table>
     <button type="button" id="addRow" class="btn btn-primary mt-3">Add Row</button>

     <button type="submit" class="btn btn-success">{{ isset($banner) ? 'Update Banner' : 'Create Banner' }}</button>
    </form>
@endsection


<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<script>
$(document).ready(function () {
    function addNewRow() {
        if ($('#bannerTable tbody tr').length < 2) {
            $('#bannerTable tbody').append(`
                <tr>
                    <td><input type="text" name="targets[]" class="form-control" placeholder="Title" required></td>
                    <div class="text-danger targetsError"></div>
                    <td><input type="text" name="links[]" class="form-control" placeholder="Value" required></td>
                    <td><button type="button" class="btn btn-danger deleteRow"><i class="fas fa-trash"></i></button></td>
                </tr>
            `);
        }
    }

    $('#addRow').click(function () {
        addNewRow();
    });

    $(document).on('click', '.deleteRow', function () {
        $(this).closest('tr').remove();
    });

    $(document).on('keypress', 'input', function (e) {
        if (e.which == 13) {
            e.preventDefault();
            addNewRow();
        }
    });
});
</script>
