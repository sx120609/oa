from demo_workflow.workflow import DemoWorkflow


def test_workflow_ordering():
    workflow = DemoWorkflow()
    names = [step.name for step in workflow.steps]
    assert names == [
        "梳理 Demo 需求",
        "评审 Demo 范围",
        "设计演示方案",
        "开发并组装 Demo",
        "内部彩排",
        "客户演示",
        "复盘与迭代",
    ]


def test_next_steps_mapping():
    workflow = DemoWorkflow()

    assert [step.key for step in workflow.next_steps("collect_requirements")] == [
        "scope_review"
    ]
    assert [step.key for step in workflow.next_steps("internal_rehearsal")] == [
        "client_demo",
        "feedback_iteration",
    ]


def test_markdown_contains_all_steps():
    workflow = DemoWorkflow()
    markdown = workflow.as_markdown()

    for step in workflow.steps:
        assert step.name in markdown
        assert step.description in markdown
