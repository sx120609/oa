# Demo 开发流程示例

该仓库实现了一个轻量级的“Demo 开发流程”模型，帮助团队快速理解并复盘流程图中高亮的 `Demo开发部分`。项目提供：

- 结构化的流程数据模型，覆盖梳理需求、范围评审、方案设计、开发联调、内部彩排、客户演示以及复盘迭代等关键环节；
- 命令行工具，可用于列出流程步骤、导出 Markdown 说明、或模拟执行流程；
- 单元测试，确保流程顺序与节点信息的正确性。

## 快速开始

1. 安装依赖（Python 3.11+，无需额外三方库）；
2. 在仓库根目录执行下面的命令。

### 列出流程步骤

```bash
python -m demo_workflow.cli list
```

### 导出 Markdown 说明

```bash
python -m demo_workflow.cli markdown
```

### 模拟执行 Demo 流程

```bash
python -m demo_workflow.cli run
```

可通过 `--start` 参数指定从任意节点开始模拟：

```bash
python -m demo_workflow.cli run --start internal_rehearsal
```

### 运行测试

```bash
pytest
```

## 目录结构

```
demo_workflow/
  __init__.py      # 暴露 DemoWorkflow 与 WorkflowStep
  workflow.py      # 核心数据模型与 Markdown 导出
  cli.py           # 命令行工具

tests/
  test_workflow.py # 工作流相关的单元测试
```

## 后续扩展建议

- 为每个节点补充耗时、输入材料等更多元数据；
- 接入真实项目管理系统的数据源，自动更新节点状态；
- 将 `DemoWorkflow` 暴露为 REST API，供外部系统查询或编排。
