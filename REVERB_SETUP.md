# Laravel Reverb WebSocket Setup

This guide explains how to set up and manage the Laravel Reverb WebSocket server for real-time messaging.

## Automatic Setup (via deploy.sh)

The deployment script (`deploy.sh`) automatically handles Reverb setup and restart. On first deploy, it will:

1. Copy `reverb.conf` to `/etc/supervisor/conf.d/`
2. Register the Reverb process with Supervisor
3. Start Reverb automatically

On subsequent deploys, it will simply restart the Reverb process.

## Manual Setup (One-time)

If you need to set up Reverb manually:

### 1. Install Supervisor (if not already installed)

```bash
sudo apt-get update
sudo apt-get install supervisor
```

### 2. Copy the Supervisor configuration

```bash
sudo cp reverb.conf /etc/supervisor/conf.d/reverb.conf
```

### 3. Update the paths in reverb.conf

Make sure the paths match your server setup:
- Replace `/home/forge/api.mmo.supply` with your actual site path
- Verify the PHP path (`/usr/bin/php`)

### 4. Load the configuration

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reverb
```

## Managing Reverb

### Check Status
```bash
sudo supervisorctl status reverb
```

### Start Reverb
```bash
sudo supervisorctl start reverb
```

### Stop Reverb
```bash
sudo supervisorctl stop reverb
```

### Restart Reverb
```bash
sudo supervisorctl restart reverb
```

### View Logs
```bash
tail -f storage/logs/reverb.log
```

## Troubleshooting

### Reverb not starting

1. **Check the log file:**
   ```bash
   tail -f storage/logs/reverb.log
   ```

2. **Verify environment variables are set:**
   ```bash
   php artisan config:show broadcasting
   ```

3. **Test Reverb manually:**
   ```bash
   php artisan reverb:start --debug
   ```

### Port already in use

If port 8080 is already in use:

1. Find the process:
   ```bash
   sudo lsof -i :8080
   ```

2. Kill the old process:
   ```bash
   sudo kill -9 <PID>
   ```

3. Restart Reverb:
   ```bash
   sudo supervisorctl restart reverb
   ```

### Permission issues

Ensure the `forge` user has access to the logs directory:
```bash
chmod -R 775 storage/logs
chown -R forge:forge storage/logs
```

## Configuration

Reverb configuration is in `.env`:

```env
REVERB_APP_ID=mmosupply
REVERB_APP_KEY=mmosupply
REVERB_APP_SECRET=your-secret-key-here
REVERB_HOST=api.mmo.supply
REVERB_PORT=8080
REVERB_SCHEME=https
```

## Nginx Configuration (Reverse Proxy)

To make Reverb accessible via standard HTTPS (port 443), add this to your Nginx config:

```nginx
# WebSocket proxy for Reverb
location /app {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_cache_bypass $http_upgrade;

    # Timeouts
    proxy_connect_timeout 7d;
    proxy_send_timeout 7d;
    proxy_read_timeout 7d;
}
```

Then restart Nginx:
```bash
sudo service nginx restart
```

## Production Checklist

- [ ] Reverb is running via Supervisor
- [ ] Logs are being written to `storage/logs/reverb.log`
- [ ] Port 8080 is accessible (or proxied via Nginx)
- [ ] Environment variables are set correctly
- [ ] Firewall allows WebSocket connections
- [ ] SSL/TLS is configured for secure WebSocket connections

## Monitoring

To monitor Reverb uptime and performance:

```bash
# Check if process is running
ps aux | grep reverb

# Check memory usage
top -p $(pgrep -f "reverb:start")

# Count active WebSocket connections
netstat -an | grep :8080 | grep ESTABLISHED | wc -l
```
