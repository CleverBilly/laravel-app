# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 项目概述

这是一个基于 Laravel 12 的生产级 API 框架骨架项目,提供了完整的认证、缓存、队列、日志等核心功能的抽象和封装。

**技术栈:**
- Laravel 12 (PHP 8.2+)
- JWT 认证 (tymon/jwt-auth)
- Redis (缓存和队列)
- RabbitMQ (可选队列驱动)
- Laravel Horizon (队列监控)
- Vite + TailwindCSS 4 (前端构建)

## 常用开发命令

### 项目初始化
```bash
composer setup                           # 完整安装(依赖、环境文件、密钥、数据库迁移、前端构建)
php artisan jwt:secret                   # 生成 JWT 密钥(首次安装后需执行)
```

### 开发环境
```bash
composer dev                             # 同时启动:server + queue + logs + vite
php artisan serve                        # 仅启动开发服务器
php artisan horizon                      # 启动队列监控(推荐)
php artisan queue:work                   # 启动队列消费者
php artisan pail                         # 实时日志查看
```

### 测试
```bash
composer test                            # 运行所有测试
php artisan test                         # 运行所有测试
php artisan test --testsuite=Unit        # 仅单元测试
php artisan test --testsuite=Feature    # 仅功能测试
php artisan test --coverage              # 生成覆盖率报告
php artisan test --filter AuthenticationTest  # 运行指定测试类
```

### 代码质量
```bash
./vendor/bin/pint                        # 格式化代码(Laravel Pint)
./vendor/bin/pint --test                 # 检查代码格式但不修改
```

### 数据库
```bash
php artisan migrate                      # 运行迁移
php artisan migrate:fresh --seed         # 重置数据库并填充数据
php artisan db:seed                      # 填充数据
```

### 性能优化(生产环境)
```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache                 # 缓存配置
php artisan route:cache                  # 缓存路由
php artisan view:cache                   # 缓存视图
php artisan event:cache                  # 缓存事件
```

### 清理缓存
```bash
php artisan cache:clear                  # 清除应用缓存
php artisan config:clear                 # 清除配置缓存
php artisan route:clear                  # 清除路由缓存
php artisan view:clear                   # 清除视图缓存
```

## 项目架构

### 核心架构模式

**控制器 -> 服务层 -> 模型** 的分层架构:
- **Controller**: 精简的请求处理和响应,不包含业务逻辑
- **Service**: 业务逻辑封装(虽然当前 app/Services/ 为空,但这是推荐的架构模式)
- **Model**: 数据访问层

### 关键目录结构

```
app/
├── Exceptions/          # 自定义异常类 + 全局异常处理器
│   ├── Handler.php      # 全局异常处理器(统一 JSON 响应)
│   ├── BusinessException.php
│   ├── NotFoundException.php
│   ├── UnauthorizedException.php
│   ├── ForbiddenException.php
│   └── ValidationException.php
│
├── Helpers/             # 全局辅助函数
│   └── functions.php    # API响应、HTTP请求、缓存、队列、日志等辅助函数
│
├── Http/
│   ├── Controllers/Api/ # API 控制器
│   ├── Middleware/      # 自定义中间件
│   │   ├── JwtAuthMiddleware.php  # JWT 认证
│   │   ├── RequestLog.php         # 请求日志
│   │   └── QueryLogger.php        # SQL 查询日志
│   ├── Requests/        # FormRequest 验证类
│   └── Traits/ApiResponse.php     # API 响应 Trait
│
├── Rules/               # 自定义验证规则
│   ├── StrongPassword.php  # 强密码验证(三种强度模式)
│   ├── Phone.php           # 手机号验证
│   ├── IdCard.php          # 身份证验证
│   ├── ImageBase64.php     # Base64 图片验证
│   ├── JsonString.php      # JSON 字符串验证
│   └── DateRange.php       # 日期范围验证
│
├── Jobs/                # 队列任务
└── Models/              # Eloquent 模型
```

### 路由组织

**routes/api.php** - 所有 API 路由均使用 `/api/v1` 前缀:
- `/api/v1/health` - 健康检查(无需认证)
- `/api/v1/auth/*` - 认证相关(register, login, logout, refresh, me)
- `/api/v1/examples/*` - 示例接口(展示各功能用法)

