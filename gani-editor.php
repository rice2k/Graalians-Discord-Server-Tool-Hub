<?php
declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'tools_common.php';

renderPageStart(
    'GANI Editor - Graalians Discord Server Tools',
    'Open, edit, preview, and download Graal .gani animation files in the browser.'
);
?>
        <section class="page-hero compact">
            <div class="shell">
                <p class="eyebrow">Online tool</p>
                <h1>GANI Editor</h1>
                <p>Edit Graal animation files, adjust sprites and frames, preview movement, and export a fresh .gani file.</p>
            </div>
        </section>

        <section class="shell tool-workspace">
            <div class="tool-panel">
                <h2>Animation editor</h2>
                <div class="form-grid">
                    <div class="wide-field">
                        <label for="ganiFile">Open .gani file</label>
                        <input id="ganiFile" type="file" accept=".gani,text/plain">
                    </div>
                    <div class="wide-field">
                        <label for="imageFiles">Optional images</label>
                        <input id="imageFiles" type="file" multiple accept="image/png,image/gif,image/jpeg,image/webp">
                        <p class="small-help">Upload the images named by DEFAULT lines if you want a real sprite preview. Without images, the preview uses colored sprite boxes.</p>
                    </div>

                    <div class="field">
                        <label for="animationName">Animation name</label>
                        <input id="animationName" value="sample_idle">
                    </div>
                    <div class="field">
                        <label for="outputName">Output file</label>
                        <input id="outputName" value="sample_idle.gani">
                    </div>
                    <div class="field">
                        <label for="endMode">End mode</label>
                        <select id="endMode">
                            <option value="loop">Loop</option>
                            <option value="freeze">Freeze</option>
                            <option value="setback">Set back to</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="setbackName">Setback animation</label>
                        <input id="setbackName" value="">
                    </div>
                    <div class="field">
                        <label for="frameSelect">Frame</label>
                        <input id="frameSelect" type="range" min="0" max="0" value="0">
                    </div>
                    <div class="field">
                        <label for="directionSelect">Direction</label>
                        <select id="directionSelect">
                            <option value="0">Up / single</option>
                            <option value="1">Left</option>
                            <option value="2">Down</option>
                            <option value="3">Right</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="holdTime">Hold time</label>
                        <input id="holdTime" type="number" min="50" step="50" value="50">
                    </div>
                    <div class="field">
                        <label for="soundName">Sound</label>
                        <input id="soundName" placeholder="sound.wav">
                    </div>
                    <div class="wide-field">
                        <label class="inline-check">
                            <input id="singleDirection" type="checkbox">
                            Single direction animation
                        </label>
                    </div>
                    <div class="wide-field">
                        <label for="ganiText">GANI level text</label>
                        <textarea id="ganiText" spellcheck="false"></textarea>
                    </div>
                </div>

                <div class="actions">
                    <button class="button" id="parseButton" type="button">Parse text</button>
                    <button class="button secondary" id="downloadButton" type="button">Download .gani</button>
                    <button class="button secondary" id="copyButton" type="button">Copy text</button>
                    <button class="button secondary" id="sampleButton" type="button">Load sample</button>
                </div>

                <h3 class="tool-subtitle">Resources</h3>
                <div class="table-scroll">
                    <table class="tool-table" id="resourceTable"></table>
                </div>
                <div class="actions">
                    <button class="button secondary small" id="addResourceButton" type="button">Add resource</button>
                </div>

                <h3 class="tool-subtitle">Sprite definitions</h3>
                <div class="table-scroll">
                    <table class="tool-table" id="spriteTable"></table>
                </div>
                <div class="actions">
                    <button class="button secondary small" id="addSpriteButton" type="button">Add sprite</button>
                </div>

                <h3 class="tool-subtitle">Current frame parts</h3>
                <div class="table-scroll">
                    <table class="tool-table" id="partTable"></table>
                </div>
                <div class="actions">
                    <button class="button secondary small" id="addFrameButton" type="button">Add frame</button>
                    <button class="button secondary small" id="duplicateFrameButton" type="button">Duplicate frame</button>
                    <button class="button secondary small" id="deleteFrameButton" type="button">Delete frame</button>
                    <button class="button secondary small" id="addPartButton" type="button">Add part</button>
                </div>

                <div class="tool-status-line" id="status">Ready.</div>
            </div>

            <aside>
                <div class="preview-panel">
                    <h2>Preview</h2>
                    <div class="preview-stack">
                        <div>
                            <p class="small-help">Animated preview</p>
                            <div class="canvas-wrap">
                                <canvas class="gani-canvas" id="animationCanvas" width="420" height="260"></canvas>
                            </div>
                        </div>
                        <div>
                            <p class="small-help">Selected frame</p>
                            <div class="canvas-wrap">
                                <canvas class="gani-canvas" id="frameCanvas" width="420" height="260"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="metric-row">
                        <div class="metric">
                            <strong id="frameMetric">0</strong>
                            <span>Frames</span>
                        </div>
                        <div class="metric">
                            <strong id="spriteMetric">0</strong>
                            <span>Sprites</span>
                        </div>
                        <div class="metric">
                            <strong id="resourceMetric">0</strong>
                            <span>Resources</span>
                        </div>
                    </div>
                </div>

                <div class="source-panel">
                    <h2>How to use it</h2>
                    <ul class="instruction-list">
                        <li>Open a <strong>.gani</strong> file or paste GANI text and click <strong>Parse text</strong>.</li>
                        <li>Edit resources, sprite cuts, frame parts, hold times, direction mode, and loop behavior.</li>
                        <li>Upload matching images when you want to see the real sprite cuts in the preview.</li>
                        <li>Download the updated .gani when the animation looks right.</li>
                    </ul>
                    <p class="format-note">GANI files describe sprite rectangles, default image files, frame lines, waits, sounds, and whether the animation loops, freezes, or sets back to another animation.</p>
                </div>
            </aside>
        </section>

        <script src="graal-tools.js"></script>
        <script>
            (function () {
                const ganiFile = document.getElementById('ganiFile');
                const imageFiles = document.getElementById('imageFiles');
                const ganiText = document.getElementById('ganiText');
                const status = document.getElementById('status');
                const animationCanvas = document.getElementById('animationCanvas');
                const frameCanvas = document.getElementById('frameCanvas');
                const frameMetric = document.getElementById('frameMetric');
                const spriteMetric = document.getElementById('spriteMetric');
                const resourceMetric = document.getElementById('resourceMetric');
                const images = {};
                let currentFrame = 0;
                let currentDirection = 0;
                let animationStarted = performance.now();

                let model = emptyModel('sample_idle');

                function emptyModel(name) {
                    return {
                        name: name || 'animation',
                        singleDirection: false,
                        endMode: 'loop',
                        setbackName: '',
                        resources: [{ name: 'body', file: 'body.png' }],
                        sprites: [{ id: 100, resource: 'body', x: 0, y: 0, w: 32, h: 32, hint: 'body' }],
                        frames: [{
                            holdMs: 50,
                            sound: '',
                            directions: [
                                [{ sprite: 100, x: 0, y: 0 }],
                                [{ sprite: 100, x: 0, y: 0 }],
                                [{ sprite: 100, x: 0, y: 0 }],
                                [{ sprite: 100, x: 0, y: 0 }]
                            ]
                        }]
                    };
                }

                function sampleGani() {
                    return [
                        'GANI0001',
                        'SPRITE 100 BODY 0 0 32 32 idle frame',
                        'SPRITE 101 BODY 32 0 32 32 idle frame 2',
                        '',
                        'LOOP',
                        'CONTINUOUS',
                        'DEFAULTBODY body.png',
                        '',
                        'ANI',
                        '100 0 0',
                        '100 0 0',
                        '100 0 0',
                        '100 0 0',
                        '',
                        '101 0 0',
                        '101 0 0',
                        '101 0 0',
                        '101 0 0',
                        'WAIT 1',
                        '',
                        'ANIEND',
                        ''
                    ].join('\n');
                }

                function splitParams(line, delimiter) {
                    const text = String(line || '').trim();
                    if (text === '') {
                        return [];
                    }
                    return text.split(delimiter || /\s+/).map(function (part) {
                        return String(part).trim();
                    }).filter(Boolean);
                }

                function htmlText(value) {
                    return String(value ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');
                }

                function htmlAttr(value) {
                    return htmlText(value).replace(/"/g, '&quot;');
                }

                function ensureResource(next, name) {
                    const safeName = GraalTools.singleLine(name || 'body').toLowerCase() || 'body';
                    let found = next.resources.find(function (resource) {
                        return resource.name.toLowerCase() === safeName;
                    });
                    if (!found) {
                        found = { name: safeName, file: safeName + '.png' };
                        next.resources.push(found);
                    }
                    return found;
                }

                function findSprite(id) {
                    const spriteId = Number.parseInt(id, 10);
                    return model.sprites.find(function (sprite) {
                        return sprite.id === spriteId;
                    }) || null;
                }

                function parsePartLine(line) {
                    return String(line || '').split(',').map(function (chunk) {
                        const parts = splitParams(chunk);
                        if (parts.length < 3) {
                            return null;
                        }
                        return {
                            sprite: GraalTools.clamp(parts[0], 0, 999999),
                            x: Number.parseInt(parts[1], 10) || 0,
                            y: Number.parseInt(parts[2], 10) || 0
                        };
                    }).filter(Boolean);
                }

                function parseGani(text, filename) {
                    const next = emptyModel((filename || 'animation').replace(/\.gani$/i, ''));
                    next.resources = [];
                    next.sprites = [];
                    next.frames = [];

                    const lines = String(text || '').replace(/\r/g, '').split('\n');
                    let inAni = false;
                    let pending = [];

                    function flushFrame() {
                        if (pending.length === 0) {
                            return;
                        }

                        const directionLines = [];
                        let holdMs = 50;
                        let sound = '';

                        pending.forEach(function (line) {
                            const trimmed = line.trim();
                            const params = splitParams(trimmed);
                            if (trimmed === '') {
                                return;
                            }
                            if (/^WAIT$/i.test(params[0] || '')) {
                                holdMs = Math.max(50, (Number.parseInt(params[1], 10) + 1) * 50 || 50);
                                return;
                            }
                            if (/^PLAYSOUND$/i.test(params[0] || '')) {
                                sound = params[1] || '';
                                return;
                            }
                            directionLines.push(trimmed);
                        });

                        if (directionLines.length > 0) {
                            const parsedDirections = [0, 1, 2, 3].map(function (index) {
                                return parsePartLine(directionLines[next.singleDirection ? 0 : index] || directionLines[0] || '');
                            });
                            next.frames.push({
                                holdMs: holdMs,
                                sound: sound,
                                directions: parsedDirections
                            });
                        }
                        pending = [];
                    }

                    lines.forEach(function (rawLine) {
                        const line = rawLine.trim();
                        const params = splitParams(line);

                        if (!inAni) {
                            if (params.length === 0 || /^GANI/i.test(params[0])) {
                                return;
                            }
                            if (/^SPRITE$/i.test(params[0]) && params.length >= 7) {
                                const resource = ensureResource(next, params[2]);
                                next.sprites.push({
                                    id: GraalTools.clamp(params[1], 0, 999999),
                                    resource: resource.name,
                                    x: Number.parseInt(params[3], 10) || 0,
                                    y: Number.parseInt(params[4], 10) || 0,
                                    w: Math.max(1, Number.parseInt(params[5], 10) || 32),
                                    h: Math.max(1, Number.parseInt(params[6], 10) || 32),
                                    hint: params.slice(7).join(' ')
                                });
                                return;
                            }
                            if (/^SINGLEDIRECTION$/i.test(params[0] || '')) {
                                next.singleDirection = true;
                                return;
                            }
                            if (/^LOOP$/i.test(params[0] || '')) {
                                next.endMode = 'loop';
                                return;
                            }
                            if (/^SETBACKTO$/i.test(params[0] || '')) {
                                next.endMode = 'setback';
                                next.setbackName = params[1] || '';
                                return;
                            }
                            if (/^DEFAULT/i.test(params[0] || '')) {
                                const name = params[0].slice(7).toLowerCase();
                                const resource = ensureResource(next, name);
                                resource.file = params[1] || resource.file;
                                return;
                            }
                            if (/^ANI$/i.test(params[0] || '')) {
                                inAni = true;
                                return;
                            }
                            return;
                        }

                        if (/^ANIEND$/i.test(params[0] || '')) {
                            flushFrame();
                            inAni = false;
                            return;
                        }

                        if (line === '') {
                            flushFrame();
                            return;
                        }

                        pending.push(rawLine);
                    });
                    flushFrame();

                    if (next.resources.length === 0) {
                        next.resources.push({ name: 'body', file: 'body.png' });
                    }
                    if (next.sprites.length === 0) {
                        next.sprites.push({ id: 100, resource: next.resources[0].name, x: 0, y: 0, w: 32, h: 32, hint: 'sprite' });
                    }
                    if (next.frames.length === 0) {
                        next.frames.push(emptyModel(next.name).frames[0]);
                    }

                    next.frames.forEach(function (frame) {
                        frame.directions.forEach(function (parts) {
                            parts.forEach(function (part) {
                                if (!next.sprites.some(function (sprite) { return sprite.id === part.sprite; })) {
                                    next.sprites.push({ id: part.sprite, resource: next.resources[0].name, x: 0, y: 0, w: 32, h: 32, hint: 'auto-added' });
                                }
                            });
                        });
                    });

                    return next;
                }

                function frameToLine(parts) {
                    return parts.map(function (part) {
                        return part.sprite + ' ' + part.x + ' ' + part.y;
                    }).join(', ');
                }

                function makeGani() {
                    const rows = ['GANI0001'];
                    const sortedSprites = model.sprites.slice().sort(function (a, b) {
                        return a.id - b.id;
                    });

                    sortedSprites.forEach(function (sprite) {
                        rows.push([
                            'SPRITE',
                            sprite.id,
                            String(sprite.resource || 'body').toUpperCase(),
                            sprite.x,
                            sprite.y,
                            sprite.w,
                            sprite.h,
                            GraalTools.singleLine(sprite.hint || '')
                        ].join('\t').trim());
                    });

                    rows.push('');

                    if (model.singleDirection) {
                        rows.push('SINGLEDIRECTION');
                    }
                    if (model.endMode === 'loop') {
                        rows.push('LOOP');
                        rows.push('CONTINUOUS');
                    } else if (model.endMode === 'setback' && model.setbackName) {
                        rows.push('CONTINUOUS');
                        rows.push('SETBACKTO ' + GraalTools.singleLine(model.setbackName));
                    }

                    model.resources.forEach(function (resource) {
                        rows.push('DEFAULT' + String(resource.name || 'body').toUpperCase() + '\t' + GraalTools.singleLine(resource.file || ''));
                    });

                    rows.push('', 'ANI');
                    model.frames.forEach(function (frame) {
                        const limit = model.singleDirection ? 1 : 4;
                        for (let direction = 0; direction < limit; direction++) {
                            rows.push(frameToLine(frame.directions[direction] || []));
                        }
                        const wait = Math.max(0, Math.round((frame.holdMs || 50) / 50) - 1);
                        if (wait > 0) {
                            rows.push('WAIT ' + wait);
                        }
                        if (frame.sound) {
                            rows.push('PLAYSOUND ' + frame.sound + ' 0 0');
                        }
                        rows.push('');
                    });
                    rows.push('ANIEND', '');
                    return rows.join('\n');
                }

                function syncControlsFromModel() {
                    document.getElementById('animationName').value = model.name;
                    document.getElementById('endMode').value = model.endMode;
                    document.getElementById('setbackName').value = model.setbackName || '';
                    document.getElementById('singleDirection').checked = model.singleDirection;
                    const frameSelect = document.getElementById('frameSelect');
                    frameSelect.max = Math.max(0, model.frames.length - 1);
                    currentFrame = GraalTools.clamp(currentFrame, 0, model.frames.length - 1);
                    frameSelect.value = currentFrame;
                    if (model.singleDirection && currentDirection > 0) {
                        currentDirection = 0;
                    }
                    document.getElementById('directionSelect').value = String(currentDirection);
                    const frame = model.frames[currentFrame] || model.frames[0];
                    document.getElementById('holdTime').value = frame.holdMs || 50;
                    document.getElementById('soundName').value = frame.sound || '';
                }

                function renderResourceTable() {
                    const table = document.getElementById('resourceTable');
                    table.innerHTML = '<tr><th>Name</th><th>Image file</th><th></th></tr>';
                    model.resources.forEach(function (resource, index) {
                        const row = document.createElement('tr');
                        row.innerHTML = '<td><input data-kind="resource" data-index="' + index + '" data-field="name" value="' + htmlAttr(resource.name) + '"></td>'
                            + '<td><input data-kind="resource" data-index="' + index + '" data-field="file" value="' + htmlAttr(resource.file) + '"></td>'
                            + '<td><button class="icon-button" data-action="remove-resource" data-index="' + index + '" type="button">X</button></td>';
                        table.appendChild(row);
                    });
                }

                function resourceOptions(selected) {
                    return model.resources.map(function (resource) {
                        return '<option value="' + htmlAttr(resource.name) + '"' + (resource.name === selected ? ' selected' : '') + '>' + htmlText(resource.name) + '</option>';
                    }).join('');
                }

                function spriteOptions(selected) {
                    return model.sprites.map(function (sprite) {
                        return '<option value="' + htmlAttr(sprite.id) + '"' + (sprite.id === selected ? ' selected' : '') + '>' + htmlText(sprite.id + ' (' + sprite.resource + ')') + '</option>';
                    }).join('');
                }

                function renderSpriteTable() {
                    const table = document.getElementById('spriteTable');
                    table.innerHTML = '<tr><th>ID</th><th>Resource</th><th>X</th><th>Y</th><th>W</th><th>H</th><th>Hint</th><th></th></tr>';
                    model.sprites.forEach(function (sprite, index) {
                        const row = document.createElement('tr');
                        row.innerHTML = '<td><input type="number" data-kind="sprite" data-index="' + index + '" data-field="id" value="' + htmlAttr(sprite.id) + '"></td>'
                            + '<td><select data-kind="sprite" data-index="' + index + '" data-field="resource">' + resourceOptions(sprite.resource) + '</select></td>'
                            + '<td><input type="number" data-kind="sprite" data-index="' + index + '" data-field="x" value="' + htmlAttr(sprite.x) + '"></td>'
                            + '<td><input type="number" data-kind="sprite" data-index="' + index + '" data-field="y" value="' + htmlAttr(sprite.y) + '"></td>'
                            + '<td><input type="number" min="1" data-kind="sprite" data-index="' + index + '" data-field="w" value="' + htmlAttr(sprite.w) + '"></td>'
                            + '<td><input type="number" min="1" data-kind="sprite" data-index="' + index + '" data-field="h" value="' + htmlAttr(sprite.h) + '"></td>'
                            + '<td><input data-kind="sprite" data-index="' + index + '" data-field="hint" value="' + htmlAttr(sprite.hint || '') + '"></td>'
                            + '<td><button class="icon-button" data-action="remove-sprite" data-index="' + index + '" type="button">X</button></td>';
                        table.appendChild(row);
                    });
                }

                function renderPartTable() {
                    const table = document.getElementById('partTable');
                    const frame = model.frames[currentFrame];
                    const parts = frame.directions[currentDirection] || [];
                    table.innerHTML = '<tr><th>Sprite</th><th>X</th><th>Y</th><th></th></tr>';
                    parts.forEach(function (part, index) {
                        const row = document.createElement('tr');
                        row.innerHTML = '<td><select data-kind="part" data-index="' + index + '" data-field="sprite">' + spriteOptions(part.sprite) + '</select></td>'
                            + '<td><input type="number" data-kind="part" data-index="' + index + '" data-field="x" value="' + htmlAttr(part.x) + '"></td>'
                            + '<td><input type="number" data-kind="part" data-index="' + index + '" data-field="y" value="' + htmlAttr(part.y) + '"></td>'
                            + '<td><button class="icon-button" data-action="remove-part" data-index="' + index + '" type="button">X</button></td>';
                        table.appendChild(row);
                    });
                }

                function imageForResource(resourceName) {
                    const resource = model.resources.find(function (item) {
                        return item.name === resourceName;
                    });
                    if (!resource || !resource.file) {
                        return null;
                    }
                    const filename = resource.file.toLowerCase();
                    const base = filename.replace(/^.*[\\/]/, '');
                    return images[filename] || images[base] || null;
                }

                function colorForId(id) {
                    const hue = (Number(id) * 47) % 360;
                    return 'hsl(' + hue + ' 48% 45%)';
                }

                function drawFrame(canvas, frameIndex, direction) {
                    const ctx = canvas.getContext('2d');
                    const frame = model.frames[frameIndex] || model.frames[0];
                    const dir = model.singleDirection ? 0 : direction;
                    const parts = (frame && frame.directions[dir]) || [];
                    const scale = 1.6;
                    const originX = canvas.width / 2;
                    const originY = 118;

                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.fillStyle = 'rgba(255, 250, 240, .08)';
                    ctx.fillRect(0, originY, canvas.width, 1);
                    ctx.fillRect(originX, 0, 1, canvas.height);

                    if (parts.length === 0) {
                        ctx.fillStyle = '#f8eedc';
                        ctx.font = '700 15px Arial';
                        ctx.textAlign = 'center';
                        ctx.fillText('No frame parts yet', originX, originY);
                        return;
                    }

                    parts.forEach(function (part) {
                        const sprite = findSprite(part.sprite);
                        if (!sprite) {
                            return;
                        }
                        const width = Math.max(1, sprite.w) * scale;
                        const height = Math.max(1, sprite.h) * scale;
                        const x = originX + (Number(part.x) || 0) * scale;
                        const y = originY + (Number(part.y) || 0) * scale;
                        const image = imageForResource(sprite.resource);

                        if (image && image.complete && image.naturalWidth > 0) {
                            try {
                                ctx.drawImage(image, sprite.x, sprite.y, sprite.w, sprite.h, x, y, width, height);
                                return;
                            } catch (error) {}
                        }

                        ctx.fillStyle = colorForId(sprite.id);
                        ctx.fillRect(x, y, width, height);
                        ctx.strokeStyle = 'rgba(255, 250, 240, .7)';
                        ctx.strokeRect(x, y, width, height);
                        ctx.fillStyle = '#fffaf0';
                        ctx.font = '700 12px Arial';
                        ctx.textAlign = 'center';
                        ctx.fillText(String(sprite.id), x + width / 2, y + height / 2 + 4);
                    });
                }

                function renderMetrics() {
                    frameMetric.textContent = model.frames.length;
                    spriteMetric.textContent = model.sprites.length;
                    resourceMetric.textContent = model.resources.length;
                }

                function renderAll(message, type) {
                    syncControlsFromModel();
                    renderResourceTable();
                    renderSpriteTable();
                    renderPartTable();
                    renderMetrics();
                    ganiText.value = makeGani();
                    drawFrame(frameCanvas, currentFrame, currentDirection);
                    if (message) {
                        GraalTools.setStatus(status, message, type || 'success');
                    }
                }

                function updateModelFromControls() {
                    model.name = GraalTools.singleLine(document.getElementById('animationName').value) || 'animation';
                    model.endMode = document.getElementById('endMode').value;
                    model.setbackName = GraalTools.singleLine(document.getElementById('setbackName').value);
                    model.singleDirection = document.getElementById('singleDirection').checked;
                    const frame = model.frames[currentFrame] || model.frames[0];
                    frame.holdMs = Math.max(50, GraalTools.clamp(document.getElementById('holdTime').value, 50, 60000));
                    frame.sound = GraalTools.singleLine(document.getElementById('soundName').value);
                }

                function parseTextFromBox(filename) {
                    try {
                        model = parseGani(ganiText.value, filename || document.getElementById('animationName').value || 'animation');
                        currentFrame = 0;
                        currentDirection = 0;
                        animationStarted = performance.now();
                        document.getElementById('outputName').value = GraalTools.normalizeFilename(model.name, 'animation', '.gani');
                        renderAll('GANI parsed: ' + model.frames.length + ' frame' + (model.frames.length === 1 ? '' : 's') + ', ' + model.sprites.length + ' sprite definition' + (model.sprites.length === 1 ? '' : 's') + '.', 'success');
                    } catch (error) {
                        GraalTools.setStatus(status, error.message || 'Could not parse that GANI text.', 'error');
                    }
                }

                document.getElementById('parseButton').addEventListener('click', function () {
                    parseTextFromBox();
                });

                ganiFile.addEventListener('change', function () {
                    const file = ganiFile.files[0];
                    if (!file) {
                        return;
                    }
                    GraalTools.readFile(file).then(function (text) {
                        ganiText.value = text;
                        parseTextFromBox(file.name.replace(/\.gani$/i, ''));
                    }).catch(function () {
                        GraalTools.setStatus(status, 'Could not read that .gani file.', 'error');
                    });
                });

                imageFiles.addEventListener('change', function () {
                    Array.from(imageFiles.files || []).forEach(function (file) {
                        GraalTools.loadImage(file).then(function (image) {
                            images[file.name.toLowerCase()] = image;
                            images[file.name.replace(/^.*[\\/]/, '').toLowerCase()] = image;
                            renderAll('Loaded preview image: ' + file.name + '.', 'success');
                        }).catch(function () {
                            GraalTools.setStatus(status, 'Could not load image ' + file.name + '.', 'error');
                        });
                    });
                });

                ['animationName', 'setbackName', 'holdTime', 'soundName'].forEach(function (id) {
                    document.getElementById(id).addEventListener('change', function () {
                        updateModelFromControls();
                        renderAll();
                    });
                });

                ['endMode', 'singleDirection'].forEach(function (id) {
                    document.getElementById(id).addEventListener('change', function () {
                        updateModelFromControls();
                        renderAll();
                    });
                });

                document.getElementById('frameSelect').addEventListener('input', function () {
                    updateModelFromControls();
                    currentFrame = GraalTools.clamp(this.value, 0, model.frames.length - 1);
                    renderAll();
                });

                document.getElementById('directionSelect').addEventListener('change', function () {
                    currentDirection = GraalTools.clamp(this.value, 0, 3);
                    renderAll();
                });

                document.getElementById('resourceTable').addEventListener('change', function (event) {
                    const target = event.target;
                    if (target.dataset.kind !== 'resource') {
                        return;
                    }
                    const resource = model.resources[Number(target.dataset.index)];
                    if (!resource) {
                        return;
                    }
                    if (target.dataset.field === 'name') {
                        const oldName = resource.name;
                        resource.name = GraalTools.singleLine(target.value).toLowerCase() || oldName;
                        model.sprites.forEach(function (sprite) {
                            if (sprite.resource === oldName) {
                                sprite.resource = resource.name;
                            }
                        });
                    } else {
                        resource.file = GraalTools.singleLine(target.value);
                    }
                    renderAll();
                });

                document.getElementById('spriteTable').addEventListener('change', function (event) {
                    const target = event.target;
                    if (target.dataset.kind !== 'sprite') {
                        return;
                    }
                    const sprite = model.sprites[Number(target.dataset.index)];
                    if (!sprite) {
                        return;
                    }
                    const field = target.dataset.field;
                    if (field === 'resource' || field === 'hint') {
                        sprite[field] = GraalTools.singleLine(target.value);
                    } else {
                        sprite[field] = field === 'id'
                            ? GraalTools.clamp(target.value, 0, 999999)
                            : (field === 'w' || field === 'h' ? Math.max(1, Number.parseInt(target.value, 10) || 1) : Number.parseInt(target.value, 10) || 0);
                    }
                    renderAll();
                });

                document.getElementById('partTable').addEventListener('change', function (event) {
                    const target = event.target;
                    if (target.dataset.kind !== 'part') {
                        return;
                    }
                    const frame = model.frames[currentFrame];
                    const part = frame.directions[currentDirection][Number(target.dataset.index)];
                    if (!part) {
                        return;
                    }
                    part[target.dataset.field] = target.dataset.field === 'sprite'
                        ? GraalTools.clamp(target.value, 0, 999999)
                        : Number.parseInt(target.value, 10) || 0;
                    renderAll();
                });

                document.addEventListener('click', function (event) {
                    const action = event.target.dataset.action;
                    if (!action) {
                        return;
                    }
                    const index = Number(event.target.dataset.index);
                    if (action === 'remove-resource' && model.resources.length > 1) {
                        const removed = model.resources.splice(index, 1)[0];
                        model.sprites.forEach(function (sprite) {
                            if (sprite.resource === removed.name) {
                                sprite.resource = model.resources[0].name;
                            }
                        });
                    }
                    if (action === 'remove-sprite' && model.sprites.length > 1) {
                        const removed = model.sprites.splice(index, 1)[0];
                        const fallback = model.sprites[0].id;
                        model.frames.forEach(function (frame) {
                            frame.directions.forEach(function (parts) {
                                parts.forEach(function (part) {
                                    if (part.sprite === removed.id) {
                                        part.sprite = fallback;
                                    }
                                });
                            });
                        });
                    }
                    if (action === 'remove-part') {
                        model.frames[currentFrame].directions[currentDirection].splice(index, 1);
                    }
                    renderAll('Removed item.', 'success');
                });

                document.getElementById('addResourceButton').addEventListener('click', function () {
                    let index = model.resources.length + 1;
                    let name = 'resource' + index;
                    while (model.resources.some(function (resource) { return resource.name === name; })) {
                        index++;
                        name = 'resource' + index;
                    }
                    model.resources.push({ name: name, file: name + '.png' });
                    renderAll('Resource added.', 'success');
                });

                document.getElementById('addSpriteButton').addEventListener('click', function () {
                    const ids = model.sprites.map(function (sprite) { return sprite.id; });
                    let id = ids.length ? Math.max.apply(null, ids) + 1 : 100;
                    while (ids.includes(id)) {
                        id++;
                    }
                    model.sprites.push({ id: id, resource: model.resources[0].name, x: 0, y: 0, w: 32, h: 32, hint: 'sprite' });
                    renderAll('Sprite definition added.', 'success');
                });

                document.getElementById('addPartButton').addEventListener('click', function () {
                    model.frames[currentFrame].directions[currentDirection].push({ sprite: model.sprites[0].id, x: 0, y: 0 });
                    renderAll('Frame part added.', 'success');
                });

                document.getElementById('addFrameButton').addEventListener('click', function () {
                    model.frames.push({ holdMs: 50, sound: '', directions: [[], [], [], []] });
                    currentFrame = model.frames.length - 1;
                    renderAll('Frame added.', 'success');
                });

                document.getElementById('duplicateFrameButton').addEventListener('click', function () {
                    const frame = model.frames[currentFrame];
                    const copy = JSON.parse(JSON.stringify(frame));
                    model.frames.splice(currentFrame + 1, 0, copy);
                    currentFrame++;
                    renderAll('Frame duplicated.', 'success');
                });

                document.getElementById('deleteFrameButton').addEventListener('click', function () {
                    if (model.frames.length <= 1) {
                        GraalTools.setStatus(status, 'A GANI needs at least one frame.', 'error');
                        return;
                    }
                    model.frames.splice(currentFrame, 1);
                    currentFrame = GraalTools.clamp(currentFrame, 0, model.frames.length - 1);
                    renderAll('Frame deleted.', 'success');
                });

                document.getElementById('downloadButton').addEventListener('click', function () {
                    updateModelFromControls();
                    const name = GraalTools.normalizeFilename(document.getElementById('outputName').value || model.name, 'animation', '.gani');
                    GraalTools.downloadText(name, makeGani(), 'text/plain;charset=utf-8');
                    GraalTools.setStatus(status, 'GANI download created.', 'success');
                });

                document.getElementById('copyButton').addEventListener('click', function () {
                    updateModelFromControls();
                    const text = makeGani();
                    ganiText.value = text;
                    GraalTools.copyText(text).then(function () {
                        GraalTools.setStatus(status, 'GANI text copied.', 'success');
                    }).catch(function () {
                        GraalTools.setStatus(status, 'Copy failed. Select the GANI text and copy it manually.', 'error');
                    });
                });

                document.getElementById('sampleButton').addEventListener('click', function () {
                    ganiText.value = sampleGani();
                    parseTextFromBox('sample_idle');
                });

                function animationFrameIndex() {
                    const total = model.frames.reduce(function (sum, frame) {
                        return sum + Math.max(50, frame.holdMs || 50);
                    }, 0);
                    if (total <= 0) {
                        return 0;
                    }
                    let elapsed = (performance.now() - animationStarted) % total;
                    for (let i = 0; i < model.frames.length; i++) {
                        elapsed -= Math.max(50, model.frames[i].holdMs || 50);
                        if (elapsed <= 0) {
                            return i;
                        }
                    }
                    return model.frames.length - 1;
                }

                function tick() {
                    drawFrame(animationCanvas, animationFrameIndex(), currentDirection);
                    requestAnimationFrame(tick);
                }

                ganiText.value = sampleGani();
                parseTextFromBox('sample_idle');
                tick();
            }());
        </script>
<?php
renderPageEnd();
