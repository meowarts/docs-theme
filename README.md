# Docs Theme

A modern, documentation-first WordPress theme with automatic navigation generation.

## Features

- 📚 **Auto Navigation** - Sidebar automatically lists all pages by category
- 📑 **Auto Table of Contents** - Generates TOC from page headings
- 🗂️ **Page Categories** - Organize pages into sections  
- 🌙 **Dark Theme** - Professional dark design for documentation
- 💻 **Code Friendly** - Syntax highlighting and copy buttons
- 📱 **Responsive** - Works on all devices

## Development & Deployment

### Setup
```bash
npm install
```

### Development Commands
```bash
npm run build       # Build CSS files
npm run watch:css   # Watch SCSS for changes
```

### Deployment Commands

**Deploy with version bump (recommended):**
```bash
npm run deploy:patch   # Bug fixes (1.2.3 → 1.2.4)
npm run deploy:minor   # New features (1.2.3 → 1.3.0)  
npm run deploy:major   # Breaking changes (1.2.3 → 2.0.0)
```

**Deploy without version bump:**
```bash
npm run deploy        # Deploy current version
```

**Version bump only (no deployment):**
```bash
npm run version:patch  # Just bump version
npm run version:minor
npm run version:major
```

### Initial Deployment Setup
1. Copy `.env.example` to `.env`
2. Fill in your SFTP credentials:
   ```
   SFTP_HOST=your-server.com
   SFTP_PORT=22
   SFTP_USER=your-username
   SFTP_PASSWORD=your-password
   SFTP_REMOTE_PATH=/wp-content/themes/docs-theme
   ```

### Typical Workflow
1. Make changes to theme files
2. Test locally
3. Deploy with version bump: `npm run deploy:patch`
4. Commit to git: `git add . && git commit -m "Deploy version X.X.X"`
5. Push: `git push`

## License

GPL v2 or later - Built by [Meow Apps](https://meowapps.com)