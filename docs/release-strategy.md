# Release Strategy

Release strategy explains how reviewed repository changes become a GitHub release and a WordPress.org plugin update. CI/CD strategy explains the automation mechanics; QA strategy explains the validation layers.

## Release goals

1. Ship only reviewed, tested, package-ready plugin files.
2. Keep GitHub release artifacts, WordPress.org SVN state, plugin headers, `readme.txt`, and changelogs aligned.
3. Avoid publishing a release until release package QA, Plugin Check, and security-sensitive validation pass.
4. Make hotfixes fast without bypassing release safety checks.

## Versioning policy

This project uses semantic versioning:

| Version part | Use for |
| --- | --- |
| Major | Breaking ability names, inputs, outputs, authentication/permission model, or MCP client compatibility. |
| Minor | Backward-compatible abilities, features, compatibility support, or user-visible enhancements. |
| Patch | Bug fixes, security fixes, packaging fixes, documentation corrections, and compatibility patches. |

Security fixes can be patch releases when the public API remains compatible.

## Release readiness checklist

Before tagging a release:

1. Choose the version bump.
2. Update the plugin header `Version`.
3. Update `readme.txt` `Stable tag`.
4. Move plugin-facing notes from `CHANGELOG.md` `## Unreleased` into the release section.
5. Add matching `readme.txt` changelog and upgrade notice entries.
6. Keep repository-only notes in `.github/REPOSITORY_CHANGELOG.md`.
7. Run or confirm `composer qa`.
8. Run or confirm `composer qa:release`.
9. Review the latest `6 - Compatibility QA` result for upstream drift.
10. Confirm no unexpected Plugin Check findings remain.

## GitHub release flow

The tag workflow owns GitHub release creation:

1. Maintainer pushes `vX.Y.Z`.
2. `.github/workflows/release.yml` validates the tag, plugin header, stable tag, text domain, short description, release notes, and duplicate release state.
3. `scripts/release-qa.sh` runs Ability Contract QA and Full MCP E2E QA, builds the release zip, validates package contents, and runs WordPress Plugin Check.
4. GitHub release is created with the built zip and notes from `readme.txt`.

Do not manually upload an unvalidated zip to GitHub releases.

## WordPress.org release flow

WordPress.org uses SVN as the release repository. GitHub remains the development repository.

Recommended process:

1. Download the GitHub release zip after the tag workflow passes.
2. Inspect or extract the package if needed.
3. Commit finished release files to WordPress.org SVN `trunk`.
4. Copy or tag the release to the matching SVN `tags/X.Y.Z` path.
5. Confirm `Stable tag` points to the intended release.
6. Verify the WordPress.org plugin page updates as expected.

Avoid frequent small SVN commits. WordPress.org guidance treats SVN as a release repository, not the day-to-day development repository.

## WordPress.org guideline governance

Before tagging or uploading a release, review the official Detailed Plugin Guidelines for the release diff and final package. For this repository, the highest-risk guideline areas are:

| Guideline area | Repository implication |
| --- | --- |
| GPL compatibility and responsibility for contents | Confirm all bundled code, screenshots, images, fonts, and libraries are GPL-compatible and intentionally included in the release zip. |
| Stable WordPress.org distribution | Keep the WordPress.org package current with released GitHub code; do not distribute a newer stable plugin only through alternate channels. |
| Human-readable code and build tooling | Do not ship obfuscated code. If build tooling becomes necessary, keep source/build instructions public and maintained. |
| External services and tracking consent | Document any external service dependency in `readme.txt` and require explicit consent before contacting external servers for tracking or non-essential functionality. |
| Executable third-party code | Do not load executable plugin/theme/update code from non-WordPress.org systems; bundle non-service JavaScript and CSS locally. |
| Readme and public-page hygiene | Keep `readme.txt` useful for people, avoid keyword stuffing, keep tags to five or fewer, and disclose any affiliate/service links. |
| WordPress default libraries | Use WordPress-bundled libraries instead of shipping duplicate copies of libraries WordPress already provides. |
| SVN release discipline | Treat SVN as the release repository, use descriptive commit messages, avoid rapid minor readme/package churn, and tag each release once ready. |
| Version increments | Increment the plugin version for every user-facing release and keep the plugin header, `Stable tag`, changelog, Git tag, and SVN tag aligned. |
| Trademarks and project names | Avoid names/slugs that imply ownership of WordPress, MCP Adapter, SEO plugins, or other third-party projects. |

## Hotfix policy

Use a hotfix release when a shipped version has a user-impacting bug, security issue, packaging error, or compatibility break.

Hotfix steps:

1. Branch from current `main`.
2. Make the smallest safe fix.
3. Add a regression test, manifest case, or security QA policy check when feasible.
4. Run the normal release readiness checklist.
5. Publish a patch version unless compatibility requires a larger version bump.
6. Document the issue clearly in `CHANGELOG.md` and `readme.txt`.
7. Use a GitHub security advisory when the fix addresses an exploitable vulnerability.

## Rollback policy

Prefer forward fixes over deleting or rewriting release history.

If a release is bad:

1. Stop promoting the release.
2. Open a hotfix issue/PR immediately.
3. If WordPress.org users are exposed to a severe issue, coordinate with WordPress.org plugin support/review channels.
4. Publish a corrected patch release.
5. Document what happened in maintainer-facing notes and, if user-facing, in release notes.

## Official references

- GitHub Docs: [About releases](https://docs.github.com/en/repositories/releasing-projects-on-github/about-releases)
- GitHub Docs: [Repository security advisories](https://docs.github.com/en/code-security/concepts/vulnerability-reporting-and-management/repository-security-advisories)
- WordPress Plugin Handbook: [Using Subversion](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/)
- WordPress Plugin Handbook: [Detailed Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
- WordPress.org: [Plugin Check](https://wordpress.org/plugins/plugin-check/)
- WordPress Plugin Handbook: [Plugin readmes](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/)
