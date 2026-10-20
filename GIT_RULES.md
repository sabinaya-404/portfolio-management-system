\# Git Rules



\## 1. Core Principle



Use Git to protect working code and maintain a clean, understandable history.



Follow this workflow:



\*\*Inspect → Change → Test → Review → Commit\*\*



Keep changes small and focused. Do not overcomplicate the workflow.



\## 2. Before Making Changes



\- Check the current branch and working tree before editing.

\- Review existing changes before touching relevant files.

\- Never overwrite or discard existing user changes without permission.

\- Do not mix unrelated fixes, features, refactoring, or formatting changes.

\- Never commit secrets, credentials, private data, or unnecessary generated files.



\## 3. Commit Rules



\- Make one logical change per commit.

\- Test the change before committing whenever practical.

\- Commit each completed function or focused task separately when appropriate.

\- Use clear, concise commit messages.



Examples:



\- `fix: verify login passwords correctly`

\- `fix: prevent unauthorized portfolio access`

\- `feat: validate Demat account details`

\- `docs: update setup instructions`



\- Never add `Co-authored-by` trailers.

\- Never add AI attribution, AI signatures, or AI-generated-by notices to commit messages or commit metadata.

\- Do not change the configured Git author identity.

\- Do not amend, rewrite, or remove existing commits unless explicitly requested.

\- Do not push changes unless requested.



\## 4. Review Before Committing



Run:



```bash

git status --short

git diff

git diff --check

```



A `git diff --check` warning may be treated as a CRLF-only false positive only when the flagged file uses CRLF, the flagged lines contain no genuine trailing spaces or tabs, and `git -c core.whitespace=cr-at-eol diff --check` passes. If genuine trailing whitespace exists, fix it instead of using this exception.

Stage only the files relevant to the current task. Prefer explicit file paths:



```bash

git add path/to/file.php

```



Review the staged changes:



```bash

git diff --cached

```



Commit only after confirming that the staged changes are intentional and the relevant checks have passed.



\## 5. Testing



\- Run relevant tests or verification commands before committing.

\- Check important failure cases, not just the successful path.

\- Never claim a test passed unless it was actually executed.

\- If a test fails, investigate before committing.

\- If testing is impossible, explain why before proceeding with a commit.



\## 6. Protect Existing Work



\- Never use destructive Git commands without understanding their effects and obtaining permission when they could discard work.

\- Avoid `git reset --hard`, `git clean -fd`, and broad restore or checkout commands unless explicitly authorized.

\- Never discard unrelated working-tree changes.

\- Back up important database data before risky schema changes. Git does not protect database contents.

\- If unexpected changes appear, stop and inspect them instead of cleaning them up automatically.



\## 7. Branches and Remote Operations



\- Use the existing branch unless a separate branch is genuinely necessary.

\- Do not create extra branches or pull requests just for ceremony.

\- Do not force-push.

\- Do not rewrite published history.

\- Do not push or publish local work without authorization.



\## 8. Markdown and Local Instruction Files



\- Do not add, stage, commit, or track Markdown (`.md`) files.

\- This applies to project goals, Git rules, agent instructions, notes, plans, reports, and other Markdown documentation.

\- Do not add Markdown files to `.gitignore`.

\- Do not modify `.gitignore` to exclude Markdown files.

\- Keep local-only Markdown files untracked.

\- If an existing tracked Markdown file must be changed for a task, stop and ask before staging or committing it.



\## 9. Completion Report



After each focused task, report:



1\. What changed.

2\. What tests or checks were run and their actual results.

3\. Whether the change was committed and the commit hash.

4\. Any remaining issues.



Do not claim a commit was created unless Git confirms it.



\## 10. Final Rule



Prefer the smallest clear change that satisfies the requirement.



Protect working code, existing user changes, database integrity, and Git history. Avoid unrelated cleanup and unnecessary process.
