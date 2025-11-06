# 资产管理最小骨架

基于 PHP 8.2 的最小化样板，包含单入口路由、Dotenv 配置、CSRF 防护、PDO 数据持久化以及审计日志，全流程覆盖项目与设备的预留、借用、归还操作。

## 功能概览

- 单入口 `public/index.php` 管理 GET/POST 路由。
- 控制器覆盖登录、项目/设备创建、预留、领用、归还等核心流程。
- `App\Utils\DB` 封装 PDO 连接，所有写入均使用预处理语句。
- `.env` + Composer PSR-4 自动加载，统一环境配置。
- `app/Views/home.php` 提供单页 HTML 控制台，内置 6 个带 CSRF 隐藏字段的表单。
- 所有写操作落入 `audit_logs`，响应遵循 `OK` / `ERROR: <原因>` 文本规范。
- `bin/notify_due.php` 定时脚本：扫描即将到期的借用记录并生成通知。
- `tests/e2e.sh` 伪 e2e 脚本，串联种子数据、接口调用与数据库校验。
- 扩展锚点：设备转交、处罚、延期审批，预留路由与服务骨架。

## 环境要求

- PHP ≥ 8.2，启用 `pdo_mysql` 扩展；
- Composer ≥ 2.0；
- MySQL ≥ 8.0；
- curl、bash（用于 e2e 脚本）。

## 快速上手

```bash
git clone <repo> oa
cd oa
composer install
cp .env .env.local  # 如需备份，可自行保留模板
```

编辑 `.env`：

```env
APP_ENV=local
APP_DEBUG=true
APP_KEY=base64:...             # 可用 `php -r "echo base64_encode(random_bytes(32));"` 生成
DB_DSN=mysql:host=127.0.0.1;port=3306;dbname=asset_pm;charset=utf8mb4
DB_USER=your_user
DB_PASS=your_password
UPLOAD_PATH=data/uploads       # 默认写入项目外部目录
SESSION_COOKIE_SECURE=false    # 生产环境务必改为 true（HTTPS only）
```

### 数据库初始化

```bash
mysql -u root -p -e "CREATE DATABASE asset_pm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p asset_pm < app/Migrations/001_init.mysql.sql
```

`app/Migrations/002_future_extensions.mysql.sql` 为未来扩展占位文件。

### 本地开发服务

```bash
php -S 127.0.0.1:8000 -t public
```

浏览器打开 [http://127.0.0.1:8000/](http://127.0.0.1:8000/) 即可使用单页控制台，建议依次体验：

1. 登录（需要先向 `users` 手工插入账号）；
2. 创建项目；
3. 创建设备；
4. 预留设备；
5. 设备领用（借出）；
6. 设备归还（可尝试超期触发通知）。

每个 POST 表单都会返回纯文本 `OK` 或 `ERROR: ...`，所有操作都会写入 `audit_logs`。

### 插入测试账号

```sql
INSERT INTO users (email, name, password_hash, role, created_at)
VALUES (
  'owner@example.com',
  'Owner',
  '$2y$10$K8zVvN6wX6Zl6VdI6yYB1.MX4T5xFZbCW9HEIblzEl3bLTsayDb/m',
  'owner',
  NOW()
)
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash);
```

如需自建密码哈希：

```bash
php -r "echo password_hash('secretpass', PASSWORD_BCRYPT), PHP_EOL;"
```

### 到期提醒脚本

```bash
php bin/notify_due.php
```

示例 Crontab（每小时运行一次）：

```
0 * * * * cd /path/to/project && /usr/bin/php bin/notify_due.php >> storage/logs/notify.log 2>&1
```

### 伪 E2E 脚本

条件：

- 确保 `php -S 127.0.0.1:8000 -t public` 已启服务；
- 本机可执行 `mysql` 命令并有库表权限（可通过 `MYSQL_URI` 自定义连接字符串）。

执行：

```bash
./tests/e2e.sh
```

脚本流程：

1. 插入测试用户；
2. 登录成功/失败用例；
3. 创建项目（正常 & 时间非法）；
4. 创建设备（正常 & code 重复）；
5. 设备预留（正常 & 冲突）；
6. 设备借用（正常 & 状态非法）；
7. 设备归还（正常 & 无借用 & 超期触发通知）；
8. 运行到期提醒脚本；
9. 汇总输出 `audit_logs`、`devices`、`notifications` 关键记录。

### 安全基线

- 所有 POST 必须携带 CSRF Token，校验失败返回 `419`；
- 会话 Cookie 默认 HttpOnly，生产环境请开启 `SESSION_COOKIE_SECURE=true`；
- 所有 SQL 写入使用 PDO 预处理，杜绝拼接；
- 视图输出使用 `escape()`（`htmlspecialchars`）；
- 文件上传尚未实现，未来实现时请写入 `UPLOAD_PATH` 并校验 MIME/大小；
- 所有写操作记录审计日志（删除敏感字段后存入 detail JSON）；
- 异常路径统一 `error_log` 记录请求方法与 URI。

### 项目结构

```
app/
  Controllers/         # 控制器：Auth、Project、DeviceFlow、Extension 等
  Services/            # 服务层：AuditLogger、PenaltyService、TransferService...
  Utils/               # 工具类：DB、Env、Router、Response
  Views/               # 视图：单页控制台模板
bin/
  notify_due.php       # 定时脚本
public/
  index.php            # 单入口
tests/
  e2e.sh               # 伪 e2e 测试脚本
.env                   # 环境变量配置
composer.json          # Composer 依赖配置 / 自动加载
```

### 扩展锚点

- 设备转交：`DeviceFlowController@transferRequest` / `transferConfirm`，`TransferService` 已预留；
- 处罚系统：`PenaltyService::ensureEligibleForCheckout`，未实现时返回 501；
- 延期审批：`ExtensionController` 与 `ExtensionService` 留有 TODO；
- 迁移文件 `002_future_extensions.mysql.sql` 提供 penalties/extensions/transfers 表结构草案。

### 常见问题排查

- **419 CSRF**：刷新首页获取新 Token，确认浏览器允许保存 Cookie；
- **数据库异常**：检查 DSN 配置、数据库是否存在、表结构是否已迁移；
- **Session 不生效**：清理浏览器 Cookie 或重启 PHP 内建服务器；
- **审计/通知未出现**：直接执行查询：
  `SELECT id, action, entity_type, detail, created_at FROM audit_logs ORDER BY id DESC LIMIT 10;`

### 许可证

MIT License（可按需替换）。欢迎提交 Issue 或 PR 扩充功能。
