#!/bin/bash
set -o nounset
set -o errexit
set -o pipefail

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

docker compose \
  -f "$DIR/blackbox/docker-compose.yaml" \
  up \
  --abort-on-container-exit
