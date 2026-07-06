# CI/CD Strategy

This repository uses GitHub Actions as the automation layer for pull request checks, Docker WordPress validation, scheduled compatibility checks, and release publishing. The CI/CD strategy explains how automation is organized; the QA strategy explains what confidence each check provides.

## Goals

1. Keep the default pull request path fast enough for routine contribution.
2. Require stronger evidence for runtime, ability, security-sensitive, and release-impacting changes.
3. Keep workflow permissions narrow and explicit.
4. Make every required status check name unique and stable for branch protection.
5. Preserve enough artifacts and PR comments to debug failures without rerunning everything locally.

## Workflow inventory

| Workflow | Primary trigger | Purpose |
| --- | --- | --- |
| `1 - Static QA` | `pull_request`, `push`, `workflow_dispatch` | PHP lint, WPCS, PHPStan, Composer audit, E2E manifest validation, security QA validation, and diff whitespace checks. |
| `2 - Unit Tests` | `pull_request`, `push`, `workflow_dispatch` | Fast PHPUnit helper tests. |
| `3-4 - Docker QA` | `pull_request`, `push`, `workflow_dispatch` | Runtime-impact detection, Ability Contract QA, Full MCP E2E QA, failure artifacts, and PR comment summaries. |
| `5 - Release Package QA` | release-impacting `pull_request`, `workflow_dispatch` | Contract + transport Docker QA, package validation, and WordPress Plugin Check without publishing. |
| `5 - Release` | tag push `v*` | Validates release inputs, runs Release Package QA, and publishes the GitHub release. |
| `6 - Compatibility QA` | weekly `schedule`, `workflow_dispatch` | Runs Docker QA against primary and floating-latest WordPress/PHP stacks. |

## Pull request policy

All pull requests should pass:

1. `1 - Static QA`
2. `2 - Unit Tests`

Runtime, ability, and security-sensitive pull requests should also pass:

1. `3 - Ability Contract QA`
2. `4 - Full MCP E2E QA`

Release-impacting pull requests should pass:

1. `5 - Release Package QA`

Compatibility QA starts as scheduled/manual. Promote matrix jobs to branch protection only after they are stable and low-noise.

## Branch protection

Branch protection should require unique status check names before merge. GitHub warns that duplicate job names across workflows can create ambiguous status checks, so workflow and job display names should remain stable and unique.

Recommended `main` protection:

1. Require pull request before merge.
2. Require `1 - Static QA` and `2 - Unit Tests`.
3. Require `3 - Ability Contract QA` and `4 - Full MCP E2E QA` for runtime-impacting PRs through process/checklist discipline.
4. Require conversation resolution.
5. Require linear history if repository settings continue to disallow merge commits.
6. Do not allow force pushes or branch deletion.

## Workflow permissions

Use least privilege per job:

- Read-only jobs use `contents: read`.
- PR comment jobs use `pull-requests: write` and avoid checking out PR-controlled code.
- Release publishing uses `contents: write` only in the tag release workflow.
- Secrets should not be needed for PR validation from forks. If future workflows require secrets, use environments and reviewer gates.

## Scheduling policy

Scheduled jobs should detect drift that a PR did not cause:

- `6 - Compatibility QA` runs weekly to catch upstream WordPress, PHP, MCP Adapter, Yoast SEO, SEOPress, Plugin Check, and Docker image changes.
- Scheduled failures should create maintainer follow-up work only after triage confirms the failure is not transient infrastructure noise.
- Compatibility failures are release blockers only when they affect the supported primary stack or a supported version combination.

## Artifact and reporting policy

Docker jobs should upload artifacts on failure:

- Docker Compose logs
- WordPress debug log
- E2E summary JSON when available
- MCP CRUD summary JSON when available

PR comments should summarize runtime facts rather than static success claims:

- tested commit
- workflow run
- ability coverage counts
- pass/fail counts
- debug-log status
- tested WordPress/PHP/MySQL/plugin versions

## Failure handling

1. Fix `1 - Static QA` failures first; they are usually syntax, standards, static-analysis, security-policy, dependency, manifest, or whitespace issues.
2. Fix `2 - Unit Tests` before Docker failures; unit failures often indicate helper contract drift.
3. For Docker failures, inspect the PR comment and uploaded artifacts.
4. For release failures, distinguish package metadata/content failures from Plugin Check failures.
5. For scheduled compatibility failures, reproduce manually before changing required branch protection.

## Official references

- GitHub Docs: [Continuous integration](https://docs.github.com/en/actions/get-started/continuous-integration)
- GitHub Docs: [Workflow syntax for GitHub Actions](https://docs.github.com/en/actions/reference/workflows-and-actions/workflow-syntax)
- GitHub Docs: [About protected branches](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-protected-branches/about-protected-branches)
- GitHub Docs: [Secure use reference](https://docs.github.com/en/actions/reference/security/secure-use)
- GitHub Docs: [About releases](https://docs.github.com/en/repositories/releasing-projects-on-github/about-releases)
