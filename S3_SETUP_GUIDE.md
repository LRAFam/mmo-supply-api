# AWS S3 Setup Guide for MMO Supply

This guide will help you set up AWS S3 for storing product images in the MMO Supply marketplace.

## Prerequisites

- AWS Account ([Sign up here](https://aws.amazon.com/))
- Basic understanding of AWS IAM and S3

## Step 1: Create an S3 Bucket

1. Log in to the [AWS Management Console](https://console.aws.amazon.com/)
2. Navigate to **S3** service
3. Click **Create bucket**
4. Configure your bucket:
   - **Bucket name**: `mmo-supply-products` (or your preferred name)
   - **AWS Region**: Select closest to your users (e.g., `us-east-1`)
   - **Object Ownership**: ACLs disabled (recommended)
   - **Block Public Access**: Uncheck "Block all public access"
     - ✅ We want images to be publicly accessible
     - Acknowledge the warning
   - **Bucket Versioning**: Disabled (unless you want version control)
   - **Default encryption**: Enable with SSE-S3
5. Click **Create bucket**

## Step 2: Configure Bucket Policy for Public Read Access

1. Select your newly created bucket
2. Go to the **Permissions** tab
3. Scroll to **Bucket policy** and click **Edit**
4. Add the following policy (replace `mmo-supply-products` with your bucket name):

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "PublicReadGetObject",
            "Effect": "Allow",
            "Principal": "*",
            "Action": "s3:GetObject",
            "Resource": "arn:aws:s3:::mmo-supply-products/*"
        }
    ]
}
```

5. Click **Save changes**

## Step 3: Enable CORS (Cross-Origin Resource Sharing)

1. Still in the bucket's **Permissions** tab
2. Scroll to **Cross-origin resource sharing (CORS)**
3. Click **Edit**
4. Add the following CORS configuration:

```json
[
    {
        "AllowedHeaders": [
            "*"
        ],
        "AllowedMethods": [
            "GET",
            "PUT",
            "POST",
            "DELETE",
            "HEAD"
        ],
        "AllowedOrigins": [
            "http://localhost:3000",
            "https://yourdomain.com"
        ],
        "ExposeHeaders": [
            "ETag"
        ],
        "MaxAgeSeconds": 3000
    }
]
```

5. Replace `https://yourdomain.com` with your production domain
6. Click **Save changes**

## Step 4: Create IAM User with S3 Access

1. Navigate to **IAM** service in AWS Console
2. Click **Users** in the left sidebar
3. Click **Add users**
4. Configure user:
   - **User name**: `mmo-supply-s3-uploader`
   - **Access type**: Select "Access key - Programmatic access"
5. Click **Next: Permissions**
6. Click **Attach existing policies directly**
7. Search for and select **AmazonS3FullAccess** (or create a custom policy for more security)
8. Click **Next: Tags** (optional, skip)
9. Click **Next: Review**
10. Click **Create user**

### Custom IAM Policy (More Secure, Recommended for Production)

Instead of `AmazonS3FullAccess`, create a custom policy with limited permissions:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "s3:PutObject",
                "s3:GetObject",
                "s3:DeleteObject",
                "s3:ListBucket"
            ],
            "Resource": [
                "arn:aws:s3:::mmo-supply-products",
                "arn:aws:s3:::mmo-supply-products/*"
            ]
        }
    ]
}
```

## Step 5: Save Access Keys

⚠️ **IMPORTANT**: This is the only time you can view the **Secret Access Key**

1. After creating the user, you'll see:
   - **Access key ID**: `AKIAIOSFODNN7EXAMPLE`
   - **Secret access key**: `wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY`
2. **Download the .csv file** or copy these credentials immediately
3. Store them securely (never commit to Git!)

## Step 6: Configure Laravel Environment

1. Open `api/.env` file
2. Update the AWS configuration:

```env
# AWS S3 Configuration for Product Images
AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE
AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=mmo-supply-products
AWS_URL=
AWS_USE_PATH_STYLE_ENDPOINT=false

# Set filesystem disk to S3
FILESYSTEM_DISK=s3
```

3. Replace with your actual credentials from Step 5
4. Make sure `FILESYSTEM_DISK=s3` is set

## Step 7: Test the Upload

1. Start your Laravel API server:
```bash
cd api
php artisan serve
```

2. Start your Nuxt frontend:
```bash
cd frontend
npm run dev
```

3. Navigate to `http://localhost:3000/seller/create`
4. Create a new listing and upload an image
5. Check your S3 bucket - you should see the image in `product-images/` folder

## Troubleshooting

### Issue: "Access Denied" error when uploading

**Solution**:
- Verify IAM user has correct permissions
- Check bucket policy allows uploads
- Ensure AWS credentials in `.env` are correct

### Issue: Images not loading in browser

**Solution**:
- Verify bucket policy allows public read access (`s3:GetObject`)
- Check that "Block all public access" is disabled
- Verify CORS configuration includes your domain

### Issue: "The bucket does not allow ACLs"

**Solution**:
- Edit bucket settings
- Go to **Permissions** > **Object Ownership**
- Enable "ACLs enabled"
- OR update the upload code to not use ACLs (remove `'public'` parameter)

### Issue: Wrong region error

**Solution**:
- Verify `AWS_DEFAULT_REGION` in `.env` matches your bucket's region
- Check bucket region in S3 console

## Security Best Practices

1. **Never commit AWS credentials to Git**
   - Add `.env` to `.gitignore`
   - Use environment variables in production

2. **Use IAM policies with least privilege**
   - Don't use `AmazonS3FullAccess` in production
   - Create custom policies limiting to specific bucket/actions

3. **Enable CloudFront CDN (Optional but Recommended)**
   - Improves image loading speed globally
   - Provides DDoS protection
   - Reduces S3 costs

4. **Set up lifecycle policies**
   - Automatically delete orphaned images after X days
   - Move old images to cheaper storage classes

5. **Monitor costs**
   - Enable AWS Cost Explorer
   - Set up billing alerts
   - Most small apps cost < $5/month for S3

## Cost Estimation

For a marketplace with moderate traffic:

- **Storage**: $0.023 per GB/month
  - 10GB images = ~$0.23/month
- **PUT Requests**: $0.005 per 1,000 requests
  - 1,000 uploads = ~$0.005
- **GET Requests**: $0.0004 per 1,000 requests
  - 100,000 views = ~$0.04
- **Data Transfer**: First 100GB/month free, then $0.09/GB

**Estimated monthly cost for small marketplace**: $1-10/month

## Alternative: Local Storage for Development

If you don't want to set up S3 yet, you can use local storage:

1. In `api/.env`:
```env
FILESYSTEM_DISK=public
```

2. Create storage link:
```bash
php artisan storage:link
```

3. Images will be stored in `storage/app/public/product-images/`

⚠️ **Note**: Local storage won't work in production with multiple servers or serverless deployments.

## Next Steps

- ✅ S3 bucket created and configured
- ✅ IAM user created with access keys
- ✅ Laravel configured with S3 credentials
- ✅ Test image upload working
- 🔄 (Optional) Set up CloudFront CDN
- 🔄 (Optional) Configure image optimization/resizing
- 🔄 (Optional) Set up automated backups

## Support

For issues specific to AWS:
- [AWS S3 Documentation](https://docs.aws.amazon.com/s3/)
- [AWS Support](https://console.aws.amazon.com/support/)

For MMO Supply specific issues:
- Check the main README.md
- Review error logs in `storage/logs/laravel.log`
