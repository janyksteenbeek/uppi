# Server Monitoring System

This document describes the server monitoring system implementation for Uppi.

## Overview

The server monitoring system allows you to track server metrics including:
- CPU usage and load averages
- Memory usage and swap usage  
- Disk space per mount point
- Network interface statistics

## Components

### Models

#### Server Model (`app/Models/Server.php`)
- Stores server information (name, hostname, IP, OS)
- Automatically generates HMAC secret for authentication
- User-scoped with global scope
- Tracks online status via `last_seen_at`

#### ServerMetric Model (`app/Models/ServerMetric.php`)  
- Stores individual metric data points
- Efficiently indexed for time-series queries
- JSON storage for flexible disk/network metrics
- Helper methods for data formatting

### Database Schema

#### servers table
```sql
- id (ULID)
- user_id (ULID, foreign key)
- name (string)
- hostname (string)
- ip_address (string)
- os (string, nullable)
- secret (string, 64 chars)
- is_active (boolean, default true)
- last_seen_at (timestamp, nullable)
- created_at, updated_at
```

#### server_metrics table
```sql
- id (ULID)
- server_id (ULID, foreign key)
- cpu_usage (float, percentage)
- cpu_load_1, cpu_load_5, cpu_load_15 (float, load averages)
- memory_total, memory_used, memory_available (bigint, bytes)
- memory_usage_percent (float, percentage)
- swap_total, swap_used (bigint, bytes)
- swap_usage_percent (float, percentage)
- disk_metrics (JSON array)
- network_metrics (JSON array)
- collected_at (timestamp)
- created_at, updated_at
```

### API Endpoints

#### POST `/api/server/{server_id}/report`
Submit server metrics data. Requires HMAC authentication.

**Headers:**
- `X-Timestamp`: Unix timestamp
- `X-Signature`: HMAC-SHA256 signature
- `Content-Type`: application/json

**Payload Example:**
```json
{
  "cpu_usage": 45.2,
  "cpu_load_1": 0.75,
  "cpu_load_5": 0.82,
  "cpu_load_15": 0.79,
  "memory_total": 8589934592,
  "memory_used": 3221225472,
  "memory_available": 5368709120,
  "memory_usage_percent": 37.5,
  "swap_total": 2147483648,
  "swap_used": 0,
  "swap_usage_percent": 0.0,
  "disk_metrics": [
    {
      "mount": "/",
      "total": 21474836480,
      "used": 10737418240,
      "available": 10737418240
    },
    {
      "mount": "/var",
      "total": 5368709120,
      "used": 1073741824,
      "available": 4294967296
    }
  ],
  "network_metrics": [
    {
      "interface": "eth0",
      "rx_bytes": 12345678,
      "tx_bytes": 9876543,
      "rx_packets": 12345,
      "tx_packets": 9876
    }
  ],
  "collected_at": "2025-01-20T10:30:00Z"
}
```

#### GET `/api/server/{server_id}/config`
Get server configuration. Requires HMAC authentication.

#### POST `/api/server/cleanup` 
Clean up old metrics (authenticated route). Removes metrics older than 30 days.

### HMAC Authentication

Server monitoring uses HMAC-SHA256 for authentication:

1. **Generate signature:**
   ```
   payload = JSON request body
   message = timestamp + payload
   signature = HMAC-SHA256(message, server_secret)
   ```

2. **Include headers:**
   - `X-Timestamp`: Current Unix timestamp
   - `X-Signature`: Generated HMAC signature

3. **Verification:** 
   - Timestamp must be within 5 minutes of server time
   - Signature must match expected HMAC

### Filament Resource

The `ServerResource` provides:
- Server management interface
- Real-time metrics display
- Secret key management and regeneration
- Online/offline status indicators
- Filterable metrics by date range

### Performance Considerations

**Database Indexes:**
- Compound indexes on `(server_id, collected_at)` for time-series queries
- Indexes on `server_id` and `collected_at` individually
- Performance indexes for common filtering patterns

**Data Retention:**
- Use the cleanup endpoint to manage data volume
- Consider partitioning for very high-volume deployments
- JSON columns allow flexible schema evolution

## Usage

### Adding a Server

1. Go to Admin panel → Servers → Create
2. Fill in server details (name, hostname, IP, OS)
3. Save - secret key is auto-generated
4. Copy the secret key for daemon configuration

### Daemon Configuration

Configure your monitoring daemon with:
- Server ID (from URL/database)
- Secret key (from Filament interface)
- Report URL: `https://yourapp.com/api/server/{server_id}/report`

### Example Daemon Request (cURL)

```bash
#!/bin/bash

SERVER_ID="01234567-89ab-cdef-0123-456789abcdef"
SECRET="your-32-character-secret-key"
URL="https://yourapp.com/api/server/$SERVER_ID/report"

# Collect metrics (implement these functions)
CPU_USAGE=$(get_cpu_usage)
MEMORY_DATA=$(get_memory_stats)
# ... collect other metrics

TIMESTAMP=$(date +%s)
PAYLOAD=$(cat <<EOF
{
  "cpu_usage": $CPU_USAGE,
  "memory_total": 8589934592,
  "memory_used": 3221225472,
  "memory_usage_percent": 37.5,
  "collected_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
}
EOF
)

# Generate HMAC signature
SIGNATURE=$(echo -n "${TIMESTAMP}${PAYLOAD}" | openssl dgst -sha256 -hmac "$SECRET" -binary | base64)

# Send request
curl -X POST "$URL" \
  -H "Content-Type: application/json" \
  -H "X-Timestamp: $TIMESTAMP" \
  -H "X-Signature: $SIGNATURE" \
  -d "$PAYLOAD"
```

### Viewing Metrics

1. Go to Admin panel → Servers
2. Click on a server to view detailed metrics
3. Use date range filters to analyze historical data
4. View real-time status indicators

## Next Steps

The current implementation provides the foundation for:
- **Alert thresholds:** Set CPU/memory/disk usage alerts
- **Dashboard widgets:** Real-time server overview
- **Historical analytics:** Trend analysis and capacity planning
- **Multi-server dashboards:** Fleet monitoring views
- **Automated scaling triggers:** Integration with infrastructure automation

## Security Notes

- Secret keys should be stored securely on monitored servers
- Use HTTPS for all API communications
- Regularly rotate secret keys if needed
- Monitor for authentication failures in logs
- Consider rate limiting for report endpoints in high-volume scenarios