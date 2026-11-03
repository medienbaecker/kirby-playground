for (const section of document.querySelectorAll(".playground-ui-case")) {
  const box = section.querySelector(".playground-ui-case__frame");
  const frame = section.querySelector(".playground-ui-case__iframe");
  const handle = section.querySelector(".playground-ui-case__handle");
  const width = section.querySelector(".playground-ui-case__width");

  new ResizeObserver(() => {
    width.textContent = `${Math.round(frame.clientWidth)}px`;
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
  const update = (hidden) => {
    document.documentElement.toggleAttribute("data-playground-sidebar-hidden", hidden);
    sidebarToggle.setAttribute("aria-expanded", String(!hidden));
    localStorage.setItem("playground-sidebar", hidden ? "hidden" : "shown");
  };

  update(document.documentElement.hasAttribute("data-playground-sidebar-hidden"));
  sidebarToggle.addEventListener("click", () => update(sidebarToggle.getAttribute("aria-expanded") === "true"));
}
