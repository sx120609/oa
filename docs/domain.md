# 设备全生命周期领域说明

## 1. 资产模型
- **资产 (assets)**：记录设备名称、序列号、当前状态及领用人。
- 状态机：`in_stock` → `in_use` → `in_stock`，或在任一状态进入 `under_repair`。
- 仅支持使用 MySQL 存储，通过环境变量配置连接信息。

## 2. 领用与归还
- **领用**：`POST /assets/{id}/assign`
  - 请求体示例：
    ```json
    {
      "assigned_to": "alice",
      "no": "REQ-20240101-001",
      "note": "办公笔记本"
    }
    ```
  - 必须包含幂等号 `no`，服务端对同号请求判重。
  - 成功后资产状态改为 `in_use`，并记录 `asset_logs`。
- **归还**：`POST /assets/{id}/return`
  - 将状态恢复到 `in_stock`，清空领用人并写入日志。

## 3. 维修单管理
- **创建维修单**：`POST /assets/{id}/repairs`
  - 资产状态强制变为 `under_repair`。
  - 维修单状态初始为 `created`。
- **状态流转**：`PATCH /repair-orders/{id}/status`
  - 合法状态：`created` → `repairing` → `qa` → `closed`。
  - 关闭维修单时资产可恢复 `in_stock`。
- **查询**：
  - `GET /repair-orders` 查看所有维修单。
  - `GET /assets/{id}/logs` 查看资产审计记录。

## 4. 报表与关键用例
- 资产列表：`GET /assets?status=in_stock` 按状态筛选库存设备。
- 维修跟踪：结合 `repair_orders` 与 `asset_logs` 分析维修耗时。
- 领用审计：通过 `asset_logs` 及 `asset_assignments` 追溯设备领用历史。
- 合规报表：定期导出 MySQL 数据生成资产台账。

## 5. 认证与接入
- 所有 API 请求需携带 `X-Api-Key: devkey`（可配置）。
- 默认部署命令：
  ```bash
  php -S 0.0.0.0:8000 -t public
  ```
- 需设置 `DB_HOST`、`DB_PORT`、`DB_DATABASE`、`DB_USERNAME`、`DB_PASSWORD`、`DB_CHARSET` 以连接 MySQL。

## 6. 错误处理
| HTTP 状态 | `code` 字段           | 说明                           |
|-----------|------------------------|--------------------------------|
| 400       | `invalid_json`         | 请求体解析失败                 |
| 401       | `unauthorized`         | API Key 无效或缺失             |
| 404       | `not_found`            | 资源不存在                     |
| 409       | `invalid_state`        | 资产当前状态不允许操作         |
| 409       | `assignment_conflict`  | 幂等单号已被其他资产使用       |
| 422       | `validation_error`     | 参数校验错误                   |
| 500       | *(无)*                 | 服务端未知错误                 |

## 7. 示例调用
```bash
curl -X PATCH \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: devkey" \
  -d '{"status":"qa"}' \
  http://localhost:8000/repair-orders/1/status
```
