
@extends('layouts.main')
@section('title', 'Change Terms')

@section('content_header')
    <h1>Change Terms</h1>
@endsection

@section('content')

@if(session('success'))
        <div class="alert-success" id="success-alert">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('update-terms-and-condition', $termAndCondition->id) }}" method="POST">
        @csrf
        @method('POST')
        <textarea name="content" id="content">{{ $termAndCondition->content }}</textarea>
        <button type="submit" class="btnStyle">Save</button>
    </form>
@endsection
    
<style>
  .btnStyle {
    color: #fff;
    background-color: #28a745;
    padding: 5px;
    margin: 10px;
    width: 100px;
    background-color: #28a745;
    border-color: #28a745;
    box-shadow: none;
    cursor: pointer;
  }

  .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
    }

</style>
<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
<script>
     document.addEventListener("DOMContentLoaded", function() {
            if (typeof CKEDITOR !== 'undefined') {
                CKEDITOR.replace('content', {
                    height: 500
                });
            } else {
                console.error("CKEDITOR is not defined");
            }
        });
  
  var successAlert = document.querySelector('#success-alert');
  if (successAlert) {
        successAlert.style.display = 'block';
        setTimeout(function() {
            successAlert.style.opacity = 0;
            setTimeout(function() {
                successAlert.style.display = 'none';
            }, 500); 
        }, 2000);
    }

</script>
