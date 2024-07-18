#!/bin/ash
set -o errexit
set -o nounset
set -o pipefail

echo "capturing logs from $LOG_LISTEN_PORT to $LOG_OUTPUT_FILE"
nc -lk -s 0.0.0.0 -p $LOG_LISTEN_PORT >> "$LOG_OUTPUT_FILE"
echo "done"
