# webman + Filament 集成架构方案(适配器模式、路由映射、中间件桥接、数据库与认证集成、生命周期与性能优化)

## 执行摘要与目标

在高并发、低延迟的业务场景中,采用常驻内存与事件驱动的运行模型已成为提升服务端吞吐与稳定性的关键路径。webman 以其非阻塞 I/O、连接池复用和进程模型优化,在相同开发体验下显著提升性能表现,已在社区形成“一样的写法,十倍的性能”的工程认知[^1][^2]。与此同时,Filament 作为基于 Laravel 的服务器驱动 UI(Server-Driven UI,SDUI)后台面板,提供了以 PHP 配置对象组织界面与交互的工程范式,显著降低后台管理系统的开发与维护成本[^3]。

本方案旨在在不牺牲 Filament 完整能力的前提下,将 Filament 的面板、资源、表单、表格、动作、通知与小组件等核心组件桥接到 webman 的常驻内存运行模型上,实现以下目标:
- 保持 Filament 的完整能力:面板、页面、资源、表单、表格、动作、通知与小组件等组件按原有契约工作;
- 复用 webman 的高性能特征:常驻内存、事件驱动、非阻塞 I/O 与连接池;
- 提供清晰的适配器边界与接口契约:桥接适配器、请求/响应转换器、服务容器适配层;
- 落地可执行的工程路径:路由映射、中间件桥接、数据库与认证集成、生命周期管理与性能优化。

方案范围覆盖:适配器模式设计、路由映射与中间件桥接、数据库迁移与模型适配、认证与权限系统集成、配置系统集成、生命周期管理、性能优化与风险缓解,并以阶段性里程碑推进交付。

为确保方案可落地,本报告在关键环节给出架构图与表格化对照,并在“风险与信息缺口”中明确尚需通过实测与官方文档核对的边界。

---

## 背景与约束:webman 与 Filament 的架构差异与互补性

webman 的核心是常驻内存与事件循环,避免请求级重复初始化,辅以非阻塞 I/O 与连接池,提升并发与资源效率[^4][^5]。Filament 基于 Laravel 的服务提供者、路由与中间件体系,以 SDUI 范式组织后台界面与交互,依赖 Laravel 的认证授权与 Eloquent ORM 完成数据访问与权限控制[^3][^6]。

两者的互补性在于:webman 提供高性能运行底座与进程/插件扩展机制,Filament 提供成熟的后台管理工程范式与生态。约束与挑战主要来自运行模型差异(常驻内存 vs FPM)、服务容器差异(Laravel Container vs 可选的 php-di)、中间件体系差异(Laravel 中间件栈 vs webman 中间件洋葱模型)、路由注册与保护机制差异,以及静态资源与资产加载的差异。

为直观呈现两者差异与集成切入点,见下表。

表 1:webman vs Laravel/Filament 关键差异与集成切入点

| 维度 | webman(常驻内存/事件驱动) | Laravel/Filament(FPM/服务提供者) | 集成切入点 |
|---|---|---|---|
| 运行模型 | 常驻内存、事件循环、非阻塞 I/O | 每次请求初始化(FPM) | 生命周期桥接、请求转换 |
| 路由机制 | config/route.php、fallback | PanelProvider 注册、web 中间件组 | 路由映射与保护策略 |
| 中间件体系 | 全局/应用/路由洋葱模型 | auth/guest/web 中间件组 | 中间件桥接与顺序治理 |
| 服务容器 | 可选 php-di 注入 | Laravel Container、Service Providers | 容器适配与接口绑定 |
| 数据库 | 连接池、长连接复用 | Eloquent ORM、迁移 | 连接适配与模型一致性 |
| 认证授权 | 需桥接 Laravel 能力 | Guards/Providers/Policies | 认证桥接与权限检查 |
| 静态资源 | 静态文件中间件与缓存 | 资产注册(packageBooted) | 资源管线与缓存策略 |
| 性能侧重 | 高并发、低延迟 | 生态与开发效率 | 性能优化与监控 |

上述差异与切入点决定了本方案采用“适配器分层 + 生命周期桥接 + 路由映射 + 中间件治理 + 资源管线”的总体设计路径[^4][^5][^6]。

