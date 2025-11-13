# Laravel API 框架骨架 🚀

> 生产级、开箱即用的 Laravel 12 API 项目骨架

一个经过深度优化、安全可靠、高性能的 Laravel 框架起始模板，适合快速启动新项目。

## ✨ 核心特性

- ✅ **全局异常处理** - 统一的异常处理和错误响应
- ✅ **JWT 认证** - 完整的用户认证系统
- ✅ **高性能缓存** - Redis Pipeline 优化，防穿透和雪崩
- ✅ **队列抽象层** - 支持 Redis 和 RabbitMQ
- ✅ **日志管理** - 多频道日志，自动脱敏
- ✅ **HTTP 客户端** - 优化的 GuzzleHttp 封装
- ✅ **数据验证** - 增强的验证规则（含密码强度验证）
- ✅ **性能监控** - 慢查询检测、队列监控

## 🚀 快速开始

### 1. 环境要求

- PHP >= 8.2
- Composer
- Redis
- MySQL/PostgreSQL/SQLite

### 2. 安装步骤

```bash
# 1. 克隆或下载项目
git clone <your-repo-url>
cd example-app

# 2. 安装依赖
composer install

# 3. 环境配置
cp .env.example .env
php artisan key:generate
php artisan jwt:secret

# 4. 配置数据库（编辑 .env 文件）
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# 5. 运行迁移
php artisan migrate

# 6. 启动服务
php artisan serve

# 7. 启动队列（可选）
php artisan horizon
# 或
php artisan queue:work
```

### 3. 测试接口

访问示例接口测试是否正常运行：

```bash
# 健康检查
curl http://localhost:8000/api/v1/health

# 示例接口
curl http://localhost:8000/api/v1/examples
```

## 📖 功能使用

### 1. JWT 认证

```bash
# 注册
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "Password123",
    "password_confirmation": "Password123"
  }'

# 登录
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Password123"
  }'

# 获取用户信息（需要 Token）
curl http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN"

# 刷新 Token
curl -X POST http://localhost:8000/api/v1/auth/refresh \
  -H "Authorization: Bearer YOUR_TOKEN"

# 登出
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 2. 缓存服务

```php
// 基础操作
cache_set('key', 'value', 3600);
$value = cache_get('key');
cache_delete('key');

// Remember 模式
$user = cache_remember("user:{$id}", function () use ($id) {
    return User::findOrFail($id);
}, 3600);

// 防缓存穿透
$data = cache_service()->rememberSafe("product:{$id}", function () use ($id) {
    return Product::find($id); // 可能返回 null
}, 3600);

// 批量操作（优化版，性能提升 70%+）
$data = [
    'key1' => 'value1',
    'key2' => 'value2',
    'key3' => 'value3',
];
cache_service()->setMultiple($data, 3600);
$cached = cache_service()->getMultiple(['key1', 'key2', 'key3']);

// 防缓存雪崩（自动添加随机过期时间）
cache_service()->setWithJitter('key', 'value', 3600);
```

### 3. 队列服务

```php
// 推送消息
queue_push(['type' => 'email', 'to' => 'user@example.com'], 'emails', 'redis');

// 延迟推送（60秒后）
queue_later(['type' => 'notification'], 60, 'notifications', 'redis');

// 批量推送
$messages = [
    ['id' => 1, 'data' => 'value1'],
    ['id' => 2, 'data' => 'value2'],
];
queue_bulk($messages, 'bulk_queue', 'redis');

// 获取队列大小
$size = queue_size('emails', 'redis');

// 监控队列（命令行）
php artisan queue:monitor --threshold=1000 --queues=default,emails
```

### 4. 日志服务

```php
// 不同级别的日志
logger_info('操作成功', ['user_id' => 1], 'business');
logger_warning('警告信息', ['action' => 'risky'], 'api');
logger_error('错误信息', ['error' => $e->getMessage()], 'exception');

// 业务日志
logger_business('用户操作', [
    'action' => 'create_order',
    'user_id' => $userId,
]);

// 性能日志
logger_performance('API Request', 1200, [
    'url' => '/api/users',
    'method' => 'GET',
]);

// 异常日志
try {
    // ...
} catch (\Exception $e) {
    logger_exception($e, ['context' => 'additional info']);
}
```

### 5. HTTP 客户端

```php
$httpService = app(\App\Services\HttpService::class);

