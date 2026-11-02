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
    if (field.type === "checkbox") {
      if (field.checked) {
        props[field.name] = true;
      }
    } else if (field.value !== "") {
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
      code.textContent = fresh.querySelector(`.playground-ui-code__${name}`).textContent;
    }
  }
}

for (const form of document.querySelectorAll(".playground-ui-controls__form")) {
  let timeout;

  form.addEventListener("submit", (event) => event.preventDefault());
  form.addEventListener("input", () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => render(form), 300);
  });
}

/**
 * Runs axe-core on every case and lists the violations under each one
 */
async function check(button) {
  const result = button.nextElementSibling;
  const cases = document.querySelectorAll(".playground-ui-case");
  let violations = 0;
  let failing = 0;

  button.disabled = true;
  result.textContent = "Checking…";

  try {
    if (window.axe === undefined) {
      await new Promise((resolve, reject) => {
        const script = document.createElement("script");
        script.src = button.dataset.script;
        script.onload = resolve;
        script.onerror = reject;
        document.head.append(script);
      });
    }
  } catch {
    result.textContent = "axe-core could not be loaded.";
    button.disabled = false;
    return;
  }

  for (const section of cases) {
    section.querySelector(".playground-ui-axe")?.remove();

    const { violations: found } = await axe.run({ include: [section.querySelector(".playground-ui-case__render")] });

    if (found.length === 0) {
      continue;
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

    section.querySelector(".playground-ui-case__render").after(list);
    violations += found.length;
    failing += 1;
  }

  result.textContent = violations === 0 ? `No violations in ${cases.length} cases.` : `${violations} violations in ${failing} of ${cases.length} cases.`;
  button.disabled = false;
}

const axeButton = document.querySelector(".playground-ui-header__axe");

if (document.querySelector(".playground-ui-case") === null) {
  axeButton?.parentElement.remove();
} else {
  axeButton?.addEventListener("click", () => check(axeButton));
}
