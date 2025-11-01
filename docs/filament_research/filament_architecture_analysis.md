# Laravel Filament 核心架构与依赖关系深度研究蓝图

## 引言与研究范围

Filament 是构建在 Laravel 之上的一套全栈组件集合,旨在以声明式、服务器驱动的范式快速生成管理面板与交互式应用。其核心特征是以 PHP 配置对象描述界面(Schema),并通过服务端渲染与轻量前端协作完成交互,从而显著降低对 JavaScript 的依赖与前端工程复杂度[^1][^2]。本研究聚焦于 Filament v4.x 的核心架构与依赖关系,围绕以下问题展开:核心架构与 SDUI(Server-Driven UI)范式、核心包与 Blade 组件的分工协作、与 Laravel 的集成方式、资源管理与权限/用户管理、迁移与 Eloquent 适配、路由与中间件、认证与授权、Laravel 核心服务依赖、配置系统与扩展点,以及常见兼容性问题与工程化建议。

方法上,本文以 Filament 官方与中文文档为主要依据,结合 Laravel 官方文档与社区实践材料进行交叉验证,所有结论均以公开文档为准并给出必要引用[^1][^2][^3][^4][^5][^6][^7][^8][^9][^10][^11][^12][^13][^14][^15][^16][^17][^18][^19][^20][^21][^22][^23][^24][^25][^26][^27][^28][^29][^30][^31][^32][^33][^34][^35][^36][^37][^38][^39][^40][^41][^42][^43]。同时,本文明确以下信息缺口:未完整覆盖官方仓库源码级类图与中间件栈细节;多租户与 MFA 的数据库字段与配置细节在现有材料中未完全展开;插件生态的完整接口清单与示例需进一步补充官方源码或插件文档;路由注册细节与中间件顺序在官方文档中未系统阐述;与 Laravel 11 的兼容性矩阵需结合发布说明进一步核实。

## 核心架构总览:Server-Driven UI 与技术栈

Filament 的核心范式是服务器驱动的 UI(Server-Driven UI,SDUI)。开发者以 PHP 的 Schema 配置对象描述界面,服务端将这些配置渲染为 HTML,并通过 Livewire 与 Alpine.js 处理交互与状态更新,前端样式则由 Tailwind CSS 提供语义化设计令牌与工具类,最终形成“配置即界面”的开发体验[^1][^2]。与传统模板引擎相比,SDUI 更强调在服务器端集中管理业务逻辑与界面结构,前端保持轻量并与后端声明式契约对齐,从而降低复杂度与提升可维护性。

Filament 的技术栈分工清晰:Laravel 负责路由、认证授权、数据库与 Eloquent ORM;Livewire 提供组件化的服务端交互模型;Alpine.js 负责轻量级前端交互与状态同步;Tailwind CSS 提供语义化样式系统(基于设计令牌),并支持通过覆盖语义类实现主题化[^1][^2][^35][^36]。在此基础上,Filament 构建了 Panel(面板)作为顶级容器,组织页面(Pages)、资源(Resources)、表单(Forms)、表格(Tables)、动作(Actions)、通知(Notifications)、信息列表(Infolists)与小组件(Widgets),实现从数据到界面的端到端映射[^3][^4][^5][^6][^7][^8][^9][^10][^11]。

为更直观呈现技术栈的职责分工,见下表。

表 1:Filament 技术栈与职责映射

| 技术/组件 | 职责 | 关键能力 | 典型使用场景 |
|---|---|---|---|
| Laravel | 应用骨架、服务容器、路由、认证授权、数据库 | 服务提供者、门面、中间件、Eloquent ORM、迁移 | 注册 Filament 面板路由、保护后台访问、定义模型与迁移[^21][^22][^23][^24][^25] |
| Livewire | 服务端驱动的组件与交互 | 组件化 UI、数据绑定、事件处理、验证 | 资源页面的表单与表格交互、动作的模态提交[^1][^2] |
| Alpine.js | 轻量前端交互与状态同步 | 事件监听、状态切换、简单动画 | 模态框开关、下拉菜单、折叠面板[^1][^2] |
| Tailwind CSS | 语义化样式系统与主题化 | 设计令牌、工具类、语义类覆盖 | 主题定制、组件外观一致性[^35][^36] |