---

## 总体架构蓝图与适配器分层

总体架构由以下层次构成:入口层(webman 事件循环接收请求)→ 路由映射层(Filament 面板路由注册与保护)→ 中间件桥接层(Laravel 中间件栈与 webman 洋葱模型对齐)→ 适配器层(桥接适配器、请求/响应转换器、服务容器适配层)→ 应用层(Filament 面板、资源、表单、表格、动作、通知与小组件)→ 基础设施层(数据库、缓存、静态资源与日志监控)。

核心适配器包括:
- 桥接适配器(Bridge):将 Filament 的面板、插件与资源注册生命周期与 webman 的启动与重载流程对齐;
- 请求/响应转换器(Translator):将 webman 的 Request/Response 与 Laravel 的 Illuminate Request/Response 进行双向转换;
- 服务容器适配层(Container Adapter):在 Laravel Container 与可选 php-di 之间进行接口绑定与解耦。

接口契约与边界:
- 路由注册:PanelProvider 在 webman 启动阶段完成注册,路由落在 web 中间件组下,受 auth/guest 等保护;
- 中间件顺序:在桥接层保持 Laravel 原生顺序,同时在 webman 洋葱模型中设定执行顺序;
- 容器绑定:以接口解耦,优先使用 Laravel Container;如需 php-di 注入,在适配层统一绑定;
- 错误处理:统一在桥接层捕获并转换为 webman 可消费的响应格式。

表 2:适配器接口与职责矩阵

| 接口/组件 | 输入 | 输出 | 依赖 | 错误边界 |
|---|---|---|---|---|
| Bridge | PanelProvider、插件列表 | 已注册面板路由与资源 | webman 启动流程、Laravel 服务提供者 | 启动异常、注册失败 |
| Translator | webman Request/Response | Illuminate Request/Response | Laravel 请求/响应契约 | 转换失败、类型错误 |
| Container Adapter | Laravel Container、php-di 配置 | 统一容器接口 | psr/container、php-di | 绑定失败、循环依赖 |

该分层确保 Filament 的组件在 webman 的运行模型下保持行为一致,同时将 Laravel 的服务契约映射到适配层,避免框架级耦合扩散[^7][^8][^9][^10]。

---

## 适配器模式设计

适配器模式是本方案的核心方法论,旨在以最小侵入方式将 Filament 的面板与组件嵌入到 webman 的生命周期与中间件体系中。

### 桥接适配器设计

桥接适配器负责在 webman 启动阶段加载 Filament 的 PanelProvider 与插件注册信息,并完成面板路由、页面、资源与资产的注册。考虑到 webman 的基础插件与应用插件机制,建议将 Filament 的面板注册视为“应用级插件”,在 reload 或 restart 后生效;同时对资产加载顺序进行治理,确保 Livewire、Alpine 与 Tailwind 的加载在 packageBooted 阶段完成[^11][^12][^13]。

表 3:桥接适配器 API 与生命周期事件

| 事件/方法 | 触发时机 | 职责 | 备注 |
|---|---|---|---|
| onStart | webman 启动 | 注册 PanelProvider、加载插件元数据 | 基础/应用插件识别与合并[^11][^12] |
| onReload | 重载时 | 更新路由与资源映射 | 应用插件需 restart/reload[^12] |
| onRouteRegistered | 路由注册后 | 校验保护策略与 fallback | 防止未授权访问与 404 漏斗 |
| onAssetsReady | 资产加载后 | 记录缓存清单与版本 | 与静态资源缓存策略联动 |

通过上述事件,桥接适配器将 Filament 的面板生命周期与 webman 的进程生命周期对齐,确保注册逻辑仅在启动或重载时执行,避免请求级副作用[^11][^12][^13]。

### 请求/响应转换器设计

转换器负责将 webman 的 Request/Response 与 Laravel 的 Illuminate Request/Response 进行双向转换,保证 Filament 的中间件、路由与控制器(Livewire 组件)按预期工作。转换器需处理以下要点:
- 请求构建:解析 webman 的请求头、参数与体,映射到 Illuminate Request;
- 响应转换:将 Illuminate Response 转译为 webman 可输出的响应(字符串/数组/Response 对象),保留状态码与头信息;
- 错误与异常映射:统一将 Laravel 的异常转换为 webman 的错误处理通道可消费的格式。

