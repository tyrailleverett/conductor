---
description: Commit changes with commitlint style and push to origin
---

Commit all staged and unstaged changes using conventional commit (commitlint) format and push to remote origin.

First, check the current git status to see what files have changed:
!`git status --short`

Then review the diff to understand the changes:
!`git diff --cached --stat`
!`git diff --stat`

Based on the changes, determine the appropriate conventional commit type:
- feat: A new feature
- fix: A bug fix
- docs: Documentation only changes
- style: Changes that don't affect code meaning (formatting, semicolons, etc.)
- refactor: Code change that neither fixes a bug nor adds a feature
- perf: Performance improvement
- test: Adding or correcting tests
- chore: Changes to build process or auxiliary tools

Stage all changes:
!`git add -A`

Create a commit with a properly formatted conventional commit message that describes the changes. Use the format: type(scope): description

For example:
- "feat(auth): add OAuth2 login support"
- "fix(api): resolve null pointer in user endpoint"
- "docs(readme): update installation instructions"
- "style(button): fix indentation in component"
- "refactor(utils): simplify date parsing logic"
- "test(unit): add coverage for edge cases"
- "chore(deps): update dependencies"

After committing, push to the remote origin:
!`git push origin $(git branch --show-current)`

Report back the commit hash and the branch that was pushed.
