<?php
declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'tools_common.php';

renderPageStart(
    'GS2 Beautify - Graalians Discord Server Tools',
    'Format GS2 scripts with indentation and brace style controls.'
);
?>
        <section class="page-hero compact">
            <div class="shell">
                <p class="eyebrow">Online tool</p>
                <h1>GS2 Beautify</h1>
                <p>Clean up Graal GS2 scripts with consistent indentation, brace handling, copy, and download.</p>
            </div>
        </section>

        <section class="shell tool-workspace">
            <div class="tool-panel">
                <h2>GS2 editor</h2>
                <div class="form-grid">
                    <div class="field">
                        <label for="indentSize">Indent</label>
                        <select id="indentSize">
                            <option value="2">2 spaces</option>
                            <option value="4">4 spaces</option>
                            <option value="tab">Tabs</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="braceStyle">Braces</label>
                        <select id="braceStyle">
                            <option value="collapse">Same line</option>
                            <option value="expand">New line</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="outputName">File</label>
                        <input id="outputName" value="script.gs2">
                    </div>
                    <div class="field">
                        <label for="lineSpacing">Blank lines</label>
                        <select id="lineSpacing">
                            <option value="keep">Keep</option>
                            <option value="trim">Trim extras</option>
                        </select>
                    </div>
                    <div class="wide-field">
                        <label for="code">GS2 code</label>
                        <div class="gs2-editor-shell">
                            <pre class="gs2-highlight" id="codeHighlight" aria-hidden="true"></pre>
                            <textarea class="code-editor gs2-code-input" id="code" spellcheck="false"></textarea>
                        </div>
                        <p class="small-help">Live colors use the imported Notepad++ GScript2 syntax profile.</p>
                    </div>
                </div>

                <div class="actions">
                    <button class="button" id="beautifyButton" type="button">Beautify</button>
                    <button class="button secondary" id="copyButton" type="button">Copy</button>
                    <button class="button secondary" id="downloadButton" type="button">Download</button>
                    <button class="button secondary" id="sampleButton" type="button">Load sample</button>
                </div>

                <div class="tool-status-line" id="status">Ready.</div>
            </div>

            <aside>
                <div class="preview-panel">
                    <h2>Script stats</h2>
                    <div class="metric-row">
                        <div class="metric">
                            <strong id="lineMetric">0</strong>
                            <span>Lines</span>
                        </div>
                        <div class="metric">
                            <strong id="charMetric">0</strong>
                            <span>Characters</span>
                        </div>
                        <div class="metric">
                            <strong id="braceMetric">0</strong>
                            <span>Brace depth</span>
                        </div>
                    </div>
                </div>

                <div class="source-panel">
                    <h2>How to use it</h2>
                    <p>Paste GS2 code, choose your indentation and brace style, then beautify before copying or downloading the cleaned script.</p>
                    <ul class="instruction-list">
                        <li>Strings and comments are kept together while the surrounding code is spaced and indented.</li>
                        <li>Use <strong>Trim extras</strong> when copied code has too many empty lines.</li>
                        <li>The stats panel helps spot scripts that still have unmatched braces or unexpected size changes.</li>
                    </ul>
                </div>
            </aside>
        </section>

        <script src="graal-tools.js"></script>
        <script>
            (function () {
                const code = document.getElementById('code');
                const codeHighlight = document.getElementById('codeHighlight');
                const status = document.getElementById('status');
                const lineMetric = document.getElementById('lineMetric');
                const charMetric = document.getElementById('charMetric');
                const braceMetric = document.getElementById('braceMetric');
                const highlightWords = {
                    keyword1: new Set('function return break if public with for while do case default switch else enum const in continue class'.split(' ')),
                    keyword2: new Set('true false null nil NULL new'.split(' ')),
                    keyword3: new Set('echo triggeraction length size sleep index pos type = == && || != ! ? @ @= < > <= =< >= =>'.split(' '))
                };
                const operators = ['==', '&&', '||', '!=', '@=', '<=', '=<', '>=', '=>', '=', '!', '?', '@', '<', '>', '(', ')', ',', '.', ':', ';', '[', ']', '{', '}'];

                function indentUnit() {
                    const value = document.getElementById('indentSize').value;
                    return value === 'tab' ? '\t' : ' '.repeat(Number.parseInt(value, 10));
                }

                function escapeHtml(value) {
                    return String(value || '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');
                }

                function span(className, value) {
                    return '<span class="' + className + '">' + escapeHtml(value) + '</span>';
                }

                function highlightGs2(source) {
                    let html = '';
                    let index = 0;
                    const text = String(source || '').replace(/\r/g, '');

                    while (index < text.length) {
                        const char = text[index];
                        const next = text[index + 1];

                        if (char === '/' && next === '/') {
                            let value = '//';
                            index += 2;
                            while (index < text.length && text[index] !== '\n') {
                                value += text[index++];
                            }
                            html += span('gs2-token-comment', value);
                            continue;
                        }

                        if (char === '/' && next === '*') {
                            let value = '/*';
                            index += 2;
                            while (index < text.length) {
                                value += text[index];
                                if (text[index] === '*' && text[index + 1] === '/') {
                                    value += '/';
                                    index += 2;
                                    break;
                                }
                                index++;
                            }
                            html += span('gs2-token-comment', value);
                            continue;
                        }

                        if (char === '"' || char === "'") {
                            let value = char;
                            const quote = char;
                            index++;
                            while (index < text.length) {
                                value += text[index];
                                if (text[index] === '\\') {
                                    index++;
                                    if (index < text.length) {
                                        value += text[index];
                                    }
                                } else if (text[index] === quote) {
                                    index++;
                                    break;
                                }
                                index++;
                            }
                            html += span('gs2-token-string', value);
                            continue;
                        }

                        if (/\d/.test(char)) {
                            let value = char;
                            index++;
                            while (index < text.length && /[0-9.]/.test(text[index])) {
                                value += text[index++];
                            }
                            html += span('gs2-token-number', value);
                            continue;
                        }

                        if (/\s/.test(char)) {
                            html += escapeHtml(char);
                            index++;
                            continue;
                        }

                        const matchedOperator = operators.find(function (operator) {
                            return text.slice(index, index + operator.length) === operator;
                        });
                        if (matchedOperator) {
                            const className = highlightWords.keyword3.has(matchedOperator) ? 'gs2-token-keyword3' : 'gs2-token-operator';
                            html += span(className, matchedOperator);
                            index += matchedOperator.length;
                            continue;
                        }

                        if (/[A-Za-z_]/.test(char)) {
                            let value = char;
                            index++;
                            while (index < text.length && /[A-Za-z0-9_]/.test(text[index])) {
                                value += text[index++];
                            }

                            if (highlightWords.keyword1.has(value)) {
                                html += span('gs2-token-keyword1', value);
                            } else if (highlightWords.keyword2.has(value)) {
                                html += span('gs2-token-keyword2', value);
                            } else if (highlightWords.keyword3.has(value)) {
                                html += span('gs2-token-keyword3', value);
                            } else {
                                html += escapeHtml(value);
                            }
                            continue;
                        }

                        html += escapeHtml(char);
                        index++;
                    }

                    return html || ' ';
                }

                function refreshHighlight() {
                    codeHighlight.innerHTML = highlightGs2(code.value) + '\n';
                    codeHighlight.scrollTop = code.scrollTop;
                    codeHighlight.scrollLeft = code.scrollLeft;
                }

                function tokenise(source) {
                    const tokens = [];
                    let i = 0;

                    while (i < source.length) {
                        const char = source[i];
                        const next = source[i + 1];

                        if (char === '"' || char === "'") {
                            let value = char;
                            const quote = char;
                            i++;

                            while (i < source.length) {
                                value += source[i];
                                if (source[i] === '\\') {
                                    i++;
                                    if (i < source.length) {
                                        value += source[i];
                                    }
                                } else if (source[i] === quote) {
                                    i++;
                                    break;
                                }
                                i++;
                            }

                            tokens.push({ type: 'string', value: value });
                            continue;
                        }

                        if (char === '/' && next === '/') {
                            let value = '//';
                            i += 2;
                            while (i < source.length && source[i] !== '\n') {
                                value += source[i++];
                            }
                            tokens.push({ type: 'comment', value: value.trimEnd() });
                            continue;
                        }

                        if (char === '/' && next === '*') {
                            let value = '/*';
                            i += 2;
                            while (i < source.length) {
                                value += source[i];
                                if (source[i] === '*' && source[i + 1] === '/') {
                                    value += '/';
                                    i += 2;
                                    break;
                                }
                                i++;
                            }
                            tokens.push({ type: 'comment', value: value.trimEnd() });
                            continue;
                        }

                        if ('{};,'.includes(char)) {
                            tokens.push({ type: char, value: char });
                            i++;
                            continue;
                        }

                        if (/\s/.test(char)) {
                            let value = '';
                            while (i < source.length && /\s/.test(source[i])) {
                                value += source[i++];
                            }
                            tokens.push({ type: 'space', value: value });
                            continue;
                        }

                        let value = '';
                        while (i < source.length && !/\s/.test(source[i]) && !'{};,'.includes(source[i])) {
                            if (source[i] === '"' || source[i] === "'" || (source[i] === '/' && (source[i + 1] === '/' || source[i + 1] === '*'))) {
                                break;
                            }
                            value += source[i++];
                        }
                        tokens.push({ type: 'text', value: value });
                    }

                    return tokens;
                }

                function beautifyGs2(source) {
                    const unit = indentUnit();
                    const braceStyle = document.getElementById('braceStyle').value;
                    const trimBlankLines = document.getElementById('lineSpacing').value === 'trim';
                    const lines = [];
                    let line = '';
                    let depth = 0;
                    let maxDepth = 0;

                    function currentIndent() {
                        return unit.repeat(Math.max(0, depth));
                    }

                    function pushLine(force) {
                        const trimmed = line.trimEnd();
                        if (trimmed || force) {
                            lines.push(trimmed);
                        } else if (!trimBlankLines && lines[lines.length - 1] !== '') {
                            lines.push('');
                        }
                        line = currentIndent();
                    }

                    function normalizeCodeFragment(value) {
                        return value.trim()
                            .replace(/\s*(==|!=|>=|<=|\+=|-=|\*=|\/=|%=|&&|\|\||=)\s*/g, ' $1 ')
                            .replace(/\s+/g, ' ')
                            .trim();
                    }

                    function addText(value, preserveSpacing) {
                        const fragment = preserveSpacing ? value.trim() : normalizeCodeFragment(value);

                        if (fragment === '') {
                            return;
                        }

                        const trimmedLine = line.trimEnd();

                        if (/(==|!=|>=|<=|\+=|-=|\*=|\/=|%=|&&|\|\||=)$/.test(trimmedLine)) {
                            line = trimmedLine + ' ';
                        } else if (line.trim() !== '' && !/[\s([{.!<>+\-*\/%,:]$/.test(line)) {
                            line += ' ';
                        }
                        line += fragment;
                    }

                    line = currentIndent();

                    tokenise(source.replace(/\r/g, '')).forEach(function (token) {
                        if (token.type === 'space') {
                            if (token.value.includes('\n')) {
                                if (line.trim() !== '') {
                                    pushLine(false);
                                } else if (!trimBlankLines && /\n\s*\n/.test(token.value) && lines[lines.length - 1] !== '') {
                                    lines.push('');
                                }
                            }
                            return;
                        }

                        if (token.type === '{') {
                            if (braceStyle === 'expand' && line.trim() !== '') {
                                pushLine(true);
                            } else if (line.trim() !== '' && !line.endsWith(' ')) {
                                line += ' ';
                            }
                            line += '{';
                            depth++;
                            maxDepth = Math.max(maxDepth, depth);
                            pushLine(true);
                            return;
                        }

                        if (token.type === '}') {
                            if (line.trim() !== '') {
                                pushLine(true);
                            }
                            depth = Math.max(0, depth - 1);
                            line = currentIndent() + '}';
                            pushLine(true);
                            return;
                        }

                        if (token.type === ';') {
                            line = line.trimEnd() + ';';
                            pushLine(true);
                            return;
                        }

                        if (token.type === ',') {
                            line = line.trimEnd() + ', ';
                            return;
                        }

                        if (token.type === 'comment') {
                            if (line.trim() !== '') {
                                line += ' ';
                            }
                            line += token.value;
                            pushLine(true);
                            return;
                        }

                        addText(token.value, token.type === 'string');
                    });

                    if (line.trim() !== '') {
                        pushLine(true);
                    }

                    let output = lines.join('\n').replace(/[ \t]+\n/g, '\n').trim();

                    if (output !== '') {
                        output += '\n';
                    }

                    output = output
                        .replace(/}\n\s*else/g, '} else')
                        .replace(/}\n\s*catch/g, '} catch')
                        .replace(/\(\s+/g, '(')
                        .replace(/\s+\)/g, ')')
                        .replace(/\[\s+/g, '[')
                        .replace(/\s+\]/g, ']');

                    return {
                        code: output,
                        maxDepth: maxDepth
                    };
                }

                function updateStats(depth) {
                    const value = code.value;
                    lineMetric.textContent = value.trim() === '' ? '0' : value.replace(/\n$/, '').split('\n').length;
                    charMetric.textContent = value.length;
                    braceMetric.textContent = depth || Math.max(0, (value.match(/{/g) || []).length - (value.match(/}/g) || []).length);
                }

                function sample() {
                    return [
                        'function onCreated(){',
                        'this.message="Welcome to the Graalians Discord Server";',
                        'setTimer(1);',
                        '}',
                        'function onPlayerChats(){if (player.chat=="join"){player.chat="discord.gg/AeDurPz";}}',
                        'function onTimeout(){',
                        '// community reminder',
                        'echo(this.message);',
                        'setTimer(30);',
                        '}'
                    ].join('\n');
                }

                document.getElementById('beautifyButton').addEventListener('click', function () {
                    const result = beautifyGs2(code.value);
                    code.value = result.code;
                    updateStats(result.maxDepth);
                    refreshHighlight();
                    GraalTools.setStatus(status, 'GS2 formatted.', 'success');
                });

                document.getElementById('copyButton').addEventListener('click', function () {
                    GraalTools.copyText(code.value).then(function () {
                        GraalTools.setStatus(status, 'Code copied.', 'success');
                    }).catch(function () {
                        GraalTools.setStatus(status, 'Copy failed. Select the code and copy it manually.', 'error');
                    });
                });

                document.getElementById('downloadButton').addEventListener('click', function () {
                    const name = GraalTools.normalizeFilename(document.getElementById('outputName').value, 'script', '.gs2');
                    GraalTools.downloadText(name, code.value, 'text/plain;charset=utf-8');
                    GraalTools.setStatus(status, 'Download created.', 'success');
                });

                document.getElementById('sampleButton').addEventListener('click', function () {
                    code.value = sample();
                    updateStats(0);
                    refreshHighlight();
                    GraalTools.setStatus(status, 'Sample loaded.', 'success');
                });

                code.addEventListener('input', function () {
                    updateStats(0);
                    refreshHighlight();
                });

                code.addEventListener('scroll', function () {
                    codeHighlight.scrollTop = code.scrollTop;
                    codeHighlight.scrollLeft = code.scrollLeft;
                });

                code.addEventListener('keydown', function (event) {
                    if (event.key !== 'Tab') {
                        return;
                    }

                    event.preventDefault();
                    const start = code.selectionStart;
                    const end = code.selectionEnd;
                    const insert = indentUnit();
                    code.value = code.value.slice(0, start) + insert + code.value.slice(end);
                    code.selectionStart = start + insert.length;
                    code.selectionEnd = start + insert.length;
                    updateStats(0);
                    refreshHighlight();
                });

                code.value = sample();
                updateStats(0);
                refreshHighlight();
            }());
        </script>
<?php
renderPageEnd();
