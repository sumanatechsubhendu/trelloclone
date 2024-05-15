<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BoardsController;
use App\Http\Controllers\BoardSectionController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceMemberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::post('register', [RegisterController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forget-password', [AuthController::class, 'forgetPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);
Route::middleware('auth:api')->group( function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('verify-token', [AuthController::class, 'verifyToken']);
    Route::middleware(['admin'])->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('teams', TeamController::class);
        Route::resource('team-members', TeamMemberController::class);
        //Route::resource('workspaces', WorkspaceController::class);
        Route::resource('workspaces', WorkspaceController::class)->except(['index']);
        Route::resource('workspaceMembers', WorkspaceMemberController::class);
        Route::resource('boards', BoardsController::class);
        Route::resource('sections', SectionController::class);
        Route::resource('boardSections', BoardSectionController::class);
    });
    Route::get('workspaces', [WorkspaceController::class, 'index']);
    Route::get('list-workspaces', [UserDashboardController::class, 'listWorkspaces']);
    Route::get('workspace-details/{id}', [UserDashboardController::class, 'workspaceDetails']);
    Route::get('list-boards', [UserDashboardController::class, 'listBoards']);
    Route::post('/users/{user}/profile-image', [ImageController::class, 'storeProfileImage']);
    Route::post('/workspaces/{workspace}/workspace-image', [ImageController::class, 'storeWorkspaceImage']);
});
