<?php
// examples/basic/app/Filament/Resources/UserResource/Pages/ListUsers.php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Table;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return '用户列表';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('创建用户')
                ->icon('heroicon-o-plus'),
            
            Actions\Action::make('import')
                ->label('导入用户')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('file')
                        ->label('CSV 文件')
                        ->required()
                        ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel'])
                        ->helperText('请上传 CSV 格式的用户数据文件'),
                ])
                ->action(function (array $data) {
                    // 处理 CSV 导入
                    $this->importUsers($data['file']);
                }),
            
            Actions\Action::make('export')
                ->label('导出用户')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    // 导出用户数据
                    $this->exportUsers();
                }),
        ];
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('index')
                ->label('#')
                ->rowIndex()
                ->width('50px'),
            
            Tables\Columns\TextColumn::make('name')
                ->label('姓名')
                ->searchable()
                ->sortable()
                ->weight(FontWeight::Bold),
            
            Tables\Columns\TextColumn::make('email')
                ->label('邮箱')
                ->searchable()
                ->copyable()
                ->copyMessage('邮箱已复制'),
            
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
                ->toggleable(),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('role')
                ->label('用户角色')
                ->options([
                    'admin' => '管理员',
                    'editor' => '编辑',
                    'author' => '作者',
                    'subscriber' => '订阅者',
                ])
                ->multiple(),
            
            Tables\Filters\TernaryFilter::make('is_active')
                ->label('激活状态'),
            
            Tables\Filters\Filter::make('recent_users')
                ->label('最近注册')
                ->query(fn ($query) => $query->where('created_at', '>=', now()->subDays(7)))
                ->toggle(),
            
            Tables\Filters\Filter::make('with_articles')
                ->label('有文章的用户')
                ->query(fn ($query) => $query->has('articles'))
                ->toggle(),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\BulkAction::make('activate')
                    ->label('批量激活')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($records) {
                        $count = $records->count();
                        $records->each->update(['is_active' => true]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('批量激活完成')
                            ->body("已激活 {$count} 个用户")
                            ->success()
                            ->send();
                    }),
                
                Tables\Actions\BulkAction::make('deactivate')
                    ->label('批量停用')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->action(function ($records) {
                        $count = $records->count();
                        $records->each->update(['is_active' => false]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('批量停用完成')
                            ->body("已停用 {$count} 个用户")
                            ->warning()
                            ->send();
                    }),
                
                Tables\Actions\BulkAction::make('change_role')
                    ->label('修改角色')
                    ->icon('heroicon-o-user-group')
                    ->form([
                        \Filament\Forms\Components\Select::make('role')
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
                        $count = $records->count();
                        $records->each->update(['role' => $data['role']]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('角色修改完成')
                            ->body("已修改 {$count} 个用户的角色")
                            ->success()
                            ->send();
                    }),
            ]),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make()
                ->label('查看')
                ->color('info'),
            
            Tables\Actions\EditAction::make()
                ->label('编辑'),
            
            Tables\Actions\Action::make('login_as')
                ->label('模拟登录')
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('模拟用户登录')
                ->modalDescription('确定要模拟该用户登录吗？这将使用该用户的身份访问系统。')
                ->action(function (\App\Models\User $record) {
                    auth()->login($record);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('已模拟用户登录')
                        ->body("当前以 {$record->name} 的身份登录")
                        ->success()
                        ->send();
                })
                ->visible(fn (\App\Models\User $record): bool => auth()->id() !== $record->id),
            
            Tables\Actions\DeleteAction::make()
                ->label('删除')
                ->modalHeading('删除用户')
                ->modalDescription('确定要删除该用户吗？此操作不可撤销。')
                ->before(function (\App\Models\User $record) {
                    if ($record->articles()->count() > 0) {
                        throw new \Exception('该用户有关联的文章，无法删除。请先删除或转移相关文章。');
                    }
                }),
        ];
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100, 200];
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'created_at';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }

    protected function importUsers($filePath): void
    {
        // CSV 导入逻辑
        $file = fopen($filePath, 'r');
        $header = fgetcsv($file);
        
        $imported = 0;
        $errors = [];
        
        while (($row = fgetcsv($file)) !== false) {
            try {
                $data = array_combine($header, $row);
                
                \App\Models\User::create([
                    'name' => $data['name'] ?? '',
                    'email' => $data['email'] ?? '',
                    'role' => $data['role'] ?? 'subscriber',
                    'password' => \Hash::make($data['password'] ?? 'password'),
                    'is_active' => true,
                ]);
                
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "行 " . ($imported + 2) . ": " . $e->getMessage();
            }
        }
        
        fclose($file);
        
        if (!empty($errors)) {
            \Filament\Notifications\Notification::make()
                ->title('导入完成，但有错误')
                ->body("成功导入 {$imported} 个用户\\n" . implode("\\n", $errors))
                ->warning()
                ->send();
        } else {
            \Filament\Notifications\Notification::make()
                ->title('导入成功')
                ->body("成功导入 {$imported} 个用户")
                ->success()
                ->send();
        }
    }

    protected function exportUsers(): void
    {
        $users = \App\Models\User::with('articles')->get();
        
        $filename = 'users_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $path = storage_path('app/exports/' . $filename);
        
        $file = fopen($path, 'w');
        
        // 写入 CSV 头部
        fputcsv($file, ['ID', '姓名', '邮箱', '角色', '状态', '文章数', '创建时间', '最后登录']);
        
        // 写入数据
        foreach ($users as $user) {
            fputcsv($file, [
                $user->id,
                $user->name,
                $user->email,
                $user->role_name,
                $user->is_active ? '激活' : '停用',
                $user->articles->count(),
                $user->created_at->format('Y-m-d H:i:s'),
                $user->last_login_at?->format('Y-m-d H:i:s') ?? '从未登录',
            ]);
        }
        
        fclose($file);
        
        \Filament\Notifications\Notification::make()
            ->title('导出成功')
            ->body("文件已保存到: {$filename}")
            ->success()
            ->send();
        
        // 返回下载链接
        return response()->download($path, $filename)->deleteFileAfterSend();
    }
}