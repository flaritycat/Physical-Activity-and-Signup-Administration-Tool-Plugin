# PASAT GitHub Publishing Guide

This repository is intended to publish to:

```text
https://github.com/flaritycat/Physical-Activity-and-Signup-Administration-Tool-Plugin
```

The local plugin work is ready to publish when `git status --short --branch` is clean and the release checks pass. In this container, publishing is currently blocked because HTTPS has no username/token, SSH has no accepted key, `gh` is not authenticated, and the GitHub integration has read-only access.

## Preferred Publishing

Use one authenticated path:

```text
gh auth login
gh auth status
git push origin main
```

or configure an SSH key that GitHub accepts:

```text
ssh -T git@github.com
git push origin main
```

After pushing, confirm GitHub Actions passes and retain the release ZIP/checksum artifact or build one locally with `tools/build-release.sh`.

## Offline Handoff

If this environment still cannot push, export the unpushed commits:

```text
tools/export-publish-handoff.sh
```

The script writes ignored files under `dist/publish-handoff-<sha>/`:

- a git bundle containing the unpushed commits
- a numbered patch series
- a manifest with commit list and apply commands

Copy that handoff directory to a machine with GitHub write access.

## Apply Bundle On An Authenticated Machine

```text
git clone https://github.com/flaritycat/Physical-Activity-and-Signup-Administration-Tool-Plugin.git pasat-publish
cd pasat-publish
git fetch /path/to/pasat-<base>-to-<head>.bundle HEAD:pasat-handoff
git checkout pasat-handoff
tools/check-release.sh
git push origin pasat-handoff:main
```

## Apply Patch Series On An Authenticated Machine

```text
git clone https://github.com/flaritycat/Physical-Activity-and-Signup-Administration-Tool-Plugin.git pasat-publish
cd pasat-publish
git am /path/to/patches/*.patch
tools/check-release.sh
git push origin main
```

Prefer the bundle path when possible because it preserves commit objects exactly. Use the patch series if a bundle is inconvenient for the receiving environment.
