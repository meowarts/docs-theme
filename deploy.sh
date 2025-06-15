#!/bin/bash

# Docs Theme Deployment Script
# Builds and deploys the theme to your WordPress site via SFTP

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Docs Theme Deployment Script${NC}"
echo "=================================="

# Check if .env file exists
if [ ! -f .env ]; then
    echo -e "${RED}❌ Error: .env file not found!${NC}"
    echo -e "${YELLOW}Please copy .env.example to .env and fill in your SFTP credentials.${NC}"
    exit 1
fi

# Load environment variables
export $(cat .env | grep -v '^#' | xargs)

# Validate required variables
if [ -z "$SFTP_HOST" ] || [ -z "$SFTP_USER" ] || [ -z "$SFTP_PASSWORD" ]; then
    echo -e "${RED}❌ Error: Missing required SFTP credentials in .env file${NC}"
    echo "Required: SFTP_HOST, SFTP_USER, SFTP_PASSWORD"
    exit 1
fi

echo -e "${YELLOW}📦 Building theme...${NC}"
npm run build

echo -e "${YELLOW}📁 Preparing deployment files...${NC}"
# Create deployment directory
rm -rf deploy
mkdir -p deploy

# Copy files excluding development stuff
rsync -av \
    --exclude='.git' \
    --exclude='.github' \
    --exclude='node_modules' \
    --exclude='assets/scss' \
    --exclude='package*.json' \
    --exclude='*.md' \
    --exclude='.gitignore' \
    --exclude='.editorconfig' \
    --exclude='bump-version.js' \
    --exclude='deploy.sh' \
    --exclude='.env*' \
    --exclude='deploy' \
    ./ deploy/

echo -e "${YELLOW}🌐 Connecting to SFTP server...${NC}"
echo "Host: $SFTP_HOST:$SFTP_PORT"
echo "User: $SFTP_USER"

# Create SFTP batch file
cat > sftp_batch << EOF
-mkdir /wp-content
-mkdir /wp-content/themes
-mkdir /wp-content/themes/docs-theme
cd /wp-content/themes/docs-theme
put -r deploy/* .
bye
EOF

# Execute SFTP deployment
export SSHPASS="$SFTP_PASSWORD"
if sshpass -e sftp -o StrictHostKeyChecking=no -P "$SFTP_PORT" -b sftp_batch "$SFTP_USER@$SFTP_HOST"; then
    echo -e "${GREEN}✅ Deployment successful!${NC}"
    echo -e "${GREEN}🎉 Theme deployed to your WordPress site${NC}"
else
    echo -e "${RED}❌ Deployment failed!${NC}"
    exit 1
fi

# Cleanup
rm -f sftp_batch
rm -rf deploy

echo -e "${BLUE}🎯 Deployment complete!${NC}"