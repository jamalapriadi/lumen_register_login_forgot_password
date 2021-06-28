<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

use Illuminate\Support\Facades\Mail;
use App\Mail\RegistrasiEmail;

class WebController extends Controller
{

    public function save_registrasi(Request $request)
    {
        $this->validate($request, [
            'first_name'=>'required',
            'last_name'=>'required',
            'email'=>'required',
            'username'=>'required',
            'password'=>'required|confirmed',
            'password_confirmation'=>'required',
        ]);

        $model = new User;
        $model->first_name = $request->input('first_name');
        $model->last_name = $request->input('last_name');
        $model->username = $request->input('username');
        $model->email = $request->input('email');
        $model->password = Hash::make($request->input('password'));
        $model->token = Str::random(40) . $request->input('email');
        $model->active = 'N';

        $save = $model->save();

        if($save)
        {
            $receiver = $model->email;
            $full_name = $model->first_name.' '.$model->last_name;
            $link_activation = env('FE_URL').'/activation/'.$model->token;

            Mail::to($receiver)->send(new RegistrasiEmail($full_name, $link_activation));

            $data = array( 
                'success'=>true,
                'message'=>'Registration successfull' 
            );
        }else{
            $data = array( 
                'success'=>false,
                'message'=>'Registration failed'
            );
        }

        return $data;
    }

    public function verifikasi_email(Request $request)
    {
        $id = $request->input('id');

        $user = User::where('token', $id)
            ->where('active','N')
            ->first();

        if($user)
        {
            $ac = \App\Models\User::find($user->id);
            $ac->active = 'Y';
            $ac->email_verified_at = date('Y-m-d H:i:s');
            $ac->token = Str::random(40) . $user->email;
            $ac->save();

            $data = array( 
                'success'=>true,
                'message'=>'Your Account has been Activated, <br> Now you can Login using your <strong>Username / Email</strong>',
            );
        }else{
            $data = array( 
                'success'=>false,
                'message'=>'Account not found'
            );
        }

        return $data;
    }

    public function login(Request $request)
    {
        $this->validate($request,[
            'email'=>'required',
            'password'=>'required'
        ]);

        $user=\App\Models\User::where('email',$request->input('email'))
            ->where('active','Y')
            ->first();

        if($user){
            if(Hash::check($request->input('password'), $user->password)){

                $token = $user->createToken('YourApp')->accessToken;

                return array(
                    'token_type'=>'Bearer', 
                    'expires_in'=>60*60*24*7,
                    'access_token'=>$token,
                    'refresh_token'=>''
                );
            }else{
                return response()->json(
                    [
                        'success'=>false,
                        'message'=>'Password wrong'
                    ]
                );
            }
        }else{
            return response()->json(
                [
                    'success'=>false,
                    'message'=>'User Not Found'
                ]
            );
        }
    }

    public function forgot_password(Request $request)
    {
        $this->validate($request, [
            'email' => 'required'
        ]);

        $email = $request->input('email');

        $user = User::where('email',$email)
        ->where('active','Y')
        ->first();

        if($user)
        {
            //send email
            $c_user = \App\Models\User::find($user->id);
            $c_user->token = Str::random(40) . $user->email;
            $c_user->save();

            $link_activation = env('FE_URL').'/recovery/'.$c_user->token;

            Mail::to($user->email)->send(new \App\Mail\ForgotPassword($user->full_name, $link_activation));

            $data = array( 
                'success'=>true,
                'message'=>'Your link forgot password has been sent mail'
            );
        }else{
            $data = array( 
                'success'=>false,
                'message'=>'Username / Email not found'
            );
        }

        return $data;
    }

    public function cek_user_recovery(Request $request)
    {
        $kode = $request->input('kode');

        $cek = User::where('token', $kode)
            ->first();

        if($cek)
        {
            $data = array( 
                'success'=>true,
                'message'=>'User Found'
            );
        }else{
            $data = array( 
                'message'=>false,
                'message'=>'User Not Found'
            );
        }

        return $data;
    }

    public function update_forgot_password(Request $request)
    {
        $this->validate($request, [
            'kode'=>'required',
            'password'=>'required|confirmed',
            'password_confirmation'=>'required'
        ]);
            
        $kode = $request->input('kode');

        $cek = User::where('token', $kode)
            ->first();

        if($cek)
        {
            $password = $request->input('password');
            $password_confirmation = $request->input('password_confirmation');

            if($password == $password_confirmation)
            {
                $user = \App\Models\User::find($cek->id);
                $user->password = Hash::make($request->input('password'));
                $user->remember_token = Str::random(40) . $cek->email;
                $user->save();

                $data = array( 
                    'success'=>true,
                    'message'=>'Your Password has been change'
                );   
            }else{
                $data = array( 
                    'success'=>false,
                    'message'=>'Password doesnt match'
                );
            }
        }else{
            $data = array( 
                'message'=>false,
                'message'=>'User Not Found'
            );
        }

        return $data;
    }
}
