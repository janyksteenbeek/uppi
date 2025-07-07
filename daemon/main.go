package main

import (
	"bytes"
	"crypto/hmac"
	"crypto/sha256"
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net/http"
	"os"
	"strconv"
	"time"

	"github.com/shirou/gopsutil/v3/cpu"
	"github.com/shirou/gopsutil/v3/disk"
	"github.com/shirou/gopsutil/v3/load"
	"github.com/shirou/gopsutil/v3/mem"
	"github.com/shirou/gopsutil/v3/net"
	"github.com/spf13/cobra"
)

const (
	Version = "1.0.0"
	DefaultInstance = "https://uppi.dev"
	DefaultInterval = 60 // seconds
)

type Config struct {
	Secret        string
	Instance      string
	ServerId      string
	SkipUpdates   bool
	IntervalMinutes int
}

type ServerMetrics struct {
	CpuUsage           *float64       `json:"cpu_usage,omitempty"`
	CpuLoad1           *float64       `json:"cpu_load_1,omitempty"`
	CpuLoad5           *float64       `json:"cpu_load_5,omitempty"`
	CpuLoad15          *float64       `json:"cpu_load_15,omitempty"`
	MemoryTotal        *uint64        `json:"memory_total,omitempty"`
	MemoryUsed         *uint64        `json:"memory_used,omitempty"`
	MemoryAvailable    *uint64        `json:"memory_available,omitempty"`
	MemoryUsagePercent *float64       `json:"memory_usage_percent,omitempty"`
	SwapTotal          *uint64        `json:"swap_total,omitempty"`
	SwapUsed           *uint64        `json:"swap_used,omitempty"`
	SwapUsagePercent   *float64       `json:"swap_usage_percent,omitempty"`
	DiskMetrics        []DiskMetric   `json:"disk_metrics,omitempty"`
	NetworkMetrics     []NetworkMetric `json:"network_metrics,omitempty"`
	CollectedAt        string         `json:"collected_at"`
}

type DiskMetric struct {
	MountPoint     string  `json:"mount_point"`
	TotalBytes     uint64  `json:"total_bytes"`
	UsedBytes      uint64  `json:"used_bytes"`
	AvailableBytes uint64  `json:"available_bytes"`
	UsagePercent   float64 `json:"usage_percent"`
}

type NetworkMetric struct {
	InterfaceName string `json:"interface_name"`
	RxBytes       uint64 `json:"rx_bytes"`
	TxBytes       uint64 `json:"tx_bytes"`
	RxPackets     uint64 `json:"rx_packets"`
	TxPackets     uint64 `json:"tx_packets"`
	RxErrors      uint64 `json:"rx_errors"`
	TxErrors      uint64 `json:"tx_errors"`
}

func main() {
	var config Config

	var rootCmd = &cobra.Command{
		Use:   "uppi-agent [secret]",
		Short: "Uppi Server Monitoring Agent",
		Long:  `A daemon for monitoring server metrics and reporting to Uppi monitoring service.`,
		Args:  cobra.ExactArgs(1),
		Run: func(cmd *cobra.Command, args []string) {
			config.Secret = args[0]
			if len(config.Secret) != 64 {
				log.Fatal("Secret must be exactly 64 characters long")
			}

			// TODO: The server ID should be provided by the installation script 
			// or retrieved from the server API. For now, we'll use a hash of the secret
			config.ServerId = fmt.Sprintf("%x", sha256.Sum256([]byte(config.Secret)))[:16]

			runDaemon(config)
		},
	}

	rootCmd.Flags().StringVar(&config.Instance, "instance", DefaultInstance, "Instance URL")
	rootCmd.Flags().BoolVar(&config.SkipUpdates, "skip-updates", false, "Skip automatic updates")
	rootCmd.Flags().IntVar(&config.IntervalMinutes, "interval-minutes", 1, "Reporting interval in minutes")

	if err := rootCmd.Execute(); err != nil {
		log.Fatal(err)
	}
}

func runDaemon(config Config) {
	log.Printf("Starting Uppi Agent v%s", Version)
	log.Printf("Instance: %s", config.Instance)
	log.Printf("Interval: %d minutes", config.IntervalMinutes)
	log.Printf("Skip Updates: %v", config.SkipUpdates)

	// Check for updates unless skipped
	if !config.SkipUpdates {
		checkForUpdates()
	}

	// Send initial ping
	if err := sendMetrics(config); err != nil {
		log.Printf("Failed to send initial metrics: %v", err)
	} else {
		log.Println("Initial metrics sent successfully")
	}

	// Start monitoring loop
	ticker := time.NewTicker(time.Duration(config.IntervalMinutes) * time.Minute)
	defer ticker.Stop()

	for {
		select {
		case <-ticker.C:
			if err := sendMetrics(config); err != nil {
				log.Printf("Failed to send metrics: %v", err)
			} else {
				log.Println("Metrics sent successfully")
			}
		}
	}
}

