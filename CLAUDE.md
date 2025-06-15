# Docs Theme - Simple WordPress Documentation Theme

## Overview

Docs Theme is a straightforward, classic WordPress theme designed specifically for documentation sites. It automatically generates navigation from your page hierarchy and categories - no configuration needed!

**Author**: Meow Apps  
**Website**: https://meowapps.com  
**Version**: 1.0.0  
**Requires**: WordPress 5.0+, PHP 7.4+

## Key Features

- **Automatic Navigation**: Sidebar automatically lists all pages by category
- **Page Hierarchy Support**: Child pages shown with expandable toggles
- **Page Categories**: Organize pages into categories (custom taxonomy)
- **Auto Table of Contents**: Generates TOC from page headings (H2, H3, H4)
- **Dark Theme**: Professional dark design optimized for documentation
- **Zero Configuration**: Works immediately after activation
- **Clean & Simple**: No complex settings or page builders needed

## How It Works

### 1. Install & Activate
Simply install and activate the theme. That's it - no setup required!

### 2. Create Page Categories (Optional)
- Go to **Pages → Page Categories**
- Create categories like "Getting Started", "API Reference", "Tutorials"
- Categories appear as section headers in the sidebar

### 3. Create Pages
- Create pages normally in WordPress
- Assign a category using the "Page Categories" box in the sidebar (like regular post categories)
- Create child pages for hierarchical documentation
- Pages automatically appear in the left sidebar under their category

### 4. Write Documentation
- Use headings (H2, H3, H4) to structure your content
- Table of contents generates automatically in right sidebar
- Code blocks get automatic copy buttons
- Markdown-friendly typography

## Theme Structure

```
docs-theme/
├── assets/
│   ├── css/
│   │   ├── style.css         # All theme styles
│   │   └── navigation.css    # Navigation styles
│   └── js/
│       └── theme.js          # Interactive features
├── blocks/
│   └── table-of-contents/    # TOC block (for future use)
├── template-parts/
│   └── sidebar-pages.php     # Automatic page navigation
├── header.php                # Site header with search
├── footer.php                # Site footer
├── index.php                 # Blog index template
├── page.php                  # Page template
├── single.php                # Single post template
├── functions.php             # Theme functionality
└── style.css                 # Theme information
```

## Features in Detail

### Automatic Page Navigation

The left sidebar automatically:
- Lists all published pages
- Groups them by category
- Shows hierarchy with expandable child pages
- Highlights the current page
- Right-aligns text for clean appearance

### Page Categories

A custom taxonomy for pages that:
- Appears as section headers in sidebar
- Groups related documentation
- Shows in uppercase with letter spacing
- Optional - uncategorized pages show under "Documentation"

### Table of Contents

The right sidebar automatically:
- Parses H2, H3, and H4 headings
- Creates anchor links
- Highlights current section on scroll
- Smooth scrolling to sections
- Sticky positioning

### Interactive Features

- **Copy Code Button**: Hover over code blocks to copy
- **Search Shortcut**: Press Cmd/Ctrl + K to focus search
- **Collapsible Navigation**: Click chevrons to expand/collapse
- **Smooth Scrolling**: All anchor links scroll smoothly

## Styling Details

### Colors
- Background: `#0f172a` (dark blue)
- Text: `#f1f5f9` (light gray)
- Primary: `#2563eb` (blue)
- Accent: `#38bdf8` (light blue)
- Muted: `#94a3b8` (gray)

### Typography
- Body: Inter font family
- Code: Fira Code (monospace)
- Line height: 1.7 for readability

### Layout
- Fixed 3-column layout
- Left sidebar: 280px (navigation)
- Main content: Max 760px centered
- Right sidebar: 280px (TOC)

## Page Organization Tips

### Using Categories
1. Create logical categories: "Getting Started", "Features", "API", etc.
2. Assign pages to appropriate categories
3. Categories appear as gray uppercase headers

### Using Hierarchy
1. Create parent pages for main topics
2. Add child pages for subtopics
3. Children are hidden until parent is expanded
4. Active parents auto-expand to show current page

### Example Structure
```
Getting Started (category)
├── Introduction
├── Installation
│   ├── Requirements
│   └── Quick Start
└── Configuration

API Reference (category)
├── Authentication
├── Endpoints
│   ├── Users
│   ├── Posts
│   └── Comments
└── Error Codes
```

