# Quick Reference for Claude Instances

**Essential information for future Claude Code sessions working on dalthaus.net CMS**

## 🚨 **CRITICAL: Read First**

1. **SSH Agent Available**: Use `python agents/deploy_agent.py` for production deployments
2. **Credentials Security**: NEVER commit `ssh_config.py` - it's gitignored
3. **Deployment Process**: Local → GitHub → SSH Pull (not direct upload)
4. **Database Schema**: See actual table structure before making model changes

## 🔧 **Project Setup Commands**

```bash
# SSH Agent Setup (one-time)
cp ssh_config.template.py ssh_config.py  # Configure with real credentials
pip install paramiko                     # Install dependencies

# Development Server (optional)
php -S localhost:8000 router.php

# Production Deployment
python agents/deploy_agent.py deploy main       # Full deployment
python agents/deploy_agent.py status           # Check git status
python agents/deploy_agent.py db               # Test database config
```

## 🏗️ **Architecture Quick Facts**

- **Framework**: Custom PHP MVC
- **Database**: MySQL with PDO
- **Routes**: `config/routes.php` with namespace groups
- **Views**: Arrays expected (use `model->toArray()`)
- **Auth**: Session-based with CSRF protection
- **Uploads**: `/uploads/` with year/month structure

## 📂 **Key Directories**

```
src/Controllers/Admin/   # Admin controllers (require auth)
src/Controllers/Public/  # Public controllers  
src/Models/             # Database models
src/Views/admin/        # Admin templates
src/Views/public/       # Public templates
config/                 # Configuration files
uploads/                # File uploads
```

## 🚨 **Common Pitfalls to Avoid**

1. **Views expect arrays, not objects** - Always use `toArray()`
2. **Route parameters must match method arguments** - Check `routes.php`
3. **CSRF token is `$csrf_token`** - Not `$this->csrfToken()`
4. **Menu table uses `menu_name`** - Not `name` or `location`
5. **Database columns** - Check actual schema, don't assume field names

## 🔄 **Standard Workflow**

1. **Make changes locally**
2. **Test if possible** (`php -S localhost:8000 router.php`)
3. **Commit with good message**
4. **Deploy**: `python agents/deploy_agent.py deploy main`
5. **Verify**: Check live site functionality

## 🗄️ **Database Quick Reference**

**Tables**: `content`, `pages`, `users`, `menus`, `menu_items`, `settings`

**Content Table Fields** (check actual schema):
- `content_id`, `title`, `body`, `teaser`, `url_alias`
- `content_type` (article/photobook), `status` (draft/published)
- `user_id`, `sort_order`, `created_at`, `updated_at`
- Image fields: `featured_image`, `teaser_image`

**Default Admin User**:
- Username: `kevin`
- Password: `(130Bpm)`

## 🔐 **Security Notes**

- **SSH Agent**: Runs locally, credentials in `ssh_config.py` (gitignored)
- **Production Server**: A2 Hosting, SSH port 7822
- **Database**: Production credentials in server's `config/config.php`
- **HTTPS**: Live site uses secure cookies

## 🚀 **SSH Agent Commands**

```bash
python agents/deploy_agent.py status    # Git status on server
python agents/deploy_agent.py pull      # Pull latest code
python agents/deploy_agent.py deploy    # Full deployment  
python agents/deploy_agent.py db        # Test database config
python agents/deploy_agent.py health    # Server health check
```

## 📋 **Before Making Changes**

1. **Check current git status**: `python agents/deploy_agent.py status`
2. **Read recent commits**: `git log --oneline -5`
3. **Understand the issue**: Test locally if possible
4. **Check database schema**: Don't assume field names exist

## 📋 **After Making Changes**

1. **Test functionality**: Verify changes work
2. **Commit properly**: Descriptive message with Claude signature
3. **Deploy**: `python agents/deploy_agent.py deploy main`
4. **Verify**: Check live site works correctly

## 📖 **Documentation Files**

- **CLAUDE.md** - Project instructions for Claude
- **DEPLOYMENT_WORKFLOW.md** - Complete deployment guide
- **SSH_AGENT_README.md** - SSH agent documentation
- **CLAUDE_QUICK_REFERENCE.md** - This file

## 🆘 **Emergency Contacts/Info**

- **Hosting**: A2 Hosting
- **Server**: `mi3-cl9-its2.a2hosting.com:7822`
- **Domain**: dalthaus.net
- **GitHub**: Repository with deployment via SSH agent

---

**Remember**: Always prioritize security, test changes when possible, and use the SSH agent for all production deployments.