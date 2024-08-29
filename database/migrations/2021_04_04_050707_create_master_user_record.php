<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

class CreateMasterUserRecord extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Models\User::create([
            'name' => 'Erik G',
            'email' => 'erik@erikgratz.com',
            'password' => Hash::make(env('MASTER_PASSWORD')),
            'imageUrl' => 'storage/images/webp/face.webp',
        ]);
        \App\Models\User::create([
            'name' => 'Amy G',
            'email' => 'hudgins.a8@gmail.com',
            'password' => Hash::make(env('MASTER_PASSWORD_2')),
            'imageUrl' => 'storage/images/webp/face.webp',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        User::where('email', 'erik@erikgratz.com')->delete();
    }
}
