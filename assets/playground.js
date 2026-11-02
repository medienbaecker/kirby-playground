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
