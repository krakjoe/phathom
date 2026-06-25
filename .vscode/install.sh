#!/usr/bin/env bash
ROOT=$(dirname "${BASH_SOURCE[0]}")
VSIX="${ROOT}/phathom.vsix"

# Bailout if installed
code --list-extensions 2>/dev/null | \
    grep -q "^krakjoe.phathom$"

if [ $? -eq 0 ];
then
    exit 0
fi

# Build extension
if [ ! -f "${VSIX}" ];
then
    which vsce
    if [ $? -ne 0 ];
    then
        npm install -g @vscode/vsce
        if [ $? -ne 0 ];
        then
            echo "Failed to find or install vsce"
            exit 1
        fi
    fi

    cd "${ROOT}/phathom"
    vsce package \
        --no-dependencies \
        --out "${ROOT}/phathom.vsix"
    if [ $? -ne 0 ];
    then
        echo "Could not build phathom vscode extension"
        exit 1
    fi
fi

code --install-extension "${VSIX}"

if [ $? -ne 0 ];
then
    echo "Could not install phathom vscode extension"
    rm -rf "${VSIX}"
    exit 1
fi

rm -rf "${VSIX}"