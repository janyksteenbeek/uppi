# Uppi Server Agent

A lightweight monitoring daemon for Linux servers that reports system metrics to the Uppi monitoring service.

## Features

- **System Metrics**: CPU usage, memory, disk, and network monitoring
- **Auto-updates**: Automatically updates to the latest stable release
- **Secure**: HMAC-SHA256 authentication with the monitoring service
- **Lightweight**: Single binary with minimal resource usage
- **Systemd Integration**: Runs as a system service with auto-restart

## Installation

Use the one-liner installation command provided by your Uppi dashboard:

```bash
curl -sSL https://raw.githubusercontent.com/janyksteenbeek/uppi-server-agent/main/install.sh | sudo bash -s -- <your-64-char-secret>
```

Or with custom instance and interval:

```bash
curl -sSL https://raw.githubusercontent.com/janyksteenbeek/uppi-server-agent/main/install.sh | sudo bash -s -- <secret> https://your-instance.com 5
```

## Manual Installation

1. Download the latest release for your architecture:
   ```bash
   # For amd64
   wget https://github.com/janyksteenbeek/uppi-server-agent/releases/latest/download/uppi-agent-amd64
   
   # For arm64
   wget https://github.com/janyksteenbeek/uppi-server-agent/releases/latest/download/uppi-agent-arm64
   ```

2. Make it executable and move to `/usr/local/bin`:
   ```bash
   chmod +x uppi-agent-*
   sudo mv uppi-agent-* /usr/local/bin/uppi-agent
   ```

3. Run the agent:
   ```bash
   uppi-agent <your-64-char-secret> --instance=https://uppi.dev --interval-minutes=1
   ```

## Usage

```bash
uppi-agent [secret] [flags]

Flags:
  --instance string         Instance URL (default "https://uppi.dev")
  --interval-minutes int    Reporting interval in minutes (default 1)
  --skip-updates           Skip automatic updates
  -h, --help               Help for uppi-agent
```

## Service Management

When installed via the script, the agent runs as a systemd service:

```bash
# View status
sudo systemctl status uppi-agent

# View logs
sudo journalctl -u uppi-agent -f

# Restart service
sudo systemctl restart uppi-agent

# Stop service
sudo systemctl stop uppi-agent

# Start service
sudo systemctl start uppi-agent
```

## Configuration

Configuration is stored in `/etc/uppi-agent/config`:

```
SECRET=your-64-character-secret
INSTANCE=https://uppi.dev
INSTALLED_AT=2024-01-01T00:00:00Z
VERSION=v1.0.0
```

## Metrics Collected

The agent collects the following metrics:

### System Metrics
- CPU usage percentage
- Load averages (1, 5, 15 minutes)
- Memory total, used, available, usage percentage
- Swap total, used, usage percentage

### Disk Metrics (per mount point)
- Total, used, available bytes
- Usage percentage

### Network Metrics (per interface)
- Bytes received/transmitted
- Packets received/transmitted
- Errors received/transmitted

## Development

### Building

```bash
# Build for current platform
go build -o uppi-agent .

# Build for Linux amd64
GOOS=linux GOARCH=amd64 go build -o uppi-agent-amd64 .

# Build for Linux arm64
GOOS=linux GOARCH=arm64 go build -o uppi-agent-arm64 .
```

### Dependencies

- [gopsutil](https://github.com/shirou/gopsutil) - System and process utilities
- [cobra](https://github.com/spf13/cobra) - CLI framework

## Security

The agent uses HMAC-SHA256 authentication when communicating with the Uppi service:

1. Each request includes a timestamp and HMAC signature
2. The signature is calculated using the secret and request payload
3. Requests older than 5 minutes are rejected to prevent replay attacks

## License

This project follows the same license as the main Uppi project.