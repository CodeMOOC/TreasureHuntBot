#!/bin/bash

# Telegram Bot Sample
# ===================
# UWiClab, University of Urbino
# ===================
# Basic script to remove an existing webhook from the Telegram API.

function PrintHelp {
    echo -e "usage: $0 -t <bot token>"
    echo "EXAMPLE: $0 -r -t 123456789:AAF6me-NAHyXLlZ2eO3d3lEdYKnXuZPd98"
    echo ""
}

while getopts ":t:" opt; do
  case $opt in
    t)
      TOKEN=$OPTARG
      ;;
    \?)
      PrintHelp
      ;;
  esac
done

if [ -z "$TOKEN" ]; then
        echo -e "\nERROR: missing token argument.\n"
        PrintHelp;
        exit 1;
fi

echo "Executing: curl -F \"url=\" https://api.telegram.org/bot$TOKEN/setWebhook"

curl -F "url=" https://api.telegram.org/bot$TOKEN/setWebhook

echo ""
