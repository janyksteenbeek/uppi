package main

import (
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net/http"
	"os"
	"os/exec"
	"runtime"
	"strings"
	"time"
)

type GitHubRelease struct {
	TagName string `json:"tag_name"`
	Assets  []struct {
		Name               string `json:"name"`
		BrowserDownloadURL string `json:"browser_download_url"`
	} `json:"assets"`
}

func checkForUpdates() {
	log.Println("Checking for updates...")

	// Get current version
	currentVersion := Version

	// Check for latest release
	latestRelease, err := getLatestRelease()
	if err != nil {
		log.Printf("Failed to check for updates: %v", err)
		return
	}

	if isNewerVersion(latestRelease.TagName, currentVersion) {
		log.Printf("New version available: %s (current: %s)", latestRelease.TagName, currentVersion)

		if err := performUpdate(latestRelease); err != nil {
			log.Printf("Failed to update: %v", err)
			return
		}

		log.Println("Update completed successfully. Restarting...")
		restartAgent()
	} else {
		log.Println("Already running the latest version")
	}
}

func getLatestRelease() (*GitHubRelease, error) {
	resp, err := http.Get("https://api.github.com/repos/janyksteenbeek/uppi-server-agent/releases/latest")
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("GitHub API returned status %d", resp.StatusCode)
	}

	var release GitHubRelease
	if err := json.NewDecoder(resp.Body).Decode(&release); err != nil {
		return nil, err
	}

	return &release, nil
}

func isNewerVersion(latest, current string) bool {
	// Simple version comparison - in production, use proper semver
	latest = strings.TrimPrefix(latest, "v")
	current = strings.TrimPrefix(current, "v")
	return latest != current
}

func performUpdate(release *GitHubRelease) error {
	// Determine architecture
	arch := runtime.GOARCH
	assetName := fmt.Sprintf("uppi-agent-%s", arch)

	// Find the asset for our architecture
	var downloadURL string
	for _, asset := range release.Assets {
		if asset.Name == assetName {
			downloadURL = asset.BrowserDownloadURL
			break
		}
	}

	if downloadURL == "" {
		return fmt.Errorf("no asset found for architecture %s", arch)
	}

	// Download the new binary
	tempFile := "/tmp/uppi-agent-new"
	if err := downloadFile(downloadURL, tempFile); err != nil {
		return fmt.Errorf("failed to download update: %w", err)
	}

	// Make it executable
	if err := os.Chmod(tempFile, 0755); err != nil {
		return fmt.Errorf("failed to make new binary executable: %w", err)
	}

	// Get current executable path
	currentPath, err := os.Executable()
	if err != nil {
		return fmt.Errorf("failed to get current executable path: %w", err)
	}

	// Backup current binary
	backupPath := currentPath + ".backup"
	if err := copyFile(currentPath, backupPath); err != nil {
		log.Printf("Warning: failed to create backup: %v", err)
	}

	// Replace current binary
	if err := copyFile(tempFile, currentPath); err != nil {
		// Try to restore backup
		if backupErr := copyFile(backupPath, currentPath); backupErr != nil {
			log.Printf("Critical: failed to restore backup after failed update: %v", backupErr)
		}
		return fmt.Errorf("failed to replace binary: %w", err)
	}

	// Cleanup
	os.Remove(tempFile)
	os.Remove(backupPath)

	return nil
}

func downloadFile(url, filepath string) error {
	resp, err := http.Get(url)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("download failed with status %d", resp.StatusCode)
	}

	out, err := os.Create(filepath)
	if err != nil {
		return err
	}
	defer out.Close()

	_, err = io.Copy(out, resp.Body)
	return err
}

func copyFile(src, dst string) error {
	sourceFile, err := os.Open(src)
	if err != nil {
		return err
	}
	defer sourceFile.Close()

	destFile, err := os.Create(dst)
	if err != nil {
		return err
	}
	defer destFile.Close()

	_, err = io.Copy(destFile, sourceFile)
	if err != nil {
		return err
	}

	// Copy permissions
	sourceInfo, err := os.Stat(src)
	if err != nil {
		return err
	}

	return os.Chmod(dst, sourceInfo.Mode())
}

func restartAgent() {
	// If running as systemd service, restart it
	if isSystemdService() {
		cmd := exec.Command("systemctl", "restart", "uppi-agent")
		cmd.Run()
		return
	}

	// Otherwise, restart the process
	args := os.Args

	if err := exec.Command(args[0], args[1:]...).Start(); err != nil {
		log.Printf("Failed to restart: %v", err)
	}

	// Exit current process after short delay
	go func() {
		time.Sleep(2 * time.Second)
		os.Exit(0)
	}()
}

func isSystemdService() bool {
	// Simple check if we're running under systemd
	return os.Getenv("SYSTEMD_EXEC_PID") != "" ||
		os.Getppid() == 1 // PID 1 is usually systemd
}
