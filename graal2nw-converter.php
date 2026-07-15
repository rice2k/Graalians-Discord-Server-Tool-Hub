<?php
declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'tools_common.php';

renderPageStart(
    'Graal2NW Converter - Graalians Discord Server Tools',
    'Convert or normalize Graal level data into GLEVNW01 .nw files.'
);
?>
        <section class="page-hero compact">
            <div class="shell">
                <p class="eyebrow">Online tool</p>
                <h1>Graal2NW Converter</h1>
                <p>Normalize .nw level text, convert tile lists or base64 rows, and try tile-only imports from classic Graal level files.</p>
            </div>
        </section>

        <section class="shell tool-workspace">
            <div class="tool-panel">
                <h2>Convert input</h2>
                <div class="form-grid two">
                    <div class="field">
                        <label for="sourceFile">Open file</label>
                        <input id="sourceFile" type="file" accept=".nw,.graal,.zelda,text/plain">
                    </div>
                    <div class="field">
                        <label for="sourceKind">Input type</label>
                        <select id="sourceKind">
                            <option value="auto">Auto detect</option>
                            <option value="nw">NW level</option>
                            <option value="tiles">Tile list</option>
                            <option value="binary">Classic binary</option>
                        </select>
                    </div>
                    <div class="wide-field">
                        <label for="sourceText">Input data</label>
                        <textarea id="sourceText" spellcheck="false"></textarea>
                    </div>
                    <div class="wide-field">
                        <label for="outputName">Output file</label>
                        <input id="outputName" value="converted-level.nw">
                    </div>
                    <div class="wide-field">
                        <label for="outputText">NW output</label>
                        <textarea id="outputText" spellcheck="false"></textarea>
                    </div>
                </div>

                <div class="actions">
                    <button class="button" id="convertButton" type="button">Convert to NW</button>
                    <button class="button secondary" id="downloadButton" type="button">Download .nw</button>
                    <button class="button secondary" id="copyButton" type="button">Copy output</button>
                    <button class="button secondary" id="sampleButton" type="button">Load sample</button>
                </div>

                <div class="tool-status-line" id="status">Ready.</div>
            </div>

            <aside>
                <div class="preview-panel">
                    <h2>Preview</h2>
                    <div class="canvas-wrap">
                        <canvas class="level-canvas" id="previewCanvas" width="640" height="640"></canvas>
                    </div>
                    <div class="metric-row">
                        <div class="metric">
                            <strong id="modeMetric">Auto</strong>
                            <span>Detected</span>
                        </div>
                        <div class="metric">
                            <strong id="tileMetric">0</strong>
                            <span>Tiles</span>
                        </div>
                        <div class="metric">
                            <strong id="warnMetric">0</strong>
                            <span>Warnings</span>
                        </div>
                    </div>
                </div>

                <div class="source-panel">
                    <h2>What this converter does</h2>
                    <p>Use this when you need level data turned into a clean <strong>.nw</strong> file that starts with <strong>GLEVNW01</strong> and writes the main 64 x 64 layer as <strong>BOARD</strong> rows.</p>
                    <ul class="instruction-list">
                        <li>Open or paste an existing .nw file to normalize the board rows.</li>
                        <li>Paste tile numbers, tile pairs, or 128-character BOARD data rows to build a new level.</li>
                        <li>Classic binary imports are tile-only, so scripts, signs, links, baddies, and chests may need to be rebuilt after conversion.</li>
                    </ul>
                </div>
            </aside>
        </section>

        <script src="graal-tools.js"></script>
        <script>
            (function () {
                const sourceFile = document.getElementById('sourceFile');
                const sourceText = document.getElementById('sourceText');
                const outputText = document.getElementById('outputText');
                const status = document.getElementById('status');
                const canvas = document.getElementById('previewCanvas');
                const modeMetric = document.getElementById('modeMetric');
                const tileMetric = document.getElementById('tileMetric');
                const warnMetric = document.getElementById('warnMetric');
                let lastBuffer = null;
                let lastTiles = GraalTools.createTiles(0);

                function decodeHeader(buffer) {
                    const bytes = new Uint8Array(buffer.slice(0, 8));
                    return Array.from(bytes).map(function (byte) {
                        return String.fromCharCode(byte);
                    }).join('');
                }

                function bitReader(bytes, startByte) {
                    let bitPos = startByte * 8;
                    return {
                        read: function (count) {
                            let value = 0;
                            for (let i = 0; i < count; i++) {
                                const byteIndex = bitPos >> 3;
                                const offset = 7 - (bitPos & 7);
                                value = (value << 1) | ((bytes[byteIndex] >> offset) & 1);
                                bitPos++;
                            }
                            return value;
                        },
                        has: function (count) {
                            return bitPos + count <= bytes.length * 8;
                        }
                    };
                }

                function decodeClassicBinary(buffer) {
                    const bytes = new Uint8Array(buffer);
                    const header = decodeHeader(buffer);

                    if (!/^(GR-V1\.0[0-3]|Z3-V1\.0[3-4])/.test(header)) {
                        throw new Error('Classic Graal header not found.');
                    }

                    const packetBits = /GR-V1\.0[23]/.test(header) ? 13 : 12;
                    const valueBits = packetBits - 1;
                    const valueMask = (1 << valueBits) - 1;
                    const countMask = (1 << (packetBits - 2)) - 1;
                    const reader = bitReader(bytes, 8);
                    const flat = [];
                    let guard = 0;

                    while (flat.length < 4096 && reader.has(packetBits) && guard < 50000) {
                        guard++;
                        const packet = reader.read(packetBits);
                        const repeat = packet >> (packetBits - 1);

                        if (repeat === 0) {
                            flat.push(packet & valueMask);
                            continue;
                        }

                        const doubleTile = (packet >> (packetBits - 2)) & 1;
                        const count = Math.max(1, packet & countMask);

                        if (!reader.has(packetBits)) {
                            break;
                        }

                        const tileA = reader.read(packetBits) & valueMask;

                        if (doubleTile) {
                            if (!reader.has(packetBits)) {
                                break;
                            }

                            const tileB = reader.read(packetBits) & valueMask;
                            for (let i = 0; i < count && flat.length < 4096; i++) {
                                flat.push(tileA, tileB);
                            }
                        } else {
                            for (let i = 0; i < count && flat.length < 4096; i++) {
                                flat.push(tileA);
                            }
                        }
                    }

                    while (flat.length < 4096) {
                        flat.push(0);
                    }

                    return {
                        header: header,
                        tiles: GraalTools.flatToTiles(flat.slice(0, 4096)),
                        warnings: ['Classic binary import is tile-only; links, NPCs, baddies, signs, and chests are not preserved.']
                    };
                }

                function parseTileList(text) {
                    const compactRows = String(text || '').replace(/\r/g, '').split('\n').map(function (line) {
                        return line.trim();
                    }).filter(Boolean);
                    const flat = [];

                    compactRows.forEach(function (line) {
                        if (/^[A-Za-z0-9+/]{128}$/.test(line)) {
                            for (let i = 0; i < 64; i++) {
                                flat.push(GraalTools.pairToTile(line.slice(i * 2, (i * 2) + 2)));
                            }
                            return;
                        }

                        line.split(/[\s,;|]+/).filter(Boolean).forEach(function (token) {
                            if (/^\d+$/.test(token)) {
                                flat.push(GraalTools.clamp(token, 0, 4095));
                            } else if (/^[A-Za-z0-9+/]{2}$/.test(token)) {
                                flat.push(GraalTools.pairToTile(token));
                            }
                        });
                    });

                    if (flat.length === 0) {
                        throw new Error('No tile data found.');
                    }

                    while (flat.length < 4096) {
                        flat.push(0);
                    }

                    return GraalTools.flatToTiles(flat.slice(0, 4096));
                }

                function sampleTiles() {
                    const rows = [];
                    for (let y = 0; y < 64; y++) {
                        const row = [];
                        for (let x = 0; x < 64; x++) {
                            if (x < 5 || y < 5 || x > 58 || y > 58) {
                                row.push('AQ');
                            } else if ((x + y) % 9 === 0) {
                                row.push('Ag');
                            } else {
                                row.push('AA');
                            }
                        }
                        rows.push(row.join(' '));
                    }
                    return rows.join('\n');
                }

                function renderOutput(mode, warnings) {
                    const parsed = GraalTools.parseNw(outputText.value);
                    lastTiles = parsed.tiles;
                    GraalTools.renderBoard(canvas, parsed.tiles, { tileSize: 10 });
                    modeMetric.textContent = mode;
                    tileMetric.textContent = '4096';
                    warnMetric.textContent = String((warnings || []).length + parsed.warnings.length);
                }

                function convert() {
                    const kind = document.getElementById('sourceKind').value;
                    const text = sourceText.value;
                    const warnings = [];
                    let mode = 'NW';
                    let tiles;
                    let extras = [];

                    try {
                        if ((kind === 'binary' || (kind === 'auto' && lastBuffer && /^(GR-V|Z3-)/.test(decodeHeader(lastBuffer)))) && lastBuffer) {
                            const decoded = decodeClassicBinary(lastBuffer);
                            tiles = decoded.tiles;
                            mode = decoded.header;
                            warnings.push.apply(warnings, decoded.warnings);
                        } else if (kind === 'nw' || (kind === 'auto' && /^GLEVNW01/m.test(text))) {
                            const parsed = GraalTools.parseNw(text);
                            tiles = parsed.tiles;
                            extras = parsed.extraLines;
                            warnings.push.apply(warnings, parsed.warnings);
                            mode = 'NW';
                        } else {
                            tiles = parseTileList(text);
                            mode = 'Tile list';
                        }

                        outputText.value = GraalTools.serializeNw(tiles, extras);
                        renderOutput(mode, warnings);
                        GraalTools.setStatus(status, warnings.join(' ') || 'Converted to NW.', warnings.length ? 'info' : 'success');
                    } catch (error) {
                        GraalTools.setStatus(status, error.message || 'Conversion failed.', 'error');
                    }
                }

                sourceFile.addEventListener('change', function () {
                    const file = sourceFile.files[0];
                    if (!file) {
                        return;
                    }

                    document.getElementById('outputName').value = GraalTools.normalizeFilename(file.name.replace(/\.[^.]+$/, ''), 'converted-level', '.nw');
                    GraalTools.readFile(file, 'buffer').then(function (buffer) {
                        lastBuffer = buffer;
                        const header = decodeHeader(buffer);
                        const text = new TextDecoder('utf-8').decode(buffer);
                        sourceText.value = /^(GR-V|Z3-)/.test(header) ? 'Classic binary file loaded: ' + file.name + '\nHeader: ' + header : text;
                        if (/^(GR-V|Z3-)/.test(header)) {
                            document.getElementById('sourceKind').value = 'binary';
                        }
                        convert();
                    }).catch(function () {
                        GraalTools.setStatus(status, 'Could not read that file.', 'error');
                    });
                });

                document.getElementById('convertButton').addEventListener('click', function () {
                    convert();
                });

                document.getElementById('downloadButton').addEventListener('click', function () {
                    const name = GraalTools.normalizeFilename(document.getElementById('outputName').value, 'converted-level', '.nw');
                    GraalTools.downloadText(name, outputText.value, 'text/plain;charset=utf-8');
                    GraalTools.setStatus(status, 'Download created.', 'success');
                });

                document.getElementById('copyButton').addEventListener('click', function () {
                    GraalTools.copyText(outputText.value).then(function () {
                        GraalTools.setStatus(status, 'Output copied.', 'success');
                    }).catch(function () {
                        GraalTools.setStatus(status, 'Copy failed. Select the output text and copy it manually.', 'error');
                    });
                });

                document.getElementById('sampleButton').addEventListener('click', function () {
                    document.getElementById('sourceKind').value = 'tiles';
                    sourceText.value = sampleTiles();
                    lastBuffer = null;
                    convert();
                });

                sourceText.addEventListener('input', function () {
                    lastBuffer = null;
                });

                sourceText.value = sampleTiles();
                document.getElementById('sourceKind').value = 'tiles';
                convert();
            }());
        </script>
<?php
renderPageEnd();
