#!/bin/bash

# BaxerMux Album - Clean Blazor Build Artifacts
# Designed for Linux / WSL2 Environment

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
cd "$SCRIPT_DIR"

BLAZOR_DIR="./BlazorAlbumExplorer"

echo "=================================================="
echo "🧹 Cleaning BlazorAlbumExplorer Build Artifacts"
echo "=================================================="

if [ -d "$BLAZOR_DIR" ]; then
    echo "🔍 Cleaning directories in $BLAZOR_DIR..."
    
    # Remove bin
    if [ -d "$BLAZOR_DIR/bin" ]; then
        rm -rf "$BLAZOR_DIR/bin"
        echo "✅ Removed bin/"
    fi
    
    # Remove obj
    if [ -d "$BLAZOR_DIR/obj" ]; then
        rm -rf "$BLAZOR_DIR/obj"
        echo "✅ Removed obj/"
    fi
    
    # Remove publish
    if [ -d "$BLAZOR_DIR/publish" ]; then
        rm -rf "$BLAZOR_DIR/publish"
        echo "✅ Removed publish/"
    fi
    
    echo "✨ Cleanup complete for Blazor project."
else
    echo "❌ Error: $BLAZOR_DIR not found."
fi

echo "=================================================="
