<?php

namespace App\Adapter;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 模型适配器
 * 提供Eloquent ORM的兼容性和优化功能
 */
class ModelAdapter
{
    /**
     * 缓存配置
     */
    protected array $cacheConfig = [
        'ttl' => 3600, // 1小时
        'prefix' => 'model_adapter_',
    ];

    /**
     * 查询缓存键前缀
     */
    protected string $cachePrefix = 'model_adapter_';

    /**
     * 查找单条记录
     */
    public function find(Model $model, mixed $id, array $columns = ['*']): ?Model
    {
        try {
            return $model->find($id, $columns);
        } catch (\Exception $e) {
            Log::error('Model find error', [
                'model' => get_class($model),
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 通过字段查找记录
     */
    public function findBy(Model $model, string $field, mixed $value, array $columns = ['*']): ?Model
    {
        try {
            return $model->where($field, $value)->first($columns);
        } catch (\Exception $e) {
            Log::error('Model findBy error', [
                'model' => get_class($model),
                'field' => $field,
                'value' => $value,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 查找多条记录
     */
    public function findMany(Model $model, array $ids, array $columns = ['*']): Collection
    {
        try {
            return $model->findMany($ids, $columns);
        } catch (\Exception $e) {
            Log::error('Model findMany error', [
                'model' => get_class($model),
                'ids' => $ids,
                'error' => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * 查找或失败
     */
    public function findOrFail(Model $model, mixed $id, array $columns = ['*']): Model
    {
        try {
            return $model->findOrFail($id, $columns);
        } catch (ModelNotFoundException $e) {
            Log::warning('Model not found', [
                'model' => get_class($model),
                'id' => $id,
            ]);
            throw $e;
        }
    }

    /**
     * 查找或创建
     */
    public function firstOrCreate(Model $model, array $attributes, array $values = []): Model
    {
        try {
            return $model->firstOrCreate($attributes, $values);
        } catch (\Exception $e) {
            Log::error('Model firstOrCreate error', [
                'model' => get_class($model),
                'attributes' => $attributes,
                'values' => $values,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 更新或创建
     */
    public function updateOrCreate(Model $model, array $attributes, array $values = []): Model
    {
        try {
            return $model->updateOrCreate($attributes, $values);
        } catch (\Exception $e) {
            Log::error('Model updateOrCreate error', [
                'model' => get_class($model),
                'attributes' => $attributes,
                'values' => $values,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 分页查询
     */
    public function paginate(Model $model, int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        try {
            return $model->paginate($perPage, $columns, $pageName, $page);
        } catch (\Exception $e) {
            Log::error('Model paginate error', [
                'model' => get_class($model),
                'perPage' => $perPage,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 简单分页
     */
    public function simplePaginate(Model $model, int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null): \Illuminate\Contracts\Pagination\Paginator
    {
        try {
            return $model->simplePaginate($perPage, $columns, $pageName, $page);
        } catch (\Exception $e) {
            Log::error('Model simplePaginate error', [
                'model' => get_class($model),
                'perPage' => $perPage,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 缓存查询
     */
    public function cachedQuery(Model $model, \Closure $callback, ?string $cacheKey = null, ?int $ttl = null): mixed
    {
        $cacheKey = $cacheKey ?: $this->generateCacheKey($model);
        $ttl = $ttl ?: $this->cacheConfig['ttl'];

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * 批量插入
     */
    public function insertBatch(Model $model, array $data, int $chunkSize = 1000): bool
    {
        try {
            $chunks = array_chunk($data, $chunkSize);
            
            foreach ($chunks as $chunk) {
                $model->insert($chunk);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Model insertBatch error', [
                'model' => get_class($model),
                'chunkSize' => $chunkSize,
                'dataCount' => count($data),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 批量更新
     */
    public function updateBatch(Model $model, array $data, string $key = 'id'): bool
    {
        try {
            $ids = array_column($data, $key);
            $instances = $model->whereIn($key, $ids)->get();
            
            foreach ($instances as $instance) {
                $updateData = collect($data)->firstWhere($key, $instance->$key);
                if ($updateData) {
                    $instance->update($updateData);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Model updateBatch error', [
                'model' => get_class($model),
                'key' => $key,
                'dataCount' => count($data),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 软删除相关方法
     */
    public function withTrashed(Model $model): \Illuminate\Database\Eloquent\Builder
    {
        return $model->withTrashed();
    }

    public function onlyTrashed(Model $model): \Illuminate\Database\Eloquent\Builder
    {
        return $model->onlyTrashed();
    }

    public function restore(Model $model, mixed $id): bool
    {
        try {
            return $model->withTrashed()->find($id)?->restore() ?? false;
        } catch (\Exception $e) {
            Log::error('Model restore error', [
                'model' => get_class($model),
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function forceDelete(Model $model, mixed $id): bool
    {
        try {
            return $model->withTrashed()->find($id)?->forceDelete() ?? false;
        } catch (\Exception $e) {
            Log::error('Model forceDelete error', [
                'model' => get_class($model),
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 关系查询
     */
    public function withRelation(Model $model, array $relations): \Illuminate\Database\EloquentBuilder
    {
        return $model->with($relations);
    }

    public function hasRelation(Model $model, string $relation, string $operator = '>=', int $count = 1): \Illuminate\Database\Eloquent_builder
    {
        return $model->has($relation, $operator, $count);
    }

    public function whereHasRelation(Model $model, string $relation, \Closure $callback = null): \Illuminate\Database\Eloquent_builder
    {
        return $model->whereHas($relation, $callback);
    }

    /**
     * 统计查询
     */
    public function count(Model $model, string $column = '*'): int
    {
        try {
            return $model->count($column);
        } catch (\Exception $e) {
            Log::error('Model count error', [
                'model' => get_class($model),
                'column' => $column,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    public function sum(Model $model, string $column): float
    {
        try {
            return $model->sum($column);
        } catch (\Exception $e) {
            Log::error('Model sum error', [
                'model' => get_class($model),
                'column' => $column,
                'error' => $e->getMessage(),
            ]);
            return 0.0;
        }
    }

    public function avg(Model $model, string $column): float
    {
        try {
            return $model->avg($column);
        } catch (\Exception $e) {
            Log::error('Model avg error', [
                'model' => get_class($model),
                'column' => $column,
                'error' => $e->getMessage(),
            ]);
            return 0.0;
        }
    }

    public function max(Model $model, string $column): mixed
    {
        try {
            return $model->max($column);
        } catch (\Exception $e) {
            Log::error('Model max error', [
                'model' => get_class($model),
                'column' => $column,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function min(Model $model, string $column): mixed
    {
        try {
            return $model->min($column);
        } catch (\Exception $e) {
            Log::error('Model min error', [
                'model' => get_class($model),
                'column' => $column,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 清空模型缓存
     */
    public function clearCache(Model $model): void
    {
        $cacheKey = $this->generateCacheKey($model);
        Cache::forget($cacheKey);
    }

    /**
     * 生成缓存键
     */
    protected function generateCacheKey(Model $model, ?string $suffix = null): string
    {
        $class = get_class($model);
        $key = $this->cachePrefix . str_replace('\\', '_', $class);
        
        if ($suffix) {
            $key .= '_' . $suffix;
        }
        
        return $key;
    }

    /**
     * 获取模型信息
     */
    public function getModelInfo(Model $model): array
    {
        return [
            'class' => get_class($model),
            'table' => $model->getTable(),
            'primaryKey' => $model->getKeyName(),
            'fillable' => $model->getFillable(),
            'guarded' => $model->getGuarded(),
            'timestamps' => $model->timestamps,
            'softDeletes' => $model->usesSoftDeletes(),
            'connection' => $model->getConnectionName(),
        ];
    }

    /**
     * 验证模型数据
     */
    public function validateModelData(Model $model, array $data): array
    {
        $errors = [];
        $fillable = $model->getFillable();
        $guarded = $model->getGuarded();

        foreach ($data as $key => $value) {
            // 检查是否为可填充字段
            if (!empty($fillable) && !in_array($key, $fillable)) {
                $errors[$key] = "Field '{$key}' is not fillable";
                continue;
            }

            // 检查是否为受保护字段
            if (!empty($guarded) && in_array($key, $guarded)) {
                $errors[$key] = "Field '{$key}' is guarded";
                continue;
            }
        }

        return $errors;
    }
}