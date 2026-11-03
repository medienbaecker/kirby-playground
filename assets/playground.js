/**
 * Copy buttons of the code panels, with a screen reader announcement
 */
function copy(element) {
  if (window.isSecureContext) {
    return navigator.clipboard.writeText(element.textContent);
  }

  const range = document.createRange();
  range.selectNodeContents(element);
  getSelection().removeAllRanges();
  getSelection().addRange(range);
  document.execCommand("copy");
  getSelection().removeAllRanges();
}

for (const button of document.querySelectorAll(".playground-ui-code__copy")) {
  const code = button.parentElement.querySelector("code");
  const status = button.parentElement.querySelector(".playground-ui-code__status");
  let timeout;

  button.addEventListener("click", async () => {
    await copy(code);
    button.textContent = "Copied";
    status.textContent = status.textContent === "Copied to clipboard" ? "Copied to clipboard." : "Copied to clipboard";
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      button.textContent = "Copy";
    }, 3000);
  });
}

/**
 * Renders a component case again on the server whenever one of its props changes
 */
async function render(form) {
  const section = form.closest(".playground-ui-case");
  const props = {};

  for (const field of form.elements) {
    if (field.value === "") {
      continue;
    }

    if (field.dataset.type === "boolean") {
      props[field.name] = field.value === "true";
    } else {
      props[field.name] = field.type === "number" ? Number(field.value) : field.value;
    }
  }

  const url = new URL(location.href);
  url.hash = "";
  url.searchParams.set("case", section.id);
  url.searchParams.set("props", JSON.stringify(props));

  const response = await fetch(url);
  const fresh = new DOMParser().parseFromString(await response.text(), "text/html").getElementById(section.id);

  if (fresh === null) {
    return;
  }

  section.querySelector(".playground-ui-case__render").replaceWith(fresh.querySelector(".playground-ui-case__render"));
  section.querySelector(".playground-ui-case__alone").href = url.search;

  for (const name of ["php", "html"]) {
    const code = section.querySelector(`.playground-ui-code__${name}`);

    if (code) {
      code.textContent = fresh.querySelector(`.playground-ui-code__${name}`)?.textContent ?? "";
    }
  }

  checking = checking.then(async () => {
    if (window.axe) {
      await check(section);
      summarize(axeResult);
    }
  });
}

for (const form of document.querySelectorAll(".playground-ui-controls__form")) {
  let timeout;

  form.addEventListener("submit", (event) => event.preventDefault());
  form.addEventListener("input", () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => render(form), 300);
  });
}

const axeResult = document.querySelector(".playground-ui-header__axe");
const violations = new Map();
let checking = Promise.resolve();

/**
 * Runs axe-core on a case and lists the violations under it
 */
async function check(section) {
  section.querySelector(".playground-ui-axe")?.remove();

  const render = section.querySelector(".playground-ui-case__render");
  const { violations: found } = await axe.run({ include: [render] });

  violations.set(section, found.length);

  if (found.length === 0) {
    return;
  }

  const list = document.createElement("ul");
  list.className = "playground-ui-axe";

  for (const violation of found) {
    const item = document.createElement("li");
    const link = document.createElement("a");
    link.href = violation.helpUrl;
    link.textContent = violation.id;
    item.append(link, `: ${violation.help}, ${violation.nodes.length} ${violation.nodes.length === 1 ? "element" : "elements"}`);
    list.append(item);
  }

  render.after(list);
}

/**
 * Sums up the violations of every case in the header
 */
function summarize(result) {
  const counts = [...violations.values()];
  const total = counts.reduce((sum, count) => sum + count, 0);
  const failing = counts.filter((count) => count > 0).length;

  result.textContent = total === 0 ? `Accessibility: no violations in ${counts.length} cases.` : `Accessibility: ${total} ${total === 1 ? "violation" : "violations"} in ${failing} of ${counts.length} cases.`;
}

/**
 * Loads axe-core and checks every case of the page
 */
async function checkAll(result) {
  result.textContent = "Checking accessibility…";

  try {
    await new Promise((resolve, reject) => {
      const script = document.createElement("script");
      script.src = result.dataset.script;
      script.onload = resolve;
      script.onerror = reject;
      document.head.append(script);
    });
  } catch {
    result.textContent = "axe-core could not be loaded.";
    return;
  }

  for (const section of document.querySelectorAll(".playground-ui-case")) {
    await check(section);
  }

  summarize(result);
}

if (document.querySelector(".playground-ui-case") === null) {
  axeResult?.remove();
} else if (axeResult) {
  checking = checkAll(axeResult);
}
