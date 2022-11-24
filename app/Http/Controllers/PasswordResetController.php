<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\SendDemoMail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use App\Models\PasswordReset;
use App\Traits\HttpResponses;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\ResetPasswordEmailRequest;

class PasswordResetController extends Controller
{
    use HttpResponses;
    
    public function sendResetPasswordEmail(ResetPasswordEmailRequest $request)
    {
        $email = $request->email;

        // Check users email exists or not
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            return $this->error('', 'Email doesn\'t exists', 404);
        }

        // generate token
        $token = Str::random(60);
   
        $mailData = [
            'title'   => 'You have requested to reset your password',
            'content' => 'We cannot simply send you your old password. A unique link to reset your
        password has been generated for you. To reset your password, click the
        following link and follow the instructions.',
            'url'     => env('FRONT_END_APP_URL') . "/api/users/reset/" . $token
        ];
  
        Mail::to($email)->send(new SendDemoMail($mailData));

        // saving data to password reset table
        $pas = PasswordReset::create([
            'email'      => $email,
            'token'      => $token,
            'created_at' => Carbon::now()
        ]);

        return $this->success([
            'data' => $pas,
            'message' => 'Password reset Email Sent... Check your email'
        ]);
    }

    public function resetPassword( ResetPasswordRequest $request, $token )
    {
        // Delete token older then 5 minutes
        $formatted = Carbon::now()->subMinutes(5)
        ->toDateTimeString();
        PasswordReset::where('created_at', '<=', $formatted)->delete();

        $passwordReset =  PasswordReset::where('token', $token)->first();

        if (!$passwordReset) {
            return $this->error('', 'Token is invalid or expired', 404);
        }

        $user = User::where('email', $passwordReset->email)->first();

        if (!$user) {
            return $this->error('', 'User not found', 404);
        }

        $user->password = Hash::make($request->password);

        $user->save();

        // delete the token after resetting password
        PasswordReset::where('email', $user->email)->delete();

        return $this->success([
            'message' => 'Password change successfully'
        ]);
    }
}
