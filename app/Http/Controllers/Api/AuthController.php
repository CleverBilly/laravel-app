<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * 用户注册
     *
     * @param RegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = JWTAuth::fromUser($user);

            return $this->success([
                'user' => $user,
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60, // 转换为秒
            ], '注册成功', 201);
        } catch (\Exception $e) {
            return $this->serverError('注册失败：' . $e->getMessage());
        }
    }

    /**
     * 用户登录
     *
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return $this->error('邮箱或密码错误', 401);
            }
        } catch (JWTException $e) {
            return $this->serverError('无法创建令牌');
        }

        $user = Auth::user();

        return $this->success([
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60, // 转换为秒
        ], '登录成功');
    }

    /**
     * 获取当前用户信息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return $this->error('用户不存在', 404);
            }

            return $this->success([
                'user' => $user,
            ], '获取成功');
        } catch (JWTException $e) {
            return $this->error('Token 无效', 401);
        }
    }

    /**
     * 刷新 Token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        try {
            $token = JWTAuth::parseToken()->refresh();
            
            return $this->success([
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60, // 转换为秒
            ], 'Token 刷新成功');
        } catch (JWTException $e) {
            return $this->error('Token 刷新失败', 401);
        }
    }

    /**
     * 用户登出
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        try {
            JWTAuth::parseToken()->invalidate();

            return $this->success(null, '登出成功');
        } catch (JWTException $e) {
            return $this->error('登出失败', 500);
        }
    }
}