表 4:请求/响应字段映射表

| 字段 | webman → Laravel | Laravel → webman | 备注 |
|---|---|---|---|
| Method | 直接映射 | 直接映射 | REST/表单一致 |
| URI | 直接映射 | 直接映射 | 路由匹配 |
| Headers | 标准化头(含 Accept) | 透传或重写 | JSON 场景需 Accept: application/json[^14] |
| Query | 解析为 Illuminate Request->query | 回填到 webman Response | 分页/过滤一致 |
| Body | 解析为 Illuminate Request->request | 转译为输出体 | 表单/JSON 一致 |
| Cookies/Session | 通过桥接层获取 | 透传或重建 | 认证态维持 |
| Status/Headers | 从 Illuminate Response 读取 | 写入 webman Response | 错误页与重定向一致 |

通过该映射,转换器确保 Filament 的服务端交互(Livewire)与中间件(auth/guest/web)在桥接后行为不变[^14][^15]。

### 服务容器适配层设计

容器适配层在 Laravel Container 与可选 php-di 之间提供统一接口与绑定策略。原则是:优先使用 Laravel Container 完成服务解析;如需注解注入或接口替换,则在适配层显式绑定 php-di 配置,避免“手动 new”的对象无法享受自动注入[^16][^17][^18][^19]。

表 5:容器绑定清单(示例)

| 接口 | 实现 | 作用域 | 生命周期 |
|---|---|---|---|
| AuthManagerInterface | Laravel Auth | 全局 | 单例(进程级) |
| PolicyRegistryInterface | 自定义策略映射 | 面板 | 每次请求解析 |
| TranslatorInterface | 请求/响应转换器 | 适配层 | 单例 |
| ConnectionPoolInterface | 数据库连接池 | 基础设施 | 单例/长连接 |

通过该清单,容器适配层确保依赖注入的边界清晰、替换成本低,并与 webman 的进程生命周期一致[^16][^17][^18][^19]。

---

## 路由映射与中间件集成

Filament 的面板路由通过 PanelProvider 注册,通常落在 web 中间件组保护下,并与认证中间件协作。webman 的路由系统支持分组与 fallback,便于对未命中路由进行统一处理。将两者对齐的映射策略如下:在 webman 启动阶段完成面板路由注册;在 webman 路由层定义与 Filament 面板路径一致的分段;对未命中或未授权访问通过 fallback 与中间件返回统一错误响应。

表 6:Filament 路由 → webman 路由映射(示例)

| 面板路径段 | webman 路由定义 | 保护策略 | 备注 |
|---|---|---|---|
| /admin | group(['middleware' => ['web', 'auth']], function () { Filament::routes(); }) | auth/guest | 面板路由注册[^20][^21] |
| /admin/resources/{resource} | 同上 | 同上 | 资源 CRUD |
| /admin/pages/{page} | 同上 | 同上 | 自定义页面 |
| /admin/actions/{action} | 同上 | 同上 | 动作触发 |

中间件桥接的核心是将 Laravel 的 auth/guest/web 中间件栈与 webman 的洋葱模型对齐,确保前置与后置逻辑按预期执行。

表 7:中间件桥接对照

| Laravel 中间件 | webman 中间件 | 顺序 | 职责 |
|---|---|---|---|
| web(组) | 全局中间件 | 前置 | 会话、CSRF、基础设置[^22][^23] |
| auth | 路由中间件 | 前置 | 认证检查 |
| guest | 路由中间件 | 前置 | 游客放行/重定向 |
| throttle | 路由中间件 | 前置 | 限流 |
| 日志/错误 | 全局中间件 | 后置 | 统一响应与审计 |

静态资源处理方面,Filament 的资产(Livewire、Alpine、Tailwind)需在 packageBooted 阶段注册;webman 的静态资源中间件负责文件服务与缓存控制。建议建立资产清单与缓存策略,确保版本化与长缓存。

表 8:静态资源管线清单(示例)

