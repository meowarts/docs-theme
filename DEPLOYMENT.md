# Deployment Setup

## GitHub Actions SFTP Deployment

This theme is automatically deployed to WordPress via SFTP on every push to the main branch.

### Setup Instructions

1. Go to your GitHub repository settings
2. Navigate to Settings > Secrets and variables > Actions
3. Add the following secrets:
   - `FTP_SERVER`: Your SFTP server address (e.g., 35.240.176.35)
   - `FTP_USERNAME`: Your SFTP username
   - `FTP_PORT`: Your SFTP port (e.g., 61352)
   - `SSH_PRIVATE_KEY`: Your SSH private key for authentication

### SSH Key Setup

1. Generate an SSH key pair locally:
   ```bash
   ssh-keygen -t rsa -b 4096 -f ~/.ssh/deploy_key
   ```

2. Add the public key to your server:
   ```bash
   ssh-copy-id -i ~/.ssh/deploy_key.pub -p 61352 github@35.240.176.35
   ```
   Or manually append the public key to `~/.ssh/authorized_keys` on your server.

3. Copy the private key content:
   ```bash
   cat ~/.ssh/deploy_key
   ```

4. Add the entire private key (including `-----BEGIN/END-----` lines) as the `SSH_PRIVATE_KEY` secret in GitHub.

Note: The deployment uses SFTP (SSH File Transfer Protocol) with SSH key authentication for secure transfers.

### Version Management

Before pushing changes, remember to bump the version:

```bash
# For bug fixes (1.0.0 -> 1.0.1)
npm run version:patch

# For new features (1.0.0 -> 1.1.0)
npm run version:minor

# For breaking changes (1.0.0 -> 2.0.0)
npm run version:major
```

Then commit the version change:
```bash
git commit -am "Bump version to X.X.X"
git push
```

### Deployment Process

1. Make your changes
2. Bump the version: `npm run version:patch`
3. Commit all changes: `git add . && git commit -m "Your message"`
4. Push to GitHub: `git push`
5. GitHub Actions will automatically:
   - Build the theme
   - Deploy to your WordPress site via FTP

### What Gets Deployed

The following files/folders are excluded from deployment:
- `.git` and `.github` folders
- `node_modules`
- SCSS source files
- Development files (package.json, etc.)

Only the production-ready theme files are deployed.