<?php
namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    public function createEmployee(Request $request)
{
    $validator = Validator::make($request->all(), [
        'user_name' => 'required|string|max:255',
        'user_email' => 'required|email|unique:users,email',
        'phone' => 'required|string|max:15',
        'country' => 'required|string|max:100',
        'state' => 'required|string|max:100',
        'address' => 'required|string|max:255',
        'designation' => 'required|string|max:255',
        'position' => 'required|string|max:255',
        'account_type' => 'required|string|in:admin,employee',
    ]);

    if ($validator->fails()) {
        return response()->json(['message' => 'Invalid parameters', 'errors' => $validator->errors()], 400);
    }

    $randomNumber = rand(1000, 9999);
    $userId = 'EMP' . substr($request->user_name, 0, 3) . $randomNumber;

    // Set current date and time
    $currentDateTime = now();

    // Create user
    $user = User::create([
        'user_id' => $userId,  // Ensure user_id is included
        'name' => $request->user_name,
        'email' => $request->user_email,
        'password' => bcrypt('defaultPassword'),  // Make sure to set a password
        'country' => $request->country,
        'state' => $request->state,
        'mobile' => $request->phone,
        'address' => $request->address,
        'role' => $request->account_type,
        'created_at' => $currentDateTime,
        'updated_at' => $currentDateTime,
        'trash' => 0,
    ]);

    // Create employee
    $employee = Employee::create([
        'user_id' => $userId, 
        'position' => $request->position,
        'designation' => $request->designation,
        'hire_date' => $currentDateTime,
        'created_at' => $currentDateTime,
        'updated_at' => $currentDateTime,
    ]);

    // Prepare response data
    $responseData = [
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->mobile,
            'country' => $user->country,
            'state' => $user->state,
            'address' => $user->address,
            'role' => $user->role,
        ],
        'employee' => [
            'user_id' => $employee->user_id,
            'position' => $employee->position,
            'department' => $employee->department,
            'hire_date' => $employee->hire_date,
            'status' => $employee->status,
        ],
    ];

    return response()->json(['message' => 'Employee created successfully', 'data' => $responseData], 201);
}


public function fetchEmployees(Request $request)
{
    // Validate request parameters
    $validator = Validator::make($request->all(), [
        'sort_order' => 'in:asc,desc',
        'col' => 'string|in:employees.id,users.name,users.email,employees.position,employees.department,employees.created_at,employees.status',
        'limit' => 'integer|min:1|max:100',
        'page' => 'integer|min:1',
        'status' => 'in:active,inactive',
        'start_date' => 'date',
        'end_date' => 'date',
    ]);

    if ($validator->fails()) {
        return response()->json(['message' => 'Invalid parameters', 'errors' => $validator->errors()], 400);
    }

    // Default values
    $sort_order = $request->get('sort_order', 'asc');
    $col = $request->get('col', 'employees.id');
    $limit = $request->get('limit', 10);
    $page = $request->get('page', 1);

    // Build the query
    $query = DB::table('employees')
        ->join('users', 'employees.user_id', '=', 'users.user_id')
        ->select('employees.*', 'users.name as user_name', 'users.email as user_email')
        ->where('users.trash', 0);

    // Filter by status if provided
    if ($request->has('status')) {
        $query->where('employees.status', $request->status);
    }

    // Filter by date range if both start and end dates are provided
    if ($request->has('start_date') && $request->has('end_date')) {
        $query->whereBetween('employees.created_at', [$request->start_date, $request->end_date]);
    }

    // Fetch employees with sorting and pagination
    $employees = $query
        ->orderBy($col, $sort_order)
        ->paginate($limit, ['*'], 'page', $page);

    return response()->json([
        'message' => 'Employees retrieved successfully',
        'data' => $employees->items(),
        'total' => $employees->total(),
        'current_page' => $employees->currentPage(),
        'per_page' => $employees->perPage(),
    ], 200);
}

    public function updateTrashStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid parameters',
                'errors' => $validator->errors()
            ], 400);
        }

        $userId = $request->id;

        try {
            $affectedRows = DB::table('users')
                ->where('id', $userId)
                ->update(['trash' => 1]);

            if ($affectedRows > 0) {
                return response()->json(['message' => 'Trash status updated successfully'], 200);
            } else {
                return response()->json(['message' => 'No employee found or status already set'], 404);
            }
        } catch (\Exception $e) {
            // Consider logging the exception here
            Log::error('Error updating trash status: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error updating trash status',
                'error' => 'An internal server error occurred.'
            ], 500);
        }
    }
}
