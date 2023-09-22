<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role_User;

class RoleUserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role_User::create([
            'role_id'=>1,
            'user_id'=>2,
            'user_type'=>'App\Models\Manager'
        ]);

        Role_User::create([
            'role_id'=>3,
            'user_id'=>1,
            'user_type'=>'App\Models\Manager'
        ]);

        Role_User::create([
            'role_id'=>4,
            'user_id'=>3,
            'user_type'=>'App\Models\Manager'
        ]);
        //آمر القبان
        Role_User::create([
            'role_id'=>5,

            'user_id'=>5,
            'user_type'=>'App\Models\Manager'
        ]);
        //مدير محاسبة
        Role_User::create([
            'role_id'=>6,
            'user_id'=>6,
            'user_type'=>'App\Models\Manager'
        ]);
        Role_User::create([
            'role_id'=>7,
            'user_id'=>4,
            'user_type'=>'App\Models\Manager'
        ]);
        Role_User::create([
            'role_id'=>8,
            'user_id'=>7,
            'user_type'=>'App\Models\Manager'
        ]);
        Role_User::create([
            'role_id'=>9,
            'user_id'=>8,
            'user_type'=>'App\Models\Manager'
        ]);

        Role_User::create([
            'role_id'=>10,
            'user_id'=>9,
            'user_type'=>'App\Models\Manager'
        ]);

        Role_User::create([
            'role_id'=>11,
            'user_id'=>10,
            'user_type'=>'App\Models\Manager'
        ]);

    }
}
