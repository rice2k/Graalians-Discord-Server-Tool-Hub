(function () {
    'use strict';

    const B64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
    const TILE_COUNT = 64;
    const COMMUNITY_NOTE = [
        'Brought to you by the Graalians Discord Server.',
        'Discord Server: https://discord.gg/AeDurPz',
        'Reddit: https://reddit.com/r/graal',
        'YouTube: https://www.youtube.com/@GraalDiscord',
        'Graalians Discord Wordpress: https://graaldisocrd.wordpress.com/',
        'Twitch: https://www.twitch.tv/rice2k',
        'Instagram: https://www.instagram.com/Graal_Discord/'
    ].join('\n');

    function clamp(value, min, max) {
        const number = Number.parseInt(value, 10);
        if (Number.isNaN(number)) {
            return min;
        }
        return Math.min(max, Math.max(min, number));
    }

    function tileToPair(index) {
        const safeIndex = clamp(index, 0, 4095);
        return B64[Math.floor(safeIndex / 64)] + B64[safeIndex % 64];
    }

    function pairToTile(pair) {
        if (typeof pair !== 'string' || pair.length < 2) {
            return 0;
        }

        const high = B64.indexOf(pair[0]);
        const low = B64.indexOf(pair[1]);

        if (high < 0 || low < 0) {
            return 0;
        }

        return (high * 64) + low;
    }

    function normalizePair(value, fallback) {
        const backup = fallback || 'AA';
        const raw = String(value || '').trim();

        if (/^\d+$/.test(raw)) {
            return tileToPair(raw);
        }

        const compact = raw.replace(/\s+/g, '');

        if (compact.length >= 2 && B64.includes(compact[0]) && B64.includes(compact[1])) {
            return compact.slice(0, 2);
        }

        return backup;
    }

    function normalizeFilename(value, fallback, extension) {
        const defaultName = fallback || 'download.txt';
        const requiredExtension = extension || '';
        let name = String(value || defaultName).trim()
            .replace(/[\\/:*?"<>|]+/g, '-')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^\.+/, '')
            .replace(/[.-]+$/, '');

        if (name === '') {
            name = defaultName.replace(/[\\/:*?"<>|]+/g, '-');
        }

        if (requiredExtension && !name.toLowerCase().endsWith(requiredExtension.toLowerCase())) {
            name += requiredExtension;
        }

        return name;
    }

    function singleLine(value) {
        return String(value || '').replace(/[\r\n]+/g, ' ').replace(/\s+/g, ' ').trim();
    }

    function createTiles(fillIndex) {
        const index = clamp(fillIndex || 0, 0, 4095);
        return Array.from({ length: TILE_COUNT }, function () {
            return Array(TILE_COUNT).fill(index);
        });
    }

    function cloneTiles(tiles) {
        return tiles.map(function (row) {
            return row.slice();
        });
    }

    function flatToTiles(flatTiles) {
        const tiles = createTiles(0);
        for (let y = 0; y < TILE_COUNT; y++) {
            for (let x = 0; x < TILE_COUNT; x++) {
                tiles[y][x] = clamp(flatTiles[(y * TILE_COUNT) + x] || 0, 0, 4095);
            }
        }
        return tiles;
    }

    function parseNw(text) {
        const tiles = createTiles(0);
        const extraLines = [];
        const warnings = [];
        let boardLines = 0;
        let layerLines = 0;
        let shortBoardRows = 0;

        String(text || '').replace(/\r/g, '').split('\n').forEach(function (line) {
            const trimmed = line.trim();

            if (trimmed === '' || trimmed === 'GLEVNW01') {
                return;
            }

            const match = line.match(/^BOARD\s+(-?\d+)\s+(-?\d+)\s+(\d+)\s+(-?\d+)\s+([A-Za-z0-9+/]+)/i);

            if (!match) {
                extraLines.push(line);
                return;
            }

            const xOffset = Number.parseInt(match[1], 10);
            const yOffset = Number.parseInt(match[2], 10);
            const width = Number.parseInt(match[3], 10);
            const layer = Number.parseInt(match[4], 10);
            const data = match[5];

            if (data.length < width * 2) {
                shortBoardRows++;
            }

            if (layer !== 0) {
                layerLines++;
                extraLines.push(line);
                return;
            }

            boardLines++;

            for (let i = 0; i < width; i++) {
                const x = xOffset + i;
                const y = yOffset;
                const pair = data.slice(i * 2, (i * 2) + 2);

                if (x >= 0 && x < TILE_COUNT && y >= 0 && y < TILE_COUNT && pair.length === 2) {
                    tiles[y][x] = pairToTile(pair);
                }
            }
        });

        if (boardLines === 0) {
            warnings.push('No layer 0 BOARD rows were found. A blank 64 x 64 board was created.');
        }

        if (layerLines > 0) {
            warnings.push(layerLines + ' non-zero layer BOARD row' + (layerLines === 1 ? '' : 's') + ' preserved after layer 0.');
        }

        if (shortBoardRows > 0) {
            warnings.push(shortBoardRows + ' BOARD row' + (shortBoardRows === 1 ? ' is' : 's are') + ' shorter than declared and were padded visually.');
        }

        return {
            tiles: tiles,
            extraLines: extraLines,
            boardLines: boardLines,
            layerLines: layerLines,
            warnings: warnings
        };
    }

    function serializeNw(tiles, extraLines) {
        const rows = ['GLEVNW01'];

        for (let y = 0; y < TILE_COUNT; y++) {
            let data = '';

            for (let x = 0; x < TILE_COUNT; x++) {
                data += tileToPair(tiles[y][x]);
            }

            rows.push('BOARD 0 ' + y + ' 64 0 ' + data);
        }

        (extraLines || []).forEach(function (line) {
            if (String(line).trim() !== '') {
                rows.push(line);
            }
        });

        return rows.join('\n') + '\n';
    }

    function applyFill(tiles, options) {
        const xStart = clamp(options.x, 0, 63);
        const yStart = clamp(options.y, 0, 63);
        const width = clamp(options.width, 1, 64 - xStart);
        const height = clamp(options.height, 1, 64 - yStart);
        const primary = pairToTile(normalizePair(options.tile, 'AA'));
        const secondary = pairToTile(normalizePair(options.secondaryTile, tileToPair(primary)));
        const mode = options.mode || 'blank';
        const pattern = options.pattern || 'solid';
        let changed = 0;

        for (let y = yStart; y < yStart + height; y++) {
            for (let x = xStart; x < xStart + width; x++) {
                if (mode === 'blank' && tiles[y][x] !== 0) {
                    continue;
                }

                let next = primary;
                const edge = y === yStart || y === (yStart + height - 1) || x === xStart || x === (xStart + width - 1);

                if (pattern === 'checker') {
                    next = ((x + y) % 2 === 0) ? primary : secondary;
                } else if (pattern === 'border') {
                    next = edge ? secondary : primary;
                } else if (pattern === 'path') {
                    next = (Math.abs(x - xStart - Math.floor(width / 2)) <= 1 || Math.abs(y - yStart - Math.floor(height / 2)) <= 1) ? primary : secondary;
                }

                if (tiles[y][x] !== next) {
                    tiles[y][x] = next;
                    changed++;
                }
            }
        }

        return changed;
    }

    function tileColor(index) {
        const known = {
            0: '#668d3a',
            1: '#719846',
            2: '#557c35',
            8: '#a67b46',
            16: '#88806f',
            17: '#746c5d',
            32: '#336f90',
            33: '#3f86a8',
            48: '#b08d4f',
            64: '#584d3d',
            96: '#293d2c',
            128: '#8c3d35',
            256: '#c7a048',
            512: '#425447'
        };

        if (known[index]) {
            return known[index];
        }

        const hue = (index * 47) % 360;
        const saturation = 34 + (index % 28);
        const light = 32 + (index % 24);
        return 'hsl(' + hue + ' ' + saturation + '% ' + light + '%)';
    }

    function renderBoard(canvas, tiles, options) {
        const opts = options || {};
        const tileSize = clamp(opts.tileSize || 10, 2, 24);
        const ctx = canvas.getContext('2d');
        const tileset = opts.tileset || null;

        if (!ctx) {
            return;
        }
        canvas.width = TILE_COUNT * tileSize;
        canvas.height = TILE_COUNT * tileSize;
        ctx.imageSmoothingEnabled = false;
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        for (let y = 0; y < TILE_COUNT; y++) {
            for (let x = 0; x < TILE_COUNT; x++) {
                const index = tiles[y][x];

                if (tileset) {
                    const atlasTileX = Math.floor(index / 512) * 16 + (index % 16);
                    const atlasTileY = Math.floor(index / 16) % 32;
                    const sx = atlasTileX * 16;
                    const sy = atlasTileY * 16;

                    if (sx + 16 <= tileset.width && sy + 16 <= tileset.height) {
                        ctx.drawImage(tileset, sx, sy, 16, 16, x * tileSize, y * tileSize, tileSize, tileSize);
                        continue;
                    }
                }

                ctx.fillStyle = tileColor(index);
                ctx.fillRect(x * tileSize, y * tileSize, tileSize, tileSize);
            }
        }

        if (opts.grid !== false && tileSize >= 8) {
            ctx.strokeStyle = 'rgba(20, 18, 13, .18)';
            ctx.lineWidth = 1;

            for (let i = 0; i <= TILE_COUNT; i++) {
                const pos = i * tileSize;
                ctx.beginPath();
                ctx.moveTo(pos, 0);
                ctx.lineTo(pos, canvas.height);
                ctx.stroke();
                ctx.beginPath();
                ctx.moveTo(0, pos);
                ctx.lineTo(canvas.width, pos);
                ctx.stroke();
            }
        }
    }

    function readFile(file, mode) {
        return new Promise(function (resolve, reject) {
            const reader = new FileReader();
            reader.onload = function () {
                resolve(reader.result);
            };
            reader.onerror = function () {
                reject(reader.error || new Error('Unable to read file.'));
            };

            if (mode === 'buffer') {
                reader.readAsArrayBuffer(file);
            } else if (mode === 'data-url') {
                reader.readAsDataURL(file);
            } else {
                reader.readAsText(file);
            }
        });
    }

    function loadImage(file) {
        return readFile(file, 'data-url').then(function (url) {
            return new Promise(function (resolve, reject) {
                const image = new Image();
                image.onload = function () {
                    resolve(image);
                };
                image.onerror = function () {
                    reject(new Error('Unable to load image.'));
                };
                image.src = url;
            });
        });
    }

    function recordToolDownload(bytes) {
        const data = new URLSearchParams();
        data.set('event', 'download');
        data.set('bytes', String(Math.max(0, bytes || 0)));

        if (navigator.sendBeacon) {
            navigator.sendBeacon('tool_event.php', data);
            return;
        }

        fetch('tool_event.php', {
            method: 'POST',
            body: data,
            keepalive: true
        }).catch(function () {});
    }

    function downloadBlob(filename, blob) {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(function () {
            URL.revokeObjectURL(url);
        }, 250);
        recordToolDownload(blob.size || 0);
    }

    function downloadText(filename, text, mimeType) {
        downloadBlob(filename, new Blob([text], { type: mimeType || 'text/plain;charset=utf-8' }));
    }

    function downloadCanvasPng(canvas, filename) {
        return new Promise(function (resolve, reject) {
            if (!canvas.toBlob) {
                reject(new Error('PNG export is not supported in this browser.'));
                return;
            }

            canvas.toBlob(function (blob) {
                if (blob) {
                    downloadBlob(filename, blob);
                    resolve(blob);
                    return;
                }

                reject(new Error('PNG export failed.'));
            }, 'image/png');
        });
    }

    function copyText(text) {
        if (navigator.clipboard) {
            return navigator.clipboard.writeText(text).catch(function () {
                return legacyCopyText(text);
            });
        }

        return legacyCopyText(text);
    }

    function legacyCopyText(text) {
        const box = document.createElement('textarea');
        box.value = text;
        document.body.appendChild(box);
        box.select();
        document.execCommand('copy');
        box.remove();
        return Promise.resolve();
    }

    function setStatus(element, message, type) {
        if (!element) {
            return;
        }

        element.textContent = message;
        element.dataset.type = type || 'info';
    }

    window.GraalTools = {
        B64: B64,
        TILE_COUNT: TILE_COUNT,
        COMMUNITY_NOTE: COMMUNITY_NOTE,
        clamp: clamp,
        tileToPair: tileToPair,
        pairToTile: pairToTile,
        normalizePair: normalizePair,
        normalizeFilename: normalizeFilename,
        singleLine: singleLine,
        createTiles: createTiles,
        cloneTiles: cloneTiles,
        flatToTiles: flatToTiles,
        parseNw: parseNw,
        serializeNw: serializeNw,
        applyFill: applyFill,
        renderBoard: renderBoard,
        readFile: readFile,
        loadImage: loadImage,
        downloadBlob: downloadBlob,
        downloadText: downloadText,
        downloadCanvasPng: downloadCanvasPng,
        copyText: copyText,
        setStatus: setStatus
    };
}());
