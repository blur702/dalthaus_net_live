# Test File Cleanup Plan

## Current State

**Total test files:** 146 in `testing/e2e/`

### Breakdown by Category
- Debug/Diagnostic tests: 16
- Simple/Basic tests: 18
- Comprehensive/Final tests: 23
- Autosave tests: 17
- Modal tests: 31
- Dual-image tests: 22
- Remember-me tests: 7
- TinyMCE tests: 19
- Pagebreak tests: 7
- Login tests: 8
- Production tests: 18

## Problem

The test directory is cluttered with:
1. **Debug tests** - Created for troubleshooting, no longer needed
2. **Duplicate tests** - Multiple tests for the same functionality
3. **Obsolete tests** - Tests for fixed bugs or removed features
4. **Incremental tests** - Draft versions replaced by final tests

## Cleanup Strategy

### ✅ Keep (Essential Tests - ~20 files)

**Core Functionality:**
- `cms.spec.js` - Main CMS test
- `prod-login-test.spec.js` or `simple-login-test.spec.js` - Login flow
- `modal-close-fix-test.spec.js` - Recent modal fix
- `teaser-image-link-test.spec.js` - Recent teaser fix
- `production-teaser-verification.spec.js` - Production verification
- `production-media-page-test.spec.js` - Media management

**Feature-Specific (Keep ONE per feature):**
- ONE autosave test (keep most comprehensive)
- ONE modal test (keep most comprehensive)
- ONE dual-image test (keep most comprehensive)
- ONE TinyMCE test (keep most comprehensive)
- ONE remember-me test (if feature is active)

### 🗑️ Remove (Debug & Obsolete - ~126 files)

#### Debug/Diagnostic Tests (16 files - REMOVE ALL)
```
debug-admin-articles-html.spec.js
debug-admin-login.spec.js
debug-article-data.spec.js
debug-auth-detailed.spec.js
debug-database-check.spec.js
debug-raw-article-data.spec.js
debug-remember-me-specific.spec.js
debug-remember-me.spec.js
debug-tinymce-button.spec.js
debug-tinymce-initialization.spec.js
debug-url-alias-database.spec.js
debug-url-alias-simple.spec.js
login-debug.spec.js
button-registration-debug.spec.js
capture-admin-articles-state.spec.js
diagnose-image-loading-modal.spec.js
```

#### Autosave Tests (Keep 1, Remove 16)
**KEEP:** `test-autosave.spec.js` (if exists) or most recent comprehensive one

**REMOVE:**
```
autosave-database-local-verification.spec.js
autosave-database-verification.spec.js
autosave-debugging.spec.js
autosave-enhancement-verification.spec.js
autosave-feedback-test.spec.js
autosave-final-verification.spec.js
autosave-interface-final-verification.spec.js
autosave-interface-summary-verification.spec.js
autosave-verification.spec.js
comprehensive-autosave-local-test.spec.js
create-autosave-test.spec.js
minimalistic-autosave-indicator.spec.js
production-autosave-interface-verification.spec.js
production-autosave-management-comprehensive.spec.js
production-autosave-verification.spec.js
simple-autosave-test.spec.js
```

#### Modal Tests (Keep 1, Remove 30)
**KEEP:** `modal-close-fix-test.spec.js` (recent fix)

**REMOVE:**
```
comprehensive-modal-functionality-test.spec.js
comprehensive-modal-test.spec.js
diagnose-image-loading-modal.spec.js
final-modal-functionality-verification.spec.js
final-verification-modal-fix-deployed.spec.js
fix-uploaded-images-modal-functionality.spec.js
focused-modal-verification-test.spec.js
frontend-modal-functionality-test.spec.js
frontend-modal-verification.spec.js
frontend-modal-with-content-test.spec.js
manual-modal-test.spec.js
modal-functionality-test.spec.js
modal-functionality-with-test-images.spec.js
modal-initialization-diagnosis.spec.js
modal-x-button-verification.spec.js
production-dual-image-modal.spec.js
production-modal-investigation.spec.js
production-modal-verification.spec.js
production-x-button-verification.spec.js
... and others
```

#### Dual-Image Tests (Keep 1, Remove 21)
**KEEP:** ONE comprehensive dual-image test

**REMOVE:**
```
comprehensive-dual-image-e2e-test.spec.js
comprehensive-dual-image-modal-test.spec.js
comprehensive-dual-image-modal-workflow.spec.js
comprehensive-dual-image-test.spec.js
complete-dual-image-workflow.spec.js
custom-dual-image-button-verification.spec.js
custom-dual-image-debug.spec.js
dual-image-diagnosis.spec.js
dual-image-modal-workflow.spec.js
dual-image-system-diagnosis.spec.js
dual-image-workflow-final.spec.js
final-custom-dual-image-test.spec.js
fixed-dual-image-test.spec.js
focused-dual-image-test.spec.js
frontend-dual-image-verification.spec.js
live-site-dual-image-test.spec.js
manual-dual-image-test.spec.js
post-fix-dual-image-test.spec.js
... and others
```

