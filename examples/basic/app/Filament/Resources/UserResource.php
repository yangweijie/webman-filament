<?php
// examples/basic/app/Filament/Resources/UserResource.php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Notifications\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = '用户管理';
    
    protected static ?string $modelLabel = '用户';
    
    protected static ?string $pluralModelLabel = '用户';
    
    protected static ?string $navigationGroup = '用户管理';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('姓名')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create' ? $set('email', \Illuminate\Support\Str::lower(str_replace(' ', '.', $state)) . '@example.com') : null),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('邮箱')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('登录时使用的邮箱地址'),
                        
                        Forms\Components\TextInput::make('phone')
                            ->label('电话')
                            ->tel()
                            ->unique(ignoreRecord: true)
                            ->helperText('联系电话（可选）'),
                        
                        Forms\Components\TextInput::make('password')
                            ->label('密码')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => \Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->helperText('密码长度至少8位'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('用户设置')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label('用户角色')
                            ->options([
                                'admin' => '管理员',
                                'editor' => '编辑',
                                'author' => '作者',
                                'subscriber' => '订阅者',
                            ])
                            ->required()
                            ->default('subscriber')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // 根据角色设置默认权限
                                $permissions = match ($state) {
                                    'admin' => ['users.view', 'users.create', 'users.update', 'users.delete', 'articles.view', 'articles.create', 'articles.update', 'articles.delete'],
                                    'editor' => ['articles.view', 'articles.create', 'articles.update', 'articles.delete'],
                                    'author' => ['articles.view', 'articles.create', 'articles.update'],
                                    default => ['articles.view'],
                                };
                                $set('permissions', $permissions);
                            }),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('激活状态')
                            ->required()
                            ->default(true)
                            ->helperText('关闭后用户将无法登录'),
                        
                        Forms\Components\Textarea::make('bio')
                            ->label('个人简介')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('简单的个人介绍（可选）'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('权限设置')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('用户权限')
                            ->options([
                                'users.view' => '查看用户',
                                'users.create' => '创建用户',
                                'users.update' => '更新用户',
                                'users.delete' => '删除用户',
                                'articles.view' => '查看文章',
                                'articles.create' => '创建文章',
                                'articles.update' => '更新文章',
                                'articles.delete' => '删除文章',
                                'categories.view' => '查看分类',
                                'categories.manage' => '管理分类',
                                'settings.view' => '查看设置',
                                'settings.update' => '更新设置',
                            ])
                            ->columns(3)
                            ->gridDirection('row')
                            ->helperText('为用户分配特定权限'),
                    ])
                    ->visible(fn (callable $get): bool => !empty($get('role'))),
                
                Forms\Components\Section::make('头像设置')
                    ->schema([
                        Forms\Components\FileUpload::make('avatar')
                            ->label('用户头像')
                            ->image()
                            ->avatar()
                            ->directory('avatars')
                            ->maxSize(2048)
                            ->helperText('支持 JPG、PNG 格式，最大 2MB'),
                    ]),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('avatar')
                    ->label('头像')
                    ->circular()
                    ->defaultImageUrl('/images/default-avatar.png')
                    ->size(40),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('姓名')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->label('邮箱')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('邮箱已复制到剪贴板'),
                
                Tables\Columns\TextColumn::make('role_name')
                    ->label('角色')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '管理员' => 'danger',
                        '编辑' => 'warning',
                        '作者' => 'info',
                        '订阅者' => 'success',
                        default => 'gray',
                    }),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('状态')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('articles_count')
                    ->label('文章数')
                    ->counts('articles')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('最后登录')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('用户角色')
                    ->options([
                        'admin' => '管理员',
                        'editor' => '编辑',
                        'author' => '作者',
                        'subscriber' => '订阅者',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('激活状态'),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('创建开始日期'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('创建结束日期'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn ($query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn ($query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('查看'),
                
                Tables\Actions\EditAction::make()
                    ->label('编辑'),
                
                Tables\Actions\Action::make('login_as')
                    ->label('模拟登录')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('模拟用户登录')
                    ->modalDescription('确定要模拟该用户登录吗？这将使用该用户的身份访问系统。')
                    ->action(function (User $record) {
                        // 模拟登录逻辑
                        auth()->login($record);
                        
                        Notification::make()
                            ->title('已模拟用户登录')
                            ->body("当前以 {$record->name} 的身份登录")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (User $record): bool => auth()->id() !== $record->id),
                
                Tables\Actions\Action::make('reset_password')
                    ->label('重置密码')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('重置用户密码')
                    ->modalDescription('确定要重置该用户的密码吗？将发送重置邮件。')
                    ->form([
                        Forms\Components\TextInput::make('new_password')
                            ->label('新密码')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->confirmed(),
                        
                        Forms\Components\TextInput::make('new_password_confirmation')
                            ->label('确认密码')
                            ->password()
                            ->required(),
                        
                        Forms\Components\Toggle::make('send_email')
                            ->label('发送密码重置邮件')
                            ->default(true)
                            ->helperText('是否向用户发送密码重置邮件'),
                    ])
                    ->action(function (array $data, User $record) {
                        $record->update([
                            'password' => \Hash::make($data['new_password']),
                        ]);
                        
                        if ($data['send_email']) {
                            // 发送密码重置邮件
                            // \Illuminate\Support\Facades\Mail::to($record)->send(new \App\Mail\PasswordResetMail($data['new_password']));
                        }
                        
                        Notification::make()
                            ->title('密码重置成功')
                            ->body("用户 {$record->name} 的密码已重置")
                            ->success()
                            ->send();
                    }),
                
                Tables\Actions\DeleteAction::make()
                    ->label('删除')
                    ->modalHeading('删除用户')
                    ->modalDescription('确定要删除该用户吗？此操作不可撤销。')
                    ->before(function (User $record) {
                        // 检查是否有相关文章
                        if ($record->articles()->count() > 0) {
                            throw new \Exception('该用户有关联的文章，无法删除。请先删除或转移相关文章。');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('批量激活')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                            
                            Notification::make()
                                ->title('批量激活完成')
                                ->success()
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('批量停用')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                            
                            Notification::make()
                                ->title('批量停用完成')
                                ->warning()
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('change_role')
                        ->label('批量修改角色')
                        ->icon('heroicon-o-user-group')
                        ->form([
                            Forms\Components\Select::make('role')
                                ->label('新角色')
                                ->options([
                                    'admin' => '管理员',
                                    'editor' => '编辑',
                                    'author' => '作者',
                                    'subscriber' => '订阅者',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            $records->each->update(['role' => $data['role']]);
                            
                            Notification::make()
                                ->title('批量角色修改完成')
                                ->success()
                                ->send();
                        }),
                    
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('批量删除')
                        ->modalHeading('批量删除用户')
                        ->modalDescription('确定要删除选中的用户吗？此操作不可撤销。')
                        ->before(function ($records) {
                            $recordsWithArticles = $records->filter->articles()->isNotEmpty();
                            
                            if ($recordsWithArticles->isNotEmpty()) {
                                throw new \Exception('选中的用户中有关联文章的用户，无法删除。');
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->poll('30s');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('用户信息')
                    ->schema([
                        TextEntry::make('name')->label('姓名'),
                        TextEntry::make('email')->label('邮箱'),
                        TextEntry::make('phone')->label('电话'),
                        TextEntry::make('role_name')->label('角色'),
                        TextEntry::make('is_active')
                            ->label('状态')
                            ->badge()
                            ->color(fn (string $state): string => $state ? 'success' : 'danger'),
                        TextEntry::make('bio')->label('个人简介')->columnSpanFull(),
                        TextEntry::make('avatar_url')
                            ->label('头像')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Section::make('统计信息')
                    ->schema([
                        TextEntry::make('articles_count')->label('文章数量'),
                        TextEntry::make('created_at')->label('创建时间')->dateTime(),
                        TextEntry::make('updated_at')->label('更新时间')->dateTime(),
                        TextEntry::make('last_login_at')->label('最后登录')->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('users.create');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('users.update') && 
               ($record->id !== auth()->id() || auth()->user()->isAdmin());
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('users.delete') && 
               $record->id !== auth()->id() &&
               $record->articles()->count() === 0;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('users.view');
    }
}