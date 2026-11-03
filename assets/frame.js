/**
 * Writes the computed value of each token next to it: a colour as hex, a length in rem or px
 */
for (const element of document.querySelectorAll(".playground-ui-value")) {
  const { value, token, property = "inline-size" } = element.dataset;
  const probe = document.createElement("span");

  probe.style.position = "absolute";
  probe.style.setProperty(value === "color" ? "background-color" : property, `var(${token})`);
  element.append(probe);

  const style = getComputedStyle(probe);
  const color = style.backgroundColor;
  const pixels = parseFloat(style.getPropertyValue(property));

  probe.remove();

  if (value === "rem") {
    element.textContent = `${+(pixels / parseFloat(getComputedStyle(document.documentElement).fontSize)).toFixed(2)}rem`;
  } else if (value === "px") {
    element.textContent = `${Math.round(pixels)}px`;
  } else if (/^(rgba?\(|color\(srgb )/.test(color)) {
    const [red, green, blue, alpha = 1] = color.match(/[\d.]+/g).map(Number);
    const scale = color.startsWith("color(") ? 255 : 1;
    const hex = [red, green, blue].map((channel) => Math.round(channel * scale).toString(16).padStart(2, "0")).join("");

    element.textContent = alpha < 1 ? `#${hex} ${Math.round(alpha * 100)}%` : `#${hex}`;
  } else {
    element.textContent = color;
  }
}

/**
 * Sizes the frame to the case inside it, as soon as it renders rather than once every image has loaded
 */
if (window.frameElement) {
  const render = document.querySelector(".playground-ui-case__render");

  new ResizeObserver(() => {
    frameElement.style.blockSize = `${Math.floor(render.getBoundingClientRect().bottom + scrollY)}px`;
  }).observe(render);
}
