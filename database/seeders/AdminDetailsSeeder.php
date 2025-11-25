<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

class AdminDetailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'name'          => 'admin',
            'email'         => 'demo@mail.com',
            'phone_number'  => '7975436465',
            'country'       => 'india',
            'nationality'   => 'indian',
            'gender'        => 'male',
            'age'           => 32,
            'address'       => 'New York',
            'password'      => Hash::make('admin@mail.com'),
        ]);

        $user->assignRole('admin');

        event(new Registered($user));  

    }
}