上述分工形成清晰边界:Laravel 负责系统级能力与数据访问,Filament 在服务端组织 UI 与交互,前端仅承担轻量呈现与事件桥接。这种分层使得开发者可以在不脱离 PHP 的情况下完成复杂界面构建,同时保留 Laravel 生态的全部优势。

### 核心包与组件生态

Filament 的核心包围绕“配置即界面”的思想组织,各包职责明确并可组合使用:filament/filament(核心面板)、filament/tables(表格构建器)、filament/schemas(Schema 基础)、filament/forms(表单字段)、filament/infolists(信息列表)、filament/actions(动作对象)、filament/notifications(通知)、filament/widgets(仪表盘与统计)、filament/support(共享工具)[^1][^2][^4][^5][^6][^7][^8][^9][^10][^11]。此外,Filament 还提供一组 Blade 组件,作为更低层次的 UI 构建块,可在插件或应用中复用[^2]。

为便于工程选型与组合,见下表。

表 2:核心包与组件职责对照

| 包/组件 | 用途 | 关键特性 | 典型组合 |
|---|---|---|---|
| filament/filament | 面板与页面容器 | PanelProvider、页面注册、主题与插件 | 与 Resources/Forms/Tables/Actions/Widgets 组合[^3][^4] |
| filament/tables | 表格构建器 | 过滤、排序、分页、列定义 | 与 Resources/Widgets 组合[^10][^11] |
| filament/schemas | 声明式 UI 基础 | PHP 配置对象渲染 UI | 与 Forms/Infolists/Actions 组合[^1][^5][^6][^7] |
| filament/forms | 表单字段集合 | 验证、数据绑定、模态表单 | 与 Resources/Actions 组合[^5][^6] |
| filament/infolists | 信息列表 | 描述列表式呈现 | 与 Resources/Records 展示组合[^7] |
| filament/actions | 动作对象 | 按钮与模态、执行逻辑 | 与 Resources/Pages/Forms 组合[^6] |
| filament/notifications | 通知组件 | 成功/错误/提示、广播/数据库 | 与动作结果反馈组合[^8] |
| filament/widgets | 仪表盘与统计 | 统计卡片、图表 | 与 Dashboard/资源页组合[^9] |
| filament/support | 共享工具与 UI 基础 | 通用组件、样式钩子 | 被上述各包依赖[^2] |
| Blade 组件集 | 低层 UI 构建块 | Avatar、Badge、Button、Modal 等 | 在自定义页面/插件中复用[^2] |

该生态允许开发者以“拼装”的方式构建复杂界面:例如在资源列表页中组合表格与过滤器,在编辑动作中组合模态表单与通知,在仪表盘中组合统计卡片与图表,从而实现高一致性与高可维护性的工程实践。

## 与 Laravel 框架的集成方式与依赖项

Filament 与 Laravel 的集成以服务提供者(PanelProvider/Plugin)为核心。PanelProvider 负责面板级注册与引导;插件遵循 Laravel 包开发规范,通过服务提供者注册路由、视图、翻译与资源,生命周期包含 register()、boot() 与仅在使用时触发的插件 boot 语义[^3][^12][^26][^27][^28]。资产(CSS、JS、Alpine 组件)注册需在插件服务提供者的 packageBooted() 中完成,以确保加载顺序与隔离性[^12]。

面板的安装命令会设置 Livewire、Alpine.js 与 Tailwind CSS,并生成 PanelProvider 文件与基础配置,形成“即装即用”的面板骨架[^3][^4][^5][^10][^11]。在路由层面,Filament 的后台路由通常处于 web 中间件组保护之下,并可结合认证中间件实现访问控制;在某些场景下需注意与 Laravel 路由命名和重定向逻辑的匹配,避免出现“Route [login] not defined”的提示[^21][^22][^33]。

