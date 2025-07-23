# Uppi Server Agent Integration Guide

This document explains how to integrate the Uppi Server Agent daemon with your Uppi monitoring instance.

## Overview

The daemon provides server monitoring capabilities by:
1. Collecting system metrics (CPU, memory, disk, network)
2. Sending encrypted data to your Uppi instance via HMAC-authenticated API calls
3. Auto-updating itself from GitHub releases
4. Running as a systemd service for reliability

## Installation Methods

### 1. One-liner Installation (Recommended)

When creating a server in the Uppi dashboard, provide users with this command:

```bash
curl -sSL https://raw.githubusercontent.com/janyksteenbeek/uppi-server-agent/main/install.sh | sudo bash -s -- <64-char-secret>
```

With custom parameters:
```bash
curl -sSL https://raw.githubusercontent.com/janyksteenbeek/uppi-server-agent/main/install.sh | sudo bash -s -- <secret> <instance-url> <interval-minutes>
```

### 2. Manual Installation

For advanced users or custom deployments:

1. Download binary from GitHub releases
2. Install to `/usr/local/bin/uppi-agent`
3. Create systemd service
4. Configure and start

## Server Creation Flow

When a user creates a server in the Uppi dashboard:

1. **Generate Secret**: Create a 64-character random secret
2. **Store Server Record**: Save server with the secret in the database
3. **Display One-liner**: Show the installation command with the embedded secret
4. **First Contact**: The daemon will immediately send metrics after installation

## API Integration

The daemon communicates with these existing API endpoints:

### Metrics Reporting
- **Endpoint**: `POST /api/server/{server}/report`
- **Authentication**: HMAC-SHA256 with secret
- **Headers**: 
  - `X-Signature`: HMAC signature
  - `X-Timestamp`: Unix timestamp
  - `Content-Type: application/json`

### Configuration Retrieval
- **Endpoint**: `GET /api/server/{server}/config`
- **Authentication**: HMAC-SHA256 with secret
- **Response**: Server configuration and status

## Database Schema

Ensure your `servers` table supports 64-character secrets:

```sql
-- Migration to update secret field length
ALTER TABLE servers MODIFY COLUMN secret VARCHAR(64);
```

## Server Identification

The daemon generates a server ID from the secret using SHA256 hash (first 16 characters). This needs to match how your application identifies servers.

**Option 1**: Use the hashed secret as server ID
```php
$serverId = substr(hash('sha256', $secret), 0, 16);
```

**Option 2**: Provide the actual server ID in the installation command
```bash
# Modified installation script that accepts server ID
curl -sSL https://... | sudo bash -s -- <secret> <server-id> <instance> <interval>
```

## Recommended Integration Steps

### 1. Update Server Model

Update the Server model to support 64-character secrets:

```php
// In Server model
protected static function booted(): void
{
    // ... existing code ...
    
    static::creating(function (Server $server) {
        if (empty($server->secret)) {
            $server->secret = Str::random(64); // Changed from 32 to 64
        }
    });
}
```

### 2. Create Installation Endpoint

Add an endpoint that generates the installation command:

```php
// In your controller
public function getInstallCommand(Server $server)
{
    $secret = $server->secret;
    $instance = config('app.url');
    
    return response()->json([
        'command' => "curl -sSL https://raw.githubusercontent.com/janyksteenbeek/uppi-server-agent/main/install.sh | sudo bash -s -- {$secret} {$instance} 1",
        'secret' => $secret,
        'instance' => $instance,
    ]);
}
```

### 3. Dashboard Integration

In your server creation/management UI:

```html
<div class="installation-instructions">
    <h3>Install Monitoring Agent</h3>
    <p>Run this command on your server to install the monitoring agent:</p>
    
    <div class="code-block">
        <code id="install-command">{{ $installCommand }}</code>
        <button onclick="copyToClipboard('#install-command')">Copy</button>
    </div>
    
    <p class="text-sm text-gray-600">
        The agent will start reporting metrics immediately after installation.
    </p>
</div>
```

### 4. Status Monitoring

Add server status indicators based on `last_seen_at`:

```php
// In your Server model
public function getStatusAttribute()
{
    if (!$this->last_seen_at) {
        return 'never_connected';
    }
    
    if ($this->last_seen_at->gt(now()->subMinutes(5))) {
        return 'online';
    }
    
    if ($this->last_seen_at->gt(now()->subMinutes(15))) {
        return 'warning';
    }
    
    return 'offline';
}
```

## Security Considerations

1. **Secret Generation**: Use cryptographically secure random generation
2. **Secret Storage**: Store secrets securely (hashed or encrypted)
3. **HMAC Validation**: The existing ServerMonitoringController already validates HMAC signatures
4. **Timestamp Validation**: Requests older than 5 minutes are rejected

## Monitoring and Alerts

Consider implementing:

1. **Connection Alerts**: Alert when servers go offline
2. **Metric Thresholds**: Alert on high CPU, memory, or disk usage
3. **Update Notifications**: Track daemon versions and update status

## Testing

Test the integration:

1. Create a server in the dashboard
2. Copy the installation command
3. Run it on a test server
4. Verify metrics appear in the dashboard
5. Check that the server shows as "online"

## Troubleshooting

Common issues and solutions:

### Server Not Reporting
- Check if the daemon is running: `systemctl status uppi-agent`
- Verify network connectivity to your instance
- Check daemon logs: `journalctl -u uppi-agent -f`

### Authentication Errors
- Ensure the secret is exactly 64 characters
- Verify the server ID generation matches between daemon and API
- Check timestamp synchronization

### Service Issues
- Ensure systemd service is enabled: `systemctl enable uppi-agent`
- Check service file permissions and syntax
- Verify the binary has execute permissions

## Auto-Updates

The daemon automatically updates itself from GitHub releases. Ensure your users have:
- Internet access to GitHub
- Sufficient permissions for the daemon to update itself
- Or provide the `--skip-updates` flag for air-gapped environments