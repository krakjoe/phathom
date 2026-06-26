#!/usr/bin/env bash
ROOT=$(dirname "${BASH_SOURCE[0]}")
VSIX="${ROOT}/phathom.vsix"

# Get the local version from package.json
LOCAL_VERSION=$(node -p "require('${ROOT}/phathom/package.json').version" 2>/dev/null)

# Get the installed version (format: krakjoe.phathom@x.y.z)
INSTALLED_VERSION=$(code --list-extensions --show-versions 2>/dev/null | \
    grep "^krakjoe.phathom@" | \
    sed 's/^krakjoe\.phathom@//')

# Bailout if installed version is >= local version
if [ -n "${INSTALLED_VERSION}" ] && [ -n "${LOCAL_VERSION}" ];
then
    if ! printf '%s\n%s\n' "${INSTALLED_VERSION}" "${LOCAL_VERSION}" | \
        sort -V -C 2>/dev/null || \
            [ "${INSTALLED_VERSION}" = "${LOCAL_VERSION}" ];
    then
        exit 0
    fi
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