| 资产类型 | 路径/版本 | 缓存策略 | 备注 |
|---|---|---|---|
| CSS(Tailwind) | /filament/assets/css?v=4.x | 长缓存 + 版本号 | 语义类覆盖[^24][^25] |
| JS(Livewire) | /filament/assets/livewire?v=4.x | 长缓存 + 版本号 | 组件交互 |
| JS(Alpine) | /filament/assets/alpine?v=x.y | 长缓存 + 版本号 | 轻量交互 |
| 图标/字体 | /filament/assets/fonts | 长缓存 | 品牌化资源 |

通过上述映射与桥接,面板路由与保护策略在 webman 环境下保持一致,静态资源以版本化与缓存控制实现高效分发[^20][^21][^22][^23][^24][^25]。

---

## 数据库迁移与模型适配

Filament 的数据访问依赖 Eloquent ORM。迁移与模型适配的目标是:在 webman 的连接池与长连接模型下,保持 Eloquent 的行为一致;在批量赋值与验证方面,维持表单 Schema 与模型策略对齐;在静态数据源场景下,通过 Sushi 保持与组件的一致集成。

表 9:数据库连接配置对照(示例)

| 环境 | 驱动 | DSN | 池配置 | 超时 |
|---|---|---|---|---|
| 开发 | mysql | host=127.0.0.1;dbname=app | 最小 2/最大 10 | 3s |
| 测试 | sqlite | /var/data/test.db | 最小 1/最大 5 | 3s |
| 生产 | mysql | host=db;dbname=app | 最小 4/最大 32 | 3s |

表 10:Eloquent 模型适配要点

| 要点 | 说明 | 风险 | 缓解 |
|---|---|---|---|
| 关联关系 | BelongsTo/HasMany 在表单/表格呈现 | N+1 查询 | 预加载与查询优化[^6][^26] |
| 批量赋值 | fillable/guarded 控制可写字段 | 越权写入 | 表单 Schema 与模型对齐[^6][^26] |
| 验证规则 | 字段级与服务器端校验 | 规则不一致 | 统一规则中心 |
| 静态数据源 | Sushi 提供数组到模型桥接 | 数据一致性 | 版本化与缓存[^27] |

迁移系统集成需遵循 Laravel 的迁移规范(创建表、修改字段、索引),在 webman 启动阶段执行迁移或提供迁移命令;在常驻内存下,连接池与长连接需与迁移脚本的锁策略协调,避免长时间阻塞[^28][^6][^26][^27]。

---

## 认证与权限系统集成

认证与授权桥接围绕 Laravel 的 Guards/Providers/Policies 展开。Filament 的资源类通过 Policies 完成操作级授权控制;在面板层面可启用 MFA(TOTP)以增强安全性;在复杂权限场景下,可采用 Shield 与 spatie 的组合实现角色与权限的细粒度控制。

表 11:认证方式与适用场景

| 方式 | 特性 | 场景 | 注意事项 |
|---|---|---|---|
| Session(Guard) | 会话状态、密码登录 | 后台面板 | 路由命名与视图一致[^29][^30] |
| MFA(TOTP) | 多因素验证 | 高安全场景 | 用户表字段与面板启用[^31] |
| Shield + spatie | 角色/权限细粒度 | 复杂权限管理 | 插件依赖与数据表[^32][^33][^34] |

表 12:权限检查点与策略映射

| 检查点 | 策略方法 | 资源层 | 动作层 |
|---|---|---|---|
| view | can('view', Model::class) | ListRecords | 列表/过滤 |
| create | can('create') | CreateRecord | 新建动作 |
| edit | can('update', $record) | EditRecord | 编辑动作 |
| delete | can('delete', $record) | ListRecords | 删除动作 |

常见问题包括:登录路由命名不匹配导致“Route [login] not defined”,以及多 Guard 场景下认证列名不一致导致重定向异常。解决方案是配置正确的命名路由与中间件、在 API 场景下通过 Accept: application/json 返回 JSON 响应、在多 Guard 下显式设置认证列与守卫[^29][^30][^35]。

---

## 配置系统集成

