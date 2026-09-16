# Graalians Discord Server Tool Hub

A themed web tool hub for GraalOnline community utilities, built for the Graalians Discord Server. The hub includes browser-based helpers for creating GMAP packages, editing and filling `.nw` levels, converting Graal level formats, formatting GS2 scripts, previewing GANI files, and exporting level previews.

The interface is styled around an early-2000s GraalOnline/Graal2001 feel: parchment panels, stone-and-gold navigation, pixel-art-friendly previews, and custom background artwork.

## Features

- Shared Graalians Discord Server theme across all tool pages.
- Tool hub landing page with clear navigation for every utility.
- GMAP package generator with zip downloads, README/community links inside the output, statistics, and rate limiting.
- Graal Level Filler with `.nw` import, live preview, click-to-fill, drag-to-fill, manual region controls, auto-fill, copy, and download.
- Graal2NW Converter for normalizing `.nw` data and converting simple tile-list inputs.
- Dungeon Generator for seeded 64 x 64 dungeon starter levels.
- GS2 Beautify with indentation controls, copy/download, and imported Notepad++ GScript2 syntax highlighting colors.
- Level Editor for painting `.nw` level data in the browser.
- GANI Editor for opening, editing, previewing, and exporting `.gani` animation data.
- NW2PNG renderer for turning `.nw` BOARD data into a PNG preview.
- Community links for Discord, Reddit, YouTube, WordPress, Twitch, and Instagram.

## Screenshots

### Tool Hub

![Graalians Discord Server Tool Hub](docs/screenshots/tool-hub-home.png)

### GMAP Generator

![GMAP Generator](docs/screenshots/gmap-generator.png)

### Level Filler

![Graal Level Filler](docs/screenshots/level-filler.png)

### GS2 Beautify

![GS2 Beautify](docs/screenshots/gs2-beautify.png)

## Tools Included

| Tool | File | What it does |
| --- | --- | --- |
| Tool Hub | `index.php` | Landing page for all Graalians tools. |
| GMAP Generator | `generatelevels.php` | Creates a square `.gmap` package with linked blank `.nw` levels and a zip download. |
| Graal Level Filler | `graal-level-filler.php` | Fills blank tiles or selected regions in `.nw` level data. |
| Graal2NW Converter | `graal2nw-converter.php` | Helps normalize or convert Graal level text into `.nw` output. |
| Dungeon Generator | `dungeon-generator.php` | Builds a seeded dungeon starter level. |
| GS2 Beautify | `gs2-beautify.php` | Formats GS2 code and shows imported GScript2-style highlighting. |
| Level Editor | `level-editor.php` | Paints 64 x 64 `.nw` level data directly in the browser. |
| GANI Editor | `gani-editor.php` | Edits and previews Graal `.gani` animation files. |
| NW2PNG | `nw2png.php` | Renders `.nw` BOARD data as a PNG image. |

## Working Reference Implementations

Known-working source supplied for this project is preserved under [`reference-tools/`](reference-tools/). These references are kept separate from the live themed PHP pages so proven behavior can be compared and ported without accidentally replacing the browser Hub with unrelated desktop/runtime code.

| Reference | Use it to verify |
| --- | --- |
| `gani-edit-working-source.zip` | GANI parsing, resources, sprites, frame timing, single-direction animations, looping, freezing, setback behavior, and browser editing. |
| `GraalViewer2-working-source.zip` | Desktop GANI loading/viewing behavior and animation rendering logic. |
| `graal-gmap-generator-working-source.zip` | GMAP layout, level naming/content generation, templates, and expected behavior covered by its tests. |

See [`reference-tools/README.md`](reference-tools/README.md) for source notes, package hashes, build/dependency details, and attribution information.

## How It Works

### Shared Tool System

The shared layout, navigation, community links, and common page helpers live in `tools_common.php`. Most tool pages call into that file so they all use the same header, footer, navigation, and theme.