### 认证机制

使用 **JWT (JSON Web Token)** 认证:
- Middleware: `jwt.auth` (定义在 app/Http/Middleware/JwtAuthMiddleware.php)
- 受保护的路由需要在路由定义中添加 `middleware(['jwt.auth'])`
- Token 通过 `Authorization: Bearer <token>` 请求头传递

### 全局辅助函数系统

**app/Helpers/functions.php** 提供了丰富的辅助函数(已在 composer.json 中自动加载):

```php
// API 响应
api_success($data, $message, $code)
api_error($message, $code, $data)

// HTTP 请求
http_get($url, $params, $headers, $timeout)
http_post($url, $data, $headers, $timeout)
http_put($url, $data, $headers, $timeout)
http_delete($url, $data, $headers, $timeout)

// 缓存操作(支持 Pipeline 批量优化)
cache_get($key)
cache_set($key, $value, $ttl)
cache_delete($key)
cache_remember($key, $callback, $ttl)

// 队列操作(抽象层,支持 Redis/RabbitMQ)
queue_push($message, $queue, $connection)
queue_later($message, $delay, $queue, $connection)
queue_bulk($messages, $queue, $connection)
queue_size($queue, $connection)

// 日志记录
logger_info($message, $context, $channel)
logger_error($message, $context, $channel)
logger_warning($message, $context, $channel)
logger_business($message, $data)
logger_performance($operation, $duration, $context)
logger_exception($exception, $context)

// 异常抛出
throw_business_exception($message, $code)
throw_not_found_exception($message)
throw_unauthorized_exception($message)
throw_forbidden_exception($message)
throw_validation_exception($message, $validator)

// 字符串处理
mask_phone($phone)        # 138****8000
mask_email($email)        # u***r@example.com
generate_token($length)

// 验证
is_valid_email($email)
is_valid_phone($phone)
is_valid_url($url)

// 工具函数
get_client_ip()                  # 获取真实 IP
format_bytes($bytes)             # 格式化字节数
array_to_tree($items, $id, $pid, $children)  # 数组转树形结构
```

### 异常处理机制

**app/Exceptions/Handler.php** 提供全局异常处理:
- 所有异常统一返回 JSON 格式响应
- 生产环境自动隐藏敏感堆栈信息
- 支持自定义异常类型(BusinessException, NotFoundException 等)
- 自动记录异常日志

### 队列系统

支持双队列驱动(通过 QUEUE_CONNECTION 环境变量切换):
- **Redis** (默认,推荐): 轻量级,配置简单
- **RabbitMQ** (可选): 企业级消息队列,需额外配置

队列监控:
- 使用 **Laravel Horizon** 监控队列状态(仅支持 Redis)
- 访问 `/horizon` 查看仪表盘

### 缓存策略

优化的缓存服务(通过辅助函数使用):
- **批量操作优化**: 使用 Redis Pipeline,性能提升 70%+
- **防缓存穿透**: `cache_remember()` 自动处理 null 值
- **防缓存雪崩**: 支持随机过期时间(jitter)

### 日志系统

多通道日志配置(config/logging.php):
- **stack**: 默认通道,写入 storage/logs/laravel.log
- **business**: 业务日志
- **api**: API 请求日志
- **exception**: 异常日志
- **performance**: 性能监控日志

日志自动脱敏: password, token, secret 等敏感字段会被自动替换为 `***`

### 数据验证

**自定义验证规则** (app/Rules/):
- **StrongPassword**: 强密码验证,支持三种模式:
  - `StrongPassword::default()` - 至少8位,含大小写字母和数字
  - `StrongPassword::strong()` - 至少10位,含大小写字母、数字和特殊字符
  - `StrongPassword::relaxed()` - 至少6位,含字母和数字
- **Phone**: 中国手机号验证
- **IdCard**: 中国身份证验证
- **ImageBase64**: Base64 图片格式验证
- **JsonString**: JSON 字符串验证
- **DateRange**: 日期范围验证

**FormRequest 验证类** (app/Http/Requests/):
- 使用 `BaseFormRequest` 基类自动处理验证失败
- 验证失败返回统一的 JSON 响应格式

## 开发规范

### 代码组织原则

