# GitHub Talks Repo Sync

Status: concept / not built. For users with a linked GitHub account, Lecturn
creates and maintains a `talks` monorepo on their GitHub that mirrors their
talks. One-way projection: Lecturn is the source of truth, GitHub is a backup
and a public speaker portfolio. Reinforces the product positioning (path to
stage, visible practice) and doubles as organic marketing since every synced
repo says "managed by Lecturn".

## Decisions (settled at concept stage)

- **One-way sync only.** Lecturn → GitHub. Edits made on GitHub are never
  pulled back; DeckAssembler invariants and branding lock mean arbitrary
  markdown edits can't be trusted anyway. Two-way is out of scope, possibly
  forever.
- **GitHub App, not classic OAuth.** Classic `repo` scope grants access to all
  of a user's repos, which is a scary consent screen. A GitHub App is
  fine-grained (user can restrict it to just the talks repo), and gives us
  webhooks for free if we ever revisit two-way. Needs the **Administration**
  account permission to create the repo; fallback is a one-time flow where the
  user creates the repo themselves and installs the app on it.
- **Monorepo, not repo-per-talk.** One repo named `talks` (fallback
  `lecturn-talks` on name conflict), one folder per talk. Reads better as a
  portfolio; single-talk sharing works via folder links.
- **No local git.** Push via GitHub's Git Data API (blobs → tree → commit →
  update ref) so each deck version lands as one atomic commit. No clone, no
  working directory, no shelling out.

## Repo shape (sketch)

```
talks/
├── README.md              ← auto-regenerated index: talk list, dates,
│                            versions; activity graph.svg embedded
├── intro-to-cqrs/
│   ├── slides.md          ← markdown source (config.markdown / markdown export)
│   ├── notes.md           ← speaker notes
│   ├── deck.json          ← full deck config for round-tripping into Lecturn
│   ├── assets/            ← images, size-capped
│   └── exports/
│       └── embed.html     ← self-contained embed build
└── another-talk/
```

Folder name = talk slug. Rename on Lecturn side = move in the next commit
(delete old tree path, write new one).

### Bundled /dev/null workflow

Scaffold a `.github/workflows/devnull.yml` into the repo, mirroring this
project's own workflow: on a published release (i.e. a major talk version
tag), commit a short note to the user's [dev-null.sh](https://dev-null.sh)
microblog via `nunomaduro/dev-null.sh/.github/actions/commit` ("Released
{talk} v2.0" + link). Opt-in: the workflow is inert until the user adds their
own `DEVNULL_SSH_KEY` secret, so shipping it by default is harmless. Requires
us to actually publish GitHub **releases** for major versions, not just tags
(see version mapping above).

## Version mapping

Talks already group deck versions (major.minor), which maps directly onto git:

- New deck version → one commit, message like `intro-to-cqrs v1.3`.
- Major version → annotated tag `intro-to-cqrs/v2.0`, published as a GitHub
  release (release publication is what triggers the bundled /dev/null
  workflow below).
- Commit author: a Lecturn bot identity; the user appears as committer via the
  app installation, or just bot-authored with the user in the message. TBD.

## Architecture fit

Pure projection, very CQRS: core data is never at risk, GitHub being down
never blocks the user.

- Domain event (e.g. `DeckVersionCreated` / `DeckVersionPublished`) → queued
  job → `GitHubTalkSyncer` infrastructure service.
- Installation + repo metadata (installation id, repo full name, sync status,
  last synced version) on a `github_integrations` table keyed to the user
  (or team, later).
- Failures retry with backoff; surface a "last synced" indicator in the UI
  rather than erroring the save path.
- If the user edited the repo out-of-band and the ref moved, do **not** force
  push `main`. Either write to a `lecturn` branch, or overwrite only the files
  we own on top of their HEAD. Lean toward overwrite-on-main with a clear
  "managed by Lecturn" banner in the README; force-pushing a user's repo is
  rude.

## What stays out of the repo

- Rehearsal audio (large, private, weird in a portfolio).
- Remote tokens, anything secret-ish from `talk_settings`.
- Private decks unless the user opts in: default the repo to **private** and
  let the user flip it public, or let them exclude talks per-talk.

## Commitments this creates

- `deck.json` in a user's repo is a de facto export format. People will expect
  import to work and the format to stay stable. That's a feature ("your data
  is yours") but it means versioning the schema from day one
  (`"schema": 1` field, migration on import).
- `slides.md` should match the markdown export format
  ([export-markdown.md](./export-markdown.md) / [dsl.md](./dsl.md)): emit
  `config.markdown` verbatim for markdown-backed slides, serialize blocks
  otherwise.

## Open questions

- Team talks: repo on the personal account for v1; GitHub org support later.
- Initial backfill: sync all existing talks on install (one commit per talk,
  or one big "initial import" commit?), or only talks touched after install.
- Per-talk opt-out vs. all-or-nothing sync toggle.
- Whether `exports/embed.html` is worth the repo weight, or should be a
  release asset per tag instead.
