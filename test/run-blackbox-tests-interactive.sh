#!/bin/bash
set -o nounset
set -o errexit
set -o pipefail

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

echo "Refreshing test images"
docker compose \
  -f "$DIR/blackbox/docker-compose.yaml" \
  build

echo "Starting interactive blackbox test runner shell"
echo "Hint: Run docker compose logs -f in another terminal to see what's happening"
echo "Hint: Changes to tests are mounted live, but changes to application code *will not* apply"
echo "      until you re-build the containers (e.g. by exiting and re-running this script)"
docker compose \
  -f "$DIR/blackbox/docker-compose.yaml" \
  run \
  --entrypoint /bin/ash \
  --rm \
  test_runner

echo "Cleaning up"
docker compose \
 -f "$DIR/blackbox/docker-compose.yaml" \
 down
