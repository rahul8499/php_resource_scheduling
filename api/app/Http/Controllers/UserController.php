<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmationEmail;
use App\Mail\PasswordResetEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class UserController extends Controller
{
    private $appUrl;

    public function __construct()
    {
        $this->appUrl = rtrim(config('app.url', env('APP_URL')), '/');
    }

   public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|unique:users,email',
        'contact_number' => 'nullable|min:10',
        'password' => 'required|string|min:6',
    ]);
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $user = new User();
    $user->email = $request->email;
    $user->name = $request->name;
    $user->location_id = $request->location_id;
    $user->contact_number = $request->input('contact_number');
    $user->password = Hash::make($request->password); 
    $user->save();
    
    // Generate token for the registered user
    $token = $user->createToken('AuthToken')->plainTextToken;

    return response()->json(['message' => 'Registration successful', 'token' => $token]);
}


    public function login(Request $request)
{
    try {
        // Attempt to authenticate the user with the provided credentials
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            // Authentication successful
            $user = Auth::user();
            $token = $user->createToken('AuthToken')->plainTextToken;

            return response()->json(['token' => $token]);
        } else {
            // Authentication failed
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
    } catch (ValidationException $e) {
        return response()->json(['message' => 'Authentication error: ' . $e->getMessage()], 401);
    }
}

public function createUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'location_id' => 'required',
            'email' => 'required|email|unique:users',
            'contactNumber' => 'nullable|digits:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->input('name'),
            'location_id' => $request->input('location_id'),
            'email' => $request->input('email'),
            'contact_number' => $request->input('contactNumber'),
            'confirmation_token' => Str::uuid(),
        ]);
        // Send confirmation email
        $this->sendConfirmationEmail($user);

        return response()->json(['message' => 'User created successfully']);
    }

    public function passwordUpdate(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $user = User::where('confirmation_token', $request->input('token'))->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid token or email'], 400);
        }

        // Update user password and clear confirmation token
        $user->update([
            'password' => bcrypt($request->input('password')),
            'confirmation_token' => null,
        ]);

        return response()->json(['message' => 'Password set successfully']);
    }

    private function sendConfirmationEmail(User $user)
    {
        // Generate confirmation link
        $confirmationLink = $this->appUrl.'/CreatePassword?token=' . $user->confirmation_token->toString();
        // Send confirmation email
        Mail::to($user->email)->send(new ConfirmationEmail($confirmationLink));
    }

    public function getUsers(Request $request)
{
    $query = User::with('location');

    // Sort by specified columns if sortBy and sortOrder are provided
    $requestParams = $request->all();

    if (!empty($requestParams['q'])) {
        $searchFields = ['contact_number', 'email']; // Adjust these fields based on your User model
        $query->where(function ($q) use ($searchFields, $requestParams) {
            foreach ($searchFields as $field) {
                $q->orWhere($field, 'like', '%' . $requestParams['q'] . '%');
            }
        });
    }
    if (isset($requestParams['sortBy']) && isset($requestParams['sortOrder'])) {
        $query->orderBy($requestParams['sortBy'], $requestParams['sortOrder']);
    }

    // Paginate the results with the specified limit, default to 10 if not provided
    $limit = isset($requestParams['limit']) ? $requestParams['limit'] : 10;
    $users = $query->paginate($limit);

    return response()->json(['users' => $users]);
}

    public function updateUser(Request $request, $id)
{
    $user = User::find($id);

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $validator = Validator::make($request->all(), [
        'email' => 'required|email|unique:users,email,' . $user->id,
        'contact_number' => 'nullable|digits:10',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Update user fields
    // Define an array of fields that can be updated
    $fillableFields = ['name', 'location_id', 'email', 'contact_number'];

    // Loop through each field and update if present in the request
    foreach ($fillableFields as $field) {
        if ($request->has($field)) {
            $user->$field = $request->$field;
        }
    }

    $user->save();

    return response()->json(['message' => 'User updated successfully', 'user' => $user]);
}


    public function getByIdUser($id)
    {
        $user = User::with('location')->find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json(['user' => $user]);
    }

    public function deleteUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    private function sendPasswordResetEmail(User $user, $resetLink)
    {
        // Send password reset email
        Mail::to($user->email)->send(new PasswordResetEmail($resetLink,$user));
    }
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->input('email'))->first();

        // Generate a unique token for password reset
        $resetToken = Str::uuid();

        // Store the token in the password_resets table along with the user's email
        DB::table('password_resets')->updateOrInsert(
            ['email' => $user->email],
            ['token' => $resetToken, 'created_at' => now()]
        );

        // Send the password reset email with the reset link
        $resetLink = $this->appUrl.'/resetPassword?token=' . $resetToken->toString();
        // $resetLink = route('user.resetPassword', ['token' => $resetToken->toString()]);
        $this->sendPasswordResetEmail($user, $resetLink);

        return response()->json(['message' => 'Password reset link sent to your email']);
    }
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|exists:password_resets,token',
            'password' => 'required|string|min:8',
        ]);

        $resetData = DB::table('password_resets')
            ->where('token', $request->input('token'))
            ->first();

        if (!$resetData) {
            return response()->json(['error' => 'Invalid token'], 400);
        }

        $user = User::where('email', $resetData->email)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Update user password and clear the reset token
        $user->update([
            'password' => bcrypt($request->input('password')),
        ]);

        // Delete the reset token from the password_resets table
        DB::table('password_resets')->where('token', $request->input('token'))->delete();

        return response()->json(['message' => 'Password reset successful']);
    } 

}