@extends('layouts.main')
@section('title', 'FAQ')

@section('content_header')
<h1>FAQ</h1>
@endsection

@section('content')
<div>
<a href="{{ route('f_a_ques') }}" class="btn btn-primary w-10 float-right m-2">Back</a>

    <form action="{{ route('store-f_a_q') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="question" class="form-label">Question ?</label>
            <textarea class="form-control" id="question" name="question" rows="3" placeholder="Enter the question"></textarea>
            @if($errors->has('question'))
                <div class="text-danger">{{ $errors->first('question') }}</div>
            @endif
        </div>

        <div class="mb-3">
            <label for="answers" class="form-label">Answer</label>
            <textarea class="form-control" id="answers" name="answers" rows="5" placeholder="Enter the answer"></textarea>
            @if($errors->has('answers'))
                <div class="text-danger">{{ $errors->first('answers') }}</div>
            @endif
        </div>

        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>
@endsection