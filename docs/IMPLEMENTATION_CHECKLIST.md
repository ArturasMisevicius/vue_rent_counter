# Advanced Relationships Implementation Checklist

Use this checklist to implement the advanced relationship patterns in your application.

## ✅ Phase 1: Database Setup

- [ ] **Run migrations**
  ```bash
  php artisan migrate
  ```

- [ ] **Verify tables created**
  - [ ] `comments` table
  - [ ] `attachments` table
  - [ ] `tags` table
  - [ ] `taggables` table
  - [ ] `activities` table
  - [ ] `user_permissions` table
  - [ ] Enhanced `property_tenant` pivot

- [ ] **Check indexes**
  ```bash
  php artisan db:show
  ```

## ✅ Phase 2: Model Configuration

### Invoice Model
- [ ] Add `HasComments` trait
- [ ] Add `HasAttachments` trait
- [ ] Add `HasTags` trait
- [ ] Add `HasActivities` trait
- [ ] Test relationships work

### Property Model
- [ ] Add `HasComments` trait
- [ ] Add `HasAttachments` trait
- [ ] Add `HasTags` trait
- [ ] Add `HasActivities` trait
- [ ] Update `tenants()` relationship to use `PropertyTenantPivot`
- [ ] Test relationships work

### Meter Model
- [ ] Add `HasComments` trait
- [ ] Add `HasAttachments` trait
- [ ] Add `HasTags` trait
- [ ] Add `HasActivities` trait
- [ ] Test relationships work

### Building Model
- [ ] Add `HasComments` trait
- [ ] Add `HasAttachments` trait
- [ ] Add `HasTags` trait
- [ ] Add `HasActivities` trait
- [ ] Test relationships work

### Tenant Model
- [ ] Add `HasComments` trait
- [ ] Add `HasAttachments` trait
- [ ] Add `HasTags` trait
- [ ] Add `HasActivities` trait
- [ ] Update `properties()` relationship to use `PropertyTenantPivot`
- [ ] Test relationships work

## ✅ Phase 3: Factory Setup

- [ ] **Create Comment factory**
  ```bash
  php artisan make:factory CommentFactory
  ```

- [ ] **Create Attachment factory**
  ```bash
  php artisan make:factory AttachmentFactory
  ```

- [ ] **Create Tag factory**
  ```bash
  php artisan make:factory TagFactory
  ```

- [ ] **Create Activity factory**
  ```bash
  php artisan make:factory ActivityFactory
  ```

- [ ] **Test factories**
  ```bash
  php artisan tinker
  >>> Comment::factory()->count(5)->create()
  ```

## ✅ Phase 4: Testing

- [ ] **Create unit tests**
  - [ ] CommentTest
  - [ ] AttachmentTest
  - [ ] TagTest
  - [ ] ActivityTest
  - [ ] PropertyTenantPivotTest

- [ ] **Create feature tests**
  - [ ] CommentsTest
  - [ ] AttachmentsTest
  - [ ] TaggingTest
  - [ ] ActivityLoggingTest

- [ ] **Run tests**
  ```bash
  php artisan test --filter=Comment
  php artisan test --filter=Attachment
  php artisan test --filter=Tag
  php artisan test --filter=Activity
  ```

## ✅ Phase 5: Filament Integration

### Comments
- [ ] Add comments table to Invoice resource
- [ ] Add comments table to Property resource
- [ ] Add comments table to Meter resource
- [ ] Create CommentResource (optional)
- [ ] Add comment form modal
- [ ] Add reply functionality

### Attachments
- [ ] Add file upload to Invoice resource
- [ ] Add file upload to Property resource
- [ ] Add attachments table/gallery
- [ ] Configure file storage disk
- [ ] Add file download functionality
- [ ] Add file preview for images

### Tags
- [ ] Create TagResource
- [ ] Add tag select to Invoice resource
- [ ] Add tag select to Property resource
- [ ] Add tag select to Meter resource
- [ ] Add tag filter to tables
- [ ] Add tag management page

### Activities
- [ ] Create ActivityResource (read-only)
- [ ] Add activity log widget to dashboard
- [ ] Add activity timeline to model pages
- [ ] Add activity filters

## ✅ Phase 6: API Endpoints (Optional)

- [ ] **Comments API**
  - [ ] GET /api/invoices/{id}/comments
  - [ ] POST /api/invoices/{id}/comments
  - [ ] PUT /api/comments/{id}
  - [ ] DELETE /api/comments/{id}

- [ ] **Attachments API**
  - [ ] GET /api/invoices/{id}/attachments
  - [ ] POST /api/invoices/{id}/attachments
  - [ ] GET /api/attachments/{id}/download
  - [ ] DELETE /api/attachments/{id}

- [ ] **Tags API**
  - [ ] GET /api/tags
  - [ ] POST /api/tags
  - [ ] GET /api/properties/{id}/tags
  - [ ] POST /api/properties/{id}/tags

