package backupmanager

import (
	"fmt"
	"log"
	"os"

	"github.com/fatih/color"
)

// Logger provides colored logging with different levels
type Logger struct {
	info  *color.Color
	error *color.Color
	warn  *color.Color
}

var logger *Logger

func init() {
	logger = &Logger{
		info:  color.New(color.FgCyan),
		error: color.New(color.FgRed, color.Bold),
		warn:  color.New(color.FgYellow),
	}
	// Disable standard log prefix/flags since we're adding our own
	log.SetFlags(log.Ldate | log.Ltime)
}

// Info logs an informational message in cyan
func Info(format string, args ...interface{}) {
	msg := fmt.Sprintf(format, args...)
	logger.info.Printf("[INFO] %s\n", msg)
}

// Error logs an error message in red
func Error(format string, args ...interface{}) {
	msg := fmt.Sprintf(format, args...)
	logger.error.Printf("[ERROR] %s\n", msg)
}

// Warn logs a warning message in yellow
func Warn(format string, args ...interface{}) {
	msg := fmt.Sprintf(format, args...)
	logger.warn.Printf("[WARN] %s\n", msg)
}

// Fatal logs an error message and exits
func Fatal(format string, args ...interface{}) {
	msg := fmt.Sprintf(format, args...)
	logger.error.Printf("[FATAL] %s\n", msg)
	os.Exit(1)
}
