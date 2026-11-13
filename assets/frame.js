/**
 * Writes the computed value of each token next to it: a colour as hex, a length in rem or px
 */
for (const element of document.querySelectorAll(".playground-ui-value")) {
	const { value, token, property = value === "color" ? "background-color" : "inline-size" } = element.dataset;
	const probe = document.createElement("span");

	probe.style.position = "absolute";
	probe.style.setProperty(property, `var(${token})`);
	element.append(probe);

	const computed = getComputedStyle(probe).getPropertyValue(property);
	const pixels = parseFloat(computed);

	probe.remove();

	if (value === "rem") {
		element.textContent = `${+(pixels / parseFloat(getComputedStyle(document.documentElement).fontSize)).toFixed(2)}rem`;
	} else if (value === "px") {
		element.textContent = `${Math.round(pixels)}px`;
	} else if (/^(rgba?\(|color\(srgb )/.test(computed)) {
		const [red, green, blue, alpha = 1] = computed.match(/[\d.]+/g).map(Number);
		const scale = computed.startsWith("color(") ? 255 : 1;
		const hex = [red, green, blue].map((channel) => Math.round(channel * scale).toString(16).padStart(2, "0")).join("");

		element.textContent = alpha < 1 ? `#${hex} ${Math.round(alpha * 100)}%` : `#${hex}`;
	} else {
		element.textContent = computed;
	}
}

/**
 * Sizes the frame to the case inside it, as soon as it renders rather than once every image has loaded
 */
if (window.frameElement) {
	const render = document.querySelector(".playground-ui-case__render");

	new ResizeObserver(() => {
		frameElement.style.blockSize = `${Math.floor(render.getBoundingClientRect().bottom)}px`;
		frameElement.toggleAttribute("data-sized", true);
	}).observe(render);
}
