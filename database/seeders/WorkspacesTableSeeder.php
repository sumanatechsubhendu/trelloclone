<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\BoardSection;
use App\Models\Section;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
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
        $workspaces = DB::table('workspaces')->get();
        foreach ($workspaces as $key=>$workspace) {
            $boardData['name'] = 'General Tasks';
            $boardData['workspace_id'] = $workspace->id;
            $boardData['created_by'] = 1;
            // Create the Team
            $board = Board::create($boardData);

            $sections = Section::where('type', 0)->orderBy('position', 'asc')->get();
            $boardSections = [];
            foreach ($sections as $key => $val) {
                $boardSections[] = [
                    'board_id' => $board->id,
                    'section_id' => $val->id,
                    'position' => $key + 1,
                    'created_by' => 1,
                    'created_at' => now(), // Add created_at timestamp
                    'updated_at' => now(), // Add updated_at timestamp
                ];
            }
            // Now, you can insert the board sections
            BoardSection::insert($boardSections);
        }
    }
}
