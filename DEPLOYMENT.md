# Local Deployment

## Simple SFTP Deployment Script

This theme includes a simple deployment script that builds and uploads your theme to WordPress via SFTP.

### Setup (One-time)

1. Install `sshpass` (for password authentication):
   ```bash
   # macOS
   brew install hudochenkov/sshpass/sshpass
   
   # Ubuntu/Debian
   sudo apt-get install sshpass
   ```

2. Copy the environment template:
   ```bash
   cp .env.example .env
   ```

3. Edit `.env` with your SFTP credentials:
   ```bash
   SFTP_HOST=35.240.176.35
   SFTP_PORT=61352
   SFTP_USER=github
   SFTP_PASSWORD=your_password_here
   SFTP_REMOTE_PATH=/wp-content/themes/docs-theme
   ```

### Daily Workflow

1. **Make your changes** to the theme
2. **Bump the version**:
   ```bash
   npm run version:patch  # or minor/major
   ```
3. **Deploy to WordPress**:
   ```bash
   npm run deploy
   # OR
   ./deploy.sh
   ```

That's it! The script will:
- Build your CSS files
- Create a clean deployment package (excludes dev files)
- Upload everything to your WordPress site via SFTP
- Show you a nice progress report

### What Gets Deployed

**Included:**
- All PHP theme files
- Compiled CSS files
- JavaScript files
- Images and assets

**Excluded:**
- `.git` and development files
- `node_modules`
- SCSS source files
- Package files (package.json, etc.)
- Documentation files (*.md)
- The deployment script itself

### Troubleshooting

**"sshpass: command not found"**
- Install sshpass using the commands above

**"Permission denied"**
- Double-check your credentials in `.env`
- Test manually: `sftp -P 61352 github@35.240.176.35`

**"Connection refused"**
- Check if the server/port is correct
- Make sure you can connect manually first