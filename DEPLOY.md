# Deployment Guide

## Development Setup

### Install Dependencies
```bash
npm install
```

### Development Commands
```bash
npm run build       # Build CSS files
npm run watch:css   # Watch SCSS for changes
```

## Deployment Setup

### Initial Setup (One-time)
1. Copy `.env.example` to `.env`
2. Fill in your SFTP credentials:
   ```
   SFTP_HOST=your-server.com
   SFTP_PORT=22
   SFTP_USER=your-username
   SFTP_PASSWORD=your-password
   SFTP_REMOTE_PATH=/wp-content/themes/docs-theme
   ```

## Deployment Commands

### Deploy with Version Bump (Recommended)
```bash
npm run deploy:patch   # Bug fixes (1.2.3 → 1.2.4)
npm run deploy:minor   # New features (1.2.3 → 1.3.0)  
npm run deploy:major   # Breaking changes (1.2.3 → 2.0.0)
```

### Deploy Without Version Bump
```bash
npm run deploy        # Deploy current version
```

### Version Bump Only (No Deployment)
```bash
npm run version:patch  # Just bump version
npm run version:minor
npm run version:major
```

## Typical Workflow
1. Make changes to theme files
2. Test locally
3. Deploy with version bump: `npm run deploy:patch`
4. Commit to git: `git add . && git commit -m "Deploy version X.X.X"`
5. Push: `git push`

## Technical Details
- The deploy script automatically updates version in:
  - `style.css` (WordPress theme header)
  - `assets/scss/theme-header.scss` (source file)
  - `package.json` (npm package version)
- CSS is automatically rebuilt after version bumping
- Development files (SCSS, node_modules, etc.) are excluded from deployment
- Creates temporary `deploy/` directory that gets cleaned up after upload
- Uses `sshpass` for SFTP authentication