## ✅ Phase 7: Policies & Authorization

- [ ] **Create CommentPolicy**
  ```bash
  php artisan make:policy CommentPolicy --model=Comment
  ```
  - [ ] viewAny
  - [ ] view
  - [ ] create
  - [ ] update (only own comments)
  - [ ] delete (only own comments)

- [ ] **Create AttachmentPolicy**
  ```bash
  php artisan make:policy AttachmentPolicy --model=Attachment
  ```
  - [ ] viewAny
  - [ ] view
  - [ ] create
  - [ ] delete

- [ ] **Create TagPolicy**
  ```bash
  php artisan make:policy TagPolicy --model=Tag
  ```
  - [ ] viewAny
  - [ ] view
  - [ ] create (admin only)
  - [ ] update (admin only)
  - [ ] delete (admin only)

- [ ] **Register policies in AuthServiceProvider**

## ✅ Phase 8: Configuration

- [ ] **Configure morph map** (optional)
  ```php
  // app/Providers/AppServiceProvider.php
  Relation::enforceMorphMap([...]);
  ```

- [ ] **Configure file storage**
  ```php
  // config/filesystems.php
  'disks' => [
      'attachments' => [...],
  ],
  ```

- [ ] **Set file upload limits**
  ```php
  // php.ini or .htaccess
  upload_max_filesize = 10M
  post_max_size = 10M
  ```

## ✅ Phase 9: Seeding (Optional)

- [ ] **Create seeders**
  ```bash
  php artisan make:seeder CommentSeeder
  php artisan make:seeder TagSeeder
  ```

- [ ] **Add to DatabaseSeeder**
  ```php
  $this->call([
      CommentSeeder::class,
      TagSeeder::class,
  ]);
  ```

- [ ] **Run seeders**
  ```bash
  php artisan db:seed
  ```

## ✅ Phase 10: Performance Optimization

- [ ] **Add eager loading to queries**
  ```php
  Invoice::with(['comments.user', 'attachments', 'tags'])->get();
  ```

- [ ] **Use withCount for counting**
  ```php
  Invoice::withCount(['comments', 'attachments'])->get();
  ```

- [ ] **Add caching for popular tags**
  ```php
  Cache::remember('popular_tags', 3600, fn() => Tag::popular(10)->get());
  ```

- [ ] **Monitor query performance**
  ```bash
  php artisan telescope:install # Optional
  ```

## ✅ Phase 11: Documentation

- [ ] Update API documentation
- [ ] Create user guide for comments
- [ ] Create user guide for attachments
- [ ] Create user guide for tags
- [ ] Document activity log usage
- [ ] Add examples to README

## ✅ Phase 12: Deployment

- [ ] **Backup database**
  ```bash
  php artisan backup:run
  ```

- [ ] **Run migrations on production**
  ```bash
  php artisan migrate --force
  ```

- [ ] **Clear caches**
  ```bash
  php artisan optimize:clear
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```

- [ ] **Test in production**
  - [ ] Create comment
  - [ ] Upload file
  - [ ] Add tag
  - [ ] Check activity log

## 🎯 Quick Wins (Start Here)

If you want to see results quickly, implement in this order:

1. **Comments on Invoices** (Most useful for communication)
   - Run migrations
   - Add `HasComments` to Invoice
   - Add comment form in Filament
   - Test

2. **Tags on Properties** (Easy categorization)
   - Add `HasTags` to Property
   - Create some tags
   - Add tag select in Filament
   - Test filtering

3. **Activity Logging** (Automatic, no UI needed)
   - Add `HasActivities` to Invoice
   - Update an invoice
   - Check activities table
   - Add activity widget to dashboard

4. **Attachments on Invoices** (File management)
   - Add `HasAttachments` to Invoice
   - Configure file storage
   - Add upload form
   - Test download

## 📊 Progress Tracking

Track your progress:

```
Phase 1: Database Setup          [ ] 0/3
Phase 2: Model Configuration     [ ] 0/20
Phase 3: Factory Setup           [ ] 0/5
Phase 4: Testing                 [ ] 0/7
Phase 5: Filament Integration    [ ] 0/18
Phase 6: API Endpoints           [ ] 0/12
Phase 7: Policies                [ ] 0/7
Phase 8: Configuration           [ ] 0/3
Phase 9: Seeding                 [ ] 0/3
Phase 10: Performance            [ ] 0/4
Phase 11: Documentation          [ ] 0/6
Phase 12: Deployment             [ ] 0/4

Total Progress: 0/92 (0%)
```

## 🆘 Need Help?

- Review: `docs/examples/ADVANCED_RELATIONSHIPS_USAGE.md`
- Testing: `docs/examples/ADVANCED_RELATIONSHIPS_TESTING.md`
- Summary: `docs/ADVANCED_RELATIONSHIPS_SUMMARY.md`
- Laravel Docs: https://laravel.com/docs/eloquent-relationships