表 3:Filament 插件生命周期与关键方法

| 方法 | 触发时机 | 典型职责 | 注意事项 |
|---|---|---|---|
| register(Panel $panel) | 面板注册阶段 | 注册资源、页面、主题、渲染钩子 | 仅注册,不访问未初始化服务[^12] |
| boot(Panel $panel) | 插件启用且被实际使用时 | 扩展面板功能、订阅事件 | 严格按需执行,避免副作用[^12] |
| packageBooted() | 资产加载阶段 | 注册 CSS/JS/Alpine 组件 | 保证资产加载顺序与隔离[^12] |

表 4:安装命令与资产配置清单

| 资产/命令 | 用途 | 说明 |
|---|---|---|
| Livewire 安装 | 服务端交互 | 组件化与数据绑定[^4][^5][^10] |
| Alpine.js 安装 | 前端交互 | 轻量事件与状态[^4][^5][^10] |
| Tailwind CSS 安装 | 样式系统 | 工具类与语义类覆盖[^4][^5][^10][^35] |
| PanelProvider 生成 | 面板入口 | 注册页面/资源/插件/主题[^3] |

通过上述机制,Filament 将面板与插件的生命周期与 Laravel 的服务提供者模型深度融合,既保持了 Laravel 应用的通用约定,又为后台场景提供了结构化的扩展点。

## 核心组件与页面模型

资源(Resource)是 Filament 的核心构件之一,用于为 Eloquent 模型构建 CRUD 界面。资源类在 form() 方法中定义表单 Schema,在 table() 方法中定义表格列与交互,并关联授权策略(Policies)与守卫(Guards),同时组织 Pages(Create/Edit/List)以完成界面装配[^13][^14]。动作(Actions)以对象化方式封装按钮、模态与执行逻辑,支持在资源页或独立页面中复用;通知(Notifications)用于动作结果的反馈;小组件(Widgets)则承载统计与图表,并可嵌入仪表盘或资源页头尾区域[^6][^8][^9][^15][^16]。

表 5:资源页面类型与职责

| 页面类型 | 职责 | 典型交互 |
|---|---|---|
| ListRecords | 列表与过滤、排序、分页 | 搜索、筛选、批量操作[^13][^14] |
| CreateRecord | 新建记录与表单验证 | 模态或独立页提交[^13] |
| EditRecord | 编辑记录与数据回显 | 保存、通知反馈[^13] |

表 6:动作与通知类型对照

| 动作类型 | 触发方式 | 模态/确认 | 通知通道 |
|---|---|---|---|
| Create/Edit | 按钮点击 | 可带模态表单 | 成功/错误通知[^6][^8] |
| Delete | 按钮点击 | 二次确认 | 结果反馈[^6][^8] |
| Reorder | 拖拽/按钮 | 可选确认 | 排序结果通知[^6][^8] |
| Export | 按钮点击 | 可选确认 | 完成/失败通知[^6][^8] |

表 7:小组件类型与数据源

| 小组件类型 | 呈现内容 | 数据源 | 适用场景 |
|---|---|---|---|
| Stats | 统计卡片 | Eloquent 聚合 | KPI 监控[^9] |
| Chart | 图表 | Chart.js 或趋势包 | 时间序列分析[^9][^17] |
| Table | 表格 | Eloquent 查询 | 快速浏览与操作[^9][^10] |

通过资源与动作的组合,Filament 将数据访问与交互逻辑封装为可复用对象,既保持了界面一致性,又简化了 CRUD 的开发与维护成本。

## 资源管理、权限系统与用户管理机制

