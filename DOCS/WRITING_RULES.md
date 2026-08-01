# WRITING RULES

Rules for all writing in this project: chat replies, documentation, commit
messages, PRDs, plans, code comments.

Set on 2026-08-01 by the project owner. Reason: long replies waste tokens and
time. Write only what is needed.

## Core rules

1. No em dash. Use a comma, a colon, or a full stop instead.
2. Not verbose. Cut every word that adds nothing.
3. Clear, concise, comprehensive. Short does not mean incomplete. Cover the whole
   answer, then stop.
4. No bombastic words. No filler praise, no hype, no padding.
5. Simple English. Plain words over fancy ones.

## What this looks like

| Avoid | Use |
|---|---|
| leverage, utilise | use |
| facilitate, enable | let, help |
| in order to | to |
| it is worth noting that | (delete it, just say the thing) |
| comprehensive, robust, seamless, powerful | (delete it, or say what it does) |
| Great question! | (delete it) |

More habits to keep:

- Lead with the answer. Put the reason after it, not before.
- One idea per sentence.
- Use tables and lists when they beat prose. Do not use them for everything.
- State facts, not confidence. "Tests pass" not "Tests pass perfectly".
- Say what is not done, and why, in one line.
- Do not repeat what was already said in the conversation.
- Do not explain what you are about to do, then do it, then explain what you did.
  Do it, then report once.

## Commit messages

All rules above apply, plus normal industry practice.

Format:

```
<type>: <subject, imperative, max 72 chars, no full stop>

<body: wrapped at 72 chars. What changed and why. Not how.>
<Blank line between paragraphs. Use - for lists.>

<footer: Refs #12, BREAKING CHANGE:, Co-Authored-By:>
```

Types: `feat`, `fix`, `docs`, `refactor`, `perf`, `test`, `build`, `ci`, `chore`.

Rules:

- Imperative mood in the subject. "add share buttons" not "added" or "adds".
- Subject says what changed. Body says why it changed.
- One logical change per commit.
- For a release commit, state the version: `feat: v1.5.0 self-hosted fonts`.

Example:

```
feat: v1.4.2 single post share, back, and author links

Add Threads, X, and Telegram share buttons via a reusable share-links
helper and an extended icon set. Facebook, LinkedIn, and WhatsApp stay.

Back button now targets the post category archive, falling back to
Newsroom when the post has no category. Author name links to the author
archive.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>
```

## Documentation

- Every doc starts with what it is for, in one or two lines.
- Link to code instead of copying it.
- Date anything that will go stale.
- Update the existing doc. Do not add a second doc on the same topic.

## PRDs and plans

Keep to these sections. Drop any that is empty.

1. Goal. One or two lines.
2. Scope. What is in, what is out.
3. Approach. The chosen way, and why. Not every option considered.
4. Steps. Numbered, each one testable.
5. Risks. Only real ones, with what to do about them.
6. Done when. The check that proves it works.
