# vscode

phathom provides a syntax highlighter for `vscode` to improve QoL.

## Installation

Installation is automatic, however if you like to rebuild and install the extension yourself:

```
vsce package --no-dependencies --out /path/to/phathom/.vscode/phathom.vsix
```

Then:

```
vsce --install-extension /path/to/phathom/.vscode/phathom.vsix
```

## Updates

This extension is not published to the marketplace (yet), so updates must be performed manually.

Remove the extension using: 

```
code --uninstall-extension krakjoe.phathom
```

Then reload the window (re-installation will be automatic, built from current source).