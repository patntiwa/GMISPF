/**
 * Optional browser and accessibility test suite.
 * Run against the read-only demo only:
 * BASE_URL=http://127.0.0.1:8080 node tests/browser.mjs
 */
import assert from 'node:assert/strict';

import { createRequire } from 'node:module';
const req = createRequire(process.env.TOOLS_DIR ? process.env.TOOLS_DIR + '/package.json' : import.meta.url);
const { chromium } = req('playwright');
const AxeBuilder = req('@axe-core/playwright').default || req('@axe-core/playwright');

const base = process.env.BASE_URL || 'http://127.0.0.1:8080';
const browser = await chromium.launch({
  headless: true,
  ...(process.env.BROWSER_EXECUTABLE ? {
    executablePath: process.env.BROWSER_EXECUTABLE,
    args: ['--no-sandbox', '--disable-dev-shm-usage', '--no-zygote', '--use-angle=swiftshader'],
  } : {}),
});

let pagesChecked = 0;
try {
  const context = await browser.newContext();
  const page = await context.newPage();
  const errors = [];
  page.on('pageerror', (error) => errors.push(error.message));
  page.on('console', (message) => {
    if (message.type() === 'error') errors.push(message.text());
  });

  await page.goto(base);
  assert.ok(await page.getByText('Aperçu de la V2').isVisible(), 'Use a demo server, never a production instance.');

  const routes = [
    '/',
    '/?page=reports',
    '/?page=new',
    '/?page=report&id=128',
    '/?page=edit&id=128',
    '/?page=installations',
    '/?page=history',
    '/?page=account',
    '/?page=help',
    '/?page=login'
  ];

  for (const width of [1440, 390]) {
    await page.setViewportSize({ width, height: 960 });
    for (const route of routes) {
      const response = await page.goto(base + route);
      assert.equal(response.status(), 200, route);
      await page.evaluate(() => document.fonts.ready);
      const isOverflowing = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth);
      assert.equal(isOverflowing, false, `Horizontal overflow at ${width}px: ${route}`);
      const result = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
      assert.deepEqual(
        result.violations.map(v => ({ id: v.id, targets: v.nodes.map(n => n.target), reasons: v.nodes.map(n => n.failureSummary) })),
        [],
        `Accessibility at ${width}px: ${route}`
      );
      console.log(`PASS: ${route} · ${width}px · axe A/AA`);
      pagesChecked++;
    }
  }

  // Interactive UI tests on 390px (mobile drawer) and 1440px
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto(base);
  const menu = page.locator('[data-menu]');
  assert.equal(await menu.getAttribute('aria-label'), 'Ouvrir le menu');
  await menu.click();
  assert.equal(await menu.getAttribute('aria-expanded'), 'true');
  assert.equal(await menu.getAttribute('aria-label'), 'Fermer le menu');
  await page.locator('#sidebar').getByRole('link', { name: 'Interventions', exact: false }).click();
  await page.waitForURL('**page=reports**');
  assert.equal(await page.locator('#sidebar').evaluate(el => el.inert), true);

  // Desktop search, filter, export and account forms
  await page.setViewportSize({ width: 1440, height: 960 });
  await page.goto(base + '/?page=reports');
  await page.getByLabel('Titre, lieu ou référence').fill('INT-0128');
  await page.getByRole('button', { name: 'Filtrer', exact: true }).click();
  assert.equal(await page.locator('tbody tr').count(), 1);

  await page.getByLabel('Effacer les filtres').click();
  await page.getByLabel('Installation', { exact: true }).selectOption('Forage');
  await page.getByRole('button', { name: 'Filtrer', exact: true }).click();
  assert.ok((await page.locator('.table-category').allTextContents()).every(v => v.trim() === 'Forage'));

  await page.goto(base + '/?page=reports');
  const downloadEvent = page.waitForEvent('download');
  await page.getByRole('link', { name: 'Exporter' }).click();
  const download = await downloadEvent;
  assert.match(download.suggestedFilename(), /^interventions-.*\.csv$/);
  assert.equal(await download.failure(), null);

  await page.goto(base + '/?page=new');
  assert.equal(await page.getByRole('button', { name: 'Créer l’intervention' }).isDisabled(), true);

  await page.goto(base + '/?page=login');
  await page.getByRole('button', { name: 'Afficher', exact: true }).click();
  assert.equal(await page.locator('#password').getAttribute('type'), 'text');
  await page.getByRole('button', { name: 'Masquer', exact: true }).click();
  assert.equal(await page.locator('#password').getAttribute('type'), 'password');

  assert.deepEqual(errors, [], 'No client-side JavaScript, asset, or CSP errors');
  console.log(`\n${pagesChecked} page/viewport accessibility checks passed. Menu, search, filters, export and form interactions passed.`);
} finally {
  await browser.close();
}
