/**
 * Screenshots every playground case at 320 and 1280 in Chromium and WebKit,
 * compares them with the approved set and writes a report of what changed.
 *
 * Run it from the site's root, where Playwright is installed:
 *   node site/plugins/kirby-playground/bin/shots.mjs http://example.test
 *   node site/plugins/kirby-playground/bin/shots.mjs http://example.test --approve
 */

import { createRequire } from "node:module";
import { cpSync, existsSync, mkdirSync, readFileSync, rmSync, writeFileSync } from "node:fs";
import { join } from "node:path";

const { chromium, webkit } = createRequire(join(process.cwd(), "package.json"))("playwright");

const site = process.argv[2]?.replace(/\/$/, "");
const approve = process.argv.includes("--approve");
const folder = join(process.cwd(), ".playground-shots");
const current = join(folder, "current");
const approved = join(folder, "approved");
const browsers = { chromium, webkit };
const widths = [320, 1280];
const hidden = ".playground-ui-case__header, .playground-ui-case__accessibility, .playground-ui-controls, .playground-ui-code, .playground-ui-axe { display: none !important; }";

if (site === undefined || site.startsWith("--")) {
  console.error("Usage: node bin/shots.mjs http://example.test [--approve]");
  process.exit(1);
}

if (approve) {
  if (existsSync(current) === false) {
    console.error("Nothing to approve: run it without --approve first.");
    process.exit(1);
  }

  rmSync(approved, { recursive: true, force: true });
  cpSync(current, approved, { recursive: true });
  console.log(`Approved ${approved}`);
  process.exit(0);
}

const escape = (text) => text.replace(/[&<>"]/g, (character) => `&#${character.charCodeAt(0)};`);

async function cases(page) {
  await page.goto(`${site}/playground`);
  const pages = await page.$$eval("a[href]", (links) => [...new Set(links.map((link) => new URL(link.href)).filter((url) => url.origin === location.origin && /\/playground\/.+/.test(url.pathname)).map((url) => url.pathname))]);
  const shots = [];

  for (const path of pages) {
    await page.goto(`${site}${path}`);
    const ids = await page.$$eval(".playground-ui-case[id]", (sections) => sections.map((section) => section.id));
    const name = path.replace(/^.*?\/playground\//, "").replaceAll("/", "--");

    if (ids.length === 0) {
      shots.push({ name, url: `${site}${path}?seed`, page: true });
    }

    for (const id of ids) {
      shots.push({ name: `${name}--${id}`, url: `${site}${path}?case=${encodeURIComponent(id)}`, page: false });
    }
  }

  return shots;
}

rmSync(current, { recursive: true, force: true });

const crawler = await chromium.launch();
const shots = await cases(await crawler.newPage());
await crawler.close();

const changed = [];
let total = 0;

for (const [browserName, browserType] of Object.entries(browsers)) {
  const browser = await browserType.launch();

  for (const width of widths) {
    const page = await browser.newPage({ viewport: { width, height: 900 }, reducedMotion: "reduce" });
    const set = `${browserName}-${width}`;

    mkdirSync(join(current, set), { recursive: true });

    for (const shot of shots) {
      const file = join(set, `${shot.name}.png`);

      await page.goto(shot.url, { waitUntil: "networkidle" });
      await page.addStyleTag({ content: hidden });
      await page.evaluate(async () => {
        for (const image of document.images) {
          image.loading = "eager";
        }

        for (const video of document.querySelectorAll("video")) {
          video.pause();
          video.currentTime = 0;
        }

        await Promise.all([...document.images].map((image) => image.decode().catch(() => {})));
        await Promise.all([...document.querySelectorAll("video")].map((video) => video.readyState >= 2 || new Promise((resolve) => video.addEventListener("seeked", resolve, { once: true }))));
        await document.fonts.ready;
      });

      const target = shot.page ? page : page.locator(".playground-ui-case__render");
      await target.screenshot({ path: join(current, file), animations: "disabled", caret: "hide", ...(shot.page ? { fullPage: true } : {}) });

      const before = join(approved, file);
      total += 1;

      if (existsSync(before) === false) {
        changed.push({ file, url: shot.url, reason: "new" });
      } else if (readFileSync(before).equals(readFileSync(join(current, file))) === false) {
        changed.push({ file, url: shot.url, reason: "changed" });
      }

      process.stdout.write(`\r${set} ${total} shots, ${changed.length} new or changed`);
    }

    await page.close();
  }

  await browser.close();
}

const rows = changed.map(({ file, url, reason }) => `
  <section>
    <h2>${escape(file)} <small>${reason}</small> <a href="${escape(url)}">Open</a></h2>
    <div class="pair">
      ${reason === "changed" ? `<figure><img src="approved/${escape(file)}" alt=""><figcaption>Approved</figcaption></figure>` : ""}
      <figure><img src="current/${escape(file)}" alt=""><figcaption>Now</figcaption></figure>
    </div>
  </section>`);

writeFileSync(join(folder, "report.html"), `<!doctype html>
<html lang="en">
<meta charset="utf-8">
<title>Playground shots</title>
<style>
  body { margin: 2rem; font: 14px/1.5 system-ui, sans-serif; }
  section { margin-block: 2rem; }
  h2 { font-size: 1rem; }
  small { color: GrayText; font-weight: normal; }
  .pair { display: grid; grid-template-columns: repeat(auto-fit, minmax(20rem, 1fr)); gap: 1rem; align-items: start; }
  figure { margin: 0; }
  img { max-inline-size: 100%; border: 1px solid GrayText; }
</style>
<h1>${changed.length} of ${total} shots new or changed</h1>
${rows.join("")}
</html>
`);

console.log(`\n${changed.length} of ${total} shots new or changed. Report: ${join(folder, "report.html")}`);