1. **单一文件不超过 500 行**: 超过时按功能拆分
2. **控制器精简化**: 业务逻辑放在 Service 层(app/Services/)
3. **使用辅助函数**: 优先使用 functions.php 中的辅助函数而非重复代码
4. **统一异常处理**: 使用 `throw_*_exception()` 系列函数,由全局处理器统一处理
5. **队列化耗时操作**: 超过 2 秒的操作应使用队列异步处理

### 命名约定

- **控制器**: `{ResourceName}Controller` (例: UserController)
- **模型**: 单数形式 (例: User, Product)
- **数据库表**: 复数形式、蛇形命名 (例: users, product_categories)
- **路由名称**: 点分隔 (例: auth.login, users.index)
- **队列任务**: `{Action}{Resource}Job` (例: SendEmailJob, ProcessOrderJob)

### 安全要求

1. **密码策略**: 使用 `StrongPassword` 规则验证密码强度
2. **日志脱敏**: 敏感数据会自动脱敏,但仍需避免记录明文密码
3. **JWT 黑名单**: 登出后 token 立即失效
4. **SQL 注入防护**: 始终使用 Eloquent ORM 或参数绑定
5. **XSS 防护**: API 响应已自动转义,前端需额外处理
6. **CSRF**: API 使用 JWT,无需 CSRF token

### 性能优化指南

1. **N+1 查询优化**: 使用 `with()` 预加载关联
2. **慢查询监控**: 超过阈值(默认 100ms)的查询会自动记录
3. **批量缓存操作**: 使用 `cache_service()->setMultiple()` 等批量方法
4. **队列监控**: 使用 `php artisan queue:monitor` 监控队列积压
5. **生产环境**: 必须执行配置缓存、路由缓存等优化命令

### 测试要求

1. **测试目录结构**:
   - `tests/Unit/`: 单元测试(测试单个类/方法)
   - `tests/Feature/`: 功能测试(测试完整功能流程)

2. **测试覆盖**: 新功能必须编写测试,重要业务逻辑测试覆盖率应达 80%+

3. **测试环境**: phpunit.xml 已配置测试环境(内存数据库、同步队列等)

## 环境变量配置

**关键环境变量** (.env):

```env
# 应用
APP_ENV=local|production        # 环境(影响异常信息详细度)
APP_DEBUG=true|false            # 调试模式
APP_URL=http://localhost        # 应用 URL

# 数据库
DB_CONNECTION=sqlite|mysql      # 默认 SQLite
DB_DATABASE=                    # SQLite 使用 database/database.sqlite

# Redis
REDIS_HOST=127.0.0.1           # Redis 主机
REDIS_PORT=6379                # Redis 端口
CACHE_STORE=redis              # 缓存驱动

# 队列
QUEUE_CONNECTION=redis|rabbitmq # 队列驱动(默认 redis)

# JWT
JWT_SECRET=                     # JWT 密钥(运行 php artisan jwt:secret 生成)
JWT_TTL=60                      # Token 有效期(分钟)
```

## 常见问题

### 1. 首次安装后如何配置?
```bash
composer setup          # 自动完成大部分配置
php artisan jwt:secret  # 生成 JWT 密钥
```

### 2. 如何切换队列驱动?
修改 `.env` 中的 `QUEUE_CONNECTION`:
- `redis` - 使用 Redis(推荐,配置简单)
- `rabbitmq` - 使用 RabbitMQ(需额外配置 RABBITMQ_* 环境变量)

### 3. 如何查看实时日志?
```bash
php artisan pail              # 实时日志查看(推荐)
# 或
tail -f storage/logs/laravel.log
```

### 4. 如何监控队列?
```bash
php artisan horizon           # 启动 Horizon(仅 Redis 队列)
# 访问 http://localhost:8000/horizon 查看仪表盘
```

### 5. 测试时如何模拟认证用户?
```php
$user = User::factory()->create();
$token = auth()->login($user);
$response = $this->withHeader('Authorization', 'Bearer ' . $token)
    ->getJson('/api/v1/auth/me');
```

## 参考资料

- **示例代码**: app/Http/Controllers/Api/ExampleController.php (展示所有功能用法)
- **README**: README.md (详细的功能文档和使用示例)
- **辅助函数**: app/Helpers/functions.php (所有可用的辅助函数)
- **路由定义**: routes/api.php (完整的路由结构)