func collectMetrics() (*ServerMetrics, error) {
	metrics := &ServerMetrics{
		CollectedAt: time.Now().UTC().Format(time.RFC3339),
	}

	// CPU metrics
	cpuPercent, err := cpu.Percent(time.Second, false)
	if err == nil && len(cpuPercent) > 0 {
		metrics.CpuUsage = &cpuPercent[0]
	}

	// Load average
	loadAvg, err := load.Avg()
	if err == nil {
		metrics.CpuLoad1 = &loadAvg.Load1
		metrics.CpuLoad5 = &loadAvg.Load5
		metrics.CpuLoad15 = &loadAvg.Load15
	}

	// Memory metrics
	memInfo, err := mem.VirtualMemory()
	if err == nil {
		metrics.MemoryTotal = &memInfo.Total
		metrics.MemoryUsed = &memInfo.Used
		metrics.MemoryAvailable = &memInfo.Available
		metrics.MemoryUsagePercent = &memInfo.UsedPercent
	}

	// Swap metrics
	swapInfo, err := mem.SwapMemory()
	if err == nil {
		metrics.SwapTotal = &swapInfo.Total
		metrics.SwapUsed = &swapInfo.Used
		metrics.SwapUsagePercent = &swapInfo.UsedPercent
	}

	// Disk metrics
	partitions, err := disk.Partitions(false)
	if err == nil {
		for _, partition := range partitions {
			usage, err := disk.Usage(partition.Mountpoint)
			if err == nil {
				diskMetric := DiskMetric{
					MountPoint:     partition.Mountpoint,
					TotalBytes:     usage.Total,
					UsedBytes:      usage.Used,
					AvailableBytes: usage.Free,
					UsagePercent:   usage.UsedPercent,
				}
				metrics.DiskMetrics = append(metrics.DiskMetrics, diskMetric)
			}
		}
	}

	// Network metrics
	netStats, err := net.IOCounters(true)
	if err == nil {
		for _, stat := range netStats {
			// Skip loopback interface
			if stat.Name == "lo" {
				continue
			}
			
			networkMetric := NetworkMetric{
				InterfaceName: stat.Name,
				RxBytes:       stat.BytesRecv,
				TxBytes:       stat.BytesSent,
				RxPackets:     stat.PacketsRecv,
				TxPackets:     stat.PacketsSent,
				RxErrors:      stat.Errin,
				TxErrors:      stat.Errout,
			}
			metrics.NetworkMetrics = append(metrics.NetworkMetrics, networkMetric)
		}
	}

	return metrics, nil
}

func sendMetrics(config Config) error {
	metrics, err := collectMetrics()
	if err != nil {
		return fmt.Errorf("failed to collect metrics: %w", err)
	}

	payload, err := json.Marshal(metrics)
	if err != nil {
		return fmt.Errorf("failed to marshal metrics: %w", err)
	}

	// Create HMAC signature
	timestamp := strconv.FormatInt(time.Now().Unix(), 10)
	signature := createHMACSignature(timestamp, string(payload), config.Secret)

	// Create request
	url := fmt.Sprintf("%s/api/server/%s/report", config.Instance, config.ServerId)
	req, err := http.NewRequest("POST", url, bytes.NewBuffer(payload))
	if err != nil {
		return fmt.Errorf("failed to create request: %w", err)
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-Signature", signature)
	req.Header.Set("X-Timestamp", timestamp)

	// Send request
	client := &http.Client{Timeout: 30 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		return fmt.Errorf("failed to send request: %w", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		body, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("server returned status %d: %s", resp.StatusCode, string(body))
	}

	return nil
}

func createHMACSignature(timestamp, payload, secret string) string {
	message := timestamp + payload
	h := hmac.New(sha256.New, []byte(secret))
	h.Write([]byte(message))
	return fmt.Sprintf("%x", h.Sum(nil))
}

func checkForUpdates() {
	// TODO: Implement auto-update functionality
	// Check GitHub releases and download if newer version available
	log.Println("Auto-update check skipped (not implemented yet)")
}