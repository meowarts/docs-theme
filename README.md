# Docs Theme

A modern WordPress documentation theme with automatic navigation generation and beautiful dark design.

## ✨ Features

- 📚 **Auto Navigation** - Sidebar automatically lists all pages organized by category
- 📑 **Auto Table of Contents** - Generates TOC from your page headings (H2, H3, H4)
- 🗂️ **Page Categories** - Organize documentation into logical sections
- 🔢 **Custom Ordering** - Control the order of both categories and pages
- 📊 **Page Hierarchy** - Support for parent/child page relationships
- 🌙 **Dark Theme** - Professional dark design with subtle CRT effect
- 💻 **Code Friendly** - Syntax highlighting with one-click copy buttons
- 📱 **Responsive** - Works perfectly on all devices
- ⚡ **Fast & Clean** - No bloat, no page builders, just pure performance

## 📖 Usage Guide

### Getting Started

1. **Install & Activate** the theme
2. **Create Categories** first (this organizes your sidebar)
3. **Create Pages** and assign them to categories
4. **Write Content** using headings for automatic TOC generation

### Step 1: Create Page Categories

Page categories are how you organize your documentation into sections:

1. Go to **Pages → Page Categories**
2. Create categories like:
   - Getting Started
   - User Guide
   - API Reference
   - Tutorials
   - FAQ

**Pro tip**: Use the "Order" field to control category display order (lower numbers appear first)

### Step 2: Create Documentation Pages

1. Go to **Pages → Add New**
2. Write your documentation
3. In the sidebar, find **Page Categories** and select one
4. Use the **Order** field to control page position within its category

### Step 3: Use Page Hierarchy

Create multi-level documentation:

```
Installation (parent page)
├── Requirements (child page)
├── Quick Start (child page)
└── Advanced Setup (child page)
```

**How to**: When creating a page, use the **Page Attributes** box to select a parent page.

### Writing Great Documentation

#### Use Headings for Structure
The theme automatically creates a table of contents from your headings:
- **H2** - Main sections
- **H3** - Subsections  
- **H4** - Details

#### Special Content Blocks

**Info Blocks** - Use blockquotes for important notes:
```
> **Note:** This creates a beautiful blue info block with an icon
```

**Code Blocks** - All code blocks get:
- Syntax highlighting
- Copy button on hover
- Dark background for contrast

#### Page Organization Tips

1. **Use Clear Titles** - They appear in navigation
2. **Order Matters** - Set page order in Page Attributes
3. **Parent Pages** - Automatically expand to show current child
4. **Categories First** - Always assign a category for organization

### Theme Customization

#### Adding a Logo
1. Go to **Appearance → Customize**
2. Navigate to **Site Identity**
3. Upload your logo (40px height recommended)

#### Colors & Styling
The theme uses CSS variables for easy customization. Main colors:
- Background: `#0f172a` (dark blue)
- Text: `#cad3df` (light gray)
- Accent: `#4eb9ec` (light blue)
- Links: `#c57ce8` (purple)

## 🛠️ For Developers

- Built with modern CSS (CSS Grid, Flexbox, CSS Variables)
- Vanilla JavaScript (no jQuery dependency)
- Sass/SCSS for development
- Clean, semantic HTML
- Easy to customize and extend

See [DEPLOY.md](DEPLOY.md) for development setup and deployment instructions.

## 📄 License

GPL v2 or later - Built with ❤️ by [Meow Apps](https://meowapps.com)