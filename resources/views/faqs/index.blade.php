@extends('layouts.main')
@section('title', 'FAQs')
@section('content_header')
    <h1>FAQs</h1>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif
@if(session('message'))
    <div class="alert alert-danger">
        {{ session('message') }}
    </div>
@endif
<a href="{{ route('add-f_a_q') }}" class="btn btn-primary w-10 float-right m-2">Add faq</a>
<div>

@if($faqs->isNotEmpty())
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Question</th>
                <th>Answer</th>
                <th>Created At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($faqs as $faq)
                <tr>
                    <td>{{ $faq->id }}</td>
                    <td>{{ $faq->question }}</td>
                    <td>{{ $faq->answers }}</td>
                    <td>{{ formatDate($faq->created_at) }}</td>
                    <td>
                        <form action="{{ route('faqs.delete', ['id' => $faq->id]) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('post')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this FAQ?');">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else 
    <div>No Data found</div>
@endif
@endsection