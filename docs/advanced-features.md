# 高级功能指南

## 概述

本指南介绍 webman-filament 扩展的高级功能，包括自定义组件、复杂关系处理、性能优化、插件开发等高级特性。

## 自定义组件开发

### 1. 创建自定义表单组件

```php
<?php
// app/Filament/Components/ColorPicker.php

namespace App\Filament\Components;

use Filament\Support\Components\ViewComponent;
use Filament\Support\Enums\FontWeight;
use Illuminate\View\Component;

class ColorPicker extends ViewComponent
{
    public string $name;
    public ?string $label;
    public ?string $defaultColor;
    public bool $required = false;
    public ?string $helpText;

    public function __construct(
        string $name,
        ?string $label = null,
        ?string $defaultColor = '#000000',
        bool $required = false,
        ?string $helpText = null
    ) {
        $this->name = $name;
        $this->label = $label ?? $name;
        $this->defaultColor = $defaultColor;
        $this->required = $required;
        $this->helpText = $helpText;
    }

    public function render()
    {
        return view('filament.components.color-picker');
    }
}
```

```blade
{{-- resources/views/filament/components/color-picker.blade.php --}}
@props(['name', 'label', 'defaultColor', 'required', 'helpText'])

<div class="space-y-2">
    @if($label)
        <label class="block text-sm font-medium text-gray-700">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    
    <div class="flex items-center space-x-2">
        <input
            type="color"
            name="{{ $name }}"
            value="{{ $defaultColor }}"
            {{ $required ? 'required' : '' }}
            class="h-10 w-20 rounded border border-gray-300"
        />
        <input
            type="text"
            name="{{ $name }}_text"
            value="{{ $defaultColor }}"
            placeholder="#000000"
            class="flex-1 rounded border border-gray-300 px-3 py-2 text-sm"
        />
    </div>
    
    @if($helpText)
        <p class="text-xs text-gray-500">{{ $helpText }}</p>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const colorInput = document.querySelector('input[type="color"][name="{{ $name }}"]');
    const textInput = document.querySelector('input[type="text"][name="{{ $name }}_text"]');
    
    if (colorInput && textInput) {
        colorInput.addEventListener('change', function() {
            textInput.value = this.value;
        });
        
        textInput.addEventListener('input', function() {
            if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                colorInput.value = this.value;
            }
        });
    }
});
</script>
@endpush
```

### 2. 在表单中使用自定义组件

```php
public static function form(Form $form): Form
{
    return $form->schema([
        // ... 其他字段
        
        \App\Filament\Components\ColorPicker::make('primary_color')
            ->label('主色调')
            ->defaultColor('#3B82F6')
            ->required()
            ->helpText('选择网站的主色调'),
        
        \App\Filament\Components\ColorPicker::make('secondary_color')
            ->label('辅助色')
            ->defaultColor('#6B7280'),
    ]);
}
```

### 3. 创建自定义表格列

```php
<?php
// app/Filament/Columns/ProgressBarColumn.php

namespace App\Filament\Columns;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class ProgressBarColumn extends TextColumn
{
    protected string $view = 'filament.columns.progress-bar';
    
    public int $max = 100;
    public string $color = 'primary';
    public bool $showValue = true;

    public function max(int $max): static
    {
        $this->max = $max;
        
        return $this;
    }

    public function color(string $color): static
    {
        $this->color = $color;
        
        return $this;
    }

    public function showValue(bool $showValue = true): static
    {
        $this->showValue = $showValue;
        
        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->formatStateUsing(function (?string $state, Model $record): string {
            if (!$state) {
                return '0';
            }
            
            return (string) $state;
        });
    }
}
```

```blade
{{-- resources/views/filament/columns/progress-bar.blade.php --}}
@props(['column'])

@php
    $state = $column->getState();
    $max = $column->max;
    $percentage = min(100, ($state / $max) * 100);
    $color = $column->color;
    $showValue = $column->showValue;
@endphp

<div class="w-full">
    <div class="flex items-center justify-between mb-1">
        @if($showValue)
            <span class="text-xs font-medium text-gray-700">
                {{ $state }}/{{ $max }}
            </span>
        @endif
        <span class="text-xs text-gray-500">{{ number_format($percentage, 1) }}%</span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2">
        <div 
            class="bg-{{ $color }}-600 h-2 rounded-full transition-all duration-300"
            style="width: {{ $percentage }}%"
        ></div>
    </div>
</div>
```

## 复杂关系处理