配置系统以 PanelProvider 为入口,统一管理面板、插件与主题。资产注册在 packageBooted 阶段完成,保证加载顺序与隔离性。环境变量处理遵循 Laravel 的配置加载约定,并在 webman 环境下以进程级单例读取,避免重复解析。

表 13:配置键空间与来源

| 键空间 | 来源 | 作用域 | 备注 |
|---|---|---|---|
| filament.panel | PanelProvider | 面板 | 面板级配置[^36] |
| filament.auth | Laravel Auth | 认证 | 守卫与提供者 |
| filament.plugins | 插件配置 | 插件 | 版本与启用状态 |
| filament.theme | Tailwind 主题 | 外观 | 语义类覆盖[^24][^25] |

扩展配置系统建议建立“配置台账”,记录键空间、来源、作用域与变更历史,便于升级与审计[^36][^37][^38][^39]。

---

## 生命周期管理

生命周期管理覆盖初始化与启动、服务注册与依赖管理、错误处理与日志系统。

- 初始化与启动:在 webman 启动时完成 PanelProvider 注册、插件元数据加载、路由与中间件装配;应用插件需 reload 或 restart 后生效;
- 服务注册与依赖管理:在适配层统一绑定接口与实现,避免请求级重复解析;控制器与组件的依赖由容器解析;
- 错误处理与日志系统:在桥接层捕获异常,统一转换为 webman 的错误响应;日志系统记录认证失败、权限拒绝与资源访问审计。

表 14:生命周期关键步骤与介入点

| 步骤 | 介入点 | 职责 | 备注 |
|---|---|---|---|
| 启动 | onStart | 注册面板与插件 | 基础/应用插件[^11][^12] |
| 路由注册 | onRouteRegistered | 保护策略与 fallback | 未授权与 404 |
| 请求处理 | 中间件桥接 | 认证/限流/日志 | 洋葱模型 |
| 响应输出 | Translator | 状态码与头信息 | JSON/HTML |
| 错误处理 | 统一捕获 | 转换与记录 | 审计与报警 |

通过上述流程,生命周期管理确保在常驻内存环境下各组件按预期初始化与协作[^40][^11][^12][^41]。

---

## 性能优化策略

在常驻内存与事件驱动模型下,性能优化的重点是:减少重复初始化、缩短 I/O 路径、提升连接复用、降低对象创建与回收频率。

表 15:优化项—效果—注意事项

| 优化项 | 预期效果 | 注意事项 |
|---|---|---|
| OPcache | 降低字节码重编译、稳定性能 | 合理缓存大小与失效策略[^5] |
| 进程配置 | 提升并发与 CPU 利用 | 匹配核数与负载、避免上下文切换[^5] |
| 连接池/长连接 | 降低连接开销、提升吞吐 | 管理生命周期与重试[^5][^42] |
| 控制器复用 | 降低对象创建/回收开销 | 避免请求级状态污染[^5] |
| 中间件集中化 | 简化业务层、统一横切逻辑 | 顺序与后置逻辑影响[^5] |

表 16:内存与进程阈值建议(示例)

| 进程类型 | 建议阈值 | 说明 |
|---|---|---|
| 业务进程 | 128M | 承载业务与缓存 |
| 消费者进程 | 64M | 任务处理相对单一 |
| 监控策略 | 内存监控 | 平滑重启与报警[^43] |

压测与监控建议以 RPS、响应时间与错误率为核心指标,结合进程内存与 CPU 使用率进行观测与回归[^5][^42][^43]。

---

## 实施路线图与里程碑

实施路线图分阶段推进,降低集成风险并确保每阶段可验证交付。

表 17:里程碑与交付物

| 阶段 | 目标 | 交付物 | 验收标准 |
|---|---|---|---|
| P1:适配器原型 | Bridge/Translator/Container 完成 | 适配器接口与事件清单 | 面板路由可注册与访问 |
| P2:路由与中间件桥接 | 路由映射与中间件顺序对齐 | 路由映射表与中间件对照 | 认证/授权与错误处理一致 |
| P3:数据库与模型适配 | 连接池与 Eloquent 一致 | 连接配置与模型适配表 | 迁移通过、CRUD 正常 |
| P4:认证与权限集成 | Policies/MFA/插件权限 | 认证矩阵与权限台账 | 未授权访问被正确拦截 |
| P5:配置与生命周期治理 | 配置台账与启动流程 | 配置清单与日志规范 | 重载/重启流程稳定 |
| P6:性能优化与压测 | OPcache/进程/连接池优化 | 优化对照与监控方案 | RPS/响应时间达标 |

