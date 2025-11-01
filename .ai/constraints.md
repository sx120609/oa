# 设备全生命周期管理 API 约束

## 架构与运行时
- 使用 **无框架** 原生 PHP 实现，所有请求经 `public/api.php` 单入口调度。
- 依赖 PDO 扩展，数据库仅支持 MySQL 连接（通过环境变量配置）。
- 统一使用 `config.php` 管理数据库及认证参数。

## 认证与安全
- 所有请求必须在 HTTP 头携带 `X-Api-Key`，默认值为 `devkey`。
- 使用 `hash_equals` 校验 API Key，认证失败返回 `401 Unauthorized`。

## 路由与响应规范
- 采用极简正则路由表，支持 GET/POST/PATCH 等常用动词。
- 响应统一为 JSON：成功 `{ "data": ... }`，失败 `{ "error": { "message": ..., "code"?: ..., "details"?: ... } }`。
- 全局捕获异常，向客户端隐藏内部错误细节。

## 业务状态与审计
- 资产状态 `assets.status`：`in_stock`、`in_use`、`under_repair`。
- 维修单状态 `repair_orders.status`：`created`、`repairing`、`qa`、`closed`。
- 每次状态变更写入 `asset_logs`，记录 `asset_id`、`from_status`、`to_status`、`action`、`request_id`、`created_at`。

## 幂等与业务约束
- 资产领用接口按业务单号 `no` 去重：
  - 已存在同号记录且资产一致时返回幂等成功。
  - 不一致时返回 `409 assignment_conflict`。
- 领用成功后资产状态置为 `in_use`；返还时恢复 `in_stock`。
- 派修时资产状态转为 `under_repair`，关闭维修单时可恢复库存。

## 目录结构约定
```
/                        # 根目录
├─ config.php            # 全局配置
├─ app/                  # 业务类库 (Database, Response, HttpException 等)
├─ public/api.php        # 单入口路由 & 控制器
├─ .ai/constraints.md    # 本约束文档
└─ docs/domain.md        # 领域说明文档
```

## 请求示例
```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: devkey" \
  -d '{"name":"Laptop A","serial_number":"SN123"}' \
  http://localhost:8000/api.php/assets
```

## 错误码表
| HTTP 状态 | `code` 字段         | 场景说明                     |
|-----------|----------------------|------------------------------|
| 400       | `invalid_json`       | JSON 解析失败                |
| 401       | `unauthorized`       | API Key 缺失或错误          |
| 404       | `not_found`          | 资产或维修单不存在          |
| 409       | `invalid_state`      | 状态不允许的操作            |
| 409       | `assignment_conflict`| 领用单号与资产冲突          |
| 422       | `validation_error`   | 请求参数校验失败            |
| 500       | *(无)*               | 未捕获内部错误              |