Filament 的资源管理以 Eloquent 模型为中心,资源类负责表单 Schema 与表格列的定义,并通过 Policies 对 CRUD 操作进行授权控制。Policies 基于模型与守卫(Guard)定义 view/create/update/delete 等能力的授权逻辑,资源类在授权检查点调用策略方法,实现访问控制[^13][^25]。在用户管理方面,Filament 2.x 提供默认的用户访问规则:本地环境默认允许访问;生产环境需显式确保仅授权用户可访问后台[^18]。此外,Filament 支持多因素认证(Multi-Factor Authentication,MFA),需要在用户表增加存储 TOTP(基于时间的一次性密码)密钥的字段,并在面板中启用 MFA 流程[^19]。

在权限插件生态中,Shield 插件与 spatie/laravel-permission 集成,为资源、页面与小组件提供细粒度的角色与权限控制;也可选择其他访问控制插件或自行实现策略以满足特定业务需求[^20][^25][^29][^30][^31][^32]。工程实践中,建议将授权策略与守卫配置前置为资源类与插件的基础契约,并在动作层显式进行授权检查,避免越权与误操作。

表 8:权限控制方式对比

| 方式 | 特性 | 适用场景 | 注意事项 |
|---|---|---|---|
| Policies(原生) | 模型级授权、基于 Guard | 标准 CRUD 控制 | 与资源类耦合紧密[^25] |
| Shield + spatie | 角色/权限细粒度控制 | 后台权限精细化管理 | 依赖插件生态与数据表[^20][^29][^30] |
| 自定义插件 | 定制授权逻辑 | 特殊业务规则 | 需要维护成本与测试覆盖[^12][^26][^27][^28] |

表 9:用户管理功能矩阵

| 功能 | 机制 | 依赖 | 备注 |
|---|---|---|---|
| 访问控制 | 认证中间件 + 策略 | Laravel Auth | 生产环境需严格控制[^18][^21][^22] |
| MFA | TOTP 密钥字段 | 用户表字段 + 面板启用 | 提升安全性[^19] |
| 角色/权限 | 插件或原生策略 | spatie/Policies | 细粒度分配与审计[^20][^25][^29][^30] |

## 数据库迁移与模型适配机制

Filament 的数据访问完全依赖 Eloquent ORM,所有数据库交互均通过模型与查询构建器完成。对于静态数据源(如普通 PHP 数组),可使用 Sushi 包在 Eloquent 模型中提供访问,从而保持与 Filament 组件的一致集成[^24][^25]。在批量赋值保护方面,Filament 表单提交遵循 Laravel 的fillable 或 guarded 规则,资源类在定义表单 Schema 时需明确可填充字段与验证,以避免数据模型层的安全风险[^13][^25]。

表 10:Eloquent 模型与 Filament 集成要点

| 要点 | 说明 | 风险与缓解 |
|---|---|---|
| 关联关系 | BelongsTo/HasMany 等在表单/表格中呈现 | 避免 N+1,合理预加载[^13][^25] |
| 批量赋值 | fillable/guarded 控制可写字段 | 表单 Schema 与模型策略对齐[^13][^25] |
| 验证规则 | 表单字段级验证与服务器端校验 | 保持规则与业务约束一致[^13] |
| 静态数据源 | Sushi 提供数组到模型的桥接 | 保持数据一致性[^24] |

## 路由与中间件实现

Filament 面板路由通常注册在 web 中间件组下,并在认证与授权层面与 Laravel 的 auth、guest 等中间件协作。生产环境中,认证失败的重定向逻辑需与 Laravel 的路由命名与登录视图配置一致,否则可能出现“Route [login] not defined”的错误;在前后端分离或 API 场景下,可通过 Accept: application/json 头避免重定向并返回 JSON 响应[^21][^22][^33]。在多 Guard 场景下,需确保模型与认证配置使用正确的列名与守卫,否则登录查询可能出现异常[^34]。

表 11:常见路由与中间件交互场景

