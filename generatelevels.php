<?php
declare(strict_types=1);

session_start();

const MAP_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'maps';
const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'generator_data';
const STATS_FILE = DATA_DIR . DIRECTORY_SEPARATOR . 'stats.json';
const RATE_FILE = DATA_DIR . DIRECTORY_SEPARATOR . 'rate_limits.json';
const LEGACY_COUNTER_FILE = __DIR__ . DIRECTORY_SEPARATOR . 'counter.txt';
const DISCORD_URL = 'https://discord.gg/AeDurPz';
const REDDIT_URL = 'https://reddit.com/r/graal';
const YOUTUBE_URL = 'https://www.youtube.com/@GraalDiscord';
const WORDPRESS_URL = 'https://graaldisocrd.wordpress.com/';
const TWITCH_URL = 'https://www.twitch.tv/rice2k';
const INSTAGRAM_URL = 'https://www.instagram.com/Graal_Discord/';

const DEFAULT_DIMENSION = 10;
const MIN_DIMENSION = 1;
const MAX_DIMENSION = 40;
const MAX_PREFIX_LENGTH = 32;

const GENERATE_LIMIT = 6;
const GENERATE_WINDOW_SECONDS = 600;
const GENERATE_COOLDOWN_SECONDS = 12;
const DOWNLOAD_LIMIT = 20;
const DOWNLOAD_WINDOW_SECONDS = 600;
const DOWNLOAD_COOLDOWN_SECONDS = 3;
const VISIT_COOLDOWN_SECONDS = 1800;

ensureAppStorage();
cleanupOldGeneratedMaps();
serveDownloadIfRequested();

if (empty($_SESSION['level_generator_form_token'])) {
    $_SESSION['level_generator_form_token'] = bin2hex(random_bytes(16));
}

$message = null;
$downloadLink = '';
$downloadName = '';
$generatedLevelCount = 0;
$formSize = isset($_POST['size']) ? trim((string) $_POST['size']) : (string) DEFAULT_DIMENSION;
$formPrefix = isset($_POST['prefix']) ? trim((string) $_POST['prefix']) : '';

registerVisitIfNeeded();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['form_token']) ? (string) $_POST['form_token'] : '';

    if (!hash_equals($_SESSION['level_generator_form_token'], $token)) {
        $message = [
            'type' => 'error',
            'title' => 'Refresh needed',
            'text' => 'Please refresh this page and try again. The form session expired.',
        ];
    } else {
        $prefix = normalizePrefix($formPrefix);
        $size = validateDimension($formSize);

        if ($prefix === null) {
            $message = [
                'type' => 'error',
                'title' => 'Check the package name',
                'text' => 'Use 1 to ' . MAX_PREFIX_LENGTH . ' characters: letters, numbers, dashes, and underscores only.',
            ];
        } elseif ($size === null) {
            $message = [
                'type' => 'error',
                'title' => 'Check the map size',
                'text' => 'Choose a square map size from ' . MIN_DIMENSION . ' to ' . MAX_DIMENSION . '.',
            ];
        } elseif (!class_exists('ZipArchive')) {
            $message = [
                'type' => 'error',
                'title' => 'Zip support is unavailable',
                'text' => 'PHP ZipArchive is required before this generator can build downloadable packages.',
            ];
        } else {
            $rate = checkRateLimit('generate', GENERATE_LIMIT, GENERATE_WINDOW_SECONDS, GENERATE_COOLDOWN_SECONDS);

            if (!$rate['allowed']) {
                $message = [
                    'type' => 'error',
                    'title' => 'Slow down for a moment',
                    'text' => 'To keep the generator available, please wait ' . formatWait((int) $rate['wait']) . ' before creating another package.',
                ];
            } else {
                try {
                    $package = createMapPackage($prefix, $size, $size);
                    $generatedLevelCount = $package['levels'];
                    $downloadName = $package['name'];
                    $downloadLink = 'generatelevels.php?gmap=' . rawurlencode($downloadName);

                    $stats = updateStats(static function (array $stats) use ($generatedLevelCount): array {
                        $stats['generated_gmaps']++;
                        $stats['generated_levels'] += $generatedLevelCount;
                        $stats['last_generated_at'] = gmdate(DATE_ATOM);
                        return $stats;
                    });
                    syncLegacyCounter((int) $stats['generated_gmaps']);

                    $message = [
                        'type' => 'success',
                        'title' => 'Package ready',
                        'text' => 'Your zip contains ' . number_format($generatedLevelCount) . ' blank level files, the GMAP, a README, and Graalians community shortcuts.',
                    ];
                } catch (Throwable $error) {
                    $message = [
                        'type' => 'error',
                        'title' => 'Build failed',
                        'text' => 'The generator could not create the zip package. Please try again with a different package name.',
                    ];
                }
            }
        }
    }
}

