# Image 404 Issues - Root Cause and Fix

## Problem Summary
Images showing 404 errors on the website due to two separate issues:

### Issue 1: Router.php Bug (FIXED ✅)
- **Location**: `/router.php` line 13
- **Problem**: Incorrect path checking with `../../` prefix
- **Fix**: Removed the incorrect path prefix
- **Status**: Fixed and deployed

### Issue 2: Missing Image Files
- **Location**: `/uploads/content/teasers/2025/10/` 
- **Problem**: Database references images that were never uploaded
- **Affected Content**:
  - Content ID 31: Route 66 article → 530fe5ee220115e97dfb7a6386051541.jpg
  - Content ID 33: Rhyolite article → 24e20e2aea802c08f96721eec4a66565.jpg  
  - Content ID 34: Swansea article → ad495c68c2a90419cf64064ca3a6c777.jpg
  - Content ID 35: Arizona Ghost Town → f6e47e30c04483d00557f461c4793869.jpg

## Root Cause Analysis

The upload process failed during content creation:
1. Content was created with teaser image paths
2. Directory `/uploads/content/teasers/2025/10/` was never created
3. Image files were never uploaded
4. Content was published anyway with broken references

## Solution Applied

### Immediate Fix
1. ✅ Created missing directory structure on production:
   ```bash
   mkdir -p /home/dalthaus/public_html/uploads/content/teasers/2025/10
   ```

2. Database cleanup (needs to be executed):
   ```sql
   UPDATE content 
   SET teaser_image = NULL 
   WHERE teaser_image IN (
       '530fe5ee220115e97dfb7a6386051541.jpg',
       '24e20e2aea802c08f96721eec4a66565.jpg',
       'ad495c68c2a90419cf64064ca3a6c777.jpg',
       'f6e47e30c04483d00557f461c4793869.jpg'
   );
   ```

### Long-term Prevention

1. **Add Upload Validation** in `/src/Controllers/Admin/Content.php`:
   - Verify file upload success before saving to database
   - Create directory structure automatically if missing
   - Rollback database changes if upload fails

2. **Add File Existence Check** in views:
   - Check if image file exists before rendering `<img>` tag
   - Display placeholder if missing

3. **Add Upload Directory Permissions Check**:
   - Ensure upload directories are writable
   - Alert admin if permissions are incorrect

## Testing Results

### Before Fix:
- 4 images returning 404 on homepage
- Router.php incorrectly checking paths

### After Fix:
- Router.php path checking corrected
- Directory structure created
- Database references need cleanup (pending)

## Action Items

- [ ] Execute database migration to remove broken image references
- [ ] Add upload validation to prevent future issues
- [ ] Add file existence checks in view templates
- [ ] Create automated test for image uploads
- [ ] Add monitoring for 404 errors

## Commands for Production Fix

```bash
# Create missing directory
python3 agents/deploy_agent.py exec 'mkdir -p /home/dalthaus/public_html/uploads/content/teasers/2025/10'

# Apply database fix (need to run on production database)
mysql -u dalthaus_maincms -p dalthaus_maincms < database_migrations/fix_missing_teaser_images.sql
```

## Prevention Code Sample

```php
// In Content controller store/update methods
if (!empty($_FILES['teaser_image']['name'])) {
    $uploadResult = $fileUpload->upload('teaser_image', 'teasers');
    
    if (!$uploadResult['success']) {
        // Don't save to database if upload failed
        $this->addError('teaser_image', 'Image upload failed: ' . $uploadResult['error']);
        return false;
    }
    
    // Verify file actually exists
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/uploads/content/teasers/' . $uploadResult['path'];
    if (!file_exists($fullPath)) {
        $this->addError('teaser_image', 'Uploaded file not found at expected location');
        return false;
    }
    
    $data['teaser_image'] = $uploadResult['filename'];
}
```