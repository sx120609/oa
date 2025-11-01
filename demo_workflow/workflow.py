"""Workflow model for the demo development process.

This module contains a light-weight workflow engine that mirrors the
highlighted "Demo开发部分" column from the provided process diagram.  The
structure is intentionally data driven so that the CLI can produce a step by
step explanation of the demo lifecycle without requiring a backing database
or web service.
"""
from __future__ import annotations

from dataclasses import dataclass, field
from typing import Dict, Iterable, List, Sequence


@dataclass(frozen=True)
class WorkflowStep:
    """Representation of a single workflow step.

    Attributes
    ----------
    key:
        Unique identifier for the step.  Keys are used to define relationships
        between steps.
    name:
        Human readable label that matches the terminology in the original
        workflow diagram.
    description:
        Short explanation of the deliverables that must be completed before
        the workflow can progress to the next step.
    owner:
        The primary role responsible for driving the step to completion.
    outputs:
        Tangible artefacts created while executing the step.
    next_steps:
        Keys of the steps that can follow the current one.
    """

    key: str
    name: str
    description: str
    owner: str
    outputs: Sequence[str]
    next_steps: Sequence[str] = field(default_factory=tuple)


class DemoWorkflow:
    """In memory representation of the demo development workflow."""

    def __init__(self) -> None:
        self._steps: Dict[str, WorkflowStep] = {step.key: step for step in _build_steps()}

    def __contains__(self, key: str) -> bool:  # pragma: no cover - convenience wrapper
        return key in self._steps

    def __getitem__(self, key: str) -> WorkflowStep:
        try:
            return self._steps[key]
        except KeyError as exc:  # pragma: no cover - translated into ValueError for callers
            raise ValueError(f"Unknown workflow step: {key}") from exc

    @property
    def steps(self) -> Sequence[WorkflowStep]:
        """Expose the steps in their logical order."""

        ordered_keys = [
            "collect_requirements",
            "scope_review",
            "solution_design",
            "development",
            "internal_rehearsal",
            "client_demo",
            "feedback_iteration",
        ]
        return tuple(self._steps[key] for key in ordered_keys)

    def next_steps(self, current_key: str) -> Sequence[WorkflowStep]:
        """Return the steps that can follow ``current_key``.

        Parameters
        ----------
        current_key:
            Key of the step from which the transition should start.
        """

        return tuple(self._steps[key] for key in self[current_key].next_steps)

    def as_markdown(self) -> str:
        """Render the workflow as a Markdown document.

        The output is helpful for the README and for debugging while running
        the CLI.
        """

        lines: List[str] = ["# Demo 开发流程", ""]
        for position, step in enumerate(self.steps, start=1):
            lines.append(f"## 步骤 {position}: {step.name}")
            lines.append("")
            lines.append(step.description)
            lines.append("")
            lines.append(f"- 负责人: {step.owner}")
            if step.outputs:
                lines.append("- 产出:")
                lines.extend(f"  - {item}" for item in step.outputs)
            if step.next_steps:
                labels = ", ".join(self._steps[key].name for key in step.next_steps)
                lines.append(f"- 下一步: {labels}")
            lines.append("")
        return "\n".join(lines).strip()


def _build_steps() -> Iterable[WorkflowStep]:
    """Create the static workflow definition.

    The wording of each step mirrors the highlighted demo swim-lane in the
    customer facing process diagram.  The scope focuses on the internal team
    that needs to prepare, rehearse and deliver a polished demo.
    """

    return [
        WorkflowStep(
            key="collect_requirements",
            name="梳理 Demo 需求",
            description=(
                "与销售和客户梳理 Demo 演示目标、痛点与成功标准，形成明确的需求清单。"
            ),
            owner="售前顾问",
            outputs=("Demo 需求清单", "场景优先级"),
            next_steps=("scope_review",),
        ),
        WorkflowStep(
            key="scope_review",
            name="评审 Demo 范围",
            description=(
                "召集产品、交付与研发评估需求的可行性，确认所需系统与数据接口。"
            ),
            owner="产品经理",
            outputs=("评审结论", "风险记录"),
            next_steps=("solution_design",),
        ),
        WorkflowStep(
            key="solution_design",
            name="设计演示方案",
            description=(
                "确定 Demo 的目标用户旅程，输出原型脚本、数据准备计划以及所需的环境配置。"
            ),
            owner="UX/解决方案架构师",
            outputs=("演示脚本", "Demo 环境规划", "数据准备清单"),
            next_steps=("development",),
        ),
        WorkflowStep(
            key="development",
            name="开发并组装 Demo",
            description=(
                "按照方案搭建 Demo 环境，完成前后端配置、数据灌入与必要的自动化脚本。"
            ),
            owner="研发工程师",
            outputs=("Demo 可执行版本", "部署与回滚脚本"),
            next_steps=("internal_rehearsal",),
        ),
        WorkflowStep(
            key="internal_rehearsal",
            name="内部彩排",
            description=(
                "由售前主持进行端到端演练，记录问题并验证各角色的讲解节奏。"
            ),
            owner="售前顾问",
            outputs=("演练记录", "修复列表"),
            next_steps=("client_demo", "feedback_iteration"),
        ),
        WorkflowStep(
            key="client_demo",
            name="客户演示",
            description=(
                "面向客户交付正式演示，确保覆盖关键价值点并收集现场反馈。"
            ),
            owner="售前团队",
            outputs=("客户反馈", "后续行动清单"),
            next_steps=("feedback_iteration",),
        ),
        WorkflowStep(
            key="feedback_iteration",
            name="复盘与迭代",
            description=(
                "总结演示效果，安排缺陷修复或功能增强，为下一轮演示或投标做准备。"
            ),
            owner="项目经理",
            outputs=("复盘报告", "优化计划"),
            next_steps=(),
        ),
    ]