$stats = getStats();
$previewSize = validateDimension($formSize) ?? DEFAULT_DIMENSION;

function ensureAppStorage(): void
{
    foreach ([MAP_DIR, DATA_DIR] as $directory) {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    $htaccess = DATA_DIR . DIRECTORY_SEPARATOR . '.htaccess';
    if (!is_file($htaccess)) {
        file_put_contents($htaccess, "Require all denied\nDeny from all\n", LOCK_EX);
    }
}

function cleanupOldGeneratedMaps(): void
{
    $files = glob(MAP_DIR . DIRECTORY_SEPARATOR . '*.zip');
    if ($files === false) {
        return;
    }

    $cutoff = time() - (24 * 60 * 60);

    foreach ($files as $file) {
        $name = basename($file);

        if (preg_match('/-\d{8}-\d{6}-[a-f0-9]{8}\.zip$/', $name) && filemtime($file) < $cutoff) {
            @unlink($file);
        }
    }
}

function serveDownloadIfRequested(): void
{
    if (!isset($_GET['gmap'])) {
        return;
    }

    $requestedFile = (string) $_GET['gmap'];

    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9_.-]{0,96}\.zip$/', $requestedFile) || basename($requestedFile) !== $requestedFile) {
        http_response_code(400);
        exit('Bad request');
    }

    $downloadPath = MAP_DIR . DIRECTORY_SEPARATOR . $requestedFile;

    if (!is_file($downloadPath)) {
        http_response_code(404);
        exit('This download is no longer available. Please generate a fresh GMAP package.');
    }

    $rate = checkRateLimit('download', DOWNLOAD_LIMIT, DOWNLOAD_WINDOW_SECONDS, DOWNLOAD_COOLDOWN_SECONDS);

    if (!$rate['allowed']) {
        http_response_code(429);
        exit('Please wait ' . formatWait((int) $rate['wait']) . ' before downloading another package.');
    }

    $fileSize = filesize($downloadPath);

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename($downloadPath) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . $fileSize);

    ignore_user_abort(true);
    flush();
    readfile($downloadPath);

    updateStats(static function (array $stats) use ($fileSize): array {
        $stats['downloads_served']++;
        $stats['downloaded_bytes'] += (int) $fileSize;
        $stats['last_download_at'] = gmdate(DATE_ATOM);
        return $stats;
    });

    @unlink($downloadPath);
    exit();
}

function registerVisitIfNeeded(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' || isset($_GET['gmap'])) {
        return;
    }

    $lastVisit = isset($_SESSION['level_generator_visit_at']) ? (int) $_SESSION['level_generator_visit_at'] : 0;
    $now = time();

    if (($now - $lastVisit) < VISIT_COOLDOWN_SECONDS) {
        return;
    }

    $_SESSION['level_generator_visit_at'] = $now;

    updateStats(static function (array $stats): array {
        $stats['visits_counted']++;
        return $stats;
    });
}

function normalizePrefix(string $prefix): ?string
{
    $prefix = trim($prefix);

    if ($prefix === '') {
        $prefix = 'graalians_map';
    }

    if (strlen($prefix) > MAX_PREFIX_LENGTH || !preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*$/', $prefix)) {
        return null;
    }

    return $prefix;
}

function validateDimension(string $value): ?int
{
    if (!preg_match('/^\d+$/', trim($value))) {
        return null;
    }

    $size = (int) $value;

    if ($size < MIN_DIMENSION || $size > MAX_DIMENSION) {
        return null;
    }

    return $size;
}