### 1. 多对多关系管理

```php
<?php
// app/Filament/Resources/ProductResource/RelationManagers/TagsRelationManager.php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

class TagsRelationManager extends RelationManager
{
    protected static string $relationship = 'tags';
    
    protected static ?string $recordTitleAttribute = 'name';
    
    protected static ?string $title = '标签管理';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('标签名称')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                
                Forms\Components\ColorPicker::make('color')
                    ->label('标签颜色')
                    ->default('#3B82F6'),
                
                Forms\Components\Textarea::make('description')
                    ->label('描述')
                    ->maxLength(500),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('标签名称')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\ColorColumn::make('color')
                    ->label('颜色'),
                
                Tables\Columns\TextColumn::make('products_count')
                    ->label('产品数量')
                    ->counts('products'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make()
                    ->label('关联现有标签')
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make(),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
```

### 2. 嵌套资源管理

```php
<?php
// app/Filament/Resources/CategoryResource.php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;
use App\Models\Category;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    
    protected static ?string $navigationLabel = '分类管理';
    
    protected static ?string $modelLabel = '分类';
    
    protected static ?string $pluralModelLabel = '分类';
    
    protected static ?string $navigationGroup = '内容管理';
    
    // 启用树形结构
    protected static ?string $treeTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('分类名称')
                    ->required()
                    ->maxLength(255)
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state))),
                
                Forms\Components\TextInput::make('slug')
                    ->label('别名')
                    ->required()
                    ->maxLength(255)
                    ->unique(Category::class, 'slug', ignoreRecord: true)
                    ->regex('/^[a-z0-9-]+$/'),
                
                Forms\Components\Select::make('parent_id')
                    ->label('父级分类')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('顶级分类'),
                
                Forms\Components\Textarea::make('description')
                    ->label('描述')
                    ->rows(3),
                
                Forms\Components\TextInput::make('sort_order')
                    ->label('排序')
                    ->numeric()
                    ->default(0),
                
                Forms\Components\Toggle::make('is_active')
                    ->label('启用状态')
                    ->default(true),
                
                Forms\Components\FileUpload::make('image')
                    ->label('分类图片')
                    ->image()
                    ->directory('categories'),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('分类名称')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('父级分类')
                    ->searchable()
                    ->placeholder('顶级分类'),
                
                Tables\Columns\TextColumn::make('children_count')
                    ->label('子分类数')
                    ->counts('children'),
                
                Tables\Columns\TextColumn::make('products_count')
                    ->label('产品数量')
                    ->counts('products'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('状态')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('排序')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('启用状态'),
            ])
            ->actions([
                Tables\Actions\ReorderAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->paginated(false); // 树形结构不需要分页
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ChildrenRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
    
    // 启用树形结构
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ])
            ->orderBy('sort_order');
    }
}
```

### 3. 动态关系处理

```php
<?php
// app/Filament/Resources/UserResource.php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;
use App\Models\User;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 基本信息
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('姓名')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('邮箱')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        
                        Forms\Components\TextInput::make('phone')
                            ->label('电话')
                            ->tel()
                            ->unique(ignoreRecord: true),
                        
                        Forms\Components\Select::make('user_type')
                            ->label('用户类型')
                            ->options([
                                'admin' => '管理员',
                                'editor' => '编辑',
                                'author' => '作者',
                                'subscriber' => '订阅者',
                            ])
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('permissions', [])),
                    ])
                    ->columns(2),
                
                // 动态权限配置
                Forms\Components\Section::make('权限设置')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('权限')
                            ->options(function (callable $get) {
                                $userType = $get('user_type');
                                
                                return match ($userType) {
                                    'admin' => [
                                        'users.create' => '创建用户',
                                        'users.view' => '查看用户',
                                        'users.update' => '更新用户',
                                        'users.delete' => '删除用户',
                                        'posts.create' => '创建文章',
                                        'posts.update' => '更新文章',
                                        'posts.delete' => '删除文章',
                                    ],
                                    'editor' => [
                                        'posts.create' => '创建文章',
                                        'posts.update' => '更新文章',
                                        'posts.delete' => '删除文章',
                                    ],
                                    'author' => [
                                        'posts.create' => '创建文章',
                                        'posts.update' => '更新自己的文章',
                                    ],
                                    default => [],
                                };
                            })
                            ->columns(3)
                            ->gridDirection('row'),
                    ])
                    ->visible(fn (callable $get): bool => !empty($get('user_type'))),
                
                // 用户头像
                Forms\Components\Section::make('头像设置')
                    ->schema([
                        Forms\Components\FileUpload::make('avatar')
                            ->label('头像')
                            ->image()
                            ->avatar()
                            ->directory('avatars')
                            ->maxSize(2048),
                    ]),
            ]);
    }
}
```