// GET 请求
$response = $httpService->get('https://api.example.com/users', [
    'page' => 1,
    'limit' => 10,
]);

// POST 请求
$response = $httpService->post('https://api.example.com/users', [
    'name' => 'John',
    'email' => 'john@example.com',
]);

// PUT 请求
$response = $httpService->put('https://api.example.com/users/1', [
    'name' => 'John Updated',
]);

// 文件上传（已修复资源泄漏）
$response = $httpService->upload('https://api.example.com/upload', [
    'file' => '/path/to/file.pdf',
], [
    'description' => 'File description',
]);
```

### 6. 数据验证

```php
use App\Rules\StrongPassword;
use App\Rules\Phone;
use App\Rules\IdCard;

$request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
    'phone' => ['required', new Phone()],
    'id_card' => ['required', new IdCard()],
    'password' => ['required', 'string', 'confirmed', StrongPassword::default()],
]);

// 可用的自定义规则：
// - Phone: 手机号验证
// - IdCard: 身份证验证
// - StrongPassword: 强密码验证（支持 default/strong/relaxed 三种模式）
// - ImageBase64: Base64图片验证
// - JsonString: JSON字符串验证
// - DateRange: 日期范围验证
```

### 7. 异常处理

```php
// 业务异常
throw_business_exception('操作失败', 400);

// 资源未找到
throw_not_found_exception('用户不存在');

// 未授权
throw_unauthorized_exception('请先登录');

// 禁止访问
throw_forbidden_exception('无权访问该资源');

// 验证失败
throw_validation_exception('验证失败', $validator);

// 所有异常都会被全局处理器捕获并返回统一格式的 JSON 响应
```

### 8. 辅助函数

```php
// 字符串处理
mask_phone('13800138000');          // 138****8000
mask_email('user@example.com');     // u***r@example.com
generate_token(32);                 // 生成随机 token

// 数组处理
array_get(['user' => ['name' => 'John']], 'user.name');  // 'John'

// 验证
is_valid_email('test@example.com'); // true
is_valid_phone('13800138000');      // true
is_valid_url('https://example.com'); // true

// 格式化
format_bytes(1024 * 1024 * 5);      // '5 MB'

// IP 获取
get_client_ip();                     // 获取客户端真实 IP

// 数组转树形结构
array_to_tree($items, 'id', 'parent_id', 'children');
```

## 🎯 API 示例接口

框架提供了完整的示例接口供参考：

```bash
# 1. 基础示例
GET /api/v1/examples

# 2. HTTP 客户端示例
GET /api/v1/examples/http

# 3. 缓存服务示例
GET /api/v1/examples/cache

# 4. 队列服务示例
GET /api/v1/examples/queue

# 5. 日志服务示例
GET /api/v1/examples/log

# 6. 数据验证示例
POST /api/v1/examples/validation

# 7. 异常处理示例
GET /api/v1/examples/exception?type=business

# 8. 辅助函数示例
GET /api/v1/examples/helper

# 9. 综合示例（完整业务流程）
POST /api/v1/examples/full
```

查看 `app/Http/Controllers/Api/ExampleController.php` 了解详细实现。

## 📂 项目结构

```
example-app/
├── app/
│   ├── Console/Commands/      # Artisan 命令
│   ├── Exceptions/            # 异常类（含全局处理器）
│   ├── Helpers/               # 全局辅助函数
│   ├── Http/
│   │   ├── Controllers/       # 控制器
│   │   ├── Middleware/        # 中间件
│   │   ├── Requests/          # FormRequest 验证类
│   │   └── Traits/            # Trait
│   ├── Models/                # Eloquent 模型
│   ├── Queue/                 # 队列抽象层
│   ├── Rules/                 # 自定义验证规则
│   └── Services/              # 业务服务类
├── config/                    # 配置文件
├── database/                  # 数据库迁移和种子
├── doc/                       # 详细文档
├── routes/                    # 路由定义
├── tests/                     # 测试文件
└── README.md                  # 本文件
```

## ⚙️ 环境配置

主要环境变量配置（`.env` 文件）：

```env
# 应用配置
APP_NAME=YourApp
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# 数据库配置
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Redis 配置
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CACHE_DB=1
REDIS_QUEUE_DB=2

# 队列配置
QUEUE_CONNECTION=redis
QUEUE_DRIVER=redis

