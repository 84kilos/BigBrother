(() => {
  const THEME_KEY = "bb-theme";
  const DYSTOPIA = "dystopia";

  const applyTheme = (theme) => {
    if (theme === DYSTOPIA) {
      document.documentElement.dataset.theme = DYSTOPIA;
    } else {
      delete document.documentElement.dataset.theme;
    }
  };

  const getSavedTheme = () => {
    try {
      return localStorage.getItem(THEME_KEY);
    } catch {
      return null;
    }
  };

  const saveTheme = (theme) => {
    try {
      localStorage.setItem(THEME_KEY, theme);
    } catch {
      // Ignore storage failures and still apply in-memory theme changes.
    }
  };

  applyTheme(getSavedTheme());

  const syncToggle = () => {
    const themeToggle = document.getElementById("themeToggle");
    if (!themeToggle) return;

    const currentTheme = getSavedTheme();
    themeToggle.checked = currentTheme === DYSTOPIA;

    themeToggle.addEventListener("change", () => {
      const nextTheme = themeToggle.checked ? DYSTOPIA : "blue";
      saveTheme(nextTheme);
      applyTheme(nextTheme);
    });
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", syncToggle, { once: true });
  } else {
    syncToggle();
  }
})();
