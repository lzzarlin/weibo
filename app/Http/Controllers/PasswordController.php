<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    // 访问限流
    public function __construct()
    {
        $this->middleware('throttle:2,1', [
            'only' => ['showLinkRequestForm']
        ]);
    }
    // 申请重置密码页面
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    // 发送重置密码申请

    public function sendResetLinkEmail(Request $request)
    {
        // 1. 验证提交的数据
        $request->validate(['email' => 'required|email']);
        $email = $request->email;

        // 2. 获取用户
        $user = User::where('email', $email)->first();

        // 3. 验证用户
        if (is_null($user)) {
            session()->flash('danger', '邮箱未注册');
            return redirect()->back()->withInput();
        }
        // 4. 用户存在 生成 token 
        $token = hash_hmac('sha256', Str::random(40), config('app.key'));

        // 5. Token 入库 password_resets
        DB::table('password_resets')->updateOrInsert(['email' => $email], [
            'email' => $email,
            'token' => Hash::make($token),
            'created_at' => new Carbon,
        ]);

        // 6. 将带有token的链接发给用户
        Mail::send('emails.reset_link', compact('token'), function ($message) use ($email) {
            $message->to($email)->subject('忘记密码');
        });

        session()->flash('success', '重置邮件发送成功，请查收!');
        return redirect()->back();
    }

    public function showResetForm(Request $request)
    {
        // 从链接中获取token参数
        $token = $request->route()->parameter('token');
        return view('auth.passwords.reset', compact('token'));
    }

    public function reset(Request $request)
    {
        // 1. 验证数据
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);
        $email = $request->email;
        $token = $request->token;
        // 过期时间
        $expires = 60 * 10;
        // 2. 获取对应的用户
        $user = User::where('email', $email)->first();

        // 3. 用户不存在
        if (is_null($user)) {
            session()->flash('danger', '邮箱未注册');
            return redirect()->back()->withInput();
        }
        // 4. 读取重置的记录
        $record = (array) DB::table('password_resets')->where('email', $email)->first();

        // 5. 记录存在
        if ($record) {
            // 检查是否过期
            if (Carbon::parse($record['created_at'])->addSeconds($expires)->isPast()) {
                session()->flash('danger', '链接已过期，请重新申请重置');
                return redirect()->back();
            }
            // 检查是否正确
            if ( ! Hash::check($token, $record['token'])) {
                session()->flash('danger', '令牌错误');
                return redirect()->back();
            }
            // 一切正常 更新用户密码
            $user->update(['password' => bcrypt($request->password)]);

            session()->flash('success', '密码重置成功，请使用新密码登录');
            return redirect()->route('login');
        }

        // 6. 记录不存在
        session()->flash('danger', '未找到重置记录');
        return redirect()->back();
    }
}
