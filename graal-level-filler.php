<?php
declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'tools_common.php';

renderPageStart(
    'Graal Level Filler - Graalians Discord Server Tools',
    'Fill blank space or selected regions in Graal .nw levels and download the updated file.'
);
?>
        <section class="page-hero compact">
            <div class="shell">
                <p class="eyebrow">Online tool</p>
                <h1>Graal Level Filler</h1>
                <p>Clean up blank .nw levels, fill selected regions, and export a ready-to-edit level file.</p>
            </div>
        </section>

        <section class="shell tool-workspace">
            <div class="tool-panel">
                <h2>Level input</h2>
                <div class="form-grid">
                    <div class="wide-field">
                        <label for="levelFile">Open .nw file</label>
                        <input id="levelFile" type="file" accept=".nw,text/plain">
                    </div>

                    <div class="wide-field">
                        <label for="levelText">NW level text</label>
                        <textarea id="levelText" spellcheck="false"></textarea>
                    </div>

                    <div class="field">
                        <label for="tileCode">Fill tile</label>
                        <input id="tileCode" value="AQ" maxlength="6">
                    </div>
                    <div class="field">
                        <label for="secondTileCode">Second tile</label>
                        <input id="secondTileCode" value="AA" maxlength="6">
                    </div>
                    <div class="field">
                        <label for="fillMode">Mode</label>
                        <select id="fillMode">
                            <option value="blank">Blank tiles only</option>
                            <option value="all">Overwrite region</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="pattern">Pattern</label>
                        <select id="pattern">
                            <option value="solid">Solid</option>
                            <option value="checker">Checker</option>
                            <option value="border">Border</option>
                            <option value="path">Path cross</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="regionX">X</label>
                        <input id="regionX" type="number" min="0" max="63" value="0">
                    </div>
                    <div class="field">
                        <label for="regionY">Y</label>
                        <input id="regionY" type="number" min="0" max="63" value="0">
                    </div>
                    <div class="field">
                        <label for="regionW">Width</label>
                        <input id="regionW" type="number" min="1" max="64" value="64">
                    </div>
                    <div class="field">
                        <label for="regionH">Height</label>
                        <input id="regionH" type="number" min="1" max="64" value="64">
                    </div>
                </div>

                <div class="actions">
                    <button class="button" id="fillButton" type="button">Fill level</button>
                    <button class="button secondary" id="autoFillButton" type="button">Auto-fill blanks</button>
                    <button class="button secondary" id="checkButton" type="button">Check file</button>
                    <button class="button secondary" id="downloadButton" type="button">Download .nw</button>
                    <button class="button secondary" id="copyButton" type="button">Copy level text</button>
                    <button class="button secondary" id="exampleButton" type="button">New .nw file</button>
                </div>

                <div class="tool-status-line" id="status">Ready.</div>
            </div>

            <aside>
                <div class="preview-panel">
                    <h2>Preview</h2>
                    <div class="canvas-wrap">
                        <canvas class="level-canvas" id="previewCanvas" width="640" height="640" tabindex="0" aria-label="Level preview. Click or drag to fill tiles."></canvas>
                    </div>
                    <p class="small-help">Click a tile to fill it. Drag across the preview to fill a rectangle. The X, Y, Width, and Height fields update as you select.</p>
                    <div class="metric-row">
                        <div class="metric">
                            <strong id="boardRows">0</strong>
                            <span>Board rows</span>
                        </div>
                        <div class="metric">
                            <strong id="blankTiles">4096</strong>
                            <span>Blank tiles</span>
                        </div>
                        <div class="metric">
                            <strong id="changedTiles">0</strong>
                            <span>Tiles changed</span>
                        </div>
                    </div>
                </div>

                <div class="source-panel">
                    <h2>How to use Level Filler</h2>
                    <ul class="instruction-list">
                        <li>Open a <strong>.nw</strong> file or paste level text that starts with <strong>GLEVNW01</strong>.</li>
                        <li>Use <strong>Check file</strong> to confirm the tool found layer-0 BOARD rows.</li>
                        <li>Pick a fill tile, choose a region, then use <strong>Fill level</strong>. <strong>Auto-fill blanks</strong> fills every empty tile on the board.</li>
                        <li>Download the updated .nw when the preview looks right.</li>
                    </ul>
                    <p class="format-note">A standard level is 64 x 64 tiles. Each BOARD row stores 64 tiles as 128 characters, because every tile uses a two-character code such as AA or AQ.</p>
                </div>
            </aside>
        </section>

        <script src="graal-tools.js"></script>
        <script>
            (function () {
                const fileInput = document.getElementById('levelFile');
                const levelText = document.getElementById('levelText');
                const canvas = document.getElementById('previewCanvas');
                const status = document.getElementById('status');
                const boardRows = document.getElementById('boardRows');
                const changedTiles = document.getElementById('changedTiles');
                const blankTiles = document.getElementById('blankTiles');
                const tileCode = document.getElementById('tileCode');
                const secondTileCode = document.getElementById('secondTileCode');
                const fillMode = document.getElementById('fillMode');
                const pattern = document.getElementById('pattern');
                const regionX = document.getElementById('regionX');
                const regionY = document.getElementById('regionY');
                const regionW = document.getElementById('regionW');
                const regionH = document.getElementById('regionH');
                let parsed = GraalTools.parseNw('');
                let lastFileName = 'filled-level.nw';
                let selectedRegion = { x: 0, y: 0, width: 64, height: 64 };
                let dragStart = null;
                let isSelecting = false;

                function countBlankTiles() {
                    return parsed.tiles.reduce(function (total, row) {
                        return total + row.filter(function (tile) {
                            return tile === 0;
                        }).length;
                    }, 0);
                }

                function hasTextContent() {
                    return levelText.value.trim() !== '';
                }

                function syncSelectedRegionFromControls() {
                    const x = GraalTools.clamp(regionX.value, 0, 63);
                    const y = GraalTools.clamp(regionY.value, 0, 63);
                    const width = GraalTools.clamp(regionW.value, 1, 64 - x);
                    const height = GraalTools.clamp(regionH.value, 1, 64 - y);

                    selectedRegion = { x: x, y: y, width: width, height: height };
                    regionX.value = x;
                    regionY.value = y;
                    regionW.value = width;
                    regionH.value = height;
                    return selectedRegion;
                }

                function setSelectedRegion(region) {
                    selectedRegion = {
                        x: GraalTools.clamp(region.x, 0, 63),
                        y: GraalTools.clamp(region.y, 0, 63),
                        width: GraalTools.clamp(region.width, 1, 64 - GraalTools.clamp(region.x, 0, 63)),
                        height: GraalTools.clamp(region.height, 1, 64 - GraalTools.clamp(region.y, 0, 63))
                    };
                    regionX.value = selectedRegion.x;
                    regionY.value = selectedRegion.y;
                    regionW.value = selectedRegion.width;
                    regionH.value = selectedRegion.height;
                    updatePreview();
                    return selectedRegion;
                }

                function drawSelectedRegion() {
                    const ctx = canvas.getContext('2d');
                    const tileSize = canvas.width / 64;

                    if (!ctx || !selectedRegion) {
                        return;
                    }

                    ctx.save();
                    ctx.fillStyle = 'rgba(165, 49, 45, .24)';
                    ctx.strokeStyle = 'rgba(255, 216, 117, .95)';
                    ctx.lineWidth = Math.max(2, Math.round(tileSize * .18));
                    ctx.fillRect(selectedRegion.x * tileSize, selectedRegion.y * tileSize, selectedRegion.width * tileSize, selectedRegion.height * tileSize);
                    ctx.strokeRect(selectedRegion.x * tileSize + 1, selectedRegion.y * tileSize + 1, selectedRegion.width * tileSize - 2, selectedRegion.height * tileSize - 2);
                    ctx.restore();
                }

                function updatePreview() {
                    boardRows.textContent = parsed.boardLines;
                    blankTiles.textContent = countBlankTiles();
                    GraalTools.renderBoard(canvas, parsed.tiles, { tileSize: 10 });
                    drawSelectedRegion();
                }

                function refreshFromText(message, requireBoard) {
                    parsed = GraalTools.parseNw(levelText.value);
                    updatePreview();

                    if (requireBoard && hasTextContent() && parsed.boardLines === 0) {
                        GraalTools.setStatus(status, 'No layer-0 BOARD rows found. Open a .nw file, paste GLEVNW01 level text, or click New .nw file.', 'error');
                        return false;
                    }

                    const warningText = parsed.warnings.join(' ');
                    GraalTools.setStatus(status, message || warningText || 'Level loaded.', warningText ? 'info' : 'success');
                    return true;
                }

                function writeBlankLevel() {
                    parsed = {
                        tiles: GraalTools.createTiles(0),
                        extraLines: [],
                        boardLines: 64,
                        warnings: []
                    };
                    levelText.value = GraalTools.serializeNw(parsed.tiles, []);
                    changedTiles.textContent = '0';
                    refreshFromText('New blank .nw file ready.', false);
                }

                function normalizeControls() {
                    tileCode.value = GraalTools.normalizePair(tileCode.value, 'AQ');
                    secondTileCode.value = GraalTools.normalizePair(secondTileCode.value, 'AA');
                    return syncSelectedRegionFromControls();
                }

                function currentFillOptions(region) {
                    const target = region || syncSelectedRegionFromControls();
                    return {
                        tile: tileCode.value,
                        secondaryTile: secondTileCode.value,
                        mode: fillMode.value,
                        pattern: pattern.value,
                        x: target.x,
                        y: target.y,
                        width: target.width,
                        height: target.height
                    };
                }

                function fillCurrent(options) {
                    if (!refreshFromText('Level parsed.', true)) {
                        return 0;
                    }

                    const region = normalizeControls();
                    const count = GraalTools.applyFill(parsed.tiles, Object.assign(currentFillOptions(region), options || {}));

                    levelText.value = GraalTools.serializeNw(parsed.tiles, parsed.extraLines);
                    changedTiles.textContent = count;
                    updatePreview();
                    GraalTools.setStatus(status, count + ' tile' + (count === 1 ? '' : 's') + ' changed.', count > 0 ? 'success' : 'info');
                    return count;
                }

                fileInput.addEventListener('change', function () {
                    const file = fileInput.files[0];
                    if (!file) {
                        return;
                    }

                        lastFileName = GraalTools.normalizeFilename(file.name.replace(/\.[^.]+$/, '') + '-filled', 'filled-level', '.nw');
                    GraalTools.readFile(file).then(function (text) {
                        levelText.value = text;
                        changedTiles.textContent = '0';
                        refreshFromText('Loaded ' + file.name + '.', true);
                    }).catch(function () {
                        GraalTools.setStatus(status, 'Could not read that file.', 'error');
                    });
                });

                document.getElementById('fillButton').addEventListener('click', function () {
                    fillCurrent(currentFillOptions());
                });

                document.getElementById('autoFillButton').addEventListener('click', function () {
                    fillMode.value = 'blank';
                    pattern.value = 'solid';
                    setSelectedRegion({ x: 0, y: 0, width: 64, height: 64 });
                    const count = fillCurrent({
                        tile: tileCode.value,
                        secondaryTile: secondTileCode.value,
                        mode: 'blank',
                        pattern: 'solid',
                        x: 0,
                        y: 0,
                        width: 64,
                        height: 64
                    });

                    if (count > 0) {
                        GraalTools.setStatus(status, 'Auto-filled ' + count + ' blank tile' + (count === 1 ? '' : 's') + ' across the full level.', 'success');
                    }
                });

                document.getElementById('checkButton').addEventListener('click', function () {
                    if (refreshFromText('', true)) {
                        const warnings = parsed.warnings.filter(function (warning) {
                            return !/^No layer 0 BOARD/.test(warning);
                        });
                        GraalTools.setStatus(status, 'File check passed: ' + parsed.boardLines + ' layer-0 BOARD row' + (parsed.boardLines === 1 ? '' : 's') + ' found, ' + countBlankTiles() + ' blank tile' + (countBlankTiles() === 1 ? '' : 's') + '.', warnings.length ? 'info' : 'success');
                    }
                });

                document.getElementById('downloadButton').addEventListener('click', function () {
                    if (!refreshFromText('Preparing download.', true)) {
                        return;
                    }
                    GraalTools.downloadText(GraalTools.normalizeFilename(lastFileName, 'filled-level', '.nw'), GraalTools.serializeNw(parsed.tiles, parsed.extraLines), 'text/plain;charset=utf-8');
                    GraalTools.setStatus(status, 'Download created.', 'success');
                });

                document.getElementById('copyButton').addEventListener('click', function () {
                    GraalTools.copyText(levelText.value).then(function () {
                        GraalTools.setStatus(status, 'Level text copied.', 'success');
                    }).catch(function () {
                        GraalTools.setStatus(status, 'Copy failed. Select the level text and copy it manually.', 'error');
                    });
                });

                tileCode.addEventListener('change', function () {
                    tileCode.value = GraalTools.normalizePair(tileCode.value, 'AQ');
                });
                secondTileCode.addEventListener('change', function () {
                    secondTileCode.value = GraalTools.normalizePair(secondTileCode.value, 'AA');
                });

                [regionX, regionY, regionW, regionH].forEach(function (control) {
                    control.addEventListener('change', function () {
                        syncSelectedRegionFromControls();
                        updatePreview();
                    });
                });

                function tileFromPointer(event) {
                    const rect = canvas.getBoundingClientRect();
                    const x = GraalTools.clamp(Math.floor(((event.clientX - rect.left) / rect.width) * 64), 0, 63);
                    const y = GraalTools.clamp(Math.floor(((event.clientY - rect.top) / rect.height) * 64), 0, 63);
                    return { x: x, y: y };
                }

                function regionFromTiles(start, end) {
                    const x = Math.min(start.x, end.x);
                    const y = Math.min(start.y, end.y);
                    return {
                        x: x,
                        y: y,
                        width: Math.abs(start.x - end.x) + 1,
                        height: Math.abs(start.y - end.y) + 1
                    };
                }

                canvas.addEventListener('pointerdown', function (event) {
                    if (event.button !== 0) {
                        return;
                    }

                    dragStart = tileFromPointer(event);
                    isSelecting = true;
                    canvas.setPointerCapture(event.pointerId);
                    setSelectedRegion({ x: dragStart.x, y: dragStart.y, width: 1, height: 1 });
                    GraalTools.setStatus(status, 'Selected tile X ' + dragStart.x + ', Y ' + dragStart.y + '. Drag to choose a larger area, then release to fill.', 'info');
                });

                canvas.addEventListener('pointermove', function (event) {
                    if (!isSelecting || !dragStart) {
                        return;
                    }

                    setSelectedRegion(regionFromTiles(dragStart, tileFromPointer(event)));
                });

                canvas.addEventListener('pointerup', function (event) {
                    if (!isSelecting || !dragStart) {
                        return;
                    }

                    const region = setSelectedRegion(regionFromTiles(dragStart, tileFromPointer(event)));
                    isSelecting = false;
                    dragStart = null;

                    fillCurrent(currentFillOptions(region));
                });

                canvas.addEventListener('pointercancel', function () {
                    isSelecting = false;
                    dragStart = null;
                });

                document.getElementById('exampleButton').addEventListener('click', writeBlankLevel);
                levelText.addEventListener('blur', function () {
                    refreshFromText('Preview refreshed.', true);
                });

                writeBlankLevel();
            }());
        </script>
<?php
renderPageEnd();
