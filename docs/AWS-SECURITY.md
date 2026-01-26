# AWS Security Best Practices for Uffizi Ticket App

This document outlines security requirements and best practices for AWS services used by the Uffizi Ticket App.

## Table of Contents

1. [S3 Bucket Security](#s3-bucket-security)
2. [IAM Policy Recommendations](#iam-policy-recommendations)
3. [Pre-signed URL Best Practices](#pre-signed-url-best-practices)
4. [Security Checklist](#security-checklist)

---

## S3 Bucket Security

### Bucket Configuration Requirements

The S3 bucket used for storing ticket PDF attachments must follow these security requirements:

#### 1. Block Public Access (REQUIRED)

Enable all public access blocks on the bucket:

```json
{
  "BlockPublicAcls": true,
  "IgnorePublicAcls": true,
  "BlockPublicPolicy": true,
  "RestrictPublicBuckets": true
}
```

**AWS Console**: S3 > Bucket > Permissions > Block public access > Edit > Enable ALL options

#### 2. Bucket Policy

Do NOT add any bucket policy that grants public access. The bucket should only be accessible via:
- IAM user credentials (for upload/delete operations)
- Pre-signed URLs (for temporary read access)

#### 3. Encryption at Rest

Enable server-side encryption:

```json
{
  "Rules": [
    {
      "ApplyServerSideEncryptionByDefault": {
        "SSEAlgorithm": "AES256"
      },
      "BucketKeyEnabled": true
    }
  ]
}
```

**AWS Console**: S3 > Bucket > Properties > Default encryption > Edit > Enable SSE-S3

#### 4. Versioning (Recommended)

Enable versioning to protect against accidental deletion:

**AWS Console**: S3 > Bucket > Properties > Bucket Versioning > Edit > Enable

#### 5. Lifecycle Rules (Recommended)

Set up lifecycle rules to automatically delete old files:

```json
{
  "Rules": [
    {
      "ID": "DeleteOldAttachments",
      "Status": "Enabled",
      "Filter": {
        "Prefix": "attachments/"
      },
      "Expiration": {
        "Days": 90
      }
    }
  ]
}
```

---

## IAM Policy Recommendations

### Principle of Least Privilege

Create a dedicated IAM user for the application with minimal required permissions.

### Recommended IAM Policy

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "AllowS3BucketOperations",
      "Effect": "Allow",
      "Action": [
        "s3:PutObject",
        "s3:GetObject",
        "s3:DeleteObject",
        "s3:ListBucket"
      ],
      "Resource": [
        "arn:aws:s3:::YOUR-BUCKET-NAME",
        "arn:aws:s3:::YOUR-BUCKET-NAME/*"
      ]
    }
  ]
}
```

### What This Policy Allows

| Action | Purpose |
|--------|---------|
| `s3:PutObject` | Upload PDF attachments |
| `s3:GetObject` | Generate pre-signed URLs, download files |
| `s3:DeleteObject` | Remove attachments when booking is deleted |
| `s3:ListBucket` | List files (for cleanup operations) |

### What This Policy Does NOT Allow

- Creating or deleting buckets
- Modifying bucket policies or permissions
- Accessing other buckets
- IAM operations
- Any other AWS services

### Creating the IAM User

1. Go to AWS Console > IAM > Users > Create user
2. Name: `uffizi-app-s3-user` (no console access needed)
3. Attach the custom policy above
4. Create access keys for programmatic access
5. Store credentials securely (use environment variables, never commit to code)

---

## Pre-signed URL Best Practices

Pre-signed URLs provide temporary access to private S3 objects without exposing credentials.

### URL Expiration Times

| Use Case | Recommended Expiration |
|----------|----------------------|
| Immediate download (user clicked) | 15 minutes |
| Email attachment link | 24-48 hours |
| Maximum allowed | 7 days (604800 seconds) |

### Current Implementation

The application uses 1-hour expiration for pre-signed URLs:

```php
// In AttachmentController.php or similar
$url = Storage::disk('s3')->temporaryUrl(
    $path,
    now()->addHour()  // Expires in 1 hour
);
```

### Security Considerations

1. **Short expiration times**: Use the shortest practical expiration
2. **One-time use**: Consider regenerating URLs for each access
3. **Logging**: Log when pre-signed URLs are generated for audit trail
4. **HTTPS only**: Pre-signed URLs always use HTTPS

### Revoking Pre-signed URLs

Pre-signed URLs cannot be directly revoked. To invalidate them:
1. Delete the object from S3
2. Rotate the IAM user's access keys (invalidates ALL pre-signed URLs)
3. Use bucket policies to restrict access (complex, not recommended)

---

## Security Checklist

### Initial Setup

- [ ] Create dedicated IAM user with minimal permissions
- [ ] Enable all public access blocks on S3 bucket
- [ ] Enable server-side encryption (SSE-S3)
- [ ] Enable bucket versioning
- [ ] Set up lifecycle rules for automatic cleanup
- [ ] Store AWS credentials in environment variables only
- [ ] Never commit AWS credentials to version control

### Ongoing Maintenance

- [ ] Rotate IAM access keys every 90 days
- [ ] Review IAM policy permissions quarterly
- [ ] Monitor S3 access logs for suspicious activity
- [ ] Review and clean up unused attachments
- [ ] Keep AWS SDK and dependencies updated

### Environment Variables Required

```env
# In backend/.env (NEVER commit actual values)
AWS_ACCESS_KEY_ID=your_access_key_here
AWS_SECRET_ACCESS_KEY=your_secret_key_here
AWS_DEFAULT_REGION=eu-west-1
AWS_BUCKET=your-bucket-name
```

---

## Additional Resources

- [AWS S3 Security Best Practices](https://docs.aws.amazon.com/AmazonS3/latest/userguide/security-best-practices.html)
- [IAM Best Practices](https://docs.aws.amazon.com/IAM/latest/UserGuide/best-practices.html)
- [Pre-signed URLs Documentation](https://docs.aws.amazon.com/AmazonS3/latest/userguide/ShareObjectPreSignedURL.html)
- [Laravel Filesystem S3 Driver](https://laravel.com/docs/filesystem#amazon-s3-compatible-filesystems)
