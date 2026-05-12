<?php
declare(strict_types=1);

$requireDatabase = false;
require __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Grafikon filmova';
$pageHeading = 'Grafikon filmova';
$pageDescription = 'Vizualni prikaz zastupljenosti filmskih žanrova.';
$styles = ['style.css', 'grafikon.css'];
$activePage = 'home';

require __DIR__ . '/includes/header.php';
?>
      <section class="chart-section">
        <div class="chart-wrapper">
          <div class="chart-left">
            <h2>
              <span class="desktop-chart-title">Pie chart filmova</span>
              <span class="mobile-chart-title">Bar chart filmova</span>
            </h2>

            <p>
              <span class="desktop-chart-title">
                Na većim ekranima prikazan je pie chart zastupljenosti filmskih žanrova.
              </span>
              <span class="mobile-chart-title">
                Na manjim ekranima prikazan je bar chart zastupljenosti filmskih žanrova.
              </span>
            </p>

            <div class="pie-chart"></div>

            <div class="bar-chart">
              <div class="bar-row">
                <span class="bar-label">Akcija (2)</span>
                <div class="bar-track">
                  <div class="bar-fill akcija-bar" aria-label="Akcija 40 posto"></div>
                </div>
              </div>

              <div class="bar-row">
                <span class="bar-label">Drama (1)</span>
                <div class="bar-track">
                  <div class="bar-fill drama-bar" aria-label="Drama 20 posto"></div>
                </div>
              </div>

              <div class="bar-row">
                <span class="bar-label">Komedija (1)</span>
                <div class="bar-track">
                  <div class="bar-fill komedija-bar" aria-label="Komedija 20 posto"></div>
                </div>
              </div>

              <div class="bar-row">
                <span class="bar-label">Sci-Fi (1)</span>
                <div class="bar-track">
                  <div class="bar-fill sf-bar" aria-label="Sci-Fi 10 posto"></div>
                </div>
              </div>

              <div class="bar-row">
                <span class="bar-label">Romantični (1)</span>
                <div class="bar-track">
                  <div class="bar-fill romanticni-bar" aria-label="Romantični 10 posto"></div>
                </div>
              </div>
            </div>
          </div>

          <aside class="chart-legend">
            <h3>Legenda</h3>
            <ul>
              <li><span class="legend-color akcija"></span> Akcija - 2 filma</li>
              <li><span class="legend-color drama"></span> Drama - 1 film</li>
              <li><span class="legend-color komedija"></span> Komedija - 1 film</li>
              <li><span class="legend-color sf"></span> Sci-Fi - 1 film</li>
              <li><span class="legend-color romanticni"></span> Romantični - 1 film</li>
            </ul>
          </aside>
        </div>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>

