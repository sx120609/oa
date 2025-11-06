# API 概览

## 认证
- `POST /auth/login`：使用预置账号获取演示 Token。

## 报修（Ticket）
- `POST /tickets`：创建报修，字段：`asset_id`, `symptom`, `severity`, `photos[]`。
- `GET /tickets?status=NEW`：查看新报修列表。
- `POST /work-orders`：由 `ticket_id` 生成工单。

## 工单（WorkOrder）
- `POST /work-orders/{id}/assign`：派工，字段：`assignee_id`, `eta`。
- `POST /work-orders/{id}/start`：开始工单。
- `POST /work-orders/{id}/pause`：暂停工单。
- `POST /work-orders/{id}/resume`：恢复工单。
- `POST /work-orders/{id}/complete`：完成工单并填写 `labor_minutes`, `result`。
- `POST /work-orders/{id}/acceptance`：验收，字段：`passed`, `score`, `remarks`, `photos[]`, `materials_confirmed`（当无耗材记录时需显式确认）。

## 备件与库存
- `GET /spares?keyword=`：备件列表与搜索。
- `POST /inventory/transactions`：库存事务，字段：`work_order_id`, `spare_id`, `qty`, `type` (`issue` 或 `return`)。

## 附件与二维码
- `POST /attachments`：上传文件并绑定到 `ticket` 或 `work_order`。
- `GET /assets/{id}/qrcode`：返回设备二维码。

## 看板报表
- `GET /reports/dashboard`：返回 `in_progress_count`, `overdue_count`, `sla_rate`, `top_spares`, `my_labor_minutes`。
