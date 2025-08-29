#!/bin/bash

echo "GitHub Push Helper for Dalthaus CMS"
echo "===================================="
echo ""

# Check if we have commits to push
if [ -z "$(git status --porcelain)" ]; then
    echo "✅ Working directory clean"
else
    echo "⚠️  You have uncommitted changes"
    read -p "Commit them first? (y/n): " commit_first
    if [ "$commit_first" = "y" ]; then
        read -p "Commit message: " msg
        git add -A
        git commit -m "$msg"
    fi
fi

echo ""
echo "To push to GitHub, you need a Personal Access Token"
echo "Get one at: https://github.com/settings/tokens/new"
echo ""
read -p "Enter your GitHub username: " username
read -sp "Enter your Personal Access Token: " token
echo ""

# Set the remote URL with token
git remote set-url origin https://${username}:${token}@github.com/blur702/dalthaus_net_live.git

echo ""
echo "Pushing to GitHub..."
git push origin main

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Successfully pushed to GitHub!"
    echo ""
    echo "Next steps:"
    echo "1. SSH into your shared hosting"
    echo "2. Run: git pull origin main"
    echo "3. Run: php setup.php"
    echo "4. Delete setup.php after completion"
    
    # Remove token from URL for security
    git remote set-url origin https://github.com/blur702/dalthaus_net_live.git
    echo ""
    echo "✅ Token removed from git config for security"
else
    echo "❌ Push failed. Check your token and try again."
    # Remove token from URL
    git remote set-url origin https://github.com/blur702/dalthaus_net_live.git
fi