在每阶段建立回归与基准测试,确保新增能力不影响既有功能[^11][^12][^44]。

---

## 风险与信息缺口及缓解策略

尽管方案具备可执行性,仍存在需通过实测与官方文档核对的边界与风险。

表 18:风险—影响—缓解—验证

| 风险 | 影响 | 缓解策略 | 验证方法 |
|---|---|---|---|
| 运行模型差异 | 生命周期不一致 | 生命周期桥接与事件对齐 | 启动/重载用例与日志 |
| 中间件顺序差异 | 认证/授权错序 | 顺序治理与回归测试 | 中间件覆盖测试[^22][^23] |
| 路由注册细节 | 未命中或保护失效 | 路由映射与 fallback | 路由覆盖与渗透测试[^20] |
| 容器与注解边界 | 注入失败或循环依赖 | 接口绑定与配置台账 | 单元测试与依赖图[^16][^17][^18][^19] |
| 插件生态接口差异 | 能力缺失或冲突 | 版本与兼容性台账 | 插件集成测试[^11][^12] |
| Laravel 11 兼容性 | 升级风险 | 兼容性矩阵核对 | 发布说明与回归套件[^6] |

信息缺口(需后续补充):
- Filament 官方仓库源码级类图与中间件栈细节;
- 多租户与 MFA 的数据库字段与配置的完整示例;
- 插件生态的完整接口清单与示例;
- 路由注册细节与中间件顺序的官方系统阐述;
- Laravel 11 与 Filament v4 的兼容性矩阵。

---

## 结论与架构建议

本方案以适配器分层与生命周期桥接为核心,将 Filament 的 SDUI 能力完整迁移到 webman 的常驻内存与事件驱动模型上,在不牺牲生态与开发体验的前提下获得显著的性能收益。工程建议如下:
- 权限与授权优先:以 Policies 与插件(如 Shield + spatie)构建清晰的授权边界,在动作层进行显式检查,满足审计与合规需求;
- 迁移与模型适配规范:严格遵循 Eloquent 的批量赋值与验证规则,避免表单与模型策略不一致;对静态数据源使用 Sushi 保持组件一致性;
- 路由与中间件一致性:确保登录路由命名与视图配置一致,在 API 场景下正确处理 JSON 响应与认证重定向;多 Guard 配置明确列名与守卫;
- 主题与插件治理:以 PanelProvider 统一入口管理插件与主题,资产注册置于 packageBooted(),建立版本与兼容性台账,降低升级成本;
- 性能与监控:启用 OPcache、合理配置进程与连接池、采用控制器复用与中间件集中化,以 RPS、响应时间与错误率为核心指标建立基准与回归。

该集成路径为后台管理与交互式应用的快速交付提供了可复制的工程实践,并在高并发与低延迟场景下发挥 webman 的架构优势[^1][^2][^3]。

---

## 参考文献

