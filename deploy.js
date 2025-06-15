#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const { execSync, spawn } = require('child_process');

// Colors for output
const colors = {
    red: '\x1b[31m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    blue: '\x1b[34m',
    reset: '\x1b[0m'
};

function log(message, color = 'reset') {
    console.log(`${colors[color]}${message}${colors.reset}`);
}

function showHelp() {
    log('🚀 Docs Theme Deployment Script', 'blue');
    log('================================');
    log('');
    log('Usage:', 'yellow');
    log('  node deploy.js [command] [options]');
    log('');
    log('Commands:', 'yellow');
    log('  deploy              Deploy without version bump');
    log('  patch               Bump patch version and deploy');
    log('  minor               Bump minor version and deploy');
    log('  major               Bump major version and deploy');
    log('  version [type]      Only bump version (patch/minor/major)');
    log('');
    log('Examples:', 'yellow');
    log('  npm run deploy         # Deploy current version');
    log('  npm run deploy:patch   # Bump patch (1.2.3 → 1.2.4) and deploy');
    log('  npm run deploy:minor   # Bump minor (1.2.3 → 1.3.0) and deploy');
    log('  npm run deploy:major   # Bump major (1.2.3 → 2.0.0) and deploy');
    log('  npm run version:patch  # Only bump patch version');
}

function bumpVersion(versionType = 'patch') {
    log(`📈 Bumping ${versionType} version...`, 'yellow');
    
    // Read from theme-header.scss as the source of truth
    const themeHeaderPath = path.join(__dirname, 'assets/scss/theme-header.scss');
    if (!fs.existsSync(themeHeaderPath)) {
        log('❌ Could not find assets/scss/theme-header.scss', 'red');
        process.exit(1);
    }
    
    const themeHeaderContent = fs.readFileSync(themeHeaderPath, 'utf8');

    // Extract current version
    const versionMatch = themeHeaderContent.match(/Version:\s*(\d+)\.(\d+)\.(\d+)/);
    if (!versionMatch) {
        log('❌ Could not find version in theme-header.scss', 'red');
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

    // Update assets/scss/theme-header.scss (the source of truth)
    const updatedThemeHeader = themeHeaderContent.replace(
        /Version:\s*\d+\.\d+\.\d+/,
        `Version: ${newVersion}`
    );
    fs.writeFileSync(themeHeaderPath, updatedThemeHeader);
    
    // style.css will be updated when we rebuild

    // Update package.json
    const packagePath = path.join(__dirname, 'package.json');
    const packageJson = JSON.parse(fs.readFileSync(packagePath, 'utf8'));
    packageJson.version = newVersion;
    fs.writeFileSync(packagePath, JSON.stringify(packageJson, null, 2) + '\n');

    log(`✅ Version bumped to ${newVersion}`, 'green');
    return newVersion;
}

function loadEnv() {
    const envPath = path.join(__dirname, '.env');
    if (!fs.existsSync(envPath)) {
        log('❌ Error: .env file not found!', 'red');
        log('Please copy .env.example to .env and fill in your SFTP credentials.', 'yellow');
        process.exit(1);
    }

    const env = {};
    const envContent = fs.readFileSync(envPath, 'utf8');
    
    envContent.split('\n').forEach(line => {
        const trimmed = line.trim();
        if (trimmed && !trimmed.startsWith('#')) {
            const [key, ...valueParts] = trimmed.split('=');
            if (key && valueParts.length > 0) {
                env[key.trim()] = valueParts.join('=').trim();
            }
        }
    });

    return env;
}

function validateCredentials(env) {
    const required = ['SFTP_HOST', 'SFTP_USER', 'SFTP_PASSWORD'];
    const missing = required.filter(key => !env[key]);
    
    if (missing.length > 0) {
        log('❌ Error: Missing required SFTP credentials in .env file', 'red');
        log(`Required: ${missing.join(', ')}`, 'yellow');
        process.exit(1);
    }
}

function runCommand(command, description) {
    try {
        log(`📦 ${description}...`, 'yellow');
        execSync(command, { stdio: 'inherit' });
    } catch (error) {
        log(`❌ Failed: ${description}`, 'red');
        process.exit(1);
    }
}

function prepareDeployment() {
    log('📁 Preparing deployment files...', 'yellow');
    
    // Clean and create deploy directory
    if (fs.existsSync('deploy')) {
        execSync('rm -rf deploy');
    }
    fs.mkdirSync('deploy');

    // Copy files excluding development stuff
    const rsyncCommand = `rsync -av \
        --exclude='.git' \
        --exclude='.github' \
        --exclude='.claude' \
        --exclude='node_modules' \
        --exclude='assets/scss' \
        --exclude='package*.json' \
        --exclude='*.md' \
        --exclude='.gitignore' \
        --exclude='.editorconfig' \
        --exclude='bump-version.js' \
        --exclude='deploy.js' \
        --exclude='.env*' \
        --exclude='deploy' \
        --exclude='*.map' \
        --exclude='sftp_batch' \
        ./ deploy/`;

    execSync(rsyncCommand, { stdio: 'inherit' });
}

function deployViaSFTP(env) {
    return new Promise((resolve, reject) => {
        log('🌐 Connecting to SFTP server...', 'yellow');
        log(`Host: ${env.SFTP_HOST}:${env.SFTP_PORT || 22}`);
        log(`User: ${env.SFTP_USER}`);
        
        log('🔄 Uploading files via SFTP...', 'yellow');

        const sftpCommands = [
            '-mkdir /wp-content',
            '-mkdir /wp-content/themes',
            '-mkdir /wp-content/themes/docs-theme',
            'cd /wp-content/themes/docs-theme',
            'put -r deploy/* .',
            'bye'
        ].join('\n');

        const sshpassArgs = [
            '-e', 'sftp',
            '-o', 'StrictHostKeyChecking=no',
            '-P', env.SFTP_PORT || '22',
            `${env.SFTP_USER}@${env.SFTP_HOST}`
        ];

        const sftp = spawn('sshpass', sshpassArgs, {
            stdio: ['pipe', 'inherit', 'inherit'],
            env: { ...process.env, SSHPASS: env.SFTP_PASSWORD }
        });

        sftp.stdin.write(sftpCommands);
        sftp.stdin.end();

        sftp.on('close', (code) => {
            if (code === 0) {
                resolve();
            } else {
                reject(new Error(`SFTP process exited with code ${code}`));
            }
        });

        sftp.on('error', (error) => {
            reject(error);
        });
    });
}

function cleanup() {
    log('🧹 Cleaning up...', 'yellow');
    if (fs.existsSync('deploy')) {
        execSync('rm -rf deploy');
    }
}

async function main() {
    const command = process.argv[2] || 'help';
    const versionType = process.argv[3] || 'patch';

    try {
        if (command === 'help' || command === '--help' || command === '-h') {
            showHelp();
            return;
        }

        log('🚀 Docs Theme Deployment Script', 'blue');
        log('==================================');

        // Handle version-only command
        if (command === 'version') {
            const newVersion = bumpVersion(versionType);
            log(`\nDon't forget to commit with: git commit -am "Bump version to ${newVersion}"`, 'yellow');
            return;
        }

        // Load and validate environment for deployment
        const env = loadEnv();
        validateCredentials(env);

        // Handle version bump commands
        let newVersion;
        if (['patch', 'minor', 'major'].includes(command)) {
            newVersion = bumpVersion(command);
            // Rebuild after version bump to update style.css with new version
            runCommand('npm run build', 'Rebuilding theme with new version');
        } else if (command === 'deploy' && versionType === 'bump') {
            // Handle legacy 'deploy bump' command
            newVersion = bumpVersion('patch');
            // Rebuild after version bump to update style.css with new version
            runCommand('npm run build', 'Rebuilding theme with new version');
        } else {
            // Build theme
            runCommand('npm run build', 'Building theme');
        }

        // Prepare deployment files
        prepareDeployment();

        // Deploy via SFTP
        await deployViaSFTP(env);

        log('✅ Deployment successful!', 'green');
        if (newVersion) {
            log(`🎉 Version ${newVersion} deployed to your WordPress site`, 'green');
            log(`\nDon't forget to commit with: git commit -am "Deploy version ${newVersion}"`, 'yellow');
        } else {
            log('🎉 Theme deployed to your WordPress site', 'green');
        }

    } catch (error) {
        log('❌ Deployment failed!', 'red');
        log(error.message, 'red');
        process.exit(1);
    } finally {
        cleanup();
        log('🎯 Deployment complete!', 'blue');
    }
}

// Run if called directly
if (require.main === module) {
    main();
}