The shared CSS lives in `graalians-tools.css`. It controls the Graal-inspired page background, header, navigation, panels, forms, buttons, editor surfaces, preview canvases, footer, and responsive behavior.

The shared browser-side helper library lives in `graal-tools.js`. It handles common level operations such as:

- Parsing `.nw` `BOARD` rows.
- Serializing updated `.nw` levels.
- Converting Graal tile codes to numeric tile indexes and back.
- Drawing 64 x 64 previews on canvas.
- Downloading text files, blobs, and PNG previews.
- Copying output text.
- Recording download events for simple statistics.

### GMAP Generator

The GMAP generator accepts a package name and a square map size. A map size of `10` means the generator builds a `10 x 10` GMAP layout, which creates `100` blank `.nw` level files.

Each `.nw` file is still a normal `64 x 64` tile level. The `.gmap` file does not create one giant level file. Instead, it lists the level files in rows so Graal can treat the grid as one connected overworld.

The generator keeps the GMAP square because it makes the level order predictable, keeps edge links consistent, and avoids uneven rows that are easier to break when moving or renaming files.

Generated zip files include:

- The `.gmap` file.
- Blank `.nw` levels with edge links.
- A README with community links.
- Shortcut/link files for Graalians community pages.

The generator also includes simple anti-abuse controls:

- Generation rate limit.
- Download rate limit.
- Visit counting cooldown.
- Temporary cleanup for generated zip files.

### Level Filler

The Level Filler reads `.nw` level text, parses layer-0 `BOARD` rows, and displays the level on a canvas. Users can:

- Open a `.nw` file.
- Paste `.nw` text.
- Start a new blank `.nw` file.
- Click one tile to fill it.
- Drag across the preview to fill a rectangle.
- Use manual X, Y, Width, and Height fields.
- Fill only blank tiles or overwrite the selected region.
- Download or copy the updated `.nw` output.

### GS2 Beautify

The GS2 Beautify page formats script text in the browser. It also includes a live highlighting layer based on the imported Notepad++ GScript2 syntax profile. The highlighter colors keywords, constants, comments, strings, numbers, operators, and punctuation to make pasted scripts easier to read before and after formatting.

## Setup

1. Put this folder in a PHP-capable web server, such as XAMPP under `htdocs`.
2. Open the hub in a browser:

   ```text
   http://127.0.0.1/tools/index.php
   ```

3. For GMAP zip generation, make sure PHP has `ZipArchive` enabled.
4. The app creates runtime folders/files as needed:

   - `maps/` for temporary generated zip packages.
   - `generator_data/` for visit, generation, and download statistics.

Runtime data is ignored by Git so local counts, generated zips, and private server files are not uploaded.

## Suggested GitHub Topics

`graal`, `graalonline`, `graalians`, `gmap`, `nw-levels`, `gs2`, `gani`, `level-editor`, `php`, `javascript`, `tool-hub`

## Project Structure

```text
.
├── index.php
├── tools_common.php
├── graalians-tools.css
├── graal-tools.js
├── generatelevels.php
├── graal-level-filler.php
├── graal2nw-converter.php
├── dungeon-generator.php
├── gs2-beautify.php
├── level-editor.php
├── gani-editor.php
├── nw2png.php
├── tool_event.php
├── images/
├── docs/screenshots/
└── reference-tools/
    ├── README.md
    └── packages/
        ├── gani-edit-working-source.zip
        ├── GraalViewer2-working-source.zip
        └── graal-gmap-generator-working-source.zip
```

## TODO

See `TODO.md` for planned improvements and future tool ideas.

## Community

- Discord: https://discord.gg/AeDurPz
- Reddit: https://reddit.com/r/graal
- YouTube: https://www.youtube.com/@GraalDiscord
- WordPress: https://graaldisocrd.wordpress.com/
- Twitch: https://www.twitch.tv/rice2k
- Instagram: https://www.instagram.com/Graal_Discord/
