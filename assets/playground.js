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

const sidebar = document.querySelector(".playground-ui-sidebar");

if (sidebar) {
	const toggle = sidebar.querySelector(".playground-ui-sidebar__toggle");
	const wide = matchMedia("(width >= 50rem)");
	const sync = () => {
		const expanded = wide.matches ? !sidebar.hasAttribute("data-collapsed") : sidebar.hasAttribute("data-open");

		toggle.setAttribute("aria-expanded", expanded);
	};

	toggle.addEventListener("click", () => {
		if (wide.matches) {
			const collapsed = sidebar.toggleAttribute("data-collapsed");

			localStorage.setItem("playground-sidebar", collapsed ? "collapsed" : "expanded");
		} else {
			sidebar.toggleAttribute("data-open");
		}

		sync();
	});

	wide.addEventListener("change", sync);
	sync();
}
