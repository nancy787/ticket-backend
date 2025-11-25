@extends('layouts.main')

@section('title', 'Addons')

@section('content_header')
    <h1 class="m-4">Addons</h1>
@endsection

@section('content')

@csrf
<table class="table table-hover">
    <thead>
        <tr>
            <th scope="col">Photo Pack</th>
                <td>
                    <div class="form-check form-switch">
                        <input class="form-check-input toggle-switch m-0" type="checkbox" id="photo_pack" name="photo_pack" data-on-text="On" data-off-text="Off" {{ isset($addons) && $addons->photo_pack ? 'checked' : '' }}>
                    </div>
                </td>
            </tr>
            <tr>
            <th scope="col">Race With Friend</th>
                <td>
                    <div class="form-check form-switch">
                        <input class="form-check-input toggle-switch m-0" type="checkbox" id="race_with_friend" name="race_with_friend" data-on-text="On" data-off-text="Off" {{ isset($addons) &&  $addons->race_with_friend ? 'checked' : '' }}>
                    </div>
                </td>
            </tr>
            <tr>
            <th scope="col">Spectator</th>
                <td>
                    <div class="form-check form-switch">
                    <input class="form-check-input toggle-switch m-0" type="checkbox" id="spectator" name="spectator" data-on-text="On" data-off-text="Off" {{ isset($addons) && $addons->spectator ? 'checked' : '' }}>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="col">Charity Ticket</th>
                <td>
                    <div class="form-check form-switch">
                        <input class="form-check-input toggle-switch m-0" type="checkbox" id="charity_ticket" name="charity_ticket" data-on-text="On" data-off-text="Off" {{ isset($addons) && $addons->charity_ticket ? 'checked' : '' }}>
                    </div>
                </td>
            </tr>
        </tr>
    </thead>
</table>


@endsection
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
@push('scripts')
<script>
$(document).ready(function () {
    $('.toggle-switch').change(function() {
        
        let photoPack = $('#photo_pack').prop('checked') ? 1 : 0;
        let raceWithFriend = $('#race_with_friend').prop('checked') ? 1 : 0;
        let spectator = $('#spectator').prop('checked') ? 1 : 0;;
        let charityTicket = $('#charity_ticket').prop('checked') ? 1 : 0;

        var data = {
            _token: '{{ csrf_token() }}',
            photoPack: photoPack,
            raceWithFriend: raceWithFriend,
            spectator: spectator,
            charityTicket: charityTicket,
        };
        $.ajax({
            url: '{{ route('ticket.update-addons') }}',
            type: 'POST',
            data: data,
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Addons updated successfully.',
                    showConfirmButton: false,
                    timer: 1500
                })
            },
            error: function(xhr, status, error) {
                alert('Error updating addon:', error);
            }
        });
    });
});
</script>
@endpush