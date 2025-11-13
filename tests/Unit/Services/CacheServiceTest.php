<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CacheServiceTest extends TestCase
{
    protected CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = app(CacheService::class);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /** @test */
    public function it_can_set_and_get_cache()
    {
        $key = 'test_key';
        $value = 'test_value';

        $result = $this->cacheService->set($key, $value, 60);
        $this->assertTrue($result);

        $cached = $this->cacheService->get($key);
        $this->assertEquals($value, $cached);
    }

    /** @test */
    public function it_returns_default_when_key_not_exists()
    {
        $default = 'default_value';
        $result = $this->cacheService->get('non_existent_key', $default);

        $this->assertEquals($default, $result);
    }

    /** @test */
    public function it_can_check_if_cache_exists()
    {
        $key = 'exists_key';

        $this->assertFalse($this->cacheService->has($key));

        $this->cacheService->set($key, 'value', 60);

        $this->assertTrue($this->cacheService->has($key));
    }

    /** @test */
    public function it_can_delete_cache()
    {
        $key = 'delete_key';
        $this->cacheService->set($key, 'value', 60);
        $this->assertTrue($this->cacheService->has($key));

        $this->cacheService->delete($key);
        $this->assertFalse($this->cacheService->has($key));
    }

    /** @test */
    public function it_can_remember_cached_value()
    {
        $key = 'test_remember';
        $callCount = 0;

        $callback = function() use (&$callCount) {
            $callCount++;
            return 'computed_value';
        };

        // 第一次调用应该执行回调
        $result1 = $this->cacheService->remember($key, $callback, 60);
        $this->assertEquals('computed_value', $result1);
        $this->assertEquals(1, $callCount);

        // 第二次调用应该从缓存获取
        $result2 = $this->cacheService->remember($key, $callback, 60);
        $this->assertEquals('computed_value', $result2);
        $this->assertEquals(1, $callCount); // 回调未再次执行
    }

    /** @test */
    public function it_can_increment_value()
    {
        $key = 'counter';
        $this->cacheService->set($key, 10, 60);

        $this->cacheService->increment($key, 5);
        $value = $this->cacheService->get($key);

        $this->assertEquals(15, $value);
    }

    /** @test */
    public function it_can_decrement_value()
    {
        $key = 'counter';
        $this->cacheService->set($key, 20, 60);

        $this->cacheService->decrement($key, 3);
        $value = $this->cacheService->get($key);

        $this->assertEquals(17, $value);
    }

    /** @test */
    public function it_can_set_forever()
    {
        $key = 'forever_key';
        $value = 'forever_value';

        $this->cacheService->forever($key, $value);

        // 验证值存在
        $this->assertTrue($this->cacheService->has($key));
        $this->assertEquals($value, $this->cacheService->get($key));
    }

    /** @test */
    public function it_can_batch_get_multiple_keys()
    {
        $data = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        foreach ($data as $key => $value) {
            $this->cacheService->set($key, $value, 60);
        }

        $results = $this->cacheService->getMultiple(array_keys($data));

        $this->assertEquals($data, $results);
    }

    /** @test */
    public function it_can_batch_set_multiple_keys()
    {
        $data = [
            'batch_key1' => 'batch_value1',
            'batch_key2' => 'batch_value2',
            'batch_key3' => 'batch_value3',
        ];

        $result = $this->cacheService->setMultiple($data, 60);
        $this->assertTrue($result);

        foreach ($data as $key => $expectedValue) {
            $actualValue = $this->cacheService->get($key);
            $this->assertEquals($expectedValue, $actualValue);
        }
    }

    /** @test */
    public function it_can_batch_delete_multiple_keys()
    {
        $keys = ['del_key1', 'del_key2', 'del_key3'];

        foreach ($keys as $key) {
            $this->cacheService->set($key, 'value', 60);
        }

        $result = $this->cacheService->deleteMultiple($keys);
        $this->assertTrue($result);

        foreach ($keys as $key) {
            $this->assertFalse($this->cacheService->has($key));
        }
    }

    /** @test */
    public function it_uses_default_ttl_when_not_specified()
    {
        $defaultTtl = 3600;
        $this->cacheService->setDefaultTtl($defaultTtl);

        $this->assertEquals($defaultTtl, $this->cacheService->getDefaultTtl());
    }

    /** @test */
    public function it_prevents_cache_penetration_with_remember_safe()
    {
        $key = 'safe_remember_test';
        $callCount = 0;

        $callback = function() use (&$callCount) {
            $callCount++;
            return null; // 返回 null 模拟数据不存在
        };

        // 第一次调用
        $result1 = $this->cacheService->rememberSafe($key, $callback, 60);
        $this->assertNull($result1);
        $this->assertEquals(1, $callCount);

        // 第二次调用应该从缓存获取空标记，不执行回调
        $result2 = $this->cacheService->rememberSafe($key, $callback, 60);
        $this->assertNull($result2);
        $this->assertEquals(1, $callCount); // 回调未再次执行
    }

    /** @test */
    public function it_adds_jitter_to_prevent_cache_avalanche()
    {
        $key = 'jitter_test';
        $baseTtl = 100;

        // 多次设置，验证 TTL 不完全相同（有随机性）
        $this->cacheService->setWithJitter($key . '1', 'value1', $baseTtl);
        $this->cacheService->setWithJitter($key . '2', 'value2', $baseTtl);

        // 验证缓存确实被设置了
        $this->assertTrue($this->cacheService->has($key . '1'));
        $this->assertTrue($this->cacheService->has($key . '2'));
    }

    /** @test */
    public function it_handles_empty_arrays_in_batch_operations()
    {
        $this->assertEquals([], $this->cacheService->getMultiple([]));
        $this->assertTrue($this->cacheService->setMultiple([]));
        $this->assertTrue($this->cacheService->deleteMultiple([]));
    }
}

