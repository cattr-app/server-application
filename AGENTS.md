# Agent guidelines

## Language

Everything public is written in English: pull requests, issues, comments on them, changelogs, release notes, commit messages and documentation. This applies regardless of the language used in the conversation with the user.

## Issues

When creating an issue:

1. **Type.** Set exactly one of the organization's issue types:
   - `Bug`: an unexpected problem or behavior.
   - `Task`: a specific piece of work.
   - `Feature`: a request, idea, or new functionality.
2. **Priority.** Pick one of `P0`, `P1`, `P2`, `P3` (issue field "Priority": current importance assigned to the issue).
3. **Effort.** Pick one of `XS`, `S`, `M`, `L`, `XL` (issue field "Effort": relative level of effort; `XL` means consider decomposition).

Set the type, priority and effort through the GitHub API (issue type and issue fields) rather than in the issue text. If the available API or token does not allow setting them, do not write them into the issue body or title; report the chosen type, priority and effort, with a one-line rationale, to the user who requested the issue.

## Pull requests

- Link the issue the PR is made for: `Closes #N` when the PR fully resolves it, otherwise split the issue so that each PR closes one. Verify the PR appears in the issue's Development section.
- Do not copy the issue content (context, scope, checklists) into the PR description. Describe only what is relevant to the implementation: what this PR changes, how it was verified, breaking changes, and what is left.
