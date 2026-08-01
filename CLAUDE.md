# CLAUDE.md

Rules for Claude Code in this repository. Read `DOCS/` for the full context.

## Model: plan on the default model, code on Sonnet 5

All coding runs on **Sonnet 5**. Planning, research and review run on whatever
model the session starts with.

After the user approves a plan, **stop before writing any code** and say:

> Plan approved. Switch to Sonnet 5 now: run `/model sonnet`. Tell me when you
> have, and I will start.

Do not edit project files until the user confirms the switch. If the user says to
proceed anyway, proceed and note which model is in use.

"Coding" means writing or editing project files: PHP, CSS, JS, config, templates.
It does not include doc edits in `DOCS/` that are part of planning.

Full reasoning: [DOCS/DECISIONS.md](DOCS/DECISIONS.md#d20).

## Writing

All output follows [DOCS/WRITING_RULES.md](DOCS/WRITING_RULES.md): no em dash, not
verbose, clear and complete, no bombastic words, simple English. This covers chat
replies, docs, commit messages, PRDs and plans. Commit messages also follow
conventional commit format.

## Before editing

This repo is worked on from two machines. Run `git pull --ff-only origin main`
first. When releasing, push `main` before pushing the `vX.Y.Z` tag.

Never run a scripted bulk find-and-replace over source files. Edit one change at a
time, then lint and grep for damage. See
[DOCS/DECISIONS.md](DOCS/DECISIONS.md#d22).

## Project context, and how much of it to read

Read only what the task needs. None of these load automatically.

| File | What it is | How to read it |
|---|---|---|
| [DOCS/STATE.md](DOCS/STATE.md) | Current version, layout, scope boundary, open items, release steps | Whole file. It is short and stays short. Start here |
| [DOCS/DECISIONS.md](DOCS/DECISIONS.md) | Why the code is the way it is | Search for the topic, then read that entry. Read it whole only when reviewing the design |
| [DOCS/PROJECT_LOG.md](DOCS/PROJECT_LOG.md) | What happened and when | **Top 40 lines by default**, which is the provenance note plus the newest entries. Read further only when the task is about older history |
| [DOCS/WRITING_RULES.md](DOCS/WRITING_RULES.md) | Writing standard | Whole file, it is small |

When PROJECT_LOG.md passes about 200 lines, move entries older than the current
year into `DOCS/archive/PROJECT_LOG-<year>.md` and link it from the top.

## Plugin specifics

**Files that cannot move or be renamed.**

- `metcpt.php`. It carries the plugin header. Its path is stored in the
  `active_plugins` option and used by the update checker, so moving or renaming it
  deactivates the plugin on every live site.
- `uninstall.php`. WordPress only runs it from the plugin root.
- `readme.txt`. WordPress reads it from the plugin root.
- `libs/plugin-update-checker/`. Third party, YahnisElsts PUC v5.7. Do not edit
  it. Its own `composer.json` and `vendor/` belong to it and are not a leak.

**The fragile parts.**

- `set_exception_handler()` and `set_error_handler()` in
  `includes/admin/error-log.php` install site-wide on every request. They must
  rethrow after logging. Making them swallow again produces a silent white screen.
  See [DOCS/DECISIONS.md](DOCS/DECISIONS.md#d12).
- The Haraka migration in `metcpt.php` must run **before**
  `metcpt_error_log_create_table()`. If the order flips, the legacy table can no
  longer be renamed.
- All four CPTs use `has_archive => false` and singular rewrite slugs. Turning
  archives on makes them fight the Elementor Pages for `/events/`, `/tenders/` and
  `/careers/`. See [DOCS/DECISIONS.md](DOCS/DECISIONS.md#d5).
- Before adding any shortcode tag, grep all of `wp-content/plugins/` for it.
  Another plugin on this site already collided once.

**Release steps.**

1. Bump the version in `metcpt.php` in two places, the `Version:` header and
   `METCPT_VERSION`. They must match each other and the tag.
2. Bump `Stable tag:` in `readme.txt` and add a changelog and upgrade-notice
   entry.
3. `php -l` every changed file. PHP CLI is not on PATH, it is under
   `C:\Users\IIUM Holdings\AppData\Roaming\Local\lightning-services\php-*\bin\win64\php.exe`.
4. Commit, push `main`, then push an annotated `vX.Y.Z` tag. Only the tag ships
   anything.
5. Never build the release zip locally. `release.yml` builds it on Linux because
   PowerShell `Compress-Archive` writes backslash paths that corrupt on
   extraction. That caused a live outage.
