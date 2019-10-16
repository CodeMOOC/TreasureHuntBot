#!/bin/bash
find . -iname *.po -print -execdir sh -c 'msgfmt -f -o "$(basename "$0" .po).mo" "$0"' '{}' \;
