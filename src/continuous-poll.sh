#!/bin/bash

# Telegram Bot Sample
# ===================
# UWiClab, University of Urbino
# ===================
# Script that continously launches the PHP pull script.
# Ensure that the pull scripts uses "long-polling", i.e.:
#
# telegram_get_updates(..., ..., 300);
#
# the third parameter (polling timeout) is set to a number
# greater than 0.

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

echo "Starting to poll (terminate with Ctrl+C)..."

while true; do
    php $DIR/pull.php
    sleep 1
done
