<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\HttpResponses;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\ChangePasswordRequest;

class AuthController extends Controller
{
    use HttpResponses;

    public function me(Request $request)
    {
        return new UserResource(Auth::user());
    }

    public function login(LoginUserRequest $request)
    {
        $request->validated($request->all());

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return $this->error('', 'Credentials do not match.', 401);
        }

        $user = User::where('email', $request->email)->first();

        return $this->success([
            'user'  => $user,
            'token' => $user->createToken('API Token of ' . $user->name)->plainTextToken
        ]);
    }

    public function register(StoreUserRequest $request)
    {
        $request->validated($request->all());

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password)
        ]);

        return $this->success([
            'user' => $user,
            'token' => $user->createToken('API Token of ' . $user->name)->plainTextToken
        ]);
    }

    public function logout()
    {
        Auth::user()->currentAccessToken()->delete();

        return $this->success([
            'message' => 'You are successfully logged out'
        ]);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $loggedUser = Auth::user();

        $loggedUser->password = Hash::make($request->password);

        $loggedUser->save();

        return $this->success([
            'message' => 'Password changed successfully.'
        ]);
    }
}
