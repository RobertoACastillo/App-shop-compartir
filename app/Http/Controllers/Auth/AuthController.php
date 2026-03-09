<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Auth\Guard;
  use Spatie\Permission\Models\Role;



class AuthController extends Controller
{
    public function login(Request $request){
        $credenciales = $request->only('email','password');
        //evaluamos si no se obtiene un token valido
        if(!$token = Auth::attempt($credenciales)){
            return response()->json([
                'message' => 'Credenciales invalidas'
            ], 401);
        }

        //en caso de exitoso retornamos el token
        return $this->responseWithToken($token);
    }
    
    

    //metodo para elregistro de usuarios
    public function register(Request $request){
        //validamos datos a traves de Request
        $validator = Validator::make($request->all(),[
            'name' => 'required|string|max:255',
            'email'=> 'required|string|email|max:255|unique:users',
            'password'=> 'required|string|min:8|confirmed'
        ]);
        if($validator->fails()){
            return response()->json([
                'message'=> $validator->errors()
            ],422);
        }
        $user = User::create([
            'name'=> $request->name,
            'email'=> $request->email,
            'password'=> Hash::make( $request->password ),
        ]);

        //Recordatorio--Asignar rol por defecto
        $user->assignRole('CLIENTE');
        


        //regeneramos el token
        $token = JWTAuth::fromUser($user);
        //retornamos la rspuesta 
        return response()->json([
            'message' => 'usuario registrado correctamente',
            'user' => $user->load('roles'),
            'access_token' => $token,
            'token_type'=> 'bearer'
        ],201);
    }


    protected function responseWithToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => auth()->user()->load('roles'),
            'expires_in' => auth()->factory()->getTTL() *60,
        ]);
    }

    //metodopara un usuario autenticado
    public function me(){
        return response()->json(auth()->user());
    }

    //metodo para invalidar el token(logout)
    public function logout(){
        auth()->logout();
        return response()->json([
            'message' => 'sesion cerrada correctamente' 
        ]);
    }

    //metodo para refrescar el token
    public function refresh(){
        return $this->responseWithToken(auth()->refresh());
    }
}



