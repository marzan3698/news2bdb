## Deployment & Version Control
When completing a task, making code changes, or ending a work session in this workspace, always stage, commit, and push the changes to GitHub automatically. 
Run the following commands:
1. `git add .`
2. `git commit -m "<descriptive message>"`
3. `git push origin main`
This is required to trigger the GitHub Actions auto-deployment pipeline to the cPanel live server.
