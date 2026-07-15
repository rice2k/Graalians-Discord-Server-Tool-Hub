<?php
declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'tools_common.php';

renderPageStart(
    'NW2PNG - Graalians Discord Server Tools',
    'Render Graal .nw level BOARD data as a PNG image in the browser.'
);
?>
        <section class="page-hero compact">
            <div class="shell">
                <p class="eyebrow">Online tool</p>
                <h1>NW2PNG</h1>
                <p>Render .nw level data to a PNG preview, with optional pics1.png tileset support for closer Graal-style output.</p>
            </div>
        </section>

        <section class="shell tool-workspace">
            <div class="tool-panel">
                <h2>Render settings</h2>
                <div class="form-grid two">
                    <div class="field">
                        <label for="levelFile">NW file</label>
                        <input id="levelFile" type="file" accept=".nw,text/plain">
                    </div>
                    <div class="field">
                        <label for="tilesetFile">Tileset image</label>
                        <input id="tilesetFile" type="file" accept="image/png,image/gif,image/jpeg">
                    </div>
                    <div class="field">
                        <label for="tileSize">PNG tile size</label>
                        <select id="tileSize">
                            <option value="16">16 px</option>
                            <option value="12">12 px</option>
                            <option value="10">10 px</option>
                            <option value="8">8 px</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="gridMode">Grid</label>
                        <select id="gridMode">
                            <option value="off">Off</option>
                            <option value="on">On</option>
                        </select>
                    </div>
                    <div class="wide-field">
                        <label for="levelText">NW level text</label>
                        <textarea id="levelText" spellcheck="false"></textarea>
                    </div>
                </div>

                <div class="actions">
                    <button class="button" id="renderButton" type="button">Render PNG</button>
                    <button class="button secondary" id="downloadPngButton" type="button">Download PNG</button>
                    <button class="button secondary" id="loadExampleButton" type="button">Load sample</button>
                </div>

                <div class="tool-status-line" id="status">Ready.</div>
            </div>

            <aside>
                <div class="preview-panel">
                    <h2>PNG preview</h2>
                    <div class="canvas-wrap">
                        <canvas class="level-canvas" id="previewCanvas" width="1024" height="1024"></canvas>
                    </div>
                    <div class="metric-row">
                        <div class="metric">
                            <strong id="boardRows">0</strong>
                            <span>Board rows</span>
                        </div>
                        <div class="metric">
                            <strong id="imageSize">1024</strong>
                            <span>Pixels wide</span>
                        </div>
                        <div class="metric">
                            <strong id="tilesetMode">Color</strong>
                            <span>Render mode</span>
                        </div>
                    </div>
                </div>

                <div class="source-panel">
                    <h2>How to render</h2>
                    <p>Open a <strong>.nw</strong> file or paste level text, then render it into a PNG preview you can download.</p>
                    <ul class="instruction-list">
                        <li>Leave the tileset empty for a color-coded map preview.</li>
                        <li>Upload <strong>pics1.png</strong> or another Graal-style tileset for a closer visual render.</li>
                        <li>Turn the grid on when you want to check tile placement and level structure.</li>
                    </ul>
                    <p class="format-note">The renderer reads layer-0 BOARD rows. Non-zero layers and scripts stay in the text but are not drawn into the PNG.</p>
                </div>
            </aside>
        </section>

        <script src="graal-tools.js"></script>
        <script>
            (function () {
                const levelFile = document.getElementById('levelFile');
                const tilesetFile = document.getElementById('tilesetFile');
                const levelText = document.getElementById('levelText');
                const tileSize = document.getElementById('tileSize');
                const gridMode = document.getElementById('gridMode');
                const canvas = document.getElementById('previewCanvas');
                const status = document.getElementById('status');
                const boardRows = document.getElementById('boardRows');
                const imageSize = document.getElementById('imageSize');
                const tilesetMode = document.getElementById('tilesetMode');
                let tilesetImage = null;
                let outputName = 'level.png';

                function sampleLevel() {
                    const tiles = GraalTools.createTiles(GraalTools.pairToTile('AA'));

                    for (let y = 0; y < 64; y++) {
                        for (let x = 0; x < 64; x++) {
                            if (x < 4 || y < 4 || x > 59 || y > 59) {
                                tiles[y][x] = GraalTools.pairToTile('AQ');
                            } else if ((x + y) % 13 === 0) {
                                tiles[y][x] = GraalTools.pairToTile('Ag');
                            }
                        }
                    }

                    return GraalTools.serializeNw(tiles, ['SIGN 8 8 Graalians Discord Server']);
                }

                function render(message) {
                    const parsed = GraalTools.parseNw(levelText.value);
                    const size = Number.parseInt(tileSize.value, 10);
                    GraalTools.renderBoard(canvas, parsed.tiles, {
                        tileSize: size,
                        tileset: tilesetImage,
                        grid: gridMode.value === 'on'
                    });
                    boardRows.textContent = parsed.boardLines;
                    imageSize.textContent = 64 * size;
                    tilesetMode.textContent = tilesetImage ? 'Tileset' : 'Color';
                    GraalTools.setStatus(status, message || parsed.warnings.join(' ') || 'Rendered.', parsed.warnings.length ? 'info' : 'success');
                }

                levelFile.addEventListener('change', function () {
                    const file = levelFile.files[0];
                    if (!file) {
                        return;
                    }

                    outputName = GraalTools.normalizeFilename(file.name.replace(/\.[^.]+$/, ''), 'level', '.png');
                    GraalTools.readFile(file).then(function (text) {
                        levelText.value = text;
                        render('Loaded ' + file.name + '.');
                    }).catch(function () {
                        GraalTools.setStatus(status, 'Could not read that level file.', 'error');
                    });
                });

                tilesetFile.addEventListener('change', function () {
                    const file = tilesetFile.files[0];
                    if (!file) {
                        tilesetImage = null;
                        render('Tileset removed.');
                        return;
                    }

                    GraalTools.loadImage(file).then(function (image) {
                        tilesetImage = image;
                        render('Tileset loaded: ' + file.name + '.');
                    }).catch(function () {
                        GraalTools.setStatus(status, 'Could not load that tileset image.', 'error');
                    });
                });

                document.getElementById('renderButton').addEventListener('click', function () {
                    render('Rendered.');
                });

                document.getElementById('downloadPngButton').addEventListener('click', function () {
                    render('Preparing PNG.');
                    GraalTools.downloadCanvasPng(canvas, GraalTools.normalizeFilename(outputName, 'level', '.png')).then(function () {
                        GraalTools.setStatus(status, 'PNG download created.', 'success');
                    }).catch(function (error) {
                        GraalTools.setStatus(status, error.message || 'PNG export failed.', 'error');
                    });
                });

                document.getElementById('loadExampleButton').addEventListener('click', function () {
                    levelText.value = sampleLevel();
                    outputName = 'graalians-sample.png';
                    render('Sample loaded.');
                });

                tileSize.addEventListener('change', function () {
                    render('Size updated.');
                });

                gridMode.addEventListener('change', function () {
                    render('Grid updated.');
                });

                levelText.value = sampleLevel();
                render('Sample loaded.');
            }());
        </script>
<?php
renderPageEnd();