#### TinyMCE Tests (Keep 1, Remove 18)
**KEEP:** ONE comprehensive TinyMCE test

**REMOVE:**
```
admin-tinymce-verification.spec.js
comprehensive-tinymce-image-test.spec.js
debug-tinymce-button.spec.js
debug-tinymce-initialization.spec.js
production-tinymce-button-verification.spec.js
production-tinymce-comprehensive-test.spec.js
production-tinymce-inline-verification.spec.js
simple-upload-debug.spec.js
tinymce-image-upload-debug.spec.js
tinymce-image-upload-test.spec.js
... and others
```

#### Remember-Me Tests (Keep 0-1, Remove 6-7)
**KEEP:** ONE if feature is actively used

**REMOVE:**
```
debug-remember-me-specific.spec.js
debug-remember-me.spec.js
remember-me-database-test.spec.js
remember-me-diagnosis.spec.js
remember-me-focused.spec.js
remember-me-simple-test.spec.js
remember-me-verification.spec.js
```

#### Pagebreak Tests (Keep 0, Remove ALL 7)
**Reason:** If pagebreak feature isn't used, remove all

**REMOVE:**
```
article-pagebreak-functionality.spec.js
comprehensive-pagebreak-test.spec.js
correct-pagebreak-test.spec.js
detailed-pagebreak-analysis.spec.js
final-pagebreak-diagnosis.spec.js
pagebreak-functionality-test.spec.js
... and others
```

#### Production/Login Tests (Keep 2-3, Remove rest)
**KEEP:**
- `prod-login-test.spec.js` or `simple-login-test.spec.js`
- `production-media-page-test.spec.js`
- `production-teaser-verification.spec.js`

**REMOVE:**
```
check-dashboard-content.spec.js
cookie-test.spec.js
debug-login-test.spec.js
debug-production.spec.js
extensive-login-test.spec.js
extract-session-cookie.spec.js
final-login-verification.spec.js
prod-diagnostic.spec.js
redirect-trace-test.spec.js
... and others
```

---

## Proposed Final Test Suite (~20 files)

### Core Tests (5)
1. `cms.spec.js` - Main CMS functionality
2. `simple-login-test.spec.js` - Login flow
3. `logout-test.spec.js` - Logout flow
4. `admin-access-test.spec.js` - Auth checks
5. `admin-content-creation-test.spec.js` - Content creation

### Recent Fixes (3)
6. `modal-close-fix-test.spec.js` - Modal fix verification
7. `teaser-image-link-test.spec.js` - Teaser links
8. `production-teaser-verification.spec.js` - Production teaser verification

### Feature Tests (8)
9. `test-autosave.spec.js` - Autosave (keep most comprehensive)
10. `production-media-page-test.spec.js` - Media management
11. `media-browser-comprehensive.spec.js` - Media browser
12. ONE dual-image test (most comprehensive)
13. ONE TinyMCE test (most comprehensive)
14. `admin-articles-view-links.spec.js` - View links
15. `article-pagebreak-functionality.spec.js` - Pagebreak (if used)
16. `remember-me-verification.spec.js` - Remember me (if used)

### Production Verification (4)
17. `production-functionality-final-test.spec.js` - Overall production test
18. `production-media-page-test.spec.js` - Production media
19. `production-modal-verification.spec.js` - Production modal (if different from fix test)
20. `cache-busting-test.spec.js` - Cache testing

---

## Implementation Steps

1. **Backup tests directory** (git already tracks, but create archive)
2. **Identify the BEST test for each feature** (most comprehensive, most recent)
3. **Remove all debug tests** (16 files)
4. **Remove duplicate tests** (~100 files)
5. **Keep only essential and comprehensive tests** (~20 files)
6. **Update test documentation**
7. **Test remaining test suite** to ensure coverage

---

## Benefits

### Before:
- 146 test files
- Unclear which tests to run
- Many obsolete/duplicate tests
- Slow test execution time
- Hard to maintain

### After:
- ~20 essential test files
- Clear purpose for each test
- Up-to-date and relevant
- Fast test execution
- Easy to maintain
- ~85% reduction in test files

---

## Safety

- Git history preserves all deleted tests
- Can restore any test if needed
- Tests are for development, not production code
- Removing old tests doesn't affect functionality

---

## Next Steps

1. Review this plan
2. Confirm features to keep (autosave, modal, dual-image, etc.)
3. Execute cleanup
4. Run remaining tests to verify
5. Update testing documentation
6. Commit changes
