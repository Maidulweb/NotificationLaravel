<?php

namespace App\Http\Controllers;


use Illuminate\Support\Str;
use App\Mail\VerificationEmail;
use App\Notifications\emailVerify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use App\User;

class indexController extends Controller
{
    public function index ()
    {
        return view('index');
    }
    public function register ()
    {
           return view('login_register.register');
    }
    public function registerprocess (Request $request)
    {
        $request->validate([
            'username' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);

        $data = [
            'username' => $request->input('username'),
            'email' => strtolower($request->input('email')),
            'password' =>bcrypt($request->input('password')),
            'email_verification_token' => Str::random(32),
        ];

        $user = User::create($data);

        $user->notify(new emailVerify($user));


        session()->flash('message', 'Successfully Register!');
        session()->flash('type', 'success');
        return redirect()->route('login');

    }
    public function login ()
    {
           return view('login_register.login');
    }
    public function loginprocess (Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {

            $user = Auth::user();
            
            if($user->email_verified === 0) {

            session()->flash('message', 'Not activated');
            session()->flash('type', 'danger');

            auth()->logout();

            return redirect()->route('login');
            }

            return redirect()->route('index');
            session()->flash('message', 'Successfully login!');
            session()->flash('type', 'success');
        }

            session()->flash('message', 'Something went wrong!!!!');
            session()->flash('type', 'danger');
            return redirect()->back();

    }

    public function verifyEmail ($token = null)
    {

        if($token === null) {

            session()->flash('message', 'Null!!!!');
            session()->flash('type', 'danger');
            return redirect()->route('login');
        }

        $user = User::where('email_verification_token', $token)->first();

        if($user === null) {

            session()->flash('message', 'Invalid token!!!');
            session()->flash('type', 'warning');

            return redirect()->route('login');
        }

        $user->update([
            'email_verified' => 1,
            'email_verified_at' => Carbon::now(),
            'email_verification_token' => '',
        ]);

        session()->flash('message', 'Activated');
        session()->flash('type', 'success');
        return redirect()->route('login');
    }
}
