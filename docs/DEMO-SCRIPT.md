# 三分钟演示脚本

1. 调度使用 `/auth/login` 登入（账号 `dispatcher` / 密码 `dispatcher123`）并打开看板，确认当前在修=1、逾期=0、SLA=100%。
2. 使用 `/tickets` 创建一条新的报修，上传照片并标记严重度。
3. 通过 `/work-orders` 为新报修生成工单并派工给技师。
4. 技师在 `/work-orders/{id}` 页面点击开始，记录工时与处理措施。
5. 在工单中通过 `/inventory/transactions` 领料，展示库存扣减与并发保护。
6. 技师完成工单后提交验收，质检在移动端通过 `/work-orders/{id}/acceptance` 签字评分，若本次未使用耗材需勾选 `materials_confirmed` 以确认记录完备。
7. 刷新 `/reports/dashboard` 看板，展示SLA与耗材TOP实时更新。
8. 关闭工单后尝试再次领料，确认系统拒绝并提示工单已完结。