## Customization

### Adding a Logo
1. Go to **Appearance → Customize → Site Identity**
2. Upload a logo (recommended: 40px height)
3. Logo appears next to site title

### Modifying Styles
- Edit `assets/css/style.css` for custom styling
- CSS variables at the top for easy color changes
- Or use a child theme for safer customization

### Extending Functionality
The theme provides filters:
- `docs_theme_page_categories` - Modify categories query
- `docs_theme_pages_query` - Modify pages query
- Standard WordPress hooks supported

## Development Notes

### No Block Editor
This is a classic theme without FSE:
- Simple PHP templates
- No theme.json or block patterns
- Traditional WordPress development
- Easier to understand and modify

### JavaScript Features
- Vanilla JavaScript (no jQuery)
- Modular functions in theme.js
- Works in all modern browsers

### Performance
- Minimal dependencies
- Single CSS file
- Single JS file
- No external libraries
- Fast page loads

## Build Process & Deployment

### Local Development
The theme uses Sass for CSS development:
```bash
# Install dependencies
npm install

# Build CSS once
npm run build

# Watch for changes during development
npm run watch
```

### Deployment

The theme uses a unified deployment script (`deploy.js`) that handles both version bumping and deployment via SFTP.

**Setup (one-time)**:
1. Copy `.env.example` to `.env`
2. Fill in SFTP credentials in `.env`

**Deployment Commands**:
- `npm run deploy` - Deploy current version without bumping
- `npm run deploy:patch` - Bump patch version (1.2.3 → 1.2.4) and deploy
- `npm run deploy:minor` - Bump minor version (1.2.3 → 1.3.0) and deploy  
- `npm run deploy:major` - Bump major version (1.2.3 → 2.0.0) and deploy

**Version-only Commands**:
- `npm run version:patch` - Only bump patch version (no deployment)
- `npm run version:minor` - Only bump minor version (no deployment)
- `npm run version:major` - Only bump major version (no deployment)

**Workflow**:
1. Make your changes locally
2. Deploy with version bump: `npm run deploy:patch`
3. Commit changes: `git add . && git commit -m "Deploy version X.X.X"`
4. Push to GitHub: `git push`

**Technical Details**:
- The `deploy.js` script combines version bumping and deployment
- Updates both `style.css` and `package.json` version numbers
- Uses `sshpass` for SFTP authentication
- Excludes development files from deployment (node_modules, SCSS, etc.)
- Creates temporary `deploy/` directory that gets cleaned up after upload

### SCSS Structure
```
assets/scss/
├── style.scss          # Main file that imports all others
├── _variables.scss     # CSS variables and Sass variables
├── _mixins.scss        # Reusable mixins
├── _base.scss          # Global element styles
├── _layout.scss        # Layout and structure
└── components/         # Component-specific styles
    ├── _header.scss
    ├── _sidebar.scss
    ├── _table-of-contents.scss
    ├── _content.scss
    ├── _code.scss
    └── _utilities.scss
```

### Making Style Changes
1. Edit SCSS files in `assets/scss/`
2. Run `npm run build` to compile
3. Test your changes locally
4. Bump version and push to deploy

### Feature Modules
The theme includes modular features in `inc/features/`:
- `admin-menu-order.php` - Reorders admin menu items
- `block-styles.php` - Registers block editor styles
- `font-options.php` - Adds font selection to Customizer
- `hide-author-columns.php` - Hides author column in admin
- `seo-enhancements.php` - Basic SEO improvements

All features are loaded automatically via `functions.php`.

## Troubleshooting

### Pages Not Showing in Sidebar
- Ensure pages are published (not draft)
- Check page hierarchy is correct
- Parent pages must be published

### Categories Not Working
- Go to **Settings → Permalinks** and save
- Ensure "Page Categories" taxonomy is registered
- Check page edit screen for category metabox

### Table of Contents Empty
- Add H2, H3, or H4 headings to your content
- Headings need text content (not just images)
- TOC only shows on pages with headings

## Support

For issues or feature requests:
- GitHub: https://github.com/meowapps/docs-theme
- Website: https://meowapps.com/docs-theme

## License

GPL v2 or later