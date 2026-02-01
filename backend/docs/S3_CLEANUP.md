# S3 File Cleanup Guide

## Overview

PDF ticket attachments are stored in AWS S3 with pre-signed URLs valid for **7 days** (maximum allowed by AWS S3 SigV4). To manage storage costs and comply with data retention policies, old files should be automatically deleted.

## Recommended: S3 Lifecycle Rule

Set up automatic deletion of old PDF files using S3 Lifecycle Rules.

### Setup Steps (AWS Console)

1. Go to **AWS S3 Console**
2. Select bucket: `uffizi-tickets-bucket`
3. Go to **Management** tab
4. Click **Create lifecycle rule**
5. Configure:
   - **Rule name**: `delete-old-attachments`
   - **Prefix**: `attachments/`
   - **Rule actions**: Check "Expire current versions of objects"
   - **Days after creation**: `60`
6. Click **Create rule**

### AWS CLI Alternative

```bash
# Create lifecycle rule via CLI
aws s3api put-bucket-lifecycle-configuration \
  --bucket uffizi-tickets-bucket \
  --lifecycle-configuration '{
    "Rules": [
      {
        "ID": "delete-old-attachments",
        "Status": "Enabled",
        "Filter": {
          "Prefix": "attachments/"
        },
        "Expiration": {
          "Days": 60
        }
      }
    ]
  }'
```

### Verify Rule

```bash
aws s3api get-bucket-lifecycle-configuration --bucket uffizi-tickets-bucket
```

---

## What This Does

| Action | Timing |
|--------|--------|
| PDF uploaded | Day 0 |
| Customer receives WhatsApp with PDF | Day 0 |
| Pre-signed URL expires | Day 7 (AWS max) |
| S3 file automatically deleted | Day 60 |

---

## Why 60 Days?

- **14 days**: Pre-signed URLs remain valid for customer access
- **30 days**: Buffer for customers who need to re-access
- **60 days**: Final cleanup, covers all edge cases
- Older tickets are typically no longer needed

---

## Storage Cost Estimate

| Files/Month | Size/File | Monthly Storage | After Cleanup |
|-------------|-----------|-----------------|---------------|
| 500 | 500 KB | ~250 MB | ~500 MB (2 months) |
| 1000 | 500 KB | ~500 MB | ~1 GB (2 months) |
| 5000 | 500 KB | ~2.5 GB | ~5 GB (2 months) |

At AWS S3 Standard pricing (~$0.023/GB/month), this is negligible.

---

## Manual Cleanup (If Needed)

### List Old Files

```bash
# List all attachments
aws s3 ls s3://uffizi-tickets-bucket/attachments/ --recursive --human-readable

# Count files
aws s3 ls s3://uffizi-tickets-bucket/attachments/ --recursive | wc -l
```

### Delete Specific Booking's Files

```bash
# Delete files for a specific booking
aws s3 rm s3://uffizi-tickets-bucket/attachments/BOOKING_ID/ --recursive
```

### Delete Files Older Than N Days

```bash
# Find and delete files older than 60 days (careful!)
aws s3 ls s3://uffizi-tickets-bucket/attachments/ --recursive | \
  awk '$1 < "'$(date -d '60 days ago' +%Y-%m-%d)'" {print $4}' | \
  xargs -I {} aws s3 rm s3://uffizi-tickets-bucket/{}
```

---

## Laravel Cleanup Command (Optional)

A cleanup command exists at `app/Console/Commands/CleanupOrphanedAttachments.php`:

```bash
# Run orphaned attachment cleanup
php artisan attachments:cleanup-orphaned

# Schedule in app/Console/Kernel.php
$schedule->command('attachments:cleanup-orphaned')->daily();
```

This cleans up database records for attachments that:
- Have no associated message
- Are older than 24 hours

---

## Database Cleanup

The `message_attachments` table has soft references. When S3 files are deleted by lifecycle rules, the database records remain but the files are gone.

To clean up orphaned database records:

```php
// In Laravel Tinker
$orphaned = \App\Models\MessageAttachment::where('created_at', '<', now()->subDays(60))
    ->whereNotNull('path')
    ->get();

foreach ($orphaned as $attachment) {
    if (!$attachment->exists()) {
        $attachment->delete();
    }
}
```

---

## Monitoring

### Check Bucket Size

```bash
aws s3 ls s3://uffizi-tickets-bucket --recursive --summarize | tail -2
```

### CloudWatch Metrics

Enable S3 metrics in AWS Console to monitor:
- `BucketSizeBytes` - Total storage used
- `NumberOfObjects` - File count

---

## Security Notes

1. **Pre-signed URLs** are secure - they contain a signature that expires
2. **No public access** - Bucket should have public access blocked
3. **IAM permissions** - Only the app IAM user should have write access
4. **HTTPS only** - All S3 URLs use HTTPS

---

## Summary

| Setting | Value |
|---------|-------|
| Pre-signed URL expiry | 7 days (AWS SigV4 max) |
| S3 lifecycle deletion | 60 days |
| Bucket | uffizi-tickets-bucket |
| Region | eu-north-1 |
| Prefix | attachments/ |

## Note on URL Expiry

AWS S3 pre-signed URLs using Signature Version 4 have a **maximum expiry of 7 days** (604800 seconds). This is an AWS limitation, not a configuration choice.

If customers need access after 7 days, they can:
1. Reply to the WhatsApp message requesting a new link
2. Contact support for a fresh download link
