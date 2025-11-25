@extends('layouts.main')
@section('title', 'Notifications')

@section('content_header')
    Notifications
@endsection

@section('content')
<div id="response-message" class="mt-3"></div>
<form id="notification-form" action="{{ route('notification.send-notification') }}" method="POST">
    @csrf
    <div class="card custom-card">
        <div class="card-header">
            Send Notification
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="notification-title">Notification Title</label>
                <input type="text" class="form-control" id="notification-title" name="notification_title" placeholder="Notification Title" required>
                <div class="text text-danger p-1" id="nameError"></div>
            </div>
            <div class="form-group">
                <label for="notification-body">Content</label>
                <textarea name="notification_body" class="form-control" id="notification-body" placeholder="Notification Body" required></textarea>
                <div class="text text-danger p-1" id="nameError"></div>
            </div>
            <button type="submit" class="btn btn-primary">Send Notification</button>
        </div>
    </div>
</form>

<hr>
<h4>Recent Notifications</h4>

@if($notifications->isEmpty())
    <p>No recent notifications.</p>
@else
    <table id="notification" class="table table-hover">
            <thead>
                    <tr>
                        <th>Notification Title</th>
                        <th>Notification Content</th>
                        <th>Notification Created At</th>
                    </tr>
                </thead>
                @foreach($notifications as $notification)
                <tbody>
                    <tr>
                        <td>{{ $notification->title }}</td>
                        <td>{{ $notification->body }}</td>
                        <td>{{$notification->created_at->format('d M Y, H:i')}}</td>
                    </tr>
                </tbody>
                @endforeach
            </table>
@endif

@endsection

@push('scripts')
<script>
document.getElementById('notification-form').addEventListener('submit', function(event) {
    event.preventDefault();
    const formData = new FormData(this);
    fetch('{{ route('notification.send-notification') }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
        }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('response-message').innerHTML = `<div class="alert alert-success">${data.success}</div>`;
        setTimeout(function() {
            $('#response-message').fadeOut('slow', function() {
                window.location.reload(); 
            });
        }, 1000);
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('response-message').innerHTML = `<div class="alert alert-danger">An error occurred while sending the notification.</div>`;
        setTimeout(function() {
                    $('#response-message').fadeOut('slow');
                }, 1000);
    });
});
</script>
@endpush

<style>
    .custom-card {
    max-width: 400px; /* Adjust the width as needed */
    margin: 0 auto; /* Center the card horizontally */
    padding: 20px; /* Add some padding inside the card */
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Optional: add shadow for better appearance */
}

.card-header {
    font-size: 1.25rem;
    font-weight: bold;
}

.card-body {
    padding: 1.5rem;
}

.btn-primary {
    display: block;
    width: 100%;
}

</style>
