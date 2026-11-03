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

  if (value === "color") {
    const [red, green, blue, alpha = 1] = color.match(/[\d.]+/g).map(Number);
    const scale = color.startsWith("color(srgb") ? 255 : 1;
    const hex = [red, green, blue].map((channel) => Math.round(channel * scale).toString(16).padStart(2, "0")).join("");
    element.textContent = alpha < 1 ? `#${hex} ${Math.round(alpha * 100)}%` : `#${hex}`;
  } else {
    element.textContent = value === "rem" ? `${+(pixels / parseFloat(getComputedStyle(document.documentElement).fontSize)).toFixed(2)}rem` : `${Math.round(pixels)}px`;
  }
}
