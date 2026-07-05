## Deployment & Server Environment
The user writes code locally (XAMPP) but tests and runs the application on a **live cPanel server**. 
- **Troubleshooting**: When addressing runtime issues, server configurations (e.g., `php.ini` limits like `upload_max_filesize`), or database errors, always assume the issue is occurring on the live cPanel server unless stated otherwise. Provide solutions tailored to cPanel (e.g., using "MultiPHP INI Editor" or "Select PHP Version"). Do NOT suggest XAMPP-specific fixes for runtime errors.
- **Version Control**: When completing a task, making code changes, or ending a work session in this workspace, always stage, commit, and push the changes to GitHub automatically.
Run the following commands:
1. `git add .`
2. `git commit -m "<descriptive message>"`
3. `git push origin main`
This is required to trigger the GitHub Actions auto-deployment pipeline to the cPanel live server.
