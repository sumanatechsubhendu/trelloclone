<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkspacesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('workspaces')->insert([
            ['name' => 'WWU', 'admin_id' => null, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dekoding', 'admin_id' => null, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sumanas', 'admin_id' => null, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
