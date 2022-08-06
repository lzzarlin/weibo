<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest', [
            'only' => ['create']
        ]);
        $this->middleware('throttle:10,10', [
            'only' => ['store'],
        ]);
    }
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
            // 判断用户是否激活
            if (Auth::user()->activated) {
                # 已激活用户 允许登录并跳转到用户首页
                session()->flash('sucess', '欢迎回来！');
                $fallback = route('users.show', Auth::user());
                return redirect()->intended($fallback);
            } else {
                # 退出用户登录 提示需要激活并跳转到首页
                Auth::logout();
                session()->flash('warning', '你的账号未激活，请检查邮箱中的注册邮件进行激活。');
                return redirect('/');
            }
            
            // session()->flash('success', 'welcome back!');
            // dd(Auth::user());
            // return redirect()->route('users.show', [Auth::user()]);
            // $fallback = route('users.show', Auth::user());
            // return redirect()->intended($fallback);
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