function createMapPackage(string $prefix, int $width, int $height): array
{
    $levelsCount = $width * $height;
    $token = bin2hex(random_bytes(4));
    $zipName = $prefix . '-' . date('Ymd-His') . '-' . $token . '.zip';
    $zipPath = MAP_DIR . DIRECTORY_SEPARATOR . $zipName;

    $zip = new ZipArchive();

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Unable to create zip file.');
    }

    $baseLevel = buildBaseLevel();

    for ($i = 0; $i < $levelsCount; $i++) {
        $levelName = levelFileName($prefix, $i);
        $levelData = $baseLevel . buildLevelLinks($prefix, $i, $width, $height);

        if (!$zip->addFromString($levelName, $levelData)) {
            $zip->close();
            @unlink($zipPath);
            throw new RuntimeException('Unable to add level file.');
        }
    }

    $zip->addFromString($prefix . '.gmap', buildGmapFile($prefix, $width, $height));
    $zip->addFromString('README - Graalians Discord Server Level Generator.txt', buildReadme($prefix, $width, $height, $levelsCount));
    $zip->addFromString('Community Links - Graalians Discord Server.txt', buildCommunityLinksText());
    $zip->addFromString('Join the Graalians Discord Server.url', buildShortcut(DISCORD_URL));
    $zip->addFromString('Visit r-graal on Reddit.url', buildShortcut(REDDIT_URL));
    $zip->addFromString('Watch Graal Discord on YouTube.url', buildShortcut(YOUTUBE_URL));
    $zip->addFromString('Read Graalians Discord Wordpress.url', buildShortcut(WORDPRESS_URL));
    $zip->addFromString('Watch rice2k on Twitch.url', buildShortcut(TWITCH_URL));
    $zip->addFromString('Follow Graal Discord on Instagram.url', buildShortcut(INSTAGRAM_URL));

    if (!$zip->close()) {
        @unlink($zipPath);
        throw new RuntimeException('Unable to finish zip file.');
    }

    return [
        'name' => $zipName,
        'path' => $zipPath,
        'levels' => $levelsCount,
    ];
}

function buildBaseLevel(): string
{
    $rows = ['GLEVNW01'];
    $emptyTiles = str_repeat('AA', 64);

    for ($row = 0; $row < 64; $row++) {
        $rows[] = 'BOARD 0 ' . $row . ' 64 0 ' . $emptyTiles;
    }

    return implode("\n", $rows) . "\n";
}

function buildGmapFile(string $prefix, int $width, int $height): string
{
    $gmap = "GRMAP001\nWIDTH " . $width . "\nHEIGHT " . $height . "\nLEVELNAMES\n";

    for ($y = 0; $y < $height; $y++) {
        $row = [];

        for ($x = 0; $x < $width; $x++) {
            $index = ($y * $width) + $x;
            $row[] = '"' . levelFileName($prefix, $index) . '"';
        }

        $gmap .= implode(',', $row) . "\n";
    }

    return $gmap . "LEVELNAMESEND\n";
}

function buildLevelLinks(string $prefix, int $index, int $width, int $height): string
{
    $x = $index % $width;
    $y = intdiv($index, $width);
    $links = '';

    if ($y < ($height - 1)) {
        $links .= 'LINK ' . levelFileName($prefix, $index + $width) . " 0 63 64 1 playerx 0\n";
    }

    if ($x < ($width - 1)) {
        $links .= 'LINK ' . levelFileName($prefix, $index + 1) . " 63 0 1 64 0 playery\n";
    }

    if ($y > 0) {
        $links .= 'LINK ' . levelFileName($prefix, $index - $width) . " 0 0 64 1 playerx 61\n";
    }

    if ($x > 0) {
        $links .= 'LINK ' . levelFileName($prefix, $index - 1) . " 0 0 1 64 61 playery\n";
    }

    return $links;
}

function levelFileName(string $prefix, int $index): string
{
    return $prefix . '_' . $index . '.nw';
}

