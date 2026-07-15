<?php
declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'tools_common.php';

renderPageStart(
    'Graalians Discord Server Tools',
    'A Graalians Discord Server tools hub for Graal level building, conversion, generation, and script cleanup.'
);
?>
        <section class="page-hero">
            <div class="shell">
                <p class="eyebrow">Graalians Discord Server</p>
                <h1>Graal Tools Hub</h1>
                <p>Browser tools for making, editing, converting, previewing, and packaging Graal level files.</p>
            </div>
        </section>

        <section class="shell content-band">
            <div>
                <div class="tools-grid" aria-label="Available tools">
                    <?php foreach (toolPages() as $tool): ?>
                        <a class="tool-card" href="<?php echo siteEscape($tool['href']); ?>">
                            <span class="tool-status <?php echo $tool['status'] === 'Ready' ? '' : 'soon'; ?>"><?php echo siteEscape($tool['status']); ?></span>
                            <h2><?php echo siteEscape($tool['title']); ?></h2>
                            <p><?php echo siteEscape($tool['description']); ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <aside class="community-panel">
                <h2>What these tools do</h2>
                <p>Use the hub to create GMAP starter packs, edit 64 x 64 <strong>.nw</strong> levels, fill blank tiles, edit <strong>.gani</strong> animations, render PNG previews, generate dungeon layouts, convert level data, and clean up GS2 scripts.</p>
                <p class="tool-note">Normal <strong>.nw</strong> levels use a <strong>GLEVNW01</strong> header and layer-0 <strong>BOARD</strong> rows. These tools keep that format visible so files stay easy to inspect and edit.</p>
            </aside>
        </section>
<?php
renderPageEnd();
