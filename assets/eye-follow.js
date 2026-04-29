(() => {
  const reduceMotionQuery = window.matchMedia?.("(prefers-reduced-motion: reduce)");
  if (reduceMotionQuery?.matches) return;

  // get all eyes that should follow mouse
  const getEyes = () =>
    Array.from(document.querySelectorAll('.bb-icon-eye[data-follow-mouse][data-follow-enabled="true"]'));
  let mouseX = 0;
  let mouseY = 0;
  let rafId = 0;
  let activatedOnce = false;

  // enable or disblae eye following
  const setEyeEnabled = (enabled) => {
    const eyes = Array.from(document.querySelectorAll(".bb-icon-eye[data-follow-mouse]"));
    for (const eye of eyes) {
      eye.dataset.followEnabled = enabled ? "true" : "false";
      eye.classList.toggle("is-active", enabled);
      if (!enabled) {
        eye.style.setProperty("--bb-eye-px", "0px");
        eye.style.setProperty("--bb-eye-py", "0px");
      }
    }
  };

  // update eye positions based on mouse coords
  const update = () => {
    rafId = 0;
    const eyes = getEyes();
    for (const eye of eyes) {
      const rect = eye.getBoundingClientRect();
      if (rect.width === 0 || rect.height === 0) continue;

      const centerX = rect.left + rect.width / 2;
      const centerY = rect.top + rect.height / 2;

      let dx = mouseX - centerX;
      let dy = mouseY - centerY;

      const max = Math.min(rect.width, rect.height) * 0.22;
      const distance = Math.hypot(dx, dy);
      if (distance > 0) {
        const scale = Math.min(1, max / distance);
        dx *= scale;
        dy *= scale;
      }

      eye.style.setProperty("--bb-eye-px", `${dx.toFixed(2)}px`);
      eye.style.setProperty("--bb-eye-py", `${dy.toFixed(2)}px`);
    }
  };

  // next animation frame
  const schedule = () => {
    if (rafId) return;
    rafId = window.requestAnimationFrame(update);
  };

  // track mouse movement
  document.addEventListener(
    "pointermove",
    (event) => {
      mouseX = event.clientX;
      mouseY = event.clientY;
      schedule();
    },
    { passive: true }
  );

  window.addEventListener("resize", schedule, { passive: true });
  window.addEventListener("scroll", schedule, { passive: true });

  // activate on interaction
  const activateEyes = () => {
    if (activatedOnce) return;
    activatedOnce = true;
    setEyeEnabled(true);
    schedule();
  };

  // activate on focus or click
  document.addEventListener("focusin", activateEyes);
  document.addEventListener("pointerdown", activateEyes, { passive: true });
  document.addEventListener("click", activateEyes, { passive: true });

  // start with eyes disabled
  setEyeEnabled(false);
  schedule();
})();
