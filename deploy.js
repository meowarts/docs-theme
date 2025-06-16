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

function getDateVersion() {
    const now = new Date();
    const year = String(now.getFullYear()).slice(-2); // Last 2 digits of year
    const month = now.getMonth() + 1; // getMonth() returns 0-11
    
    // Read version history to determine deployment number
    const versionHistoryPath = path.join(__dirname, '.version-history.json');
    let versionHistory = {};
    
    if (fs.existsSync(versionHistoryPath)) {
        versionHistory = JSON.parse(fs.readFileSync(versionHistoryPath, 'utf8'));
    }
    
    const monthKey = `${year}.${month}`;
    const deploymentNumber = (versionHistory[monthKey] || 0) + 1;
    
    // Update version history
    versionHistory[monthKey] = deploymentNumber;
    fs.writeFileSync(versionHistoryPath, JSON.stringify(versionHistory, null, 2) + '\n');
    
    return `${year}.${month}.${deploymentNumber}`;
}

function updateVersion(newVersion) {
    log(`📈 Updating version to ${newVersion}...`, 'yellow');
    
    // Update assets/scss/theme-header.scss
    const themeHeaderPath = path.join(__dirname, 'assets/scss/theme-header.scss');
    const themeHeaderContent = fs.readFileSync(themeHeaderPath, 'utf8');
    const updatedThemeHeader = themeHeaderContent.replace(
        /Version:\s*\d+\.\d+\.\d+/,
        `Version: ${newVersion}`
    );
    fs.writeFileSync(themeHeaderPath, updatedThemeHeader);
    
    // Update package.json
    const packagePath = path.join(__dirname, 'package.json');
    const packageJson = JSON.parse(fs.readFileSync(packagePath, 'utf8'));
    packageJson.version = newVersion;
    fs.writeFileSync(packagePath, JSON.stringify(packageJson, null, 2) + '\n');
    
    log(`✅ Version updated to ${newVersion}`, 'green');
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

function build() {
    try {
        log('📦 Building CSS...', 'yellow');
        execSync('npm run build', { stdio: 'inherit' });
        log('✅ CSS built successfully', 'green');
    } catch (error) {
        log('❌ Failed to build CSS', 'red');
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
        --exclude='assets/css' \
        --exclude='package*.json' \
        --exclude='*.md' \
        --exclude='.gitignore' \
        --exclude='.editorconfig' \
        --exclude='deploy.js' \
        --exclude='.env*' \
        --exclude='deploy' \
        --exclude='*.map' \
        --exclude='.version-history.json' \
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
    try {
        log('🚀 Docs Theme Deployment', 'blue');
        log('========================');

        // Load and validate environment
        const env = loadEnv();
        validateCredentials(env);

        // Get new version and update files
        const newVersion = getDateVersion();
        updateVersion(newVersion);

        // Build CSS with new version
        build();

        // Prepare deployment files
        prepareDeployment();

        // Deploy via SFTP
        await deployViaSFTP(env);

        log('✅ Deployment successful!', 'green');
        log(`🎉 Version ${newVersion} deployed to your WordPress site`, 'green');
        log(`\nDon't forget to commit with: git commit -am "Deploy version ${newVersion}"`, 'yellow');

    } catch (error) {
        log('❌ Deployment failed!', 'red');
        log(error.message, 'red');
        process.exit(1);
    } finally {
        cleanup();
    }
}

// Run if called directly
if (require.main === module) {
    main();
}