| 场景 | 期望行为 | 常见问题 | 解决方案 |
|---|---|---|---|
| 未认证访问后台 | 重定向至登录 | 路由命名不匹配 | 配置 login 路由或中间件[^33] |
| API 访问 | 返回 JSON | 重定向至登录页 | 增加 Accept: application/json[^33] |
| 多 Guard 登录 | 使用正确列名 | 默认列名导致查询异常 | 模型显式设置认证列[^34] |

## 认证与授权系统集成

Filament 的认证与授权基于 Laravel 的 Guards 与 Providers,资源类通过 Policies 完成操作级授权控制。面板层面可启用 MFA 以增强安全性,需在用户表新增字段存储 TOTP 密钥,并在面板中配置 MFA 验证流程[^19][^25]。在插件生态中,Shield 与 spatie 的组合提供角色与权限的细粒度控制,包括资源、页面与小组件的访问分配;也可选择其他访问控制插件或自行实现策略,以满足审计与合规需求[^20][^29][^30][^31][^32]。

表 12:认证方式与适用场景

| 方式 | 特性 | 适用场景 | 注意事项 |
|---|---|---|---|
| Session(基于 Guard) | 会话状态、密码登录 | 后台管理面板 | 与路由/视图配置一致[^25] |
| MFA(TOTP) | 多因素验证 | 高安全场景 | 字段与流程启用[^19] |
| 插件权限 | 角色/权限细粒度 | 复杂权限管理 | 插件依赖与数据表[^20][^29][^30] |

## Laravel 核心服务与组件依赖列表

Filament 依赖多项 Laravel 核心服务与组件,以实现面板功能与工程一致性。下表列出关键依赖及其用途。

表 13:Laravel 核心服务与组件依赖清单

| 服务/组件 | 用途 | Filament 使用点 |
|---|---|---|
| 服务提供者(Service Providers) | 注册路由/视图/资产/插件 | PanelProvider、插件注册[^26][^27] |
| 路由(Routing) | 注册面板路由、认证保护 | 后台路由与中间件[^21][^22] |
| 中间件(Middleware) | 认证、会话、CSRF | auth/guest 与 web 组[^21][^22] |
| 认证(Auth) | Guards/Providers/Policies | 资源授权与访问控制[^25] |
| 数据库与迁移 | Schema 构建与版本管理 | 模型迁移与数据访问[^23][^25] |
| Eloquent ORM | 模型与关联、查询 | 资源表单/表格数据源[^25] |
| 视图与资产 | Blade 视图与静态资源 | 主题与样式覆盖[^35] |
| 事件与广播 | 通知与实时反馈 | Notifications 通道[^8] |

## 配置系统与自定义扩展点

Filament 的配置系统以 PanelProvider 为入口,支持插件注册、资源/页面注册、主题渲染与渲染钩子配置。插件遵循 Laravel 包开发规范,资产注册需在 packageBooted() 完成,保证加载顺序与隔离性[^3][^12][^26][^27][^28]。主题定制通过 Tailwind CSS 的语义类覆盖实现,设计令牌映射至语义类,可在不破坏组件结构的前提下进行外观调整[^35][^36]。

表 14:扩展点与实现方式

| 扩展点 | 实现方式 | 典型场景 |
|---|---|---|
| 插件(Plugin) | 实现 Plugin 接口,register/boot | 权限、报表、导入导出[^12] |
| 主题(Theme) | 语义类覆盖与 CSS 变量 | 品牌化与外观定制[^35][^36] |
| 资源(Resource) | 表单/表格/授权 | 模型到 CRUD 映射[^13][^14] |
| 动作(Actions) | 按钮/模态/逻辑 | 批量操作与审批[^6] |
| 通知(Notifications) | 成功/失败/广播 | 操作反馈与审计[^8] |
| 小组件(Widgets) | Stats/Chart/Table | KPI 与趋势分析[^9][^17] |

## 常见兼容性问题与解决方案

在工程实践中,Filament 与 Laravel 版本、模型兼容性与路由配置可能引发问题。以下表格总结常见错误与修复建议。

表 15:常见错误与修复建议

