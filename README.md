# Device Lifecycle API Skeleton

This repository provides a minimal, framework-free PHP project skeleton for building a device lifecycle management API. It demonstrates a single-entry HTTP endpoint, simple routing, PDO database access, and basic project structure ready for extending with real business logic.

## Project layout

```
public/           # Web root containing single entry point and optional rewrite rules
src/              # Configuration, bootstrap logic, helper utilities, and request handlers
scripts/          # CLI scripts such as database initialisation
migrations/       # SQL migration files with schema and seed data
```

## Requirements

- PHP 8.1+
- MySQL 8+ with PDO MySQL extension

## Getting started

1. **Install dependencies** – none required beyond PHP and PDO extensions.
2. **Configure environment (optional)** – set environment variables to override defaults:
   - `API_KEY` (default `devkey`)
   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_CHARSET`
3. **Initialise the database**:
   ```bash
   php scripts/init_db.php
   ```
4. **Run the development server**:
   ```bash
   php -S 0.0.0.0:8000 -t public
   ```
5. **Send a test request** (replace API key if customised):
   ```bash
   curl -H "X-Api-Key: devkey" http://127.0.0.1:8000/assets
   ```

## Frontend usage

Use the bundled static UI to interact with the API without additional tooling:

1. Start the built-in PHP server:
   ```bash
   php -S 0.0.0.0:8000 -t public
   ```
2. Open a browser at [http://localhost:8000](http://localhost:8000).
3. Use the navigation to create assets and review the reports pages rendered by the frontend.

## Automated smoke check

Run the bundled script to rebuild the schema, boot the built-in server, and exercise
the health, asset creation, assignment, return, and optimistic-lock conflict flows.
The script exits non-zero if any command fails.

```bash
scripts/check.sh
```

By default it targets `http://127.0.0.1:8000`, uses the API key from the
`API_KEY` environment variable (falling back to `devkey`), and issues parallel
assignment requests to confirm the idempotent and conflict paths.

## Container usage

### Built-in PHP server

Build and run the CLI container that serves `public/` via PHP's built-in server:

```bash
docker compose up --build app
```

The service binds to `http://127.0.0.1:8000` and watches the project directory via a
bind mount, so local code edits are reflected immediately.

### Optional Nginx + PHP-FPM stack

Launch the optional two-container stack (enabled with the `nginx` profile) to proxy
through Nginx and execute PHP via FPM:

```bash
docker compose --profile nginx up --build
```

The Nginx virtual host is defined in `deploy/nginx.conf` and exposes the API at
`http://127.0.0.1:8000`. Adjust the compose environment variables to target your
MySQL instance.

## 部署教程（中文）

下面提供一份从零开始的部署指引，覆盖本地开发环境与容器化方案：

### 1. 准备运行环境

- **必需软件**：PHP 8.1 及以上版本、PDO MySQL 扩展、MySQL 8.0+。
- **可选工具**：Docker / Docker Compose（如需容器部署）。

### 2. 配置环境变量

应用通过环境变量读取数据库与鉴权信息，可在运行前按照需要导出：

```bash
export API_KEY=devkey              # 调用方需要在请求头 X-Api-Key 中携带
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_DATABASE=assets
export DB_USERNAME=root
export DB_PASSWORD=secret
export DB_CHARSET=utf8mb4
```

> **提示**：如果数据库账号拥有建库权限，可先手动创建数据库 `assets`（或自行指定其他名称）。

### 3. 初始化数据库

执行迁移脚本创建表结构和示例数据：

```bash
php scripts/init_db.php
```

成功后会输出建表与插入种子数据的日志。

### 4. 启动后端 API

使用 PHP 内建服务器托管 `public/` 目录：

```bash
php -S 0.0.0.0:8000 -t public
```

服务启动后，可通过 `http://localhost:8000` 访问。

### 5. 健康检查与基础验证

使用 `curl` 检查健康接口与资产列表：

```bash
curl -H "X-Api-Key: ${API_KEY:-devkey}" http://127.0.0.1:8000/health
curl -H "X-Api-Key: ${API_KEY:-devkey}" http://127.0.0.1:8000/assets
```

当返回形如 `{"data":...}` 的 JSON 即表示服务正常。

### 6. 使用前端界面

浏览器打开 `http://localhost:8000`，顶部导航可切换“资产管理”“维修管理”“报表”，页面中的表单和按钮会直接调用后端 API 完成创建资产、领用/归还、查看维修成本报表等操作。

### 7. 运行自动化冒烟脚本（可选）

脚本会重建数据库、启动临时服务器，并串行执行健康、资产创建、领用、归还与冲突测试：

```bash
scripts/check.sh
```

若任何步骤失败，脚本会以非零状态退出，便于在 CI/CD 中用作快速验证。

### 8. Docker / Docker Compose 部署

**开发模式（PHP 内建服务器）：**

```bash
docker compose up --build app
```

容器启动后监听 `http://127.0.0.1:8000`，并将当前项目挂载为卷以便热更新。

**可选：Nginx + PHP-FPM 组合**

```bash
docker compose --profile nginx up --build
```

该模式将流量先经过 Nginx，再转发给 PHP-FPM，示例虚拟主机配置位于 `deploy/nginx.conf`，可根据需要调整 upstream、域名与 SSL 设置。

### 9. 生产环境建议

- 将 `API_KEY` 设置为高强度随机字符串，并在调用方同步更新。
- 使用 `scripts/init_db.php` 仅在初次部署或版本升级需要时运行；日常请使用成熟的迁移管理工具。
- Nginx 或其他前置代理需限制来源 IP、开启 HTTPS，并配置请求超时与日志。

## Next steps

Implement application logic inside the handler classes under `src/Handlers`, utilising the shared helpers in `src/helpers.php` for routing, database access, and JSON responses.

## License

MIT
