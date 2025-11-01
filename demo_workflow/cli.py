"""Command line entry points for the demo workflow."""
from __future__ import annotations

import argparse
from typing import Iterable

from .workflow import DemoWorkflow, WorkflowStep


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="Demo 开发流程演示工具")
    subparsers = parser.add_subparsers(dest="command")

    subparsers.add_parser("list", help="列出 Demo 开发的关键步骤")
    subparsers.add_parser("markdown", help="以 Markdown 形式输出完整流程")

    run_parser = subparsers.add_parser("run", help="模拟执行 Demo 开发流程")
    run_parser.add_argument(
        "--start",
        default="collect_requirements",
        help="从指定步骤开始模拟，默认从梳理需求开始",
    )

    return parser


def _format_step(step: WorkflowStep, prefix: str = "") -> Iterable[str]:
    yield f"{prefix}{step.name}"
    yield f"{prefix}  负责人: {step.owner}"
    if step.outputs:
        yield f"{prefix}  产出: {', '.join(step.outputs)}"
    if step.next_steps:
        yield f"{prefix}  下一步: {', '.join(step.next_steps)}"


def main(argv: list[str] | None = None) -> int:
    parser = build_parser()
    args = parser.parse_args(argv)

    workflow = DemoWorkflow()

    if args.command == "list":
        for index, step in enumerate(workflow.steps, start=1):
            print(f"[{index}] {step.name} - {step.description}")
        return 0

    if args.command == "markdown":
        print(workflow.as_markdown())
        return 0

    if args.command == "run":
        current = workflow[args.start]
        visited = {current.key}
        queue = [current]

        while queue:
            step = queue.pop(0)
            for line in _format_step(step):
                print(line)
            print()

            for next_step in workflow.next_steps(step.key):
                if next_step.key not in visited:
                    visited.add(next_step.key)
                    queue.append(next_step)
        return 0

    parser.print_help()
    return 0


if __name__ == "__main__":  # pragma: no cover
    raise SystemExit(main())