## 高级表格功能

### 1. 自定义操作和动作

```php
<?php
// app/Filament/Resources/OrderResource.php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Order;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    
    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                // ... 其他列
            ])
            ->filters([
                // ... 过滤器
            ])
            ->actions([
                // 查看详情
                Action::make('view')
                    ->label('查看')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (Order $record): string => 
                        route('filament.admin.resources.orders.view', $record)
                    ),
                
                // 批量导出
                Action::make('export')
                    ->label('导出')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function ($records) {
                        // 导出逻辑
                        $exportData = $records->map(function ($order) {
                            return [
                                '订单号' => $order->order_number,
                                '客户' => $order->customer_name,
                                '金额' => $order->total_amount,
                                '状态' => $order->status,
                            ];
                        });
                        
                        // 生成 CSV 文件
                        $filename = 'orders_' . now()->format('Y-m-d_H-i-s') . '.csv';
                        $path = storage_path('app/exports/' . $filename);
                        
                        \Illuminate\Support\Facades\Storage::put(
                            'exports/' . $filename,
                            (new \League\Csv\Writer())->insertAll($exportData)->toString()
                        );
                        
                        Notification::make()
                            ->title('导出完成')
                            ->body("文件已保存到: {$filename}")
                            ->success()
                            ->send();
                    }),
                
                // 自定义操作
                Action::make('duplicate')
                    ->label('复制订单')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('复制订单')
                    ->modalDescription('确定要复制这个订单吗？复制后可以修改相关信息。')
                    ->action(function (Order $record) {
                        $newOrder = $record->replicate();
                        $newOrder->order_number = 'ORD-' . now()->format('Ymd') . '-' . str_pad(Order::max('id') + 1, 6, '0', STR_PAD_LEFT);
                        $newOrder->status = 'pending';
                        $newOrder->save();
                        
                        Notification::make()
                            ->title('订单复制成功')
                            ->body("新订单号: {$newOrder->order_number}")
                            ->success()
                            ->send();
                    }),
                
                // 状态更新
                Action::make('updateStatus')
                    ->label('更新状态')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->form([
                        \Filament\Forms\Components\Select::make('status')
                            ->label('订单状态')
                            ->options([
                                'pending' => '待处理',
                                'processing' => '处理中',
                                'shipped' => '已发货',
                                'delivered' => '已送达',
                                'cancelled' => '已取消',
                            ])
                            ->required(),
                        
                        \Filament\Forms\Components\Textarea::make('notes')
                            ->label('备注')
                            ->rows(3),
                    ])
                    ->action(function (array $data, Order $record) {
                        $record->update($data);
                        
                        Notification::make()
                            ->title('订单状态已更新')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                // 批量状态更新
                Tables\Actions\BulkAction::make('bulkStatusUpdate')
                    ->label('批量更新状态')
                    ->icon('heroicon-o-pencil-square')
                    ->form([
                        \Filament\Forms\Components\Select::make('status')
                            ->label('订单状态')
                            ->options([
                                'pending' => '待处理',
                                'processing' => '处理中',
                                'shipped' => '已发货',
                                'delivered' => '已送达',
                                'cancelled' => '已取消',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data, $records) {
                        $records->each->update(['status' => $data['status']]);
                        
                        Notification::make()
                            ->title('批量状态更新完成')
                            ->success()
                            ->send();
                    }),
                
                // 批量导出
                Tables\Actions\BulkAction::make('bulkExport')
                    ->label('批量导出')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function ($records) {
                        // 批量导出逻辑
                    }),
            ]);
    }
}
```

### 2. 高级过滤器