| 问题 | 症状 | 根因 | 修复建议 |
|---|---|---|---|
| 路由未定义 | 重定向至 login 失败 | 路由命名或视图未配置 | 配置命名路由或中间件;JSON 请求加 Accept 头[^33] |
| 模型兼容性 | 继承/基类冲突 | 模型继承链异常 | 确认 Eloquent 继承关系与命名空间[^32] |
| 多 Guard 认证 | 登录查询异常 | 列名/守卫配置不一致 | 模型显式设置认证列与守卫[^34] |

## 结论与架构建议

Filament 以 SDUI 范式与 Laravel 生态深度融合,形成了“配置即界面”的工程实践:服务端集中业务逻辑与界面结构,前端保持轻量协作,组件化拼装实现高一致性、可维护性与扩展性[^1][^2]。在工程落地方面,建议:

- 权限与授权优先:以 Policies 与插件(如 Shield + spatie)构建清晰的授权边界,在动作层进行显式检查,满足审计与合规需求[^20][^25][^29][^30]。
- 迁移与模型适配规范:严格遵循 Eloquent 批量赋值与验证规则,避免表单与模型策略不一致;对静态数据源使用 Sushi 保持组件一致性[^13][^24][^25]。
- 路由与中间件一致性:确保登录路由命名与视图配置一致,在 API 场景下正确处理 JSON 响应与认证重定向;多 Guard 配置明确列名与守卫[^21][^22][^33][^34]。
- 主题与插件治理:以 PanelProvider 统一入口管理插件与主题,资产注册置于 packageBooted(),建立版本与兼容性台账,降低升级成本[^3][^12][^26][^27][^28][^35][^36]。
- 信息缺口与后续工作:补充官方仓库源码级类图与中间件栈细节;完善多租户与 MFA 的数据库字段与配置;梳理插件接口清单与示例;明确路由注册细节与中间件顺序;核对 Laravel 11 兼容性矩阵。

总体而言,Filament 的架构设计既继承了 Laravel 的工程化优势,又以 SDUI 降低了前端复杂度,为后台管理与交互式应用的快速交付提供了可复制的实践路径。

---

## 参考文献

