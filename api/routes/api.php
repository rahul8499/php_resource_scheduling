<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use App\Http\Controllers\BatchCodeController;
use App\Http\Controllers\BatchTypeController;
use App\Http\Controllers\BatchSlotController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\SlotTimeController;
use App\Http\Controllers\BatchStreamController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DoubtController;
use App\Http\Controllers\SlotTimeFoundationController;
use App\Http\Controllers\BatchslotTimeController;
use App\Http\Controllers\AutoScheduleController;


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
Route::prefix('api')->group(function () {
    Route::get('/', function () {
        return 'Hello from /api/';
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('batchdata',[BatchCodeController::class,'Batchdata']);
Route::get('getBatchdata',[BatchCodeController::class,'getBatchdata']);
Route::get('showByIDBatch/{id}',[BatchCodeController::class,'showByIDBatch']);
Route::patch('UpdateBatch/{id}',[BatchCodeController::class,'UpdateBatch']);
Route::delete('DeleteBatch/{id}',[BatchCodeController::class,'DeleteBatch']);
Route::get('checkBatchInSchedules/{id}',[FacultyController::class,'checkBatchInSchedules']);
Route::get('/export-batch/{locationId}', [BatchController::class, 'exportBatch']);

Route::post('Batchtype',[BatchTypeController::class,'Batchtype']);
Route::get('getBatchtype',[BatchTypeController::class,'getBatchtype']);
Route::get('showByIDBatchtype/{id}',[BatchTypeController::class,'showByIDBatchtype']);
Route::patch('UpdateBatchtype/{id}',[BatchTypeController::class,'UpdateBatchtype']);
Route::delete('DeleteBatchtype/{id}',[BatchTypeController::class,'DeleteBatchtype']);

Route::post('createSlotTime',[SlotTimeController::class,'createSlotTime']);
Route::get('getSlotTime',[SlotTimeController::class,'getSlotTime']);
Route::get('showByIDSlotTime/{id}',[SlotTimeController::class,'showByIDSlotTime']);
Route::patch('UpdateSlotTime/{id}',[SlotTimeController::class,'UpdateSlotTime']);
Route::delete('DeleteSlotTime/{id}',[SlotTimeController::class,'DeleteSlotTime']);

Route::post('Batchslot',[BatchSlotController::class,'Batchslot']);
Route::get('getBatchslot',[BatchSlotController::class,'getBatchslot']);
Route::get('showByIDBatchslot/{id}',[BatchSlotController::class,'showByIDBatchslot']);
Route::patch('UpdateBatchslot/{id}',[BatchSlotController::class,'UpdateBatchslot']);
Route::delete('DeleteBatchslot/{id}',[BatchSlotController::class,'DeleteBatchslot']);
Route::get('/batch-slot-times', [BatchslotTimeController::class, 'index'])->name('batch_slot_times.index');


Route::post('Batchstream',[BatchStreamController::class,'Batchstream']);
Route::get('getBatchStream',[BatchStreamController::class,'getBatchStream']);
Route::get('showByIDBatchStream/{id}',[BatchStreamController::class,'showByIDBatchStream']);
Route::patch('UpdateBatchStream/{id}',[BatchStreamController::class,'UpdateBatchStream']);
Route::delete('DeleteBatchStream/{id}',[BatchStreamController::class,'DeleteBatchStream']);

Route::post('location',[LocationController::class,'location']);
Route::get('getlocation',[LocationController::class,'getlocation']);
Route::get('showByIDlocation/{id}',[LocationController::class,'showByIDlocation']);
Route::patch('Updatelocation/{id}',[LocationController::class,'Updatelocation']);
Route::delete('Deletelocation/{id}',[LocationController::class,'Deletelocation']);

Route::post('createbatch',[BatchController::class,'createbatch']);
Route::get('getbatchdata',[BatchController::class,'getbatchdata']);
Route::get('showBatchById/{id}',[BatchController::class,'showBatchById']);
Route::patch('update/{id}',[BatchController::class,'updatebatch']);
Route::delete('delete/{id}',[BatchController::class,'delete']);
Route::get('batchesList',[BatchController::class,'batchesList']);

Route::post('createfaculty',[FacultyController::class,'createfaculty']);
Route::get('getfacultydata', [FacultyController::class, 'getfacultydata']);
Route::get('showfacultyById/{id}',[FacultyController::class,'showfacultyById']);
Route::put('updatefaculty/{id}',[FacultyController::class,'updatefaculty']);
Route::delete('deletefaculty/{id}',[FacultyController::class,'deletefaculty']);
Route::get('checkFacultyInSchedules/{id}',[FacultyController::class,'checkFacultyInSchedules']);
Route::get('/export-faculty/{locationId}', [FacultyController::class, 'exportFaculty']);

Route::post('createfacultyBatch',[FacultyController::class,'createfacultyBatch']);
Route::get('getfacultyBatchdata',[FacultyController::class,'getfacultyBatchdata']);
Route::get('showfacultyBatchById/{id}',[FacultyController::class,'showfacultyBatchById']);
Route::patch('updatefacultyBatch/{id}',[FacultyController::class,'updatefacultyBatch']);
Route::delete('deletefacultyBatch/{id}',[FacultyController::class,'deletefacultyBatch']);

Route::post('createSubject',[SubjectController::class,'createSubject']);
Route::get('getSubject',[SubjectController::class,'getSubject']);
Route::get('showByIDSubject/{id}',[SubjectController::class,'showByIDSubject']);
Route::patch('UpdateSubject/{id}',[SubjectController::class,'UpdateSubject']);
Route::delete('DeleteSubject/{id}',[SubjectController::class,'DeleteSubject']);

Route::post('storeImage',[ImageController::class,'storeImage']);
Route::get('getImage/{id}',[ImageController::class,'getImage']);
Route::get('showByIDImage/{id}',[ImageController::class,'showByIDImage']);
Route::patch('UpdateImage/{id}',[ImageController::class,'UpdateImage']);
Route::delete('DeleteImage/{id}',[ImageController::class,'DeleteImage']);

Route::post('createSchedule/{scheduleType?}', [ScheduleController::class, 'createSchedule']);
Route::get('getSchedule',[ScheduleController::class,'getSchedule']);
Route::get('showByIDSchedule/{id}',[ScheduleController::class,'showByIDSchedule']);
Route::patch('UpdateSchedule/{scheduleType?}/{id}',[ScheduleController::class,'UpdateSchedule']); 
Route::delete('DeleteSchedule/{id}',[ScheduleController::class,'DeleteSchedule']);
// Route::get('/export', [ScheduleController::class, 'exportSchedule']);
Route::get('export-schedule', [ScheduleController::class, 'exportSchedule']);
Route::post('publishSchedule', [ScheduleController::class, 'publishSchedule']); 
Route::get('getSubjectCounts', [ScheduleController::class, 'getSubjectCounts']);


// //auto-schedule
// Route::post('autoSchedule', [AutoScheduleController::class, 'autoSchedule']);
// Route::post('/api/save-data', 'DataController@saveData');
Route::post('sendAutoScheduleDataToApi', [ScheduleController::class, 'sendAutoScheduleDataToApi']);
Route::post('autoSchedule', [ScheduleController::class, 'autoSchedule']);

Route::post('createLeave',[LeaveController::class,'createLeave']);
Route::get('getLeave',[LeaveController::class,'getLeave']);
Route::get('getById/{id}',[LeaveController::class,'getById']);
Route::patch('updateLeave/{id}',[LeaveController::class,'updateLeave']);
Route::delete('deleteLeave/{id}',[LeaveController::class,'deleteLeave']);
Route::get('/export-leave', [LeaveController::class, 'exportLeave']);

Route::post('createDoubt',[DoubtController::class,'createDoubt']);
Route::get('getDoubt',[DoubtController::class,'getDoubt']);
Route::get('showByIDDoubt/{id}',[DoubtController::class,'showByIDDoubt']);
Route::patch('UpdateDoubt/{id}',[DoubtController::class,'UpdateDoubt']);
Route::delete('DeleteDoubt/{id}',[DoubtController::class,'DeleteDoubt']);

Route::get('getReport',[ReportController::class,'getReport']);
Route::get('export-report', [ReportController::class, 'exportReport']);
Route::get('getFacultiesCount',[ReportController::class,'getFacultiesCount']);
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();


});

Route::post('/register', [UserController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', [AuthController::class, 'logout']);

Route::get('getSlotTimesFoundations',[SlotTimeFoundationController::class,'getSlotTimesFoundations']);

Route::post('users', [UserController::class, 'createUser']);
Route::post('users/passwordUpdate', [UserController::class, 'passwordUpdate'])->name('user.passwordUpdate');
Route::get('getUsers', [UserController::class, 'getUsers']);
Route::patch('updateUser/{id}', [UserController::class, 'updateUser']);
Route::get('getByIdUser/{id}', [UserController::class, 'getByIdUser']);
Route::delete('deleteUser/{id}', [UserController::class, 'deleteUser']);
Route::post('password/forgot', [UserController::class, 'forgotPassword'])->name('user.forgotPassword');
Route::post('password/reset', [UserController::class, 'resetPassword'])->name('user.resetPassword');