[^1]: 一样的写法,十倍的性能—webman 官网. https://www.workerman.net/webman  
[^2]: Filament - Accelerated Laravel development framework. https://filamentphp.com/  
[^3]: Filament 是什么? - 介绍 - Filament(4.x). https://laravel-filament.cn/docs/zh-CN/4.x/introduction/overview  
[^4]: webman 手册(总览). https://www.workerman.net/doc/webman/  
[^5]: 性能—webman 手册. https://www.workerman.net/doc/webman/others/performance.html  
[^6]: Eloquent: Getting Started - Laravel 11.x. https://laravel.com/docs/11.x/eloquent  
[^7]: Documentation - Filament. https://filamentphp.com/docs  
[^8]: GitHub - filamentphp/filament. https://github.com/filamentphp/filament  
[^9]: Laravel 中的服务提供者:Service Providers 是什么以及如何使用. https://learnku.com/laravel/t/67406  
[^10]: Packages - Laravel 官方文档. https://laravel.com/docs/packages  
[^11]: 基础插件—webman 手册. https://www.workerman.net/doc/webman/plugin/base.html  
[^12]: 应用插件—Webman(rmb.run). https://webman.rmb.run/webman/plugin/app.html  
[^13]: 插件开发 | Laravel Filament(3.x). https://docs.laravel-filament.cn/zh-CN/docs/3.x/panels/plugins/  
[^14]: How to fix Route [login] not defined. https://github.com/filamentphp/filament/discussions/5226  
[^15]: Middleware | Laravel中文文档(8.x). https://docs.golaravel.com/docs/8.x/middleware  
[^16]: 依赖注入—webman 手册. https://www.workerman.net/doc/webman/di.html  
[^17]: 依赖自动注入—Webman(rmb.run). https://webman.rmb.run/webman/di.html  
[^18]: 依赖自动注入—BhAdmin. https://www.bhadmin.cn/guide/di.html  
[^19]: PHP-DI 官方文档. https://php-di.org/doc/getting-started.html  
[^20]: 路由—webman 手册. https://www.workerman.net/doc/webman/route.html  
[^21]: 快速开始 | 《Laravel Filament 官方文档》(3.x). https://learnku.com/docs/laravel-filament/3.x/panels-getting-started/  
[^22]: Middleware | Laravel中文文档(8.x). https://docs.golaravel.com/docs/8.x/middleware  
[^23]: Middleware | Laravel 中文文档(5.8). https://www.laravel.ltd/en/5.8/middleware  
[^24]: Tailwind CSS - Border Radius. https://tailwindcss.com/docs/border-radius  
[^25]: 概述 - 核心概念 - Filament(3.x). https://www.laravel-filament.cn/docs/zh-CN/3.x/support/overview  
[^26]: Eloquent: Getting Started - Laravel 11.x. https://laravel.com/docs/11.x/eloquent  
[^27]: 概述 - 核心概念 - Filament(3.x). https://www.laravel-filament.cn/docs/zh-CN/3.x/support/overview  
[^28]: 数据库迁移 | Laravel 10.x(中文). https://learnku.com/docs/laravel/10.x/migrations/14885  
[^29]: Middleware | Laravel中文文档(8.x). https://docs.golaravel.com/docs/8.x/middleware  
[^30]: How to fix Route [login] not defined. https://github.com/filamentphp/filament/discussions/5226  
[^31]: 多因素认证 - 用户 - Filament(4.x). https://laravel-filament.cn/docs/zh-CN/4.x/users/multi-factor-authentication  
[^32]: 使用 Filament Shield 在 Filament 中添加角色和权限. https://juejin.cn/post/7418408705304264755  
[^33]: GitHub - althinect/filament-spatie-roles-permissions. https://github.com/althinect/filament-spatie-roles-permissions  
[^34]: GitHub - chiiya/filament-access-control. https://github.com/chiiya/filament-access-control  
[^35]: Different auth guard for filament authentication always redirecting on login page. https://stackoverflow.com/questions/78018492/different-auth-guard-for-filament-authentication-always-redirecting-on-login-pag  
[^36]: 快速开始 | 《Laravel Filament 官方文档》(3.x). https://learnku.com/docs/laravel-filament/3.x/panels-getting-started/  
[^37]: Packages - Laravel 官方文档. https://laravel.com/docs/packages  
[^38]: Spatie Laravel Package Training. https://spatie.be/products/laravel-package-training  
[^39]: Laravel Bootcamp - Blade Installation. https://bootcamp.laravel.com/blade/installation  
[^40]: 执行流程—Webman v1(rmb.run). https://v1.webman.rmb.run/guide/others/process.html  
[^41]: 基础插件—webman 手册. https://www.workerman.net/doc/webman/plugin/base.html  
[^42]: Webman 的性能分析:为何它能成为高性能PHP框架的新标杆?(腾讯云开发者社区). https://cloud.tencent.cn/developer/article/2579383  
[^43]: webman 内存监控与进程优化问答(官方问答). https://www.workerman.net/q/12287  
[^44]: GitHub—webman-starter(基于 Webman 的现代化项目骨架). https://github.com/Nobbyte/webman-starter