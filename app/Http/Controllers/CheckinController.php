<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\CheckIn;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CheckinController extends Controller
{
    // Check-in method
    public function checkIn(Request $request)
    {
        // Validate the request
        $request->validate([
            'user_id' => 'required|string|exists:users,user_id',  // Validate user_id exists in users table
            'check_in_info' => 'nullable|string',  // Optional check-in info
        ]);

        // Retrieve the user
        $user = User::where('user_id', $request->user_id)->first();

        // Ensure the user is active and not trashed
        if ($user && $user->trash == 0) {
            // Check if there's already a check-in for today without a check-out
            $existingCheckIn = CheckIn::where('employee_id', $user->user_id)
                ->whereNull('check_out_time')
                ->whereDate('check_in_time', Carbon::now('Asia/Kolkata')->toDateString())  // Consider only today's check-ins
                ->first();

            // If a check-in already exists for today, return an error (user needs to check out first)
            if ($existingCheckIn) {
                return response()->json(['message' => 'Please check out first.'], 403);
            }

            $nowIndia = Carbon::now('Asia/Kolkata');  // Current time in IST

            // Determine the status based on check-in time
            $status = $nowIndia->format('H:i') > '10:00' ? 'Late' : 'Active';

            // Store the new check-in record
            $checkIn = CheckIn::create([
                'employee_id' => $user->user_id,
                'status' => $status,
                'user_name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'check_in_time' => $nowIndia,
                'check_in_info' => $request->input('check_in_info'),  // Optional description
            ]);

            // Store the attendance record
            Attendance::create([
                'user_id' => $user->user_id,
                'attendance_date' => $nowIndia->toDateString(),  // Store today's date
                'check_in_time' => $nowIndia,  // Current time
                'check_in_description' => 'Entry Successful',  // Optional description
                'status' => $status,
            ]);

            return response()->json([
                'message' => 'Check-in successful',
                'check_in' => $checkIn,
            ], 200);
        }

        return response()->json(['message' => 'User is not active or is trashed'], 403);
    }

    // Check-out method
    public function checkOut(Request $request)
    {
        // Validate the request
        $request->validate([
            'user_id' => 'required|string|exists:users,user_id',  // Validate user_id exists in users table
            'check_out_info' => 'nullable|string',  // Optional check-out info
        ]);

        // Retrieve the user
        $user = User::where('user_id', $request->user_id)->first();

        // Ensure the user is active and not trashed
        if ($user && $user->trash == 0) {
            // Find the latest check-in record for today that has no check-out time
            $checkIn = CheckIn::where('employee_id', $user->user_id)
                ->whereNull('check_out_time')
                ->whereDate('check_in_time', Carbon::now('Asia/Kolkata')->toDateString())  // Only consider today's check-ins
                ->first();

            if ($checkIn) {
                // Calculate duration between check-in and check-out
                $checkOutTime = Carbon::now('Asia/Kolkata');
                $durationInHours = $checkOutTime->diffInHours($checkIn->check_in_time);

                // Determine the status based on the duration
                $status = ($durationInHours < 8) ? 'HalfDayPresent' : 'Active';

                // Update the check-in record with the check-out time and status
                $checkIn->update([
                    'check_out_time' => $checkOutTime,
                    'check_out_info' => $request->input('check_out_info'),  // Optional description
                    'status' => $status,
                ]); 

                // Find the corresponding attendance record for today
                $attendance = Attendance::where('user_id', $user->user_id)
                    ->whereDate('attendance_date', Carbon::now('Asia/Kolkata')->toDateString())  // Only consider today's attendance
                    ->first();

                // If attendance record exists, update it with the check-out time and status
                if ($attendance) {
                    $attendance->update([
                        'check_out_time' => $checkOutTime,
                        'check_out_description' => $request->input('check_out_info'),  // Optional description
                        'status' => $status,  // Update the status based on the duration
                    ]);
                }

                return response()->json([
                    'message' => 'Check-out successful',
                    'check_out' => $checkIn,
                    'attendance' => $attendance,
                ], 200);
            }

            return response()->json(['message' => 'No active check-in found for today'], 404);
        }

        return response()->json(['message' => 'User is not active or is trashed'], 403);
    }
    // Fetch check-in records method
    public function getCheckIns(Request $request)
    {
        // Validate the request
        $request->validate([
            'user_id' => 'nullable|string|exists:users,user_id',  // Validate user_id if provided
        ]);
    
        $query = CheckIn::where('check_in_time', '>=', now()->subDays(30))
            ->orderBy('check_in_time', 'desc');
    
        // Fetch check-ins for a specific user if user_id is provided
        if ($request->has('user_id')) {
            $query->where('employee_id', $request->user_id);
        }
    
        $checkIns = $query->take(20)->get();
    
        if ($checkIns->isEmpty()) {
            return response()->json(['message' => 'No check-ins found for this user in the last 30 days'], 404);
        }
    
        // Variables for calculating averages and finding the last check-out time
        $totalCheckInSeconds = 0;
        $totalCheckOutSeconds = 0;
        $checkInCount = 0;
        $checkOutCount = 0;
        $lastCheckOutTime = null;
    
        foreach ($checkIns as $checkIn) {
            // Convert check-in time to seconds
            $checkInTime = strtotime($checkIn->check_in_time);
            $totalCheckInSeconds += $checkInTime;
            $checkInCount++;
    
            // If check-out time exists, convert to seconds and store the last check-out time
            if ($checkIn->check_out_time) {
                $checkOutTime = strtotime($checkIn->check_out_time);
                $totalCheckOutSeconds += $checkOutTime;
                $checkOutCount++;
    
                // Update the last check-out time
                if (!$lastCheckOutTime || $checkOutTime > strtotime($lastCheckOutTime)) {
                    $lastCheckOutTime = $checkIn->check_out_time;
                }
            }
        }
    
        // Calculate the average check-in and check-out times
        $averageCheckInTime = $checkInCount ? date('H:i:s', $totalCheckInSeconds / $checkInCount) : null;
        $averageCheckOutTime = $checkOutCount ? date('H:i:s', $totalCheckOutSeconds / $checkOutCount) : null;
    
        return response()->json([
            'message' => 'Check-ins and averages retrieved successfully',
            'check_ins' => $checkIns,
            'average_check_in_time' => $averageCheckInTime,
            'average_check_out_time' => $averageCheckOutTime,
            'last_check_out_time' => $lastCheckOutTime,  // Return the last check-out time
        ], 200);
    }
}