```php
<?php
// app/Filament/Resources/ProductResource.php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use App\Models\Product;

class ProductResource extends Resource
{
    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                // ... 列定义
            ])
            ->filters([
                // 文本搜索过滤器
                Filter::make('search')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('search')
                            ->placeholder('搜索产品名称或SKU...')
                            ->debounce(500),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['search'],
                            fn ($query, $search) => $query->where(function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                      ->orWhere('sku', 'like', "%{$search}%")
                                      ->orWhere('description', 'like', "%{$search}%");
                            })
                        );
                    }),
                
                // 价格范围过滤器
                Filter::make('price_range')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('min_price')
                            ->label('最低价格')
                            ->numeric()
                            ->prefix('¥'),
                        
                        \Filament\Forms\Components\TextInput::make('max_price')
                            ->label('最高价格')
                            ->numeric()
                            ->prefix('¥'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['min_price'],
                            fn ($query, $minPrice) => $query->where('price', '>=', $minPrice)
                        )->when(
                            $data['max_price'],
                            fn ($query, $maxPrice) => $query->where('price', '<=', $maxPrice)
                        );
                    }),
                
                // 日期范围过滤器
                Filter::make('created_date_range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from_date')
                            ->label('开始日期'),
                        
                        \Filament\Forms\Components\DatePicker::make('to_date')
                            ->label('结束日期'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['from_date'],
                            fn ($query, $fromDate) => $query->whereDate('created_at', '>=', $fromDate)
                        )->when(
                            $data['to_date'],
                            fn ($query, $toDate) => $query->whereDate('created_at', '<=', $toDate)
                        );
                    }),
                
                // 分类过滤器
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('产品分类')
                    ->searchable()
                    ->preload(),
                
                // 库存状态过滤器
                SelectFilter::make('stock_status')
                    ->options([
                        'in_stock' => '有库存',
                        'low_stock' => '库存不足',
                        'out_of_stock' => '缺货',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value']) {
                            'in_stock' => $query->where('stock', '>', 10),
                            'low_stock' => $query->whereBetween('stock', [1, 10]),
                            'out_of_stock' => $query->where('stock', '<=', 0),
                            default => $query,
                        };
                    }),
                
                // 高级查询构建器
                QueryBuilder::make()
                    ->constraintPickerColumns(2)
                    ->constraints([
                        TextConstraint::make('name')
                            ->label('产品名称'),
                        
                        TextConstraint::make('sku')
                            ->label('SKU'),
                        
                        NumberConstraint::make('price')
                            ->label('价格'),
                        
                        NumberConstraint::make('stock')
                            ->label('库存'),
                        
                        DateConstraint::make('created_at')
                            ->label('创建时间'),
                    ]),
            ])
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('筛选')
            );
    }
}
```

## 性能优化

### 1. 查询优化

```php
<?php
// app/Filament/Resources/ProductResource.php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    // 优化查询
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select([
                'id',
                'name',
                'sku',
                'price',
                'stock',
                'category_id',
                'status',
                'created_at',
                'updated_at',
            ])
            ->with([
                'category:id,name', // 预加载分类
                'tags:id,name', // 预加载标签
                'images:id,product_id,image_url', // 预加载图片
            ])
            ->when(request()->has('category'), function ($query) {
                return $query->where('category_id', request('category'));
            });
    }
    
    // 优化表格数据
    public static function getTableQuery(): Builder
    {
        return static::getEloquentQuery()
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->addSelect([
                'category_name' => \Illuminate\Database\Query\Expression::raw('categories.name'),
                'total_sales' => \Illuminate\Database\Query\Expression::raw('(SELECT COUNT(*) FROM order_items WHERE order_items.product_id = products.id)'),
            ]);
    }
    
    // 缓存统计数据
    public static function getNavigationBadge(): ?string
    {
        return cache()->remember(
            'products_count_' . auth()->id(),
            3600,
            fn () => static::getModel()::where('user_id', auth()->id())->count()
        );
    }
}
```

### 2. 懒加载和虚拟列

```php
<?php
// app/Filament/Resources/OrderResource.php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class OrderResource extends Resource
{
    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                // 虚拟列 - 计算字段
                Tables\Columns\TextColumn::make('profit_margin')
                    ->label('利润率')
                    ->getStateUsing(function (Model $record) {
                        $profit = $record->total_amount - $record->cost_amount;
                        $margin = $record->total_amount > 0 ? ($profit / $record->total_amount) * 100 : 0;
                        return number_format($margin, 2) . '%';
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->selectRaw('(total_amount - cost_amount) / total_amount * 100 as profit_margin')
                            ->orderByRaw("profit_margin {$direction}");
                    }),
                
                // 懒加载关联
                Tables\Columns\TextColumn::make('customer.email')
                    ->label('客户邮箱')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('邮箱已复制到剪贴板'),
                
                // 自定义格式化
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('订单金额')
                    ->money('CNY')
                    ->sortable(),
                
                // 状态徽章
                Tables\Columns\BadgeColumn::make('status')
                    ->label('状态')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => '待处理',
                        'processing' => '处理中',
                        'shipped' => '已发货',
                        'delivered' => '已送达',
                        'cancelled' => '已取消',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ]);
    }
}
```

