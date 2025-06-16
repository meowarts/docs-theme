# Deployment Guide

## Development Setup

### Install Dependencies
```bash
npm install
```

### Development Commands
```bash
npm run build       # Build minified CSS
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
   ```

## Build & Deploy

### Build CSS
```bash
npm run build       # Compile SCSS to minified CSS
```

This creates `style.min.css` which is loaded by the theme for optimal performance.

### Deploy
```bash
npm run deploy      # Deploy with automatic version increment
```

The deployment automatically:
- Increments version using date-based format (YY.M.N)
- Builds CSS with new version
- Uploads to your WordPress site via SFTP
- Cleans up temporary files

## Version Format
The theme uses date-based versioning:
- `25.6.1` = Year 2025, Month 6 (June), Deployment #1
- `25.6.2` = Year 2025, Month 6 (June), Deployment #2
- `25.7.1` = Year 2025, Month 7 (July), Deployment #1

Each deployment automatically increments the deployment counter for the current month.

## Typical Workflow
1. Make changes to theme files
2. Test locally
3. Deploy: `npm run deploy`
4. Commit to git: `git add . && git commit -m "Deploy version X.X.X"`
5. Push: `git push`

## Technical Details
- Theme loads `style.min.css` (minified) instead of `style.css`
- Version is updated in:
  - `assets/scss/theme-header.scss` (source)
  - `package.json` (npm package)
  - `style.min.css` (compiled output with theme header)
- Development files (SCSS, node_modules, etc.) are excluded from deployment
- Deployment history is tracked in `.version-history.json` (git ignored)
- Uses `sshpass` for SFTP authentication