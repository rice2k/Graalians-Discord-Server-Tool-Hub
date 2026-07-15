# TODO

Planned improvements for the Graalians Discord Server Tool Hub.

## Short Term

- Add more screenshots for every tool page, including Level Editor, GANI Editor, Dungeon Generator, Graal2NW, and NW2PNG.
- Add a small status panel to the hub showing which tools are fully ready and which tools need more testing.
- Add clearer per-tool error messages for invalid file uploads, empty input, malformed BOARD rows, and unsupported formats.
- Add downloadable example files for each tool so visitors can test without finding their own Graal files first.
- Add a version number and changelog on the hub footer.

## GMAP Generator

- Add an optional preview of the generated GMAP grid before creating the zip.
- Add selectable starter terrain patterns instead of only blank levels.
- Add optional world-edge behavior controls for link generation.
- Add a server-side cleanup log for generated zips.
- Add clearer messages when PHP `ZipArchive` is not enabled.

## Graal Level Filler

- Add brush sizes for click-to-fill mode.
- Add undo/redo for fill operations.
- Add hover tile coordinates on the preview.
- Add optional tileset image preview support.
- Add named presets for common fill tiles.

## GS2 Beautify

- Expand the GScript2 keyword list from additional documentation.
- Add a side-by-side before/after mode.
- Add import/export of `.gs2` and `.txt` files.
- Add warnings for obviously unmatched braces or strings.

## Level Editor

- Add undo/redo.
- Add fill bucket and line tools.
- Add tileset selection and tile picker support.
- Add layer support if needed for more advanced `.nw` editing.

## GANI Editor

- Improve sprite-sheet handling.
- Add timeline controls for frame-by-frame inspection.
- Add more validation for malformed GANI files.
- Add example animations for testing.

## NW2PNG

- Improve tileset cropping accuracy.
- Add transparent background options.
- Add scale controls for exported PNGs.
- Add batch rendering support.

## Documentation

- Add a user guide for every tool.
- Add a developer guide explaining shared helpers in `graal-tools.js`.
- Add deployment notes for XAMPP, Apache, and basic PHP hosting.
- Add troubleshooting for file permissions, zip creation, and browser download blocking.

## Quality

- Add automated browser checks for each page.
- Add fixture files for `.nw`, `.gmap`, `.gani`, and GS2 scripts.
- Add lightweight unit tests for parser and serializer behavior.
- Add a release checklist before publishing updates.
