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
| `5 - Release` | tag push `v*` | Validates release inputs, runs Release Package QA, waits for protected WordPress.org approval, deploys to SVN, and publishes the GitHub release. |
| `6 - Compatibility QA` | weekly `schedule`, `workflow_dispatch` | Discovers official upstream releases, tests baseline and candidate WordPress/MCP Adapter combinations, and opens a reviewed baseline-update PR after successful scheduled tests. |

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
- Release publishing uses `contents: write` only in the protected publish job of the tag release workflow.
- Secrets should not be needed for PR validation from forks.
- WordPress.org SVN credentials are GitHub Actions secrets named `SVN_USERNAME` and `SVN_PASSWORD`. Prefer storing them as `wordpress-org` environment secrets so they are unavailable until the protected environment is approved. Repository-level GitHub Secrets can work as a fallback, but the deploy job should still use the protected environment gate.

## Environment gates

The `wordpress-org` GitHub Environment protects the production SVN publish step. Release QA runs before the workflow reaches that environment, so normal validation does not need SVN credentials.

Recommended `wordpress-org` environment settings:

1. Require manual approval before deployment.
2. Restrict secrets to the environment when possible.
3. Store the WordPress.org SVN-specific password, not the normal account password.
4. Treat approval as confirmation that the validated tag should publish to WordPress.org production SVN.

There is no separate WordPress.org test SVN. Use pull request checks, `5 - Release Package QA`, manual compatibility checks, and staging WordPress installs for pre-production confidence.

## Scheduling policy

Scheduled jobs should detect drift that a PR did not cause:

- `6 - Compatibility QA` runs weekly to query the official WordPress version API and latest stable MCP Adapter GitHub release, then catches upstream WordPress, PHP, MCP Adapter, Yoast SEO, SEOPress, Plugin Check, and Docker image changes.
- Compatibility lanes pull fresh Docker images and record resolved runtime versions in the job summary so failures can be attributed to the support floor, pinned baseline, latest WordPress, latest MCP Adapter, or a combined update.
- Passing newer versions produce a version-specific pull request that updates concrete pins but never auto-merges. The workflow dispatches the normal QA workflows for the bot branch so required checks still run.
- Scheduled failures should create maintainer follow-up work only after triage confirms the failure is not transient infrastructure noise.
- Compatibility failures are release blockers only when they affect the supported floor, current WordPress line, or another supported version combination.

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
4. For release failures, distinguish package metadata/content failures, Plugin Check failures, protected environment approval issues, SVN deployment failures, and GitHub Release creation failures.
5. For scheduled compatibility failures, reproduce manually before changing required branch protection.

## Official references

- GitHub Docs: [Continuous integration](https://docs.github.com/en/actions/get-started/continuous-integration)
- GitHub Docs: [Workflow syntax for GitHub Actions](https://docs.github.com/en/actions/reference/workflows-and-actions/workflow-syntax)
- GitHub Docs: [About protected branches](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-protected-branches/about-protected-branches)
- GitHub Docs: [Secure use reference](https://docs.github.com/en/actions/reference/security/secure-use)
- GitHub Docs: [About releases](https://docs.github.com/en/repositories/releasing-projects-on-github/about-releases)
