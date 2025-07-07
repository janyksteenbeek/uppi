.PHONY: build build-linux build-all clean test fmt vet deps

# Binary name
BINARY_NAME=uppi-agent

# Version
VERSION?=1.0.0

# Build flags
LDFLAGS=-ldflags="-w -s -X main.Version=$(VERSION)"

# Default target
all: build

# Build for current platform
build:
	go build $(LDFLAGS) -o $(BINARY_NAME) .

# Build for Linux platforms
build-linux:
	GOOS=linux GOARCH=amd64 go build $(LDFLAGS) -o $(BINARY_NAME)-amd64 .
	GOOS=linux GOARCH=arm64 go build $(LDFLAGS) -o $(BINARY_NAME)-arm64 .

# Build for all supported platforms
build-all: build-linux

# Clean build artifacts
clean:
	go clean
	rm -f $(BINARY_NAME) $(BINARY_NAME)-amd64 $(BINARY_NAME)-arm64

# Run tests
test:
	go test -v ./...

# Format code
fmt:
	go fmt ./...

# Vet code
vet:
	go vet ./...

# Download dependencies
deps:
	go mod download
	go mod tidy

# Development build with debug info
dev:
	go build -o $(BINARY_NAME) .

# Install to /usr/local/bin (requires sudo)
install: build
	sudo cp $(BINARY_NAME) /usr/local/bin/

# Uninstall from /usr/local/bin (requires sudo)
uninstall:
	sudo rm -f /usr/local/bin/$(BINARY_NAME)

# Run locally for testing
run:
	go run . test-secret-that-is-exactly-64-characters-long-for-testing --skip-updates

# Check if dependencies are up to date
check-deps:
	go list -u -m all

# Security check
security:
	go list -json -deps | nancy sleuth