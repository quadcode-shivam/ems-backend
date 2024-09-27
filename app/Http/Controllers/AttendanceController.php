<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance; 
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\CheckIn;
class AttendanceController extends Controller
{
    
    
    public function checkIn(Request $request)
    {
        // Define validation rules for check-in
        $rules = [
            'user_id' => 'required|exists:users,user_id', // Validate that user exists in users table
            'checkin_description' => 'nullable|string', // Optional description
            'status' => 'nullable|in:present,absent,late', // Optional status
        ];
    
        // Validate request data
        $validator = Validator::make($request->all(), $rules);
    
        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
    
        // Find user by user_id
        $user = User::where('user_id', $request->user_id)->first();
    
        if (!$user || $user->status !== 'active' || $user->trash !== 0) {
            return response()->json([
                'message' => 'User is inactive or trashed'
            ], 400);
        }
    
        // Create new check-in record
        $attendance = CheckIn::create([
            'employee_id' => $request->user_id,
            'check_in_time' => now(),
            'check_in_info' => $request->checkin_description,
            'status' => $request->status,
        ]);
    
        return response()->json([
            'message' => 'Check-in record created successfully',
            'data' => $attendance,
        ], 201);
    }
    
    public function checkOut(Request $request)
    {
        // Define validation rules for check-out
        $rules = [
            'user_id' => 'required|exists:users,user_id', // Validate that user exists in users table
            'check_out_time' => 'required|date', // Required check-out time
            'check_out_description' => 'nullable|string', // Optional description
        ];
    
        // Validate request data
        $validator = Validator::make($request->all(), $rules);
    
        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
    
        // Find user by user_id
        $user = User::where('user_id', $request->user_id)->first();
    
        if (!$user || $user->status !== 'active' || $user->trash !== 0) {
            return response()->json([
                'message' => 'User is inactive or trashed'
            ], 400);
        }
    
        // Find existing check-in record
        $attendance = CheckIn::where('employee_id', $request->user_id)
            ->whereNull('check_out_time') // Ensure we are updating an open check-in
            ->first();
    
        if (!$attendance) {
            return response()->json([
                'message' => 'No active check-in record found'
            ], 404);
        }
    
        // Update check-out record
        $attendance->update([
            'check_out_time' => $request->check_out_time,
            'check_out_info' => $request->check_out_description,
        ]);
    
        return response()->json([
            'message' => 'Check-out record updated successfully',
            'data' => $attendance,
        ], 200);
    }
    

   public function fetchAttendance(Request $request)
{
    // Set default values for sorting and pagination
    $sort_order = $request->sort_order ?? 'asc';
    $col = $request->col ?? 'attendance_date'; // Sort by attendance_date by default
    $limit = $request->limit ?? 10;
    $page = $request->page ?? 1;

    $pg = $page - 1;
    $start = ($pg > 0) ? $limit * $pg : 0;

    // Query attendance data
    $query = Attendance::whereHas('employee', function ($query) {
        $query->whereHas('user', function ($query) {
            $query->where('trash', 0);  // Ensure that users who are not deleted are selected
        });
    });

    // Apply filters if provided
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    if ($request->filled('startDate') && $request->filled('endDate')) {
        $query->whereDate('attendance_date', '>=', $request->startDate)
              ->whereDate('attendance_date', '<=', $request->endDate);
    }

    // Total rows for pagination
    $totalRows = $query->count();

    // Fetch attendance records with sorting and pagination
    $attendance = $query
        ->orderBy($col, $sort_order)
        ->offset($start)
        ->limit($limit)
        ->get([
            'id',
            'user_id',
            'attendance_date',
            'check_in_time',
            'check_in_description',
            'check_out_time',
            'check_out_description',
            'status',
            'created_at',
            'updated_at'
        ]);

    // Calculate the counts for each status
    $statuses = ['absent', 'halfday', 'fullday', 'late', 'present'];
    $statusCounts = [];

    foreach ($statuses as $status) {
        $statusCounts[$status] = Attendance::where('status', $status)
            ->whereHas('employee', function ($query) {
                $query->whereHas('user', function ($query) {
                    $query->where('trash', 0);
                });
            })
            ->when($request->filled('startDate') && $request->filled('endDate'), function ($query) use ($request) {
                $query->whereDate('attendance_date', '>=', $request->startDate)
                      ->whereDate('attendance_date', '<=', $request->endDate);
            })
            ->count();
    }

    // Return the response with attendance records and status counts
    return response()->json([
        'message' => 'Attendance records retrieved successfully',
        'data' => $attendance,
        'total' => $totalRows,
        'totals' => $statusCounts, // Include status counts
        'current_page' => $page,
        'per_page' => $limit,
    ], 200);
}

    

    /**
     * Update an attendance record.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function actionAttendance(Request $request)
    {
        $request->validate([
            'action' => 'required|in:present,absent,late,fullday,halfday',
        ]);

        $attendance = Attendance::findOrFail($request->id);
        $attendance->status = $request->action;
        $attendance->save();

        return response()->json([
            'message' => 'Attendance record updated successfully',
            'data' => $attendance,
        ]);
    }

    /**
     * Delete an attendance record.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAttendance($id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->delete();

        return response()->json([
            'message' => 'Attendance record deleted successfully',
        ]);
    }
}
