<?php
// examples/advanced/app/Filament/Resources/ArticleResource.php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\ImageEntry;
use Filament\Notifications\Notification;
use App\Models\Article;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = '文章管理';
    
    protected static ?string $modelLabel = '文章';
    
    protected static ?string $pluralModelLabel = '文章';
    
    protected static ?string $navigationGroup = '内容管理';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('文章标题')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, Set $set) => 
                                $operation === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null
                            )
                            ->helperText('输入文章标题，系统会自动生成别名'),
                        
                        Forms\Components\TextInput::make('slug')
                            ->label('文章别名')
                            ->required()
                            ->maxLength(255)
                            ->unique(Article::class, 'slug', ignoreRecord: true)
                            ->regex('/^[a-z0-9-]+$/')
                            ->helperText('用于URL的唯一标识符，只能包含小写字母、数字和连字符'),
                        
                        Forms\Components\Select::make('category_id')
                            ->label('文章分类')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('分类名称')
                                    ->required()
                                    ->maxLength(255),
                                
                                Forms\Components\Textarea::make('description')
                                    ->label('分类描述')
                                    ->rows(3),
                                
                                Forms\Components\TextInput::make('sort_order')
                                    ->label('排序')
                                    ->numeric()
                                    ->default(0),
                            ]),
                        
                        Forms\Components\Select::make('author_id')
                            ->label('文章作者')
                            ->relationship('author', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => auth()->id())
                            ->helperText('选择文章作者'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('内容设置')
                    ->schema([
                        Forms\Components\RichEditor::make('content')
                            ->label('文章内容')
                            ->required()
                            ->columnSpanFull()
                            ->helperText('支持富文本编辑，可以插入图片、链接等')
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ]),
                        
                        Forms\Components\Textarea::make('excerpt')
                            ->label('文章摘要')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('文章摘要将用于文章列表页显示，留空则自动生成'),
                    ])
                    ->columns(1),
                
                Forms\Components\Section::make('媒体设置')
                    ->schema([
                        Forms\Components\FileUpload::make('featured_image')
                            ->label('特色图片')
                            ->image()
                            ->directory('articles/featured')
                            ->maxSize(5120) // 5MB
                            ->helperText('支持 JPG、PNG 格式，最大 5MB'),
                    ]),
                
                Forms\Components\Section::make('发布设置')
                    ->schema([
                        Forms\Components\Toggle::make('status')
                            ->label('发布状态')
                            ->required()
                            ->default(false)
                            ->helperText('开启后文章将对外可见'),
                        
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('发布时间')
                            ->native(false)
                            ->helperText('留空则立即发布'),
                        
                        Forms\Components\TextInput::make('reading_time')
                            ->label('预计阅读时间（分钟）')
                            ->numeric()
                            ->helperText('系统会根据内容自动计算，也可以手动设置'),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('标签设置')
                    ->schema([
                        Forms\Components\CheckboxList::make('tags')
                            ->relationship('tags', 'name')
                            ->columns(3)
                            ->gridDirection('row')
                            ->helperText('选择相关标签'),
                        
                        Forms\Components\TextInput::make('custom_tags')
                            ->label('自定义标签')
                            ->helperText('输入自定义标签，用逗号分隔')
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $tags = array_map('trim', explode(',', $state));
                                    $set('tags', $tags);
                                }
                            }),
                    ]),
                
                Forms\Components\Section::make('SEO 设置')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->label('SEO 标题')
                            ->maxLength(60)
                            ->helperText('用于搜索引擎的标题，建议 60 字符以内'),
                        
                        Forms\Components\Textarea::make('meta_description')
                            ->label('SEO 描述')
                            ->rows(2)
                            ->maxLength(160)
                            ->helperText('用于搜索引擎的描述，建议 160 字符以内'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ])
            ->statePath('data');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->label('特色图')
                    ->square()
                    ->size(60),
                
                Tables\Columns\TextColumn::make('title')
                    ->label('标题')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight(FontWeight::Bold)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),
                
                Tables\Columns\TextColumn::make('category.name')
                    ->label('分类')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('author.name')
                    ->label('作者')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status_name')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '已发布' => 'success',
                        '草稿' => 'gray',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('view_count')
                    ->label('浏览量')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => number_format($state)),
                
                Tables\Columns\TextColumn::make('reading_time_minutes')
                    ->label('阅读时间')
                    ->suffix('分钟')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('published_at')
                    ->label('发布时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('发布状态')
                    ->options([
                        '1' => '已发布',
                        '0' => '草稿',
                    ]),
                
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('文章分类')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('author')
                    ->relationship('author', 'name')
                    ->label('文章作者')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\TernaryFilter::make('has_featured_image')
                    ->label('有特色图'),
                
                Tables\Filters\Filter::make('published_recently')
                    ->label('最近发布')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('status', true)
                              ->where('published_at', '>=', now()->subDays(7))
                    )
                    ->toggle(),
                
                Tables\Filters\Filter::make('popular')
                    ->label('热门文章')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('status', true)
                              ->where('view_count', '>=', 100)
                    )
                    ->toggle(),
                
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('published_from')
                            ->label('发布日期从'),
                        Forms\Components\DatePicker::make('published_until')
                            ->label('发布日期到'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['published_from'],
                                fn (Builder $query, $date): Builder => 
                                    $query->whereDate('published_at', '>=', $date),
                            )
                            ->when(
                                $data['published_until'],
                                fn (Builder $query, $date): Builder => 
                                    $query->whereDate('published_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('查看')
                    ->color('info'),
                
                Tables\Actions\EditAction::make()
                    ->label('编辑'),
                
                Tables\Actions\Action::make('duplicate')
                    ->label('复制')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('复制文章')
                    ->modalDescription('确定要复制这篇文章吗？复制后可以修改相关信息。')
                    ->action(function (Article $record) {
                        $newArticle = $record->duplicate();
                        
                        Notification::make()
                            ->title('文章复制成功')
                            ->body("新文章标题: {$newArticle->title}")
                            ->success()
                            ->send();
                    }),
                
                Tables\Actions\Action::make('view_public')
                    ->label('查看前台')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->url(fn (Article $record): string => 
                        route('articles.show', $record->slug)
                    )
                    ->openUrlInNewTab(),
                
                Tables\Actions\Action::make('publish')
                    ->label('发布')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Article $record) {
                        $record->publish();
                        
                        Notification::make()
                            ->title('文章发布成功')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Article $record): bool => !$record->status),
                
                Tables\Actions\Action::make('unpublish')
                    ->label('取消发布')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Article $record) {
                        $record->unpublish();
                        
                        Notification::make()
                            ->title('文章已取消发布')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn (Article $record): bool => $record->status),
                
                Tables\Actions\DeleteAction::make()
                    ->label('删除')
                    ->modalHeading('删除文章')
                    ->modalDescription('确定要删除这篇文章吗？此操作不可撤销。'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_publish')
                        ->label('批量发布')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $count = $records->where('status', false)->count();
                            $records->where('status', false)->each->publish();
                            
                            Notification::make()
                                ->title('批量发布完成')
                                ->body("已发布 {$count} 篇文章")
                                ->success()
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('bulk_unpublish')
                        ->label('批量取消发布')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(function ($records) {
                            $count = $records->where('status', true)->count();
                            $records->where('status', true)->each->unpublish();
                            
                            Notification::make()
                                ->title('批量取消发布完成')
                                ->body("已取消发布 {$count} 篇文章")
                                ->warning()
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('bulk_change_category')
                        ->label('批量修改分类')
                        ->icon('heroicon-o-folder')
                        ->form([
                            Forms\Components\Select::make('category_id')
                                ->label('新分类')
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            $count = $records->count();
                            $records->each->update(['category_id' => $data['category_id']]);
                            
                            Notification::make()
                                ->title('批量分类修改完成')
                                ->body("已修改 {$count} 篇文章的分类")
                                ->success()
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('bulk_export')
                        ->label('批量导出')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            // 导出逻辑
                            $this->exportArticles($records);
                        }),
                    
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('批量删除')
                        ->modalHeading('批量删除文章')
                        ->modalDescription('确定要删除选中的文章吗？此操作不可撤销。'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->poll('30s')
            ->recordUrl(fn (?Model $record) => $record ? route('filament.admin.resources.articles.edit', $record) : null);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('文章详情')
                    ->schema([
                        ImageEntry::make('featured_image')
                            ->label('特色图片')
                            ->height(200)
                            ->width(300),
                        
                        TextEntry::make('title')
                            ->label('标题')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->weight(FontWeight::Bold),
                        
                        TextEntry::make('slug')
                            ->label('别名')
                            ->copyable()
                            ->copyMessage('别名已复制'),
                        
                        TextEntry::make('content')
                            ->label('内容')
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Section::make('分类和标签')
                    ->schema([
                        TextEntry::make('category.name')
                            ->label('分类')
                            ->badge()
                            ->color('info'),
                        
                        TextEntry::make('tag_list')
                            ->label('标签')
                            ->badge()
                            ->separator(','),
                    ])
                    ->columns(2),
                
                Section::make('统计信息')
                    ->schema([
                        TextEntry::make('status_name')
                            ->label('状态')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                '已发布' => 'success',
                                '草稿' => 'gray',
                                default => 'gray',
                            }),
                        
                        TextEntry::make('view_count')
                            ->label('浏览量')
                            ->formatStateUsing(fn (int $state): string => number_format($state)),
                        
                        TextEntry::make('reading_time_minutes')
                            ->label('预计阅读时间')
                            ->suffix('分钟'),
                        
                        TextEntry::make('author.name')
                            ->label('作者'),
                        
                        TextEntry::make('published_at')
                            ->label('发布时间')
                            ->dateTime(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'view' => Pages\ViewArticle::route('/{record}'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::published()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return '已发布的文章数量';
    }

    protected function exportArticles($records): void
    {
        $filename = 'articles_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $path = storage_path('app/exports/' . $filename);
        
        $file = fopen($path, 'w');
        
        // 写入 CSV 头部
        fputcsv($file, ['ID', '标题', '别名', '分类', '作者', '状态', '浏览量', '发布时间']);
        
        // 写入数据
        foreach ($records as $article) {
            fputcsv($file, [
                $article->id,
                $article->title,
                $article->slug,
                $article->category?->name ?? '未分类',
                $article->author?->name ?? '未知',
                $article->status_name,
                $article->view_count,
                $article->published_at?->format('Y-m-d H:i:s') ?? '',
            ]);
        }
        
        fclose($file);
        
        Notification::make()
            ->title('导出成功')
            ->body("文件已保存到: {$filename}")
            ->success()
            ->send();
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('articles.create');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('articles.update') && 
               ($record->author_id === auth()->id() || auth()->user()->isAdmin());
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('articles.delete') && 
               ($record->author_id === auth()->id() || auth()->user()->isAdmin());
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('articles.view');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ])
            ->with([
                'category:id,name',
                'author:id,name',
                'tags:id,name',
            ])
            ->when(!auth()->user()->isAdmin(), function ($query) {
                return $query->where('author_id', auth()->id());
            });
    }
}