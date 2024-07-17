#!/bin/bash
set -o nounset
set -o errexit
set -o pipefail

# This script runs *as part of building a test_subject docker container* to take the current
# version of the library and package it up with the other dependencies ready for running HTTP
# tests. It runs in a *composer* image, not our own, and is used as the first part of a
# multi-stage build. See the docker-compose.yaml for how this fits together.

# Configure directory paths
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
implementation_dir="$DIR/implementation"
temporary_library_path=`mktemp -d -t _microframework_pkg_XXXXXX`

# First, we need to clone the library *as it will be exported* into the blackbox test path
# We do this by running git archive (to produce a tar equivalent to the one that composer
# will pull from the equivalent github release URL) and then unpacking it.
# I'm piping the two commands together because the intermediate tar file itself is not
# required and will just cause confusion.
echo "Exporting packaged version of library to $temporary_library_path"
git archive HEAD | tar -x --directory "$temporary_library_path"

# Now, we need to tell composer to install the packaged version and the dependencies
# We're configuring the repo without the symlink option so that composer *copies* the
# exported package from the temporary directory into /vendor. This ensures there's only
# one copy of the code in the built image and it's in the same place / structure as if
# it had been pulled down from github as an archive.
echo "Configuring composer in with local repository"
cd "$implementation_dir"
composer init --type=project
composer config repositories.packaged "{\"type\": \"path\", \"url\": \"$temporary_library_path\", \"options\": {\"symlink\": false}}"
composer require ingenerator/microframework:*@dev --ignore-platform-reqs