[^1]: Filament 是什么? - 介绍 - Filament(4.x). https://laravel-filament.cn/docs/zh-CN/4.x/introduction/overview  
[^2]: 概述 - 组件 - Filament(4.x). https://laravel-filament.cn/docs/zh-CN/4.x/components/overview  
[^3]: 快速开始 | 《Laravel Filament 官方文档》(3.x). https://learnku.com/docs/laravel-filament/3.x/panels-getting-started/  
[^4]: 安装 - Laravel Filament(Infolists). https://docs.laravel-filament.cn/zh-CN/docs/infolists/installation/  
[^5]: Installation | Laravel Filament(Actions). https://docs.laravel-filament.cn/docs/actions/installation/  
[^6]: 安装 - 表格构造器 - Filament(3.x). https://www.laravel-filament.cn/docs/zh-CN/3.x/tables/installation  
[^7]: 概述 - 资源 - Filament(4.x). https://laravel-filament.cn/docs/zh-CN/4.x/resources/overview  
[^8]: 使用 widgets on resource pages - Resources - Filament(4.x). https://laravel-filament.cn/docs/en/4.x/resources/widgets  
[^9]: Table widgets - Widgets - Filament(3.x). https://www.laravel-filament.cn/docs/zh-CN/3.x/widgets/tables  
[^10]: 资源 - Widgets | 《Laravel Filament 官方文档》(3.x). https://learnku.com/docs/laravel-filament/3.x/panels-resources-widgets/16071  
[^11]: 1.3. 资源 - 快速入门 | 面板生成器 |《Laravel Filament 官方文档》(3.x). https://learnku.com/docs/laravel-filament/3.x/panels-resources-getting-started/16063  
[^12]: 插件开发 | Laravel Filament(3.x). https://docs.laravel-filament.cn/zh-CN/docs/3.x/panels/plugins/  
[^13]: 概述 - 资源 - Filament(4.x). https://laravel-filament.cn/docs/zh-CN/4.x/resources/overview  
[^14]: Getting started - Resources - Filament(3.x). https://docs.laravel-filament.cn/zh-CN/docs/3.x/panels/resources/getting-started/  
[^15]: 资源 - Widgets | 《Laravel Filament 官方文档》(3.x). https://learnku.com/docs/laravel-filament/3.x/panels-resources-widgets/16071  
[^16]: Table widgets - Widgets - Filament(3.x). https://www.laravel-filament.cn/docs/zh-CN/3.x/widgets/tables  
[^17]: GitHub - Flowframe/laravel-trend. https://github.com/Flowframe/laravel-trend  
[^18]: 用户 - 后台面板 - Filament(2.x). https://laravel-filament.cn/docs/zh-CN/2.x/admin/users  
[^19]: 多因素认证 - 用户 - Filament(4.x). https://laravel-filament.cn/docs/zh-CN/4.x/users/multi-factor-authentication  
[^20]: 使用 Filament Shield 在 Filament 中添加角色和权限. https://juejin.cn/post/7418408705304264755  
[^21]: Middleware | Laravel中文文档(8.x). https://docs.golaravel.com/docs/8.x/middleware  
[^22]: Middleware | Laravel 中文文档(5.8). https://www.laravel.ltd/en/5.8/middleware  
[^23]: 数据库迁移 | Laravel 10.x(中文). https://learnku.com/docs/laravel/10.x/migrations/14885  
[^24]: 概述 - 核心概念 - Filament(3.x). https://www.laravel-filament.cn/docs/zh-CN/3.x/support/overview  
[^25]: Eloquent: Getting Started - Laravel 11.x. https://laravel.com/docs/11.x/eloquent  
[^26]: Laravel 中的服务提供者:Service Providers 是什么以及如何使用. https://learnku.com/laravel/t/67406  
[^27]: Packages - Laravel 官方文档. https://laravel.com/docs/packages  
[^28]: Spatie Laravel Package Training. https://spatie.be/products/laravel-package-training  
[^29]: GitHub - althinect/filament-spatie-roles-permissions. https://github.com/althinect/filament-spatie-roles-permissions  
[^30]: GitHub - chiiya/filament-access-control. https://github.com/chiiya/filament-access-control  
[^31]: Filament 插件目录 - Users Roles Permissions by CWSPS154. https://filamentphp.com/plugins/code-with-sps-154-users-roles-permissions  
[^32]: Handle authorization in Filament: Policies, Roles & Guards. https://filamentmastery.com/articles/handle-authorization-in-filament-policies-roles-guards  
[^33]: How to fix Route [login] not defined. https://github.com/filamentphp/filament/discussions/5226  
[^34]: Different auth guard for filament authentication always redirecting on login page. https://stackoverflow.com/questions/78018492/different-auth-guard-for-filament-authentication-always-redirecting-on-login-pag  
[^35]: Tailwind CSS - Border Radius. https://tailwindcss.com/docs/border-radius  
[^36]: 概述 - 核心概念 - Filament(3.x). https://www.laravel-filament.cn/docs/zh-CN/3.x/support/overview  
[^37]: Documentation - Filament. https://filamentphp.com/docs  
[^38]: Filament - Accelerated Laravel development framework. https://filamentphp.com/  
[^39]: GitHub - filamentphp/filament. https://github.com/filamentphp/filament  
[^40]: GitHub - filamentphp/demo. https://github.com/filamentphp/demo  
[^41]: 使用 Laravel Filament 极速搭建美观大方的后台. https://learnku.com/articles/68669  
[^42]: 快速开始 | 《Laravel Filament 官方文档》(3.x). https://learnku.com/docs/laravel-filament/3.x/panels-getting-started/  
[^43]: Laravel Bootcamp - Blade Installation. https://bootcamp.laravel.com/blade/installation