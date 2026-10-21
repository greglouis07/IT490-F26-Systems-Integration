# GitHub Rules and Workflow

## Branch | Purpose | When it updates
- master | Final graded branch | Large Milestones pulled from test branch (needs 1 approval)
- test | Must always be functioning to pull from | ONLY Pull requests
- yourBranch | temporary branch for each feature | Push freely (auto deleted when merged)

## Workflow
1. Start every new piece of work from the latest test
- git switch test
- git pull
- git switch -c yourNewBranch

2. Commit after every check that passes.
**Never switch branches with uncommitted changes**
- git status
- git add <file>
- git commit -m "<what changed>"

3. Push your branch
- git push -u origin yourNewBranch

4. Open a pull request on GitHub.com
- Pull requests → New pull request
- Set base to test (GitHub may pre-select master; change it)
- Set compare to yourNewBranch
- Check the top of the page says it wants to merge into test
- Create pull request, and say how you tested it

5. Merge
Look at the owner of the folder you changed, then Merge pull request → Confirm merge.

6. Clean up and tell the group
- git switch test
- git pull
- git branch -d yourNewBranch

**Post in Discord what changed. Everyone else runs git pull on test**

**For the next piece of work, go back to step 1. Don't reuse an old branch; it's behind test**

# Getting a deleted branch back
On GitHub: open the merged pull request and click Restore branch.
From your machine: your local branch still exists.
  - git switch yourNewBranch
  - git push -u origin yourNewBranch

# If something goes wrong
Problem: Push to test rejected,
Solution: Expected. Push your own branch and open a pull request.

Problem: Pull request says "Can't automatically merge",
Solution: On your branch, run git fetch then git merge origin/test, fix conflicts, commit, push, then retry. Or use Resolve conflicts on the pull request page.

Problem: Merge conflict on your machine,
Solution: git merge --abort returns you to before the merge.

**Don't use git reset --hard**
