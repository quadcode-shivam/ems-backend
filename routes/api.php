<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\SettingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DesignationPositionController;

// User Routes
Route::post('auth/login', [AuthController::class, 'login']);
// Employee Routes
Route::post('admin/employees/create', [EmployeeController::class, 'createEmployee']);
Route::post('admin/employees/fetch', [EmployeeController::class, 'fetchEmployees']);
Route::post('admin/employees/remove', [EmployeeController::class, 'updateTrashStatus']);

// Attendance Routes
Route::post('check-ins/mark', [AttendanceController::class, 'checkIn']);
Route::post('check-out/mark', [AttendanceController::class, 'checkOut']);
Route::post('admin/attendance/fetch', [AttendanceController::class, 'fetchAttendance']);
Route::post('admin/attendance/action', [AttendanceController::class, 'actionAttendance']);


Route::get('/designations-and-positions', [DesignationPositionController::class, 'fetchDesignationsAndPositions']);


// Leave Routes
Route::post('admin/leaves/create', [LeaveController::class, 'create']);
Route::post('admin/leaves/fetch', [LeaveController::class, 'fetchLeaves']);
Route::get('admin/leaves/{id}', [LeaveController::class, 'show']);
Route::put('admin/leaves/{id}', [LeaveController::class, 'update']);
Route::delete('admin/leaves/{id}', [LeaveController::class, 'destroy']);

// Notification Routes
Route::post('admin/notifications/fetch', [NotificationController::class, 'fetchNotifications']);
Route::get('admin/notifications/{id}', [NotificationController::class, 'show']);
Route::delete('admin/notifications/{id}', [NotificationController::class, 'destroy']);

// Task Routes
Route::post('admin/tasks/create', [TaskController::class, 'create']);
Route::post('admin/tasks/fetch', [TaskController::class, 'fetchTasks']);
Route::get('admin/tasks/{id}', [TaskController::class, 'show']);
Route::put('admin/tasks/{id}', [TaskController::class, 'update']);
Route::delete('admin/tasks/{id}', [TaskController::class, 'destroy']);

// Task Comment Routes
Route::post('admin/task-comments/fetch', [TaskCommentController::class, 'fetchTaskComments']);
Route::get('admin/task-comments/{id}', [TaskCommentController::class, 'show']);
Route::delete('admin/task-comments/{id}', [TaskCommentController::class, 'destroy']);

// Setting Routes
Route::post('admin/settings/fetch', [SettingController::class, 'fetchSettings']);
Route::get('admin/settings/{id}', [SettingController::class, 'show']);
Route::put('admin/settings/{id}', [SettingController::class, 'update']);
Route::delete('admin/settings/{id}', [SettingController::class, 'destroy']);
