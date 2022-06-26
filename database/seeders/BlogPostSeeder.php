<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class BlogPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        BlogPost::truncate();
        $allExistingUsers = User::all('id');
        $rtn = [];
        foreach($allExistingUsers as $user){
            $rtn[] = ['user_id' => $user->id];
        }
        var_dump(...$rtn);
        BlogPost::factory(35)
            ->state(new Sequence(...$rtn))
            ->create()
            ;
    }
}
