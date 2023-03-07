<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeamsController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\EventsController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PoolsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::put('/addLaLigaTeams', [TeamsController::class, 'addLaLigaTeams']);
Route::put('/addNbaTeams', [TeamsController::class, 'addNbaTeams']);

Route::prefix('/users')->group(function(){
    Route::post('/login', [UsersController::class, 'login']);
    Route::put('/register', [UsersController::class, 'register']);
    Route::delete('/logOut', [UsersController::class, 'logOut']);
    Route::post('/sendEmail', [UsersController::class, 'sendMail']); 
    Route::post('/checkCorrectSecretCode', [UsersController::class, 'checkCorrectSecretCode']);
    Route::post('/changePassword', [UsersController::class, 'changePassword']);
    Route::middleware('auth:sanctum')->put('/saveImage', [UsersController::class, 'saveImage']);
    Route::middleware('auth:sanctum')->post('/edit', [UsersController::class, 'edit']);
    Route::get('/getUserById', [UsersController::class, 'getUserById']);
    Route::middleware('auth:sanctum')->get('/getCurrentUserPhoto', [UsersController::class, 'getCurrentUserPhoto']);
    Route::middleware('auth:sanctum')->post('/updateStreak', [UsersController::class, 'updateStreak']);
    Route::middleware('auth:sanctum')->get('/list', [UsersController::class, 'list']);
    Route::middleware('auth:sanctum')->get('/getUserData', [UsersController::class, 'getUserData']);
});

Route::prefix('/events')->group(function(){
    Route::put('/create', [EventsController::class, 'create']);
    Route::middleware('auth:sanctum')->get('/getEventById', [EventsController::class, 'getEventById']);
    Route::middleware('auth:sanctum')->get('/list', [EventsController::class, 'list']);
    Route::delete('/delete', [EventsController::class, 'delete']);
    Route::middleware('auth:sanctum')->post('/participateInBet', [EventsController::class, 'participateInBet']);

});
Route::prefix('/notifications')->group(function(){
    Route::middleware('auth:sanctum')->put('/create', [NotificationsController::class, 'create']);
    Route::middleware('auth:sanctum')->get('/getNotificationsByUser', [NotificationsController::class, 'getNotificationsByUser']);
});

Route::prefix('/teams')->group(function(){
    Route::put('/addBasket', [TeamsController::class, 'addBasketTeams']);
    Route::put('/addSoccer', [TeamsController::class, 'addSoccerTeams']);
});
Route::prefix('/pools')->group(function(){
    Route::put('/create', [PoolsController::class, 'create']);
    Route::get('/list', [PoolsController::class, 'list']);
    Route::middleware('auth:sanctum')->post('/participateInPool', [PoolsController::class, 'participateInPool']);
    Route::middleware('auth:sanctum')->post('/finishPool', [PoolsController::class, 'finishPool']);
});