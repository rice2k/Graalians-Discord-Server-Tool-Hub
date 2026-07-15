<?php
declare(strict_types=1);

const TOOL_DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'generator_data';
const TOOL_STATS_FILE = TOOL_DATA_DIR . DIRECTORY_SEPARATOR . 'stats.json';
const TOOL_RATE_FILE = TOOL_DATA_DIR . DIRECTORY_SEPARATOR . 'rate_limits.json';
const TOOL_EVENT_LIMIT = 60;
const TOOL_EVENT_WINDOW = 600;
const TOOL_EVENT_COOLDOWN = 1;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

ensureToolDataDir();

$input = $_POST;

if (empty($input)) {
    parse_str((string) file_get_contents('php://input'), $input);
}

$event = isset($input['event']) ? (string) $input['event'] : '';
$bytes = isset($input['bytes']) ? max(0, (int) $input['bytes']) : 0;

if ($event !== 'download') {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

if (!checkToolEventLimit()) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'rate_limited' => true]);
    exit;
}

updateToolJson(TOOL_STATS_FILE, defaultToolStats(), static function (array $stats) use ($bytes): array {
    $stats = normalizeToolStats($stats);
    $stats['downloads_served']++;
    $stats['downloaded_bytes'] += $bytes;
    $stats['last_download_at'] = gmdate(DATE_ATOM);
    return $stats;
});

echo json_encode(['ok' => true]);

function ensureToolDataDir(): void
{
    if (!is_dir(TOOL_DATA_DIR)) {
        mkdir(TOOL_DATA_DIR, 0775, true);
    }
}

function checkToolEventLimit(): bool
{
    $now = time();
    $clientKey = hash('sha256', ((string) ($_SERVER['REMOTE_ADDR'] ?? 'local')) . '|graalians-tool-event');
    $allowed = false;

    updateToolJson(TOOL_RATE_FILE, [], static function (array $data) use ($clientKey, $now, &$allowed): array {
        foreach ($data as $key => $buckets) {
            if (!is_array($buckets)) {
                unset($data[$key]);
                continue;
            }

            foreach ($buckets as $bucket => $timestamps) {
                if (!is_array($timestamps)) {
                    unset($data[$key][$bucket]);
                    continue;
                }

                $data[$key][$bucket] = array_values(array_filter($timestamps, static function ($timestamp) use ($now): bool {
                    return is_int($timestamp) && $timestamp > ($now - TOOL_EVENT_WINDOW);
                }));
            }

            if (empty($data[$key])) {
                unset($data[$key]);
            }
        }

        if (!isset($data[$clientKey]) || !is_array($data[$clientKey])) {
            $data[$clientKey] = [];
        }

        $timestamps = isset($data[$clientKey]['tool_download']) && is_array($data[$clientKey]['tool_download'])
            ? $data[$clientKey]['tool_download']
            : [];

        $last = empty($timestamps) ? null : max($timestamps);

        if ($last !== null && ($now - $last) < TOOL_EVENT_COOLDOWN) {
            $allowed = false;
        } elseif (count($timestamps) >= TOOL_EVENT_LIMIT) {
            $allowed = false;
        } else {
            $timestamps[] = $now;
            $allowed = true;
        }

        $data[$clientKey]['tool_download'] = $timestamps;
        return $data;
    });

    return $allowed;
}

function defaultToolStats(): array
{
    return [
        'visits_counted' => 0,
        'generated_gmaps' => readToolLegacyCounter(),
        'generated_levels' => 0,
        'downloads_served' => 0,
        'downloaded_bytes' => 0,
        'last_generated_at' => null,
        'last_download_at' => null,
    ];
}

function normalizeToolStats(array $stats): array
{
    $legacyCounter = readToolLegacyCounter();

    return [
        'visits_counted' => max(0, (int) ($stats['visits_counted'] ?? 0)),
        'generated_gmaps' => max($legacyCounter, (int) ($stats['generated_gmaps'] ?? $legacyCounter)),
        'generated_levels' => max(0, (int) ($stats['generated_levels'] ?? 0)),
        'downloads_served' => max(0, (int) ($stats['downloads_served'] ?? 0)),
        'downloaded_bytes' => max(0, (int) ($stats['downloaded_bytes'] ?? 0)),
        'last_generated_at' => $stats['last_generated_at'] ?? null,
        'last_download_at' => $stats['last_download_at'] ?? null,
    ];
}

function readToolLegacyCounter(): int
{
    $path = __DIR__ . DIRECTORY_SEPARATOR . 'counter.txt';

    if (!is_file($path)) {
        return 0;
    }

    $counter = trim((string) file_get_contents($path));
    return ctype_digit($counter) ? (int) $counter : 0;
}

function updateToolJson(string $path, array $default, callable $callback): array
{
    $handle = fopen($path, 'c+');

    if ($handle === false) {
        return $callback($default);
    }

    flock($handle, LOCK_EX);
    rewind($handle);
    $contents = stream_get_contents($handle);
    $data = json_decode($contents !== false ? $contents : '', true);

    if (!is_array($data)) {
        $data = $default;
    }

    $data = $callback($data);
    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, json_encode($data, JSON_PRETTY_PRINT));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    return $data;
}
