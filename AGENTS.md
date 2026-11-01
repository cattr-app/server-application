# Agent guidelines

## Language

All public artifacts (pull requests, issues, comments, changelogs, release notes, commit messages, documentation) are written in English, regardless of the language of the conversation with the user.

## Issues

Every new issue gets exactly one of each:

- **Type:** `Bug` (unexpected problem or behavior), `Task` (a specific piece of work) or `Feature` (a request, idea, or new functionality).
- **Priority:** `P0`–`P3`.
- **Effort:** `XS`, `S`, `M`, `L`, `XL` (`XL` means consider decomposition).

Set them through the GitHub API (issue type and issue fields), not in the issue text. If the API or token does not allow it, leave the issue text unchanged and report the choice, with a one-line rationale, to the user who requested the issue.

## Pull requests

- Link the issue the PR is made for: `Closes #N` when the PR fully resolves it, otherwise split the issue so that each PR closes one. Verify the PR appears in the issue's Development section.
- Do not copy the issue content (context, scope, checklists) into the PR description. Describe only what is relevant to the implementation: what this PR changes, how it was verified, breaking changes, and what is left.
