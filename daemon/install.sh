#!/bin/bash

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Default values
INSTALL_DIR="/usr/local/bin"
SERVICE_FILE="/etc/systemd/system/uppi-agent.service"
CONFIG_DIR="/etc/uppi-agent"
CONFIG_FILE="$CONFIG_DIR/config"

# GitHub repository
REPO="janyksteenbeek/uppi-server-agent"
RELEASE_URL="https://api.github.com/repos/$REPO/releases/latest"

# Functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        log_error "This script must be run as root (use sudo)"
        exit 1
    fi
}

detect_architecture() {
    local arch=$(uname -m)
    case $arch in
        x86_64)
            echo "amd64"
            ;;
        aarch64|arm64)
            echo "arm64"
            ;;
        *)
            log_error "Unsupported architecture: $arch"
            exit 1
            ;;
    esac
}

download_latest_release() {
    local arch=$1
    local temp_dir=$(mktemp -d)
    
    log_info "Detecting latest release..."
    
    # Get latest release info
    local release_info=$(curl -s "$RELEASE_URL")
    local download_url=$(echo "$release_info" | grep -o "\"browser_download_url\": \"[^\"]*uppi-agent-$arch\"" | cut -d '"' -f 4)
    
    if [[ -z "$download_url" ]]; then
        log_error "Could not find download URL for architecture: $arch"
        exit 1
    fi
    
    log_info "Downloading uppi-agent for $arch..."
    curl -L -o "$temp_dir/uppi-agent" "$download_url"
    
    if [[ ! -f "$temp_dir/uppi-agent" ]]; then
        log_error "Failed to download uppi-agent"
        exit 1
    fi
    
    # Make executable and move to install directory
    chmod +x "$temp_dir/uppi-agent"
    mv "$temp_dir/uppi-agent" "$INSTALL_DIR/uppi-agent"
    
    # Cleanup
    rm -rf "$temp_dir"
    
    log_info "uppi-agent installed to $INSTALL_DIR/uppi-agent"
}

create_service_file() {
    local secret=$1
    local instance=${2:-"https://uppi.dev"}
    local interval=${3:-"1"}
    
    log_info "Creating systemd service file..."
    
    cat > "$SERVICE_FILE" << EOF
[Unit]
Description=Uppi Server Monitoring Agent
After=network.target
Wants=network-online.target

[Service]
Type=simple
User=root
ExecStart=$INSTALL_DIR/uppi-agent $secret --instance=$instance --interval-minutes=$interval
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

    log_info "Service file created at $SERVICE_FILE"
}

create_config_directory() {
    local secret=$1
    local instance=${2:-"https://uppi.dev"}
    
    log_info "Creating configuration directory..."
    
    mkdir -p "$CONFIG_DIR"
    
    cat > "$CONFIG_FILE" << EOF
SECRET=$secret
INSTANCE=$instance
INSTALLED_AT=$(date -u +%Y-%m-%dT%H:%M:%SZ)
VERSION=$(curl -s "$RELEASE_URL" | grep -o '"tag_name": "[^"]*' | cut -d '"' -f 4)
EOF

    chmod 600 "$CONFIG_FILE"
    log_info "Configuration saved to $CONFIG_FILE"
}

enable_and_start_service() {
    log_info "Enabling and starting uppi-agent service..."
    
    systemctl daemon-reload
    systemctl enable uppi-agent
    systemctl start uppi-agent
    
    # Check if service started successfully
    sleep 2
    if systemctl is-active --quiet uppi-agent; then
        log_info "uppi-agent service started successfully"
    else
        log_error "Failed to start uppi-agent service"
        log_info "Check logs with: journalctl -u uppi-agent -f"
        exit 1
    fi
}

show_status() {
    log_info "Installation completed successfully!"
    echo
    echo "Service status:"
    systemctl status uppi-agent --no-pager -l
    echo
    echo "Useful commands:"
    echo "  View logs: journalctl -u uppi-agent -f"
    echo "  Restart:   systemctl restart uppi-agent"
    echo "  Stop:      systemctl stop uppi-agent"
    echo "  Status:    systemctl status uppi-agent"
}

# Main installation function
main() {
    local secret=$1
    local instance=${2:-"https://uppi.dev"}
    local interval=${3:-"1"}
    
    if [[ -z "$secret" ]]; then
        log_error "Usage: $0 <secret> [instance] [interval_minutes]"
        log_error "Example: $0 abc123...xyz https://uppi.dev 1"
        exit 1
    fi
    
    if [[ ${#secret} -ne 64 ]]; then
        log_error "Secret must be exactly 64 characters long"
        exit 1
    fi
    
    log_info "Starting Uppi Agent installation..."
    echo "Secret: ${secret:0:8}...${secret: -8}"
    echo "Instance: $instance"
    echo "Interval: $interval minutes"
    echo
    
    check_root
    
    # Stop existing service if running
    if systemctl is-active --quiet uppi-agent 2>/dev/null; then
        log_info "Stopping existing uppi-agent service..."
        systemctl stop uppi-agent
    fi
    
    local arch=$(detect_architecture)
    log_info "Detected architecture: $arch"
    
    download_latest_release "$arch"
    create_config_directory "$secret" "$instance"
    create_service_file "$secret" "$instance" "$interval"
    enable_and_start_service
    show_status
}

# Check if script is being sourced or executed directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi