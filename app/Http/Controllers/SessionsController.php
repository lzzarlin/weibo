<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionsController extends Controller
{
    //
    public function create()
    {
        return view('sessions.create');
    }

    public function store(Request $request)
    {
        // 验证用户信息
        $credentials = $this->validate($request, [
            'email' => 'required|email|max:255',
            'password' => 'required'
        ]);

        if(Auth::attempt($credentials, $request->has('remember'))){
            session()->flash('success', 'welcome back!');
            // dd(Auth::user());
            return redirect()->route('users.show', [Auth::user()]);
        }else{
            session()->flash('danger', 'sorry 你的邮箱和密码不匹配');
            return redirect()->back()->withInput();
        }

        return ;
    }

    public function destroy()
    {
        Auth::logout();
        session()->flash('danger', '您已经退出');
        return redirect()->route('login');
    }
}
