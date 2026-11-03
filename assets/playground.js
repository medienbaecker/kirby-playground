/**
 * Copies text, with a fallback for plain-http dev domains
 */
function copy(text) {
  if (window.isSecureContext) {
    return navigator.clipboard.writeText(text);
  }

  const element = document.createElement("span");
  element.textContent = text;
  document.body.append(element);

  const range = document.createRange();
  range.selectNodeContents(element);
  getSelection().removeAllRanges();
  getSelection().addRange(range);
  document.execCommand("copy");
  getSelection().removeAllRanges();
  element.remove();
}

for (const button of document.querySelectorAll(".playground-ui-code__copy")) {
  const code = button.parentElement.querySelector("code");
  const status = button.parentElement.querySelector(".playground-ui-code__status");
  let timeout;

  button.addEventListener("click", async () => {
    await copy(code.textContent);
    button.textContent = "Copied";
    status.textContent = status.textContent === "Copied to clipboard" ? "Copied to clipboard." : "Copied to clipboard";
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      button.textContent = "Copy";
    }, 3000);
  });
}

const pageRules = ["bypass", "landmark-one-main", "landmark-unique", "page-has-heading-one", "region"];

/**
 * Runs axe-core on a case and lists the violations under it, each with the elements and how to fix them
 */
async function check(section) {
  const render = section.querySelector(".playground-ui-case__render");
  const { violations } = await axe.run({ include: [render] }, { rules: Object.fromEntries(pageRules.map((rule) => [rule, { enabled: false }])) });

  if (violations.length === 0) {
    return 0;
  }

  const badge = document.createElement("span");
  badge.className = "playground-ui-case__problems";
  badge.textContent = `${violations.length} ${violations.length === 1 ? "problem" : "problems"}`;
  section.querySelector(".playground-ui-case__label").append(badge);

  const list = document.createElement("ul");
  list.className = "playground-ui-axe";

  for (const violation of violations) {
    const item = document.createElement("li");
    const link = document.createElement("a");

    link.href = violation.helpUrl;
    link.textContent = violation.id;
    item.className = "playground-ui-axe__item";
    item.append(link, `: ${violation.help}`);

    for (const node of violation.nodes) {
      const element = document.createElement("div");
      const code = document.createElement("code");
      const advice = document.createElement("span");

      element.className = "playground-ui-axe__node";
      code.textContent = node.html.length > 160 ? `${node.html.slice(0, 160)}…` : node.html;
      advice.textContent = node.failureSummary.split("\n")[1]?.trim() ?? "";
      element.append(code, advice);
      item.append(element);
    }

    list.append(item);
  }

  render.after(list);

  return violations.length;
}

/**
 * Checks every case of the page and sums up the violations in the header
 */
async function checkAll(result) {
  const counts = [];

  for (const section of document.querySelectorAll(".playground-ui-case")) {
    counts.push(await check(section));
  }

  const total = counts.reduce((sum, count) => sum + count, 0);
  const failing = counts.filter((count) => count > 0).length;

  result.textContent = total === 0 ? `Accessibility: no violations in ${counts.length} cases.` : `Accessibility: ${total} ${total === 1 ? "violation" : "violations"} in ${failing} of ${counts.length} cases.`;
}

const axeResult = document.querySelector(".playground-ui-header__axe");

if (document.querySelector(".playground-ui-case") === null) {
  axeResult?.remove();
} else if (axeResult) {
  checkAll(axeResult);
}
