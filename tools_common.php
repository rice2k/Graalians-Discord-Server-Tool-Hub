<?php
declare(strict_types=1);

const SITE_DISCORD_URL = 'https://discord.gg/AeDurPz';
const SITE_REDDIT_URL = 'https://reddit.com/r/graal';
const SITE_YOUTUBE_URL = 'https://www.youtube.com/@GraalDiscord';
const SITE_WORDPRESS_URL = 'https://graaldisocrd.wordpress.com/';
const SITE_TWITCH_URL = 'https://www.twitch.tv/rice2k';
const SITE_INSTAGRAM_URL = 'https://www.instagram.com/Graal_Discord/';

function siteEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function communityLinks(): array
{
    return [
        ['Discord Server', SITE_DISCORD_URL],
        ['Reddit', SITE_REDDIT_URL],
        ['YouTube', SITE_YOUTUBE_URL],
        ['Wordpress', SITE_WORDPRESS_URL],
        ['Twitch', SITE_TWITCH_URL],
        ['Instagram', SITE_INSTAGRAM_URL],
    ];
}

function toolPages(): array
{
    return [
        [
            'title' => 'GMAP Level Generator',
            'href' => 'generatelevels.php',
            'status' => 'Ready',
            'description' => 'Create square GMAP starter packages with blank linked .nw levels and community links in the zip.',
        ],
        [
            'title' => 'Graal Level Filler',
            'href' => 'graal-level-filler.php',
            'status' => 'Ready',
            'description' => 'Upload or paste a .nw level, fill blank space or selected regions, preview it, and download the updated level.',
        ],
        [
            'title' => 'Graal2NW Converter',
            'href' => 'graal2nw-converter.php',
            'status' => 'Ready',
            'description' => 'Normalize .nw files, build levels from tile lists, and try tile-only imports from classic .graal files.',
        ],
        [
            'title' => 'Dungeon Generator',
            'href' => 'dungeon-generator.php',
            'status' => 'Ready',
            'description' => 'Generate a seeded 64 x 64 dungeon starter level with rooms, corridors, preview, and .nw export.',
        ],
        [
            'title' => 'GS2 Beautify',
            'href' => 'gs2-beautify.php',
            'status' => 'Ready',
            'description' => 'Format GS2 scripts with fixed indentation, brace style controls, copy, and download.',
        ],
        [
            'title' => 'Level Editor',
            'href' => 'level-editor.php',
            'status' => 'Ready',
            'description' => 'Paint a 64 x 64 .nw level in the browser, import existing files, and export .nw or PNG previews.',
        ],
        [
            'title' => 'GANI Editor',
            'href' => 'gani-editor.php',
            'status' => 'Ready',
            'description' => 'Open, edit, preview, and download Graal animation .gani files with optional local image previews.',
        ],
        [
            'title' => 'NW2PNG',
            'href' => 'nw2png.php',
            'status' => 'Ready',
            'description' => 'Render .nw BOARD data to a PNG preview, with optional pics1.png tileset support.',
        ],
    ];
}

function renderPageStart(string $title, string $description): void
{
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?php echo siteEscape($description); ?>">
    <title><?php echo siteEscape($title); ?></title>
    <link rel="stylesheet" href="graalians-tools.css">
</head>
<body>
    <header class="site-header">
        <div class="shell header-inner">
            <a class="brand-mark" href="index.php" aria-label="Graalians Discord Server tools home">
                <i class="brand-gem" aria-hidden="true"></i>
                <span>Graalians Discord Server</span>
            </a>
            <nav class="site-nav" aria-label="Main navigation">
                <?php renderToolNavLinks(); ?>
            </nav>
        </div>
    </header>
    <main>
<?php
}

function renderPageEnd(): void
{
    ?>
    </main>
    <footer class="site-footer">
        <div class="shell">
            <p>Community</p>
            <?php renderCommunityLinks('footer-links'); ?>
        </div>
    </footer>
</body>
</html>
<?php
}

function renderToolNavLinks(): void
{
    $currentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $links = [
        ['Tools Home', 'index.php'],
        ['GMAP Generator', 'generatelevels.php'],
        ['Level Filler', 'graal-level-filler.php'],
        ['Graal2NW', 'graal2nw-converter.php'],
        ['Dungeon Generator', 'dungeon-generator.php'],
        ['GS2 Beautify', 'gs2-beautify.php'],
        ['Level Editor', 'level-editor.php'],
        ['GANI Editor', 'gani-editor.php'],
        ['NW2PNG', 'nw2png.php'],
    ];
    ?>
    <?php foreach ($links as $link): ?>
        <a<?php echo $currentPage === $link[1] ? ' class="is-current"' : ''; ?> href="<?php echo siteEscape($link[1]); ?>"><?php echo siteEscape($link[0]); ?></a>
    <?php endforeach; ?>
<?php
}

function renderCommunityLinks(string $className = 'community-links'): void
{
    ?>
    <div class="<?php echo siteEscape($className); ?>">
        <?php foreach (communityLinks() as $link): ?>
            <a href="<?php echo siteEscape($link[1]); ?>"><?php echo siteEscape($link[0]); ?></a>
        <?php endforeach; ?>
    </div>
<?php
}

function renderComingSoonPage(string $toolTitle, string $summary): void
{
    renderPageStart($toolTitle . ' - Graalians Discord Server Tools', $summary);
    ?>
        <section class="page-hero compact">
            <div class="shell">
                <p class="eyebrow">Coming soon</p>
                <h1><?php echo siteEscape($toolTitle); ?></h1>
                <p><?php echo siteEscape($summary); ?></p>
            </div>
        </section>

        <section class="shell content-band">
            <div class="notice-panel">
                <h2>This page is ready for the next tool</h2>
                <p>This tool page is connected to the shared Graalians theme and navigation. Add the working controls here when the tool is ready.</p>
                <div class="actions">
                    <a class="button" href="index.php">Back to Tools Hub</a>
                    <a class="button secondary" href="generatelevels.php">Open GMAP Generator</a>
                </div>
            </div>

            <aside class="community-panel">
                <h2>Tool guide</h2>
                <p>Each online tool should explain what it does, what file type it expects, and what the download will contain.</p>
            </aside>
        </section>
<?php
    renderPageEnd();
}
