#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

// Get version type from command line (patch, minor, major)
const versionType = process.argv[2] || 'patch';

// Read style.css
const stylePath = path.join(__dirname, 'style.css');
const styleContent = fs.readFileSync(stylePath, 'utf8');

// Extract current version
const versionMatch = styleContent.match(/Version:\s*(\d+)\.(\d+)\.(\d+)/);
if (!versionMatch) {
    console.error('Could not find version in style.css');
    process.exit(1);
}

let [, major, minor, patch] = versionMatch.map(Number);

// Increment version based on type
switch (versionType) {
    case 'major':
        major++;
        minor = 0;
        patch = 0;
        break;
    case 'minor':
        minor++;
        patch = 0;
        break;
    case 'patch':
    default:
        patch++;
        break;
}

const newVersion = `${major}.${minor}.${patch}`;

// Update style.css
const updatedStyle = styleContent.replace(
    /Version:\s*\d+\.\d+\.\d+/,
    `Version: ${newVersion}`
);
fs.writeFileSync(stylePath, updatedStyle);

// Update package.json
const packagePath = path.join(__dirname, 'package.json');
const packageJson = JSON.parse(fs.readFileSync(packagePath, 'utf8'));
packageJson.version = newVersion;
fs.writeFileSync(packagePath, JSON.stringify(packageJson, null, 2));

console.log(`✅ Version bumped to ${newVersion}`);
console.log(`\nDon't forget to commit with: git commit -am "Bump version to ${newVersion}"`);