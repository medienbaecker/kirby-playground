for (const section of document.querySelectorAll(".playground-ui-case")) {
	const box = section.querySelector(".playground-ui-case__frame");
	const frame = section.querySelector(".playground-ui-case__iframe");
	const handle = section.querySelector(".playground-ui-case__handle");
	const width = section.querySelector(".playground-ui-case__width");

	new ResizeObserver(() => {
		width.textContent = `${frame.clientWidth}px`;
	}).observe(frame);

	handle.addEventListener("pointerdown", (event) => {
		handle.setPointerCapture(event.pointerId);
		frame.style.pointerEvents = "none";
	});

	handle.addEventListener("pointermove", (event) => {
		if (handle.hasPointerCapture(event.pointerId)) {
			box.style.inlineSize = `${event.clientX - box.getBoundingClientRect().left + handle.offsetWidth / 2}px`;
		}
	});

	handle.addEventListener("lostpointercapture", () => {
		frame.style.pointerEvents = "";
	});
}

const sidebarToggle = document.querySelector(".playground-ui-sidebar__toggle");

if (sidebarToggle) {
	const root = document.documentElement;
	const wide = matchMedia("(width >= 50rem)");
	const sync = () => {
		const expanded = wide.matches ? !root.hasAttribute("data-playground-sidebar-hidden") : root.hasAttribute("data-playground-sidebar-open");

		sidebarToggle.setAttribute("aria-expanded", expanded);
	};

	sidebarToggle.addEventListener("click", () => {
		if (wide.matches) {
			const hidden = root.toggleAttribute("data-playground-sidebar-hidden");

			localStorage.setItem("playground-sidebar", hidden ? "hidden" : "shown");
		} else {
			root.toggleAttribute("data-playground-sidebar-open");
		}

		sync();
	});

	wide.addEventListener("change", sync);
	sync();
}
