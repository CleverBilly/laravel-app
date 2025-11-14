<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;

/**
 * 用户服务类
 *
 * 这是一个示例 Service 类,展示推荐的业务逻辑封装方式
 * Service 层的职责:
 * 1. 封装复杂的业务逻辑
 * 2. 协调多个模型的操作
 * 3. 处理缓存、日志、队列等横切关注点
 * 4. 保持控制器的精简
 */
class UserService
{
    /**
     * 缓存 TTL (秒)
     */
    private const CACHE_TTL = 3600;

    /**
     * 根据 ID 获取用户(带缓存)
     *
     * @param int $userId
     * @return User
     * @throws \App\Exceptions\NotFoundException
     */
    public function getUserById(int $userId): User
    {
        $cacheKey = "user:{$userId}";

        // 使用缓存辅助函数,自动处理缓存穿透
        $user = cache_remember($cacheKey, function () use ($userId) {
            return User::find($userId);
        }, self::CACHE_TTL);

        if (!$user) {
            logger_warning('用户未找到', ['user_id' => $userId], 'business');
            throw_not_found_exception("用户不存在 (ID: {$userId})");
        }

        logger_info('获取用户信息', ['user_id' => $userId], 'business');

        return $user;
    }

    /**
     * 批量获取用户(带缓存优化)
     *
     * @param array $userIds
     * @return Collection
     */
    public function getUsersByIds(array $userIds): Collection
    {
        // 生成缓存键
        $cacheKeys = array_map(fn($id) => "user:{$id}", $userIds);

        // 批量获取缓存(使用 Laravel 原生方法)
        $cached = cache()->many($cacheKeys);

        // 找出未命中缓存的用户 ID
        $missedIds = [];
        foreach ($userIds as $id) {
            if (!isset($cached["user:{$id}"])) {
                $missedIds[] = $id;
            }
        }

        // 从数据库获取未命中的用户
        $users = collect($cached)->filter()->values();
        if (!empty($missedIds)) {
            $missedUsers = User::whereIn('id', $missedIds)->get();

            // 批量写入缓存(使用辅助函数)
            $cacheData = [];
            foreach ($missedUsers as $user) {
                $cacheData["user:{$user->id}"] = $user;
                $users->push($user);
            }
            cache_set_many($cacheData, self::CACHE_TTL);
        }

        logger_info('批量获取用户', [
            'total' => count($userIds),
            'cached' => count($userIds) - count($missedIds),
            'db_query' => count($missedIds),
        ], 'performance');

        return $users;
    }

    /**
     * 创建用户
     *
     * @param array $data
     * @return User
     * @throws \App\Exceptions\BusinessException
     */
    public function createUser(array $data): User
    {
        // 检查邮箱是否已存在
        if (User::where('email', $data['email'])->exists()) {
            throw_business_exception('该邮箱已被注册', 422);
        }

        // 创建用户
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // 记录业务日志
        logger_business('创建用户', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => get_client_ip(),
        ]);

        return $user;
    }

    /**
     * 更新用户信息
     *
     * @param int $userId
     * @param array $data
     * @return User
     * @throws \App\Exceptions\NotFoundException
     */
    public function updateUser(int $userId, array $data): User
    {
        $user = User::find($userId);

        if (!$user) {
            throw_not_found_exception("用户不存在 (ID: {$userId})");
        }

        // 如果更新邮箱,检查是否已被占用
        if (isset($data['email']) && $data['email'] !== $user->email) {
            if (User::where('email', $data['email'])->where('id', '!=', $userId)->exists()) {
                throw_business_exception('该邮箱已被使用', 422);
            }
        }

        // 更新用户
        $user->update($data);

        // 清除缓存
        cache()->forget("user:{$userId}");

        // 记录业务日志
        logger_business('更新用户信息', [
            'user_id' => $userId,
            'updated_fields' => array_keys($data),
        ]);

        return $user->fresh();
    }

    /**
     * 删除用户
     *
     * @param int $userId
     * @return bool
     * @throws \App\Exceptions\NotFoundException
     */
    public function deleteUser(int $userId): bool
    {
        $user = User::find($userId);

        if (!$user) {
            throw_not_found_exception("用户不存在 (ID: {$userId})");
        }

        // 删除用户
        $result = $user->delete();

        // 清除缓存
        cache()->forget("user:{$userId}");

        // 记录业务日志
        logger_business('删除用户', [
            'user_id' => $userId,
            'email' => $user->email,
        ]);

        return $result;
    }

    /**
     * 搜索用户(带分页)
     *
     * @param string|null $keyword
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchUsers(?string $keyword = null, int $perPage = 15)
    {
        $query = User::query();

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * 获取活跃用户统计(带缓存)
     *
     * @return array
     */
    public function getActiveUserStats(): array
    {
        $cacheKey = 'stats:active_users';

        return cache_remember($cacheKey, function () {
            $stats = [
                'total' => User::count(),
                'verified' => User::whereNotNull('email_verified_at')->count(),
                'unverified' => User::whereNull('email_verified_at')->count(),
            ];

            logger_performance('计算用户统计', 0, $stats);

            return $stats;
        }, 300); // 5分钟缓存
    }

    /**
     * 批量删除用户(示例:复杂业务逻辑)
     *
     * @param array $userIds
     * @return int 删除的数量
     */
    public function bulkDeleteUsers(array $userIds): int
    {
        // 防止误删所有用户
        if (empty($userIds)) {
            throw_business_exception('未指定要删除的用户', 400);
        }

        // 批量删除
        $deletedCount = User::whereIn('id', $userIds)->delete();

        // 批量清除缓存(循环删除)
        foreach ($userIds as $id) {
            cache()->forget("user:{$id}");
        }

        // 清除统计缓存
        cache()->forget('stats:active_users');

        // 记录业务日志
        logger_business('批量删除用户', [
            'count' => $deletedCount,
            'user_ids' => $userIds,
        ]);

        return $deletedCount;
    }

    /**
     * 重置用户密码
     *
     * @param int $userId
     * @param string $newPassword
     * @return bool
     * @throws \App\Exceptions\NotFoundException
     */
    public function resetPassword(int $userId, string $newPassword): bool
    {
        $user = User::find($userId);

        if (!$user) {
            throw_not_found_exception("用户不存在 (ID: {$userId})");
        }

        // 更新密码
        $user->password = Hash::make($newPassword);
        $result = $user->save();

        // 记录安全日志
        logger_warning('重置用户密码', [
            'user_id' => $userId,
            'email' => $user->email,
            'ip' => get_client_ip(),
        ], 'business');

        // 可以在这里触发队列任务发送通知邮件
        // queue_push([
        //     'user_id' => $userId,
        //     'email' => $user->email,
        //     'type' => 'password_reset',
        // ], 'emails');

        return $result;
    }
}
