@extends('layouts.main')
@section('title', 'Users')

@section('content_header')
    @if(isset($user))
        Edit User
    @else
        Create User
    @endif
@endsection

@section('content')

<a href="{{ route('users.index') }}" class="btn btn-primary m-2 float-right ">Back</a>
    <form id="userData"  method="POST">
        @csrf
        <input type="hidden" name="user_id" value="{{ $user->id ?? '' }}">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', isset($user) ? $user->name : '') }}">
            </div>
            <div class="text text-danger p-1" id="nameError"></div>

            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" class="form-control" id="email" name="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" value="{{ old('email', isset($user) ? $user->email : '') }}">
            </div>
            <div class="text text-danger p-1" id="emailError"></div>

            @if(!isset($user))
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" value="">
                    <div class="input-group-append">
                        <span class="input-group-text" id="togglePassword" style="cursor: pointer;">
                            <i class="fa fa-eye"></i>
                        </span>
                    </div>
                </div>
            </div>
            <div class="text text-danger p-1" id="passwordError">
                @if ($errors->has('password'))
                    {{ $errors->first('password') }}
                @endif
            </div>

            <div class="form-group">
              <label for="password_confirmation">Confirm Password</label>
              <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
            </div>
            <div class="text text-danger p-1" id="passwordConfirmationError">
                @if ($errors->has('password_confirmation'))
                    {{ $errors->first('password_confirmation') }}
                @endif
            </div>
            @endif
            <div class="form-group">
                <label for="gender">Gender</label>
                <select class="form-control" id="gender" name="gender" required>
                    <option value="male" {{ (isset($user) && $user->gender == 'male') ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ (isset($user) && $user->gender == 'female') ? 'selected' : '' }}>Female</option>
                    <option value="other" {{ (isset($user) && $user->gender == 'other') ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <div class="text text-danger p-1" id="genderError"></div>

            <div class="form-group">
            <label for="country">Select Country</label>
            <select class="form-control" id="country" name="country">
                @foreach($getCountries as $country)
                <option value="{{ $country->name }}"
                    {{ old('country', isset($selectedCountry) ? $selectedCountry : '') === $country->name ? 'selected' : '' }}>
                    {{ $country->name }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="text text-danger p-1" id="countryError"></div>

        <div class="form-group">
            <label for="nationality">Nationality</label>
            <select class="form-control" id="nationality" name="nationality">
                @foreach($getCountries as $country)
                <option value="{{ $country->nationality }}"
                    {{ old('nationality', isset($selectedNationality) ? $selectedNationality : '') === $country->nationality ? 'selected' : '' }}>
                    {{ $country->nationality }}
                </option>
                @endforeach
            </select>
        </div>
            <div class="text text-danger p-1" id="nationalityError"></div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea class="form-control" id="address" name="address">{{ old('address', isset($user) ? $user->address : '') }}</textarea>
            </div>
            <div class="text text-danger p-1" id="addressError"></div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="is_premium">Is premium</label>
                            <input class="p-1" type="checkbox" id="is_premium" name="is_premium" value="1" {{ isset($user) && $user->is_premium ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                        <label for="is_premium">Free Subcription</label>
                        <input class="p-1" type="checkbox" id="is_free_subcription" name="is_free_subcription" value="1" {{ isset($user) && $user->is_free_subcription ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="chat_enabled">Chat enabled</label>
                            <input class="p-1" type="checkbox" id="chat_enabled" name="chat_enabled" value="1" {{ isset($user) && $user->chat_enabled ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>

        <button type="submit" class="btn btn-primary">Submit</button>
        </form>

@endsection
@push('scripts')
<script>
    $(document).ready(function () {
        $('#userData').on('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(this);
            var userId = $('input[name="user_id"]').val();
            var url = "{{ route('users.store') }}";

            if (userId) {
                url = "{{ route('users.update', ['id' => ':userId']) }}".replace(':userId', userId)
            }

            $.ajax({
                type: "POST",
                url: url,
                data: formData,
                processData: false,
                contentType: false,
                success: function(data, status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'User Created successfully.',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "{{ route('users.index') }}";
                        }
                    });
                },
                error: function (xhr) {
                    var errors = xhr.responseJSON.errors;
                    $('.text-danger').empty();
                    $.each(errors, function (key, value) {
                        $('#' + key + 'Error').text(value[0]);
                    });
                }
            });
        });
    });


    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            let successAlert = document.getElementById('success-alert');
            if (successAlert) {
                successAlert.classList.add('fade');
                setTimeout(function() {
                    successAlert.remove();
                }, 500);
            }
        }, 2000);

        setTimeout(function () {
            let messageAlert = document.getElementById('message-alert');
            if (messageAlert) {
                messageAlert.classList.add('fade');
                setTimeout(function() {
                    messageAlert.remove();
                }, 500);
            }
        }, 2000);

        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function (e) {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    });

    $('#name').on('keypress', function (event) {
    var charCode = event.keyCode;
    // Allow letters (A-Z, a-z), backspace (8), and space (32)
    if ((charCode > 64 && charCode < 91) || // A-Z
        (charCode > 96 && charCode < 123) || // a-z
        charCode == 8 || // Backspace
        charCode == 32) { // Space
        return true;
    } else {
        return false;
    }
});

</script>
@endpush