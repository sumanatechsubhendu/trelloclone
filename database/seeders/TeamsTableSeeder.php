<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeamsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('teams')->insert([
            [
                'name' => 'WWU',
                'description' => 'Description for WWU team',
                'team_head_id' => null, // Assuming no team head for WWU initially
                'created_by' => 1, // Change this to the appropriate user ID who is creating the team
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dekoding',
                'description' => 'Description for Dekoding team',
                'team_head_id' => null, // Assuming no team head for Dekoding initially
                'created_by' => 1, // Change this to the appropriate user ID who is creating the team
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sumanas',
                'description' => 'Description for Sumanas team',
                'team_head_id' => null, // Assuming no team head for Sumanas initially
                'created_by' => 1, // Change this to the appropriate user ID who is creating the team
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
