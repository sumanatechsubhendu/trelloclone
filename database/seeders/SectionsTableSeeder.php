<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SectionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('sections')->insert([
            ['title' => 'To Do', 'position' => 1, 'type' => 0, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Doing', 'position' => 2, 'type' => 0, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'QA Testing', 'position' => 3, 'type' => 0, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Done', 'position' => 4, 'type' => 0, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Pending Client Approval', 'position' => 5, 'type' => 0, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Completed', 'position' => 6, 'type' => 0, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
