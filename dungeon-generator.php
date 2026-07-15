<?php
declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'tools_common.php';

renderPageStart(
    'Dungeon Generator - Graalians Discord Server Tools',
    'Generate seeded Graal .nw dungeon starter levels with rooms, corridors, preview, and download.'
);
?>
        <section class="page-hero compact">
            <div class="shell">
                <p class="eyebrow">Online tool</p>
                <h1>Dungeon Generator</h1>
                <p>Create a 64 x 64 starter dungeon level with connected rooms, corridors, and downloadable .nw level text.</p>
            </div>
        </section>

        <section class="shell tool-workspace">
            <div class="tool-panel">
                <h2>Dungeon setup</h2>
                <div class="form-grid">
                    <div class="field">
                        <label for="seed">Seed</label>
                        <input id="seed" value="graalians">
                    </div>
                    <div class="field">
                        <label for="rooms">Rooms</label>
                        <input id="rooms" type="number" min="3" max="18" value="9">
                    </div>
                    <div class="field">
                        <label for="minRoom">Min room</label>
                        <input id="minRoom" type="number" min="4" max="14" value="5">
                    </div>
                    <div class="field">
                        <label for="maxRoom">Max room</label>
                        <input id="maxRoom" type="number" min="5" max="20" value="12">
                    </div>
                    <div class="field">
                        <label for="wallTile">Wall tile</label>
                        <input id="wallTile" value="AQ" maxlength="6">
                    </div>
                    <div class="field">
                        <label for="floorTile">Floor tile</label>
                        <input id="floorTile" value="AA" maxlength="6">
                    </div>
                    <div class="field">
                        <label for="pathTile">Path tile</label>
                        <input id="pathTile" value="Ag" maxlength="6">
                    </div>
                    <div class="field">
                        <label for="edgeTile">Edge tile</label>
                        <input id="edgeTile" value="AR" maxlength="6">
                    </div>
                    <div class="wide-field">
                        <label for="outputName">File name</label>
                        <input id="outputName" value="graalians_dungeon.nw">
                    </div>
                    <div class="wide-field">
                        <label for="levelText">NW level text</label>
                        <textarea id="levelText" spellcheck="false"></textarea>
                    </div>
                </div>

                <div class="actions">
                    <button class="button" id="generateButton" type="button">Generate dungeon</button>
                    <button class="button secondary" id="downloadButton" type="button">Download .nw</button>
                    <button class="button secondary" id="copyButton" type="button">Copy level text</button>
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
                            <strong id="roomMetric">0</strong>
                            <span>Rooms placed</span>
                        </div>
                        <div class="metric">
                            <strong id="floorMetric">0</strong>
                            <span>Floor tiles</span>
                        </div>
                        <div class="metric">
                            <strong id="seedMetric">Seed</strong>
                            <span>Current seed</span>
                        </div>
                    </div>
                </div>

                <div class="source-panel">
                    <h2>How it builds the level</h2>
                    <p>The generator carves rooms and corridors into one normal <strong>64 x 64</strong> Graal level, then exports it as <strong>GLEVNW01</strong> level text with layer-0 <strong>BOARD</strong> rows.</p>
                    <ul class="instruction-list">
                        <li>Use the same seed to recreate the same dungeon layout.</li>
                        <li>Change tile codes to match the grass, stone, wall, or path tiles you want to paint with.</li>
                        <li>Download the .nw file, then continue decorating it in your usual Graal level editor.</li>
                    </ul>
                </div>
            </aside>
        </section>

        <script src="graal-tools.js"></script>
        <script>
            (function () {
                const canvas = document.getElementById('previewCanvas');
                const levelText = document.getElementById('levelText');
                const status = document.getElementById('status');
                const roomMetric = document.getElementById('roomMetric');
                const floorMetric = document.getElementById('floorMetric');
                const seedMetric = document.getElementById('seedMetric');
                let currentTiles = GraalTools.createTiles(GraalTools.pairToTile('AQ'));

                function hashSeed(seed) {
                    let hash = 2166136261;
                    const text = String(seed || 'graalians');
                    for (let i = 0; i < text.length; i++) {
                        hash ^= text.charCodeAt(i);
                        hash = Math.imul(hash, 16777619);
                    }
                    return hash >>> 0;
                }

                function rng(seed) {
                    let state = hashSeed(seed) || 1;
                    return function () {
                        state = Math.imul(1664525, state) + 1013904223;
                        return ((state >>> 0) / 4294967296);
                    };
                }

                function randInt(random, min, max) {
                    return Math.floor(random() * (max - min + 1)) + min;
                }

                function carveRect(tiles, room, tile) {
                    for (let y = room.y; y < room.y + room.h; y++) {
                        for (let x = room.x; x < room.x + room.w; x++) {
                            tiles[y][x] = tile;
                        }
                    }
                }

                function carveCorridor(tiles, a, b, pathTile) {
                    let x = a.cx;
                    let y = a.cy;
                    const xStep = b.cx >= x ? 1 : -1;
                    const yStep = b.cy >= y ? 1 : -1;

                    while (x !== b.cx) {
                        for (let oy = -1; oy <= 1; oy++) {
                            if (y + oy > 0 && y + oy < 63) {
                                tiles[y + oy][x] = pathTile;
                            }
                        }
                        x += xStep;
                    }

                    while (y !== b.cy) {
                        for (let ox = -1; ox <= 1; ox++) {
                            if (x + ox > 0 && x + ox < 63) {
                                tiles[y][x + ox] = pathTile;
                            }
                        }
                        y += yStep;
                    }
                }

                function addEdges(tiles, floorTile, pathTile, edgeTile) {
                    for (let y = 1; y < 63; y++) {
                        for (let x = 1; x < 63; x++) {
                            if (tiles[y][x] === floorTile || tiles[y][x] === pathTile) {
                                continue;
                            }

                            const nearFloor = [floorTile, pathTile].includes(tiles[y - 1][x])
                                || [floorTile, pathTile].includes(tiles[y + 1][x])
                                || [floorTile, pathTile].includes(tiles[y][x - 1])
                                || [floorTile, pathTile].includes(tiles[y][x + 1]);
                            if (nearFloor) {
                                tiles[y][x] = edgeTile;
                            }
                        }
                    }
                }

                function generate() {
                    const seed = GraalTools.singleLine(document.getElementById('seed').value) || 'graalians';
                    document.getElementById('seed').value = seed;
                    const random = rng(seed);
                    const wallField = document.getElementById('wallTile');
                    const floorField = document.getElementById('floorTile');
                    const pathField = document.getElementById('pathTile');
                    const edgeField = document.getElementById('edgeTile');
                    wallField.value = GraalTools.normalizePair(wallField.value, 'AQ');
                    floorField.value = GraalTools.normalizePair(floorField.value, 'AA');
                    pathField.value = GraalTools.normalizePair(pathField.value, 'Ag');
                    edgeField.value = GraalTools.normalizePair(edgeField.value, 'AR');
                    const wallTile = GraalTools.pairToTile(wallField.value);
                    const floorTile = GraalTools.pairToTile(floorField.value);
                    const pathTile = GraalTools.pairToTile(pathField.value);
                    const edgeTile = GraalTools.pairToTile(edgeField.value);
                    const targetRooms = GraalTools.clamp(document.getElementById('rooms').value, 3, 18);
                    const minRoom = GraalTools.clamp(document.getElementById('minRoom').value, 4, 14);
                    const maxRoom = Math.max(minRoom, GraalTools.clamp(document.getElementById('maxRoom').value, 5, 20));
                    const rooms = [];
                    const tiles = GraalTools.createTiles(wallTile);

                    for (let attempts = 0; attempts < 120 && rooms.length < targetRooms; attempts++) {
                        const w = randInt(random, minRoom, maxRoom);
                        const h = randInt(random, minRoom, maxRoom);
                        const x = randInt(random, 2, 61 - w);
                        const y = randInt(random, 2, 61 - h);
                        const room = {
                            x: x,
                            y: y,
                            w: w,
                            h: h,
                            cx: x + Math.floor(w / 2),
                            cy: y + Math.floor(h / 2)
                        };

                        const overlaps = rooms.some(function (placed) {
                            return x < placed.x + placed.w + 2 && x + w + 2 > placed.x && y < placed.y + placed.h + 2 && y + h + 2 > placed.y;
                        });

                        if (!overlaps) {
                            rooms.push(room);
                            carveRect(tiles, room, floorTile);
                        }
                    }

                    for (let i = 1; i < rooms.length; i++) {
                        carveCorridor(tiles, rooms[i - 1], rooms[i], pathTile);
                    }

                    addEdges(tiles, floorTile, pathTile, edgeTile);

                    const extras = [
                        'SIGN 5 5 Graalians Discord Server dungeon seed: ' + seed,
                        'NPC 8 8',
                        'if (playerenters) {',
                        '  echo("Generated by the Graalians Discord Server Dungeon Generator.");',
                        '}',
                        'NPCEND'
                    ];

                    currentTiles = tiles;
                    levelText.value = GraalTools.serializeNw(tiles, extras);
                    GraalTools.renderBoard(canvas, tiles, { tileSize: 10 });
                    roomMetric.textContent = rooms.length;
                    floorMetric.textContent = tiles.flat().filter(function (tile) {
                        return tile === floorTile || tile === pathTile;
                    }).length;
                    seedMetric.textContent = seed.slice(0, 10);
                    const roomNote = rooms.length < targetRooms ? ' Space was tight, so fewer rooms fit than requested.' : '';
                    GraalTools.setStatus(status, 'Dungeon generated with ' + rooms.length + ' rooms.' + roomNote, rooms.length < targetRooms ? 'info' : 'success');
                }

                document.getElementById('generateButton').addEventListener('click', generate);
                document.getElementById('downloadButton').addEventListener('click', function () {
                    const name = GraalTools.normalizeFilename(document.getElementById('outputName').value, 'graalians-dungeon', '.nw');
                    GraalTools.downloadText(name, levelText.value, 'text/plain;charset=utf-8');
                    GraalTools.setStatus(status, 'Download created.', 'success');
                });
                document.getElementById('copyButton').addEventListener('click', function () {
                    GraalTools.copyText(levelText.value).then(function () {
                        GraalTools.setStatus(status, 'Level text copied.', 'success');
                    }).catch(function () {
                        GraalTools.setStatus(status, 'Copy failed. Select the level text and copy it manually.', 'error');
                    });
                });

                generate();
            }());
        </script>
<?php
renderPageEnd();
