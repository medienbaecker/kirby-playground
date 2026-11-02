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
      props[field.name] = field.checked;
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
