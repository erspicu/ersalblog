#!/bin/bash

# BaxerMux Album - Remove Win11 Theme Script
# Designed for Linux / WSL2 Environment

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
cd "$SCRIPT_DIR"

THEME_DIR="../static/themes/album-win11"

echo "=================================================="
echo "🗑️  Removing Win11 Theme"
echo "=================================================="

if [ -d "$THEME_DIR" ]; then
    rm -rf "$THEME_DIR"
    echo "✅ Successfully removed $THEME_DIR"
else
    echo "ℹ️  Win11 theme directory not found. Nothing to do."
fi

echo "=================================================="
echo "✨ Operation Completed"
echo "=================================================="