# JWT 配置
JWT_SECRET=your-secret-key
JWT_TTL=60

# 性能监控配置
DB_SLOW_QUERY_THRESHOLD=100
DB_QUERY_COUNT_THRESHOLD=20

# 日志配置
LOG_CHANNEL=stack
LOG_LEVEL=debug
LOG_DAILY_DAYS=14
```

## 🔧 常用命令

```bash
# 开发
php artisan serve                  # 启动开发服务器
php artisan queue:work            # 启动队列处理器
php artisan horizon               # 启动 Horizon（推荐）
php artisan queue:monitor         # 监控队列

# 缓存管理
php artisan cache:clear           # 清除缓存
php artisan config:cache          # 缓存配置
php artisan route:cache           # 缓存路由

# 数据库
php artisan migrate               # 运行迁移
php artisan migrate:fresh --seed  # 重置数据库并填充数据
php artisan db:seed               # 填充数据

# 测试
php artisan test                  # 运行所有测试
php artisan test --coverage       # 生成覆盖率报告

# 代码质量
./vendor/bin/pint                 # 格式化代码（Laravel Pint）
```

## 📊 性能优化

### 已优化项

- ✅ **批量缓存操作** - 使用 Redis Pipeline，性能提升 70%+
- ✅ **防缓存穿透** - `rememberSafe()` 方法缓存 null 值
- ✅ **防缓存雪崩** - `setWithJitter()` 添加随机过期时间
- ✅ **资源泄漏修复** - HttpService 文件上传、RabbitMQ 消费者
- ✅ **慢查询监控** - 自动检测超过阈值的查询
- ✅ **队列监控** - 实时监控队列积压

### 性能指标

| 操作 | 优化前 | 优化后 | 提升 |
|-----|-------|-------|------|
| 批量缓存设置 (1000条) | ~500ms | ~150ms | **70%** ⬆️ |
| 批量缓存获取 (1000条) | ~300ms | ~80ms | **73%** ⬆️ |
| 批量缓存删除 (1000条) | ~400ms | ~100ms | **75%** ⬆️ |

## 🔒 安全特性

- ✅ **全局异常处理器** - 生产环境自动隐藏敏感信息
- ✅ **密码强度验证** - 防止弱密码
- ✅ **日志自动脱敏** - 敏感数据（password、token等）自动隐藏
- ✅ **JWT 黑名单** - 登出后 token 立即失效
- ✅ **资源泄漏修复** - 防止文件描述符和连接泄漏

## 🚀 部署

### 生产环境准备

```bash
# 1. 环境变量
APP_ENV=production
APP_DEBUG=false

# 2. 优化性能
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 3. 设置权限
chmod -R 755 storage bootstrap/cache
```

### 使用 Supervisor 管理队列

```bash
# 1. 复制配置文件
sudo cp supervisor/horizon.conf.example /etc/supervisor/conf.d/horizon.conf

# 2. 编辑配置（修改路径和用户）
sudo nano /etc/supervisor/conf.d/horizon.conf

# 3. 重新加载配置
sudo supervisorctl reread
sudo supervisorctl update

# 4. 启动
sudo supervisorctl start horizon
```

## 🧪 测试

```bash
# 运行所有测试
php artisan test

# 运行单元测试
php artisan test --testsuite=Unit

# 运行功能测试
php artisan test --testsuite=Feature

# 生成覆盖率报告
php artisan test --coverage
```

## 💡 开发建议

1. **控制器** - 保持精简，业务逻辑放在 Service 层
2. **服务类** - 在 `app/Services/` 创建服务类封装业务逻辑
3. **队列任务** - 耗时操作使用队列异步处理
4. **缓存策略** - 合理使用缓存，注意缓存失效
5. **日志记录** - 重要操作记录日志，便于排查问题
6. **异常处理** - 使用自定义异常，由全局处理器统一处理
7. **代码规范** - 使用 Laravel Pint 格式化代码

## 🤝 参与贡献

欢迎提交 Issue 和 Pull Request！

## 📄 开源协议

[MIT License](LICENSE)

## 🙏 致谢

- [Laravel](https://laravel.com)
- [JWT Auth](https://github.com/tymondesigns/jwt-auth)
- [GuzzleHttp](https://docs.guzzlephp.org/)
- [Laravel Horizon](https://laravel.com/docs/horizon)

---

**Made with ❤️ for the Laravel community**
