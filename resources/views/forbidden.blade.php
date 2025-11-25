
@extends('adminlte::page')

@section('title', '403')

@section('content_header')
    <h1>Dashboard</h1>
@endsection


@section('content')
    <p>403 forbidden</p>
@endsection

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@endsection

@section('js')
    <script> console.log("Hi, I'm using the Laravel-AdminLTE package!"); </script>
@endsection
