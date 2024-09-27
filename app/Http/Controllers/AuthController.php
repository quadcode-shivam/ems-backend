<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validate input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Check if user exists and password is correct
        if ($user && Hash::check($request->password, $user->password)) {
            // Generate a custom token (e.g., 60 characters long)
            $token = bin2hex(random_bytes(30));

            // Save the token to the user record
            $user->api_token = $token;
            $user->save();

            // Return user data along with the custom token and success message
            return response()->json([
                'success' => true,
                'message' => 'Login successful!',
                'user' => $user,
                'token' => $token,
            ], 200);
        }

        // Return error response for invalid credentials
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials.'
        ], 401);
    }
}