function buildReadme(string $prefix, int $width, int $height, int $levelsCount): string
{
    return "Graalians Discord Server Level Generator\r\n"
        . "=========================================\r\n\r\n"
        . "Brought to you by the Graalians Discord Server.\r\n"
        . "Join our communities and follow the project links in the included shortcut files.\r\n\r\n"
        . "Package name: " . $prefix . "\r\n"
        . "GMAP size: " . $width . " x " . $height . "\r\n"
        . "Level files: " . $levelsCount . "\r\n"
        . "Base level size: 64 x 64 tiles per .nw level\r\n\r\n"
        . "How the map size works\r\n"
        . "----------------------\r\n"
        . "The map size is the number of .nw level files across and down in the GMAP grid. "
        . "A size of " . $width . " creates a " . $width . " x " . $height . " square GMAP, which means " . $levelsCount . " level files. "
        . "Each level file is still a normal 64 x 64 tile level; the GMAP simply stitches those individual levels together so the world scrolls as one larger map.\r\n\r\n"
        . "This generator keeps the GMAP square on purpose. Matching width and height makes the file order predictable, keeps the edge links easy to calculate, and prevents uneven rows where links or level names can become confusing. "
        . "The generated .gmap lists every level in row order, while each .nw file receives links to its neighbors on the top, bottom, left, and right edges when those neighbors exist.\r\n\r\n"
        . "Generated files are blank starter levels with edge links already added.\r\n\r\n"
        . buildCommunityLinksText();
}

function buildShortcut(string $url): string
{
    return "[InternetShortcut]\r\nURL=" . $url . "\r\n";
}

function buildCommunityLinksText(): string
{
    return "Graalians Community Links\r\n"
        . "=========================\r\n\r\n"
        . "This package was created with the Graalians Discord Server Level Generator.\r\n"
        . "Join the community, share your work, and keep up with related Graal projects here:\r\n\r\n"
        . "Discord Server: " . DISCORD_URL . "\r\n"
        . "Reddit: " . REDDIT_URL . "\r\n"
        . "YouTube: " . YOUTUBE_URL . "\r\n"
        . "Graalians Discord Wordpress: " . WORDPRESS_URL . "\r\n"
        . "Twitch: " . TWITCH_URL . "\r\n"
        . "Instagram: " . INSTAGRAM_URL . "\r\n";
}

function checkRateLimit(string $bucket, int $limit, int $windowSeconds, int $cooldownSeconds): array
{
    $now = time();
    $clientKey = getClientKey();
    $allowed = false;
    $wait = 0;

    updateJsonFile(RATE_FILE, [], static function (array $data) use ($bucket, $limit, $windowSeconds, $cooldownSeconds, $now, $clientKey, &$allowed, &$wait): array {
        foreach ($data as $key => $buckets) {
            if (!is_array($buckets)) {
                unset($data[$key]);
                continue;
            }

            $hasRecentActivity = false;

            foreach ($buckets as $bucketName => $timestamps) {
                if (!is_array($timestamps)) {
                    unset($data[$key][$bucketName]);
                    continue;
                }

                $data[$key][$bucketName] = array_values(array_filter($timestamps, static function ($timestamp) use ($now, $windowSeconds): bool {
                    return is_int($timestamp) && $timestamp > ($now - $windowSeconds);
                }));

                if (!empty($data[$key][$bucketName])) {
                    $hasRecentActivity = true;
                }
            }

            if (!$hasRecentActivity) {
                unset($data[$key]);
            }
        }

        if (!isset($data[$clientKey]) || !is_array($data[$clientKey])) {
            $data[$clientKey] = [];
        }

        $timestamps = isset($data[$clientKey][$bucket]) && is_array($data[$clientKey][$bucket])
            ? $data[$clientKey][$bucket]
            : [];

        $timestamps = array_values(array_filter($timestamps, static function ($timestamp) use ($now, $windowSeconds): bool {
            return is_int($timestamp) && $timestamp > ($now - $windowSeconds);
        }));

        $lastTimestamp = empty($timestamps) ? null : max($timestamps);

        if ($lastTimestamp !== null && ($now - $lastTimestamp) < $cooldownSeconds) {
            $allowed = false;
            $wait = $cooldownSeconds - ($now - $lastTimestamp);
        } elseif (count($timestamps) >= $limit) {
            $allowed = false;
            $wait = $windowSeconds - ($now - min($timestamps));
        } else {
            $allowed = true;
            $timestamps[] = $now;
        }

        $data[$clientKey][$bucket] = $timestamps;
        return $data;
    });

    return [
        'allowed' => $allowed,
        'wait' => max(1, $wait),
    ];
}

