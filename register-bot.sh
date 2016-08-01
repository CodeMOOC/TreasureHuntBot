#!/bin/bash

# Telegram Bot Sample
# ===================
# UWiClab, University of Urbino
# ===================
# Basic script to setup the Telegram API webhook.
# Use with a custom bot token, a webhook HTTPS URL and the HTTPS certificate
# installed onto the webhook's web server.
#
# Note: you *need* a domain and a valid certificate for this to work.

function PrintHelp {
    echo -e "usage: $0 -s <script HTTPS URL> -c <certificate path> -t <bot token>"
    echo "EXAMPLE: $0 -t 123456789:AAF6me-NAHyXLlZ2eO3d3lEdYKnXuZPd98 -c /home/delpriori/bot/public.pem -s https://botify.it/webhook"
    echo ""
}

while getopts ":s:c:t:" opt; do
  case $opt in
    t)
      TOKEN=$OPTARG
      ;;
    c)
      CERTIFICATE=$OPTARG
      ;;
    s)
      SCRIPT=$OPTARG
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

if [ -z "$CERTIFICATE" ]; then
        echo -e "\nERROR: missing <certificate path> argument.\n"
        PrintHelp;
        exit 1;
fi

if [ -z "$SCRIPT" ]; then
        echo -e "\nERROR: missing <script HTTPS URL> argument.\n"
        PrintHelp;
        exit 1;
fi

echo "Executing: curl -F \"url=$SCRIPT\" -F \"certificate=@$CERTIFICATE\" https://api.telegram.org/bot$TOKEN/setWebhook"

curl -F "url=$SCRIPT" -F "certificate=@$CERTIFICATE" https://api.telegram.org/bot$TOKEN/setWebhook

echo ""
