<?php
declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'tools_common.php';

renderPageStart(
    'Level Editor - Graalians Discord Server Tools',
    'Paint and export 64 x 64 Graal .nw levels in the browser.'
);
?>
        <section class="page-hero compact">
            <div class="shell">
                <p class="eyebrow">Online tool</p>
                <h1>Level Editor</h1>
                <p>Paint a 64 x 64 .nw level, import existing files, and export level text or a PNG preview.</p>
            </div>
        </section>

        <section class="shell tool-workspace">
            <div class="tool-panel">
                <h2>Editor</h2>
                <div class="form-grid">
                    <div class="wide-field">
                        <label for="levelFile">Open .nw file</label>
                        <input id="levelFile" type="file" accept=".nw,text/plain">
                    </div>
                    <div class="field">
                        <label for="toolMode">Tool</label>
                        <select id="toolMode">
                            <option value="paint">Paint</option>
                            <option value="fill">Fill</option>
                            <option value="pick">Pick tile</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="tileCode">Tile</label>
                        <input id="tileCode" value="AQ" maxlength="6">
                    </div>
                    <div class="field">
                        <label for="brushSize">Brush</label>
                        <select id="brushSize">
                            <option value="1">1 x 1</option>
                            <option value="2">2 x 2</option>
                            <option value="3">3 x 3</option>
                            <option value="5">5 x 5</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="outputName">File</label>
                        <input id="outputName" value="edited-level.nw">
                    </div>
                </div>

                <div class="canvas-wrap" style="margin-top:14px">
                    <canvas class="paint-canvas" id="editorCanvas" width="640" height="640"></canvas>
                </div>

                <div class="actions">
                    <button class="button" id="downloadNwButton" type="button">Download .nw</button>
                    <button class="button secondary" id="downloadPngButton" type="button">Download PNG</button>
                    <button class="button secondary" id="clearButton" type="button">Clear</button>
                </div>

                <div class="tool-status-line" id="status">Ready.</div>
            </div>

            <aside>
                <div class="preview-panel">
                    <h2>Level data</h2>
                    <div class="metric-row">
                        <div class="metric">
                            <strong id="pickedTile">AQ</strong>
                            <span>Active tile</span>
                        </div>
                        <div class="metric">
                            <strong id="cursorTile">0,0</strong>
                            <span>Cursor</span>
                        </div>
                        <div class="metric">
                            <strong id="boardRows">64</strong>
                            <span>Board rows</span>
                        </div>
                    </div>
                    <div class="wide-field" style="margin-top:14px">
                        <label for="levelText">NW level text</label>
                        <textarea id="levelText" spellcheck="false"></textarea>
                    </div>
                    <div class="actions">
                        <button class="button secondary small" id="applySourceButton" type="button">Apply text</button>
                        <button class="button secondary small" id="copySourceButton" type="button">Copy text</button>
                    </div>
                </div>

                <div class="source-panel">
                    <h2>Editor guide</h2>
                    <p>Paint directly on a 64 x 64 board, import an existing <strong>.nw</strong>, or edit the level text and apply it back to the canvas.</p>
                    <ul class="instruction-list">
                        <li><strong>Paint</strong> draws with the active tile and brush size.</li>
                        <li><strong>Fill</strong> replaces a connected area with the active tile.</li>
                        <li><strong>Pick tile</strong> samples a tile from the canvas so you can keep painting with it.</li>
                    </ul>
                    <p class="format-note">A normal .nw level stores the main layer as 64 BOARD rows. Each row has 64 tiles, and each tile is saved as a two-character code.</p>
                </div>
            </aside>
        </section>

        <script src="graal-tools.js"></script>
        <script>
            (function () {
                const canvas = document.getElementById('editorCanvas');
                const levelText = document.getElementById('levelText');
                const status = document.getElementById('status');
                const tileCode = document.getElementById('tileCode');
                const pickedTile = document.getElementById('pickedTile');
                const cursorTile = document.getElementById('cursorTile');
                const boardRows = document.getElementById('boardRows');
                let tiles = GraalTools.createTiles(0);
                let extraLines = [];
                let isDrawing = false;

                function render(message, type) {
                    GraalTools.renderBoard(canvas, tiles, { tileSize: 10 });
                    pickedTile.textContent = GraalTools.normalizePair(tileCode.value, 'AA');
                    levelText.value = GraalTools.serializeNw(tiles, extraLines);
                    if (message) {
                        GraalTools.setStatus(status, message, type || 'success');
                    }
                }

                function tileFromEvent(event) {
                    const rect = canvas.getBoundingClientRect();
                    const x = Math.floor(((event.clientX - rect.left) / rect.width) * 64);
                    const y = Math.floor(((event.clientY - rect.top) / rect.height) * 64);
                    return {
                        x: GraalTools.clamp(x, 0, 63),
                        y: GraalTools.clamp(y, 0, 63)
                    };
                }

                function paintAt(x, y) {
                    const size = Number.parseInt(document.getElementById('brushSize').value, 10);
                    const half = Math.floor(size / 2);
                    const tile = GraalTools.pairToTile(GraalTools.normalizePair(tileCode.value, 'AA'));

                    for (let yy = y - half; yy < y - half + size; yy++) {
                        for (let xx = x - half; xx < x - half + size; xx++) {
                            if (xx >= 0 && xx < 64 && yy >= 0 && yy < 64) {
                                tiles[yy][xx] = tile;
                            }
                        }
                    }
                }

                function fillAt(x, y) {
                    const target = tiles[y][x];
                    const replacement = GraalTools.pairToTile(GraalTools.normalizePair(tileCode.value, 'AA'));

                    if (target === replacement) {
                        return;
                    }

                    const queue = [[x, y]];
                    const seen = new Set();

                    while (queue.length) {
                        const current = queue.shift();
                        const key = current[0] + ',' + current[1];

                        if (seen.has(key)) {
                            continue;
                        }

                        seen.add(key);
                        const cx = current[0];
                        const cy = current[1];

                        if (cx < 0 || cx > 63 || cy < 0 || cy > 63 || tiles[cy][cx] !== target) {
                            continue;
                        }

                        tiles[cy][cx] = replacement;
                        queue.push([cx + 1, cy], [cx - 1, cy], [cx, cy + 1], [cx, cy - 1]);
                    }
                }

                function useTool(event) {
                    const pos = tileFromEvent(event);
                    const mode = document.getElementById('toolMode').value;
                    cursorTile.textContent = pos.x + ',' + pos.y;

                    if (mode === 'pick') {
                        tileCode.value = GraalTools.tileToPair(tiles[pos.y][pos.x]);
                        render('Picked tile ' + tileCode.value + '.', 'success');
                        return;
                    }

                    if (mode === 'fill') {
                        fillAt(pos.x, pos.y);
                    } else {
                        paintAt(pos.x, pos.y);
                    }

                    render(mode === 'fill' ? 'Region filled.' : 'Tile painted.', 'success');
                }

                canvas.addEventListener('pointerdown', function (event) {
                    isDrawing = true;
                    canvas.setPointerCapture(event.pointerId);
                    useTool(event);
                });

                canvas.addEventListener('pointermove', function (event) {
                    const pos = tileFromEvent(event);
                    cursorTile.textContent = pos.x + ',' + pos.y;

                    if (isDrawing && document.getElementById('toolMode').value === 'paint') {
                        useTool(event);
                    }
                });

                canvas.addEventListener('pointerup', function () {
                    isDrawing = false;
                });

                canvas.addEventListener('pointercancel', function () {
                    isDrawing = false;
                });

                document.getElementById('levelFile').addEventListener('change', function () {
                    const file = this.files[0];
                    if (!file) {
                        return;
                    }

                    document.getElementById('outputName').value = GraalTools.normalizeFilename(file.name.replace(/\.[^.]+$/, '') + '-edited', 'edited-level', '.nw');
                    GraalTools.readFile(file).then(function (text) {
                        const parsed = GraalTools.parseNw(text);
                        tiles = parsed.tiles;
                        extraLines = parsed.extraLines;
                        boardRows.textContent = parsed.boardLines;
                        render('Loaded ' + file.name + '.', parsed.warnings.length ? 'info' : 'success');
                    }).catch(function () {
                        GraalTools.setStatus(status, 'Could not read that file.', 'error');
                    });
                });

                document.getElementById('applySourceButton').addEventListener('click', function () {
                    const parsed = GraalTools.parseNw(levelText.value);
                    tiles = parsed.tiles;
                    extraLines = parsed.extraLines;
                    boardRows.textContent = parsed.boardLines;
                    render('Level text applied.', parsed.warnings.length ? 'info' : 'success');
                });

                document.getElementById('copySourceButton').addEventListener('click', function () {
                    GraalTools.copyText(levelText.value).then(function () {
                        GraalTools.setStatus(status, 'Level text copied.', 'success');
                    }).catch(function () {
                        GraalTools.setStatus(status, 'Copy failed. Select the level text and copy it manually.', 'error');
                    });
                });

                document.getElementById('downloadNwButton').addEventListener('click', function () {
                    const name = GraalTools.normalizeFilename(document.getElementById('outputName').value, 'edited-level', '.nw');
                    GraalTools.downloadText(name, GraalTools.serializeNw(tiles, extraLines), 'text/plain;charset=utf-8');
                    GraalTools.setStatus(status, 'NW download created.', 'success');
                });

                document.getElementById('downloadPngButton').addEventListener('click', function () {
                    const pngName = GraalTools.normalizeFilename((document.getElementById('outputName').value || 'edited-level').replace(/\.[^.]+$/, ''), 'edited-level', '.png');
                    GraalTools.downloadCanvasPng(canvas, pngName).then(function () {
                        GraalTools.setStatus(status, 'PNG download created.', 'success');
                    }).catch(function (error) {
                        GraalTools.setStatus(status, error.message || 'PNG export failed.', 'error');
                    });
                });

                document.getElementById('clearButton').addEventListener('click', function () {
                    tiles = GraalTools.createTiles(0);
                    extraLines = [];
                    boardRows.textContent = 64;
                    render('Level cleared.', 'success');
                });

                tileCode.addEventListener('change', function () {
                    tileCode.value = GraalTools.normalizePair(tileCode.value, 'AA');
                    pickedTile.textContent = tileCode.value;
                });
                tileCode.addEventListener('input', function () {
                    pickedTile.textContent = GraalTools.normalizePair(tileCode.value, 'AA');
                });

                render('Blank editor ready.', 'success');
            }());
        </script>
<?php
renderPageEnd();