function getClientKey(): string
{
    $ipAddress = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'local';
    return hash('sha256', $ipAddress . '|graalians-discord-level-generator');
}

function getStats(): array
{
    return updateJsonFile(STATS_FILE, defaultStats(), static function (array $stats): array {
        return normalizeStats($stats);
    });
}

function updateStats(callable $callback): array
{
    return updateJsonFile(STATS_FILE, defaultStats(), static function (array $stats) use ($callback): array {
        $stats = normalizeStats($stats);
        return normalizeStats($callback($stats));
    });
}

function defaultStats(): array
{
    return [
        'visits_counted' => 0,
        'generated_gmaps' => readLegacyCounter(),
        'generated_levels' => inferExistingLevelFiles(),
        'downloads_served' => 0,
        'downloaded_bytes' => 0,
        'last_generated_at' => null,
        'last_download_at' => null,
    ];
}

function normalizeStats(array $stats): array
{
    $legacyCounter = readLegacyCounter();

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

function updateJsonFile(string $path, array $default, callable $callback): array
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

function readLegacyCounter(): int
{
    if (!is_file(LEGACY_COUNTER_FILE)) {
        return 0;
    }

    $counter = trim((string) file_get_contents(LEGACY_COUNTER_FILE));

    return ctype_digit($counter) ? (int) $counter : 0;
}

function syncLegacyCounter(int $generatedCount): void
{
    file_put_contents(LEGACY_COUNTER_FILE, (string) $generatedCount, LOCK_EX);
}

function inferExistingLevelFiles(): int
{
    $files = glob(MAP_DIR . DIRECTORY_SEPARATOR . '*.zip');

    if ($files === false || !class_exists('ZipArchive')) {
        return 0;
    }

    $count = 0;

    foreach ($files as $file) {
        $zip = new ZipArchive();

        if ($zip->open($file) !== true) {
            continue;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = (string) $zip->getNameIndex($i);

            if (preg_match('/\.nw$/i', $entryName)) {
                $count++;
            }
        }

        $zip->close();
    }

    return $count;
}

function formatWait(int $seconds): string
{
    if ($seconds < 60) {
        return $seconds . ' second' . ($seconds === 1 ? '' : 's');
    }

    $minutes = (int) ceil($seconds / 60);
    return $minutes . ' minute' . ($minutes === 1 ? '' : 's');
}

function escapeHtml($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Graalians Discord Server Level Generator</title>
    <link rel="stylesheet" href="graalians-tools.css">
    <style>
        .gmap-hero {
            background:
                linear-gradient(90deg, rgba(14, 17, 14, .94), rgba(14, 17, 14, .52) 52%, rgba(14, 17, 14, .86)),
                url("images/graalians-level-generator-hero.png") center / cover no-repeat;
        }

        .gmap-workspace {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(300px, .72fr);
            gap: 18px;
            align-items: start;
            padding: 26px 0 54px;
        }

        .gmap-status-panel {
            min-width: 0;
            padding: 20px;
            text-align: left;
            background: linear-gradient(180deg, rgba(255, 250, 240, .96), rgba(239, 219, 176, .97));
            border: 1px solid rgba(70, 54, 28, .78);
            box-shadow: var(--shadow), inset 0 0 0 4px rgba(255, 250, 240, .42);
        }

        .panel-title {
            margin: 0 0 14px;
            font-size: 1.22rem;
            line-height: 1.15;
        }

        .generator-form {
            display: grid;
            gap: 18px;
        }

        .gmap-field-grid {
            display: grid;
            grid-template-columns: 1.2fr .8fr;
            gap: 12px;
        }

        .gmap-field-grid > div,
        .gmap-workspace > * {
            min-width: 0;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
        }

        input:focus {
            border-color: var(--ruby);
            box-shadow: 0 0 0 3px rgba(165, 49, 45, .18), inset 0 2px 0 rgba(29, 26, 20, .08);
        }

        .hint-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            min-height: 38px;
            padding: 9px 12px;
            font: 700 .86rem Arial, sans-serif;
            color: #43361f;
            background: rgba(221, 193, 140, .48);
            border-left: 4px solid var(--gold);
        }

        .button:hover,
        .button:focus {
            background: linear-gradient(180deg, #cf5a4d, #963027);
        }

        .message {
            display: grid;
            gap: 6px;
            margin-bottom: 18px;
            padding: 13px 15px;
            border-left: 5px solid var(--gold);
            background: rgba(255, 250, 240, .74);
        }

        .message strong {
            font: 800 .94rem Arial, sans-serif;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .message.success {
            border-left-color: var(--moss-light);
        }

        .message.error {
            border-left-color: var(--ruby);
        }

        .download-box {
            margin-top: 18px;
            padding: 14px;
            border: 2px solid rgba(82, 106, 47, .45);
            background: rgba(82, 106, 47, .14);
        }

        .download-box p {
            margin: 0 0 12px;
            font-weight: 700;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .stat-card {
            min-height: 104px;
            padding: 14px;
            color: #f8eedc;
            background: linear-gradient(180deg, rgba(45, 48, 42, .96), rgba(30, 32, 28, .96));
            border: 1px solid rgba(217, 166, 58, .35);
            box-shadow: inset 0 0 0 2px rgba(255, 250, 240, .05);
        }

        .stat-card strong {
            display: block;
            margin-bottom: 7px;
            color: #ffd875;
            font: 800 clamp(1.45rem, 3vw, 2.05rem) Arial, sans-serif;
        }

        .stat-card span {
            display: block;
            font: 700 .78rem Arial, sans-serif;
            line-height: 1.35;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .included {
            margin: 18px 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 10px;
        }

        .included li {
            padding-left: 22px;
            position: relative;
            line-height: 1.35;
        }

        .included li::before {
            content: "";
            position: absolute;
            left: 0;
            top: .35em;
            width: 10px;
            height: 10px;
            background: var(--gold);
            border: 2px solid #6c4b16;
            transform: rotate(45deg);
        }

        .fine-print {
            margin: 18px 0 0;
            font: 700 .82rem Arial, sans-serif;
            color: #5b4b2e;
        }

        .map-help {
            display: grid;
            gap: 12px;
            margin-top: 22px;
            padding-top: 20px;
            border-top: 1px solid rgba(128, 108, 67, .38);
        }

        .map-help h3,
        .community-block h3 {
            margin: 0;
            font-size: 1.05rem;
        }

        .map-help p {
            margin: 0;
            line-height: 1.5;
        }

        .community-block {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid rgba(128, 108, 67, .38);
        }

        .community-block p {
            margin: 8px 0 0;
            color: #5b4b2e;
            font-size: .92rem;
            line-height: 1.45;
        }

        .gmap-community-links {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 10px;
            margin-top: 10px;
        }

        .gmap-community-links a {
            display: inline-flex;
            align-items: center;
            min-height: 0;
            padding: 0;
            color: #5b4b2e;
            font: 800 .74rem Arial, sans-serif;
            letter-spacing: .02em;
            text-decoration: none;
            text-transform: none;
            background: transparent;
            border: 0;
        }

        .gmap-community-links a:first-child {
            color: #8b2d27;
            background: transparent;
        }

        .gmap-community-links a + a::before {
            content: "/";
            margin-right: 10px;
            color: rgba(128, 108, 67, .56);
        }

        @media (max-width: 840px) {
            .gmap-workspace,
            .gmap-field-grid {
                grid-template-columns: 1fr;
            }

            .lede {
                overflow-wrap: break-word;
            }
        }

        @media (max-width: 520px) {
            .stat-grid {
                grid-template-columns: 1fr;
            }

            .button {
                width: 100%;
            }

            .actions {
                display: grid;
            }

            .gmap-community-links {
                display: flex;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="shell header-inner">
            <a class="brand-mark" href="index.php" aria-label="Graalians Discord Server tools home">
                <i class="brand-gem" aria-hidden="true"></i>
                <span>Graalians Discord Server</span>
            </a>
            <nav class="site-nav" aria-label="Tool navigation">
                <a href="index.php">Tools Home</a>
                <a class="is-current" href="generatelevels.php">GMAP Generator</a>
                <a href="graal-level-filler.php">Level Filler</a>
                <a href="graal2nw-converter.php">Graal2NW</a>
                <a href="dungeon-generator.php">Dungeon Generator</a>
                <a href="gs2-beautify.php">GS2 Beautify</a>
                <a href="level-editor.php">Level Editor</a>
                <a href="gani-editor.php">GANI Editor</a>
                <a href="nw2png.php">NW2PNG</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="page-hero compact gmap-hero" aria-labelledby="page-title">
            <div class="shell">
                <p class="eyebrow">Online tool</p>
                <h1 id="page-title">GMAP Level Generator</h1>
                <p class="lede">Build a clean starter package with linked blank levels, a square GMAP, and Graalians community links included in the zip.</p>
            </div>
        </section>

        <section class="shell gmap-workspace" aria-label="Generator workspace">
            <div class="tool-panel">
                    <?php if ($message !== null): ?>
                        <div class="message <?php echo escapeHtml($message['type']); ?>" role="status">
                            <strong><?php echo escapeHtml($message['title']); ?></strong>
                            <span><?php echo escapeHtml($message['text']); ?></span>
                        </div>
                    <?php endif; ?>

                    <h2 class="panel-title">Create a GMAP package</h2>

                    <form class="generator-form" action="generatelevels.php" method="post">
                        <input type="hidden" name="form_token" value="<?php echo escapeHtml($_SESSION['level_generator_form_token']); ?>">

                        <div class="gmap-field-grid">
                            <div>
                                <label for="prefix">Package name</label>
                                <input
                                    id="prefix"
                                    name="prefix"
                                    type="text"
                                    maxlength="<?php echo MAX_PREFIX_LENGTH; ?>"
                                    pattern="[A-Za-z0-9][A-Za-z0-9_-]*"
                                    value="<?php echo escapeHtml($formPrefix); ?>"
                                    placeholder="graalians_map"
                                    autocomplete="off">
                            </div>

                            <div>
                                <label for="size">Map size</label>
                                <input
                                    id="size"
                                    name="size"
                                    type="number"
                                    min="<?php echo MIN_DIMENSION; ?>"
                                    max="<?php echo MAX_DIMENSION; ?>"
                                    value="<?php echo escapeHtml($previewSize); ?>"
                                    inputmode="numeric">
                            </div>
                        </div>

                        <div class="hint-row">
                            <span>Square GMAP</span>
                            <span id="levelPreview"><?php echo number_format($previewSize * $previewSize); ?> level files</span>
                        </div>

                        <div class="actions">
                            <button class="button" type="submit" id="createButton">Create zip</button>
                            <a class="button secondary" href="index.php">All tools</a>
                        </div>
                    </form>

                    <?php if ($downloadLink !== ''): ?>
                        <div class="download-box">
                            <p><?php echo escapeHtml($downloadName); ?></p>
                            <a class="button secondary" href="<?php echo escapeHtml($downloadLink); ?>" id="downloadButton">Download package</a>
                        </div>
                    <?php endif; ?>

                    <p class="fine-print">Generation and downloads are rate-limited to keep the tool responsive for everyone.</p>

                    <div class="map-help">
                        <h3>How map size works</h3>
                        <p>Map size means how many separate level files sit across and down in the GMAP grid. A size of <strong>10</strong> makes a <strong>10 x 10</strong> square GMAP, so the zip contains <strong>100</strong> blank <strong>.nw</strong> level files.</p>
                        <p>Each <strong>.nw</strong> level is still a normal <strong>64 x 64 tile</strong> level. The GMAP file does not make one giant level; it stitches those level files together by listing them in rows and letting the client treat the grid like one larger world.</p>
                        <p>This tool keeps the GMAP square so width and height match. That makes the level order predictable, keeps neighbor links clean on each edge, and avoids uneven rows where file names and links are easier to break.</p>
                    </div>
                </div>

                <aside class="gmap-status-panel" aria-label="Generator statistics">
                    <h2 class="panel-title">Generator statistics</h2>

                    <div class="stat-grid">
                        <div class="stat-card">
                            <strong><?php echo number_format((int) $stats['visits_counted']); ?></strong>
                            <span>Visits counted</span>
                        </div>
                        <div class="stat-card">
                            <strong><?php echo number_format((int) $stats['generated_gmaps']); ?></strong>
                            <span>GMAP packages generated</span>
                        </div>
                        <div class="stat-card">
                            <strong><?php echo number_format((int) $stats['generated_levels']); ?></strong>
                            <span>Level files created</span>
                        </div>
                        <div class="stat-card">
                            <strong><?php echo number_format((int) $stats['downloads_served']); ?></strong>
                            <span>Downloads served</span>
                        </div>
                    </div>

                    <ul class="included">
                        <li>Blank .nw levels with edge links</li>
                        <li>One .gmap file arranged as a square grid</li>
                        <li>README plus Graalians community shortcuts</li>
                    </ul>

                    <div class="community-block">
                        <h3>Community links included</h3>
                        <p>The generated package includes these shortcuts so players can find the Graalians community after downloading.</p>
                        <div class="gmap-community-links">
                            <a href="<?php echo escapeHtml(DISCORD_URL); ?>">Discord Server</a>
                            <a href="<?php echo escapeHtml(REDDIT_URL); ?>">Reddit</a>
                            <a href="<?php echo escapeHtml(YOUTUBE_URL); ?>">YouTube</a>
                            <a href="<?php echo escapeHtml(WORDPRESS_URL); ?>">Wordpress</a>
                            <a href="<?php echo escapeHtml(TWITCH_URL); ?>">Twitch</a>
                            <a href="<?php echo escapeHtml(INSTAGRAM_URL); ?>">Instagram</a>
                        </div>
                    </div>
                </aside>
            </section>
    </main>

    <footer class="site-footer">
        <div class="shell">
            <p>Community</p>
            <div class="footer-links">
                <a href="<?php echo escapeHtml(DISCORD_URL); ?>">Discord Server</a>
                <a href="<?php echo escapeHtml(REDDIT_URL); ?>">Reddit</a>
                <a href="<?php echo escapeHtml(YOUTUBE_URL); ?>">YouTube</a>
                <a href="<?php echo escapeHtml(WORDPRESS_URL); ?>">Wordpress</a>
                <a href="<?php echo escapeHtml(TWITCH_URL); ?>">Twitch</a>
                <a href="<?php echo escapeHtml(INSTAGRAM_URL); ?>">Instagram</a>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            var sizeInput = document.getElementById('size');
            var preview = document.getElementById('levelPreview');
            var form = document.querySelector('.generator-form');
            var createButton = document.getElementById('createButton');
            var downloadButton = document.getElementById('downloadButton');

            function updatePreview() {
                var size = parseInt(sizeInput.value, 10);

                if (!Number.isFinite(size) || size < <?php echo MIN_DIMENSION; ?>) {
                    size = <?php echo MIN_DIMENSION; ?>;
                }

                if (size > <?php echo MAX_DIMENSION; ?>) {
                    size = <?php echo MAX_DIMENSION; ?>;
                }

                var levels = size * size;
                preview.textContent = levels.toLocaleString() + ' level files';
            }

            sizeInput.addEventListener('input', updatePreview);
            updatePreview();

            form.addEventListener('submit', function () {
                createButton.disabled = true;
                createButton.textContent = 'Creating...';
            });

            if (downloadButton) {
                downloadButton.addEventListener('click', function () {
                    downloadButton.textContent = 'Download started';
                    downloadButton.style.pointerEvents = 'none';
                });
            }
        }());
    </script>
</body>
</html>
