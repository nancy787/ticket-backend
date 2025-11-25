@extends('layouts.main')
@section('title', 'Banner Details')

@section('content_header')
    <h1>Banner Details</h1>
@endsection

@section('content')
    <a href="{{ route('banner.create') }}" class="btn btn-primary">Create Page</a>
    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>Title</th>
                <th>Banner Image</th>
                <th>Banner Description</th>
                <th>Additional Information</th>
                <th>Target</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($banners as $banner)
                <tr>
                    <td>{{ $banner->page_tittle }}</td>
                    <td><img class="card-img-top" src="{{ asset($banner->image) }}" alt="Banner image" style="width: 100px;"></td>
                    <td>{{ $banner->description }}</td>
                    <td>{{ $banner->additional_info }}</td>
                    <td>
                    @foreach($banner->bannerAction as $action)
                        <div>{{ $action->target  ?? 'NA'}}
                            <p> Link : <a href="{{ $action->link }}">{{ $action->link }}</p><a>    
                        </div>
                    @endforeach
                    </td>
                    <td>
                    <a href="{{ route('banner.edit', $banner->id) }}" class="btn btn-warning">Edit</a>
                        <form action="{{ route('banner.delete', $banner->id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
        </tbody>
    </table>
@endsection