### 3. 分页优化

```php
<?php
// app/Filament/Resources/ProductResource.php

namespace App\Filament\Resources;

use Filament\Resources\Resource;

class ProductResource extends Resource
{
    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->poll('30s') // 自动刷新
            ->pollInterval('30s')
            ->searchable(searchPrompt: '搜索产品名称、SKU或描述...')
            ->searchDebounce('500ms')
            ->filtersFormWidth(Full::class)
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('高级筛选')
                    ->icon('heroicon-o-funnel')
            );
    }
}
```

## 插件开发

### 1. 创建自定义插件

```php
<?php
// app/Filament/Plugins/AnalyticsPlugin.php

namespace App\Filament\Plugins;

use Filament\PluginServiceProvider;
use Spatie\LaravelPackageTools\Package;
use App\Filament\Widgets\AnalyticsWidget;
use App\Filament\Resources\AnalyticsResource;

class AnalyticsPlugin extends PluginServiceProvider
{
    protected array $resources = [
        AnalyticsResource::class,
    ];
    
    protected array $widgets = [
        AnalyticsWidget::class,
    ];
    
    public function configurePackage(Package $package): void
    {
        $package
            ->name('analytics')
            ->hasViews()
            ->hasMigrations(['create_analytics_table']);
    }
    
    public function packageBooted(): void
    {
        // 注册自定义指令
        \Illuminate\Support\Facades\Blade::directive('analytics', function ($expression) {
            return "<?php echo app('analytics')->track({$expression}); ?>";
        });
    }
}
```

### 2. 插件配置

```php
<?php
// config/filament.php

return [
    'plugins' => [
        \App\Filament\Plugins\AnalyticsPlugin::class => [
            'enabled' => true,
            'api_key' => env('ANALYTICS_API_KEY'),
            'tracking_id' => env('ANALYTICS_TRACKING_ID'),
        ],
        
        \App\Filament\Plugins\SeoPlugin::class => [
            'enabled' => true,
            'sitemap_enabled' => true,
            'robots_txt_enabled' => true,
        ],
    ],
];
```

## 实时功能

### 1. WebSocket 集成

```php
<?php
// app/Filament/Actions/RealtimeAction.php

namespace App\Filament\Actions;

use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Broadcast;

class RealtimeAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'realtime';
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->label('实时更新')
             ->icon('heroicon-o-arrow-path')
             ->color('info')
             ->action(function () {
                 // 触发实时更新
                 Broadcast::channel('orders')
                         ->whisper('updated', [
                             'order_id' => $this->record->id,
                             'status' => $this->record->status,
                         ]);
                 
                 Notification::make()
                     ->title('实时更新已发送')
                     ->success()
                     ->send();
             });
    }
}
```

### 2. 实时通知

```php
<?php
// app/Filament/Listeners/OrderStatusListener.php

namespace App\Filament\Listeners;

use Illuminate\Support\Facades\Notification;
use App\Notifications\OrderStatusChanged;

class OrderStatusChangedListener
{
    public function handle($event)
    {
        $order = $event->order;
        
        // 发送实时通知
        Notification::send(
            $order->user,
            new OrderStatusChanged($order)
        );
        
        // 广播到 WebSocket 频道
        \Illuminate\Support\Facades\Broadcast::channel('user.' . $order->user_id)
            ->broadcast(new \App\Events\OrderStatusChanged($order));
    }
}
```

## 最佳实践总结

### 1. 性能优化
- 使用 `select()` 减少查询字段
- 合理使用 `with()` 预加载关联
- 启用查询缓存
- 使用索引优化数据库查询

### 2. 代码组织
- 将复杂的业务逻辑分离到服务类
- 使用 traits 复用通用功能
- 保持资源的单一职责原则

### 3. 用户体验
- 提供清晰的操作反馈
- 使用加载状态和进度指示
- 优化移动端体验

### 4. 安全性
- 验证用户权限
- 使用 CSRF 保护
- 限制文件上传类型和大小

## 下一步

- 查看 [API 参考文档](api-reference.md) 了解详细 API
- 阅读 [自定义开发指南](customization.md) 进行深度定制
- 参考 [最佳实践指南](best-practices.md) 了解推荐做法