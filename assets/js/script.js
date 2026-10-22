document.addEventListener("DOMContentLoaded", function () {
  const themeBtn = document.getElementById("themeToggleBtn");
  const sunIcon = document.getElementById("themeIconSun");
  const moonIcon = document.getElementById("themeIconMoon");
  const themeText = document.getElementById("themeToggleText");

  function updateThemeUI(theme) {
    if (!themeBtn) return;
    const themeColorMeta = document.querySelector('meta[name="theme-color"]');
    if (themeColorMeta) {
      themeColorMeta.setAttribute(
        "content",
        theme === "dark" ? "#11161b" : "#f4f6f7",
      );
    }
    if (theme === "dark") {
      sunIcon.style.display = "inline-block";
      moonIcon.style.display = "none";
      themeText.textContent = "Light";
    } else {
      sunIcon.style.display = "none";
      moonIcon.style.display = "inline-block";
      themeText.textContent = "Dark";
    }
  }

  const currentTheme =
    document.documentElement.getAttribute("data-theme") || "light";
  updateThemeUI(currentTheme);

  if (themeBtn) {
    themeBtn.addEventListener("click", function () {
      const current =
        document.documentElement.getAttribute("data-theme") || "light";
      const nextTheme = current === "dark" ? "light" : "dark";

      document.documentElement.setAttribute("data-theme", nextTheme);
      try {
        localStorage.setItem("portfolio_theme", nextTheme);
      } catch (error) {}
      updateThemeUI(nextTheme);
    });
  }

  const navToggle = document.getElementById("mobileNavToggle");
  const navClose = document.getElementById("mobileNavClose");
  const navigation = document.getElementById("primary-navigation");
  if (navToggle && navigation) {
    navToggle.addEventListener("click", function () {
      const isOpen = document.body.classList.toggle("nav-open");
      navToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });

    navigation.addEventListener("click", function (event) {
      if (event.target.closest("a")) {
        document.body.classList.remove("nav-open");
        navToggle.setAttribute("aria-expanded", "false");
      }
    });
    if (navClose) {
      navClose.addEventListener("click", function () {
        document.body.classList.remove("nav-open");
        navToggle.setAttribute("aria-expanded", "false");
        navToggle.focus();
      });
    }
    document.addEventListener("keydown", function (event) {
      if (
        event.key === "Escape" &&
        document.body.classList.contains("nav-open")
      ) {
        document.body.classList.remove("nav-open");
        navToggle.setAttribute("aria-expanded", "false");
        navToggle.focus();
      }
    });
  }

  function initSearchableSelects() {
    document
      .querySelectorAll(".searchable-select")
      .forEach(function (selectContainer) {
        const hiddenInput = selectContainer.querySelector(
          'input[type="hidden"]',
        );
        const control = selectContainer.querySelector(
          ".searchable-select-control",
        );
        const input = control.querySelector('input[type="text"]');
        const placeholder = control.querySelector(
          ".searchable-select-placeholder",
        );
        const indicator = control.querySelector(".searchable-select-indicator");
        const menu = selectContainer.querySelector(".searchable-select-menu");

        let selectedValue = hiddenInput.value;
        let selectedText = placeholder.textContent;

        if (
          selectedValue &&
          selectedText === selectContainer.getAttribute("data-placeholder")
        ) {
          const option = selectContainer.querySelector(
            `.searchable-select-option[data-value="${selectedValue}"]`,
          );
          if (option) {
            selectedText = option.textContent.trim();
            placeholder.textContent = selectedText;
          }
        }

        function toggleMenu(open) {
          if (open === undefined) {
            open =
              !menu.hasAttribute("aria-hidden") ||
              menu.getAttribute("aria-hidden") === "false";
          }

          if (open) {
            menu.setAttribute("aria-hidden", "false");
            control.classList.add("is-open");
            input.focus();
          } else {
            menu.setAttribute("aria-hidden", "true");
            control.classList.remove("is-open");
          }
        }

        function handleClickOutside(event) {
          if (!selectContainer.contains(event.target)) {
            toggleMenu(false);
          }
        }

        function selectOption(value, text) {
          hiddenInput.value = value;
          placeholder.textContent = text;

          const options = menu.querySelectorAll(".searchable-select-option");
          options.forEach((option) => {
            const isSelected = option.getAttribute("data-value") === value;
            option.setAttribute("aria-selected", isSelected);
            if (isSelected) {
              option.classList.add("is-selected");
            } else {
              option.classList.remove("is-selected");
            }
          });

          toggleMenu(false);
        }

        function filterOptions(searchTerm) {
          const options = menu.querySelectorAll(".searchable-select-option");
          let hasVisibleOptions = false;

          options.forEach((option) => {
            const text = option.textContent.toLowerCase();
            const shouldShow =
              text.includes(searchTerm.toLowerCase()) || searchTerm === "";

            if (shouldShow) {
              option.style.display = "";
              hasVisibleOptions = true;
            } else {
              option.style.display = "none";
            }
          });

          const emptyMsg = menu.querySelector(".searchable-select-empty");
          if (emptyMsg) {
            if (!hasVisibleOptions && searchTerm !== "") {
              emptyMsg.style.display = "";
            } else {
              emptyMsg.style.display = "none";
            }
          }

          if (searchTerm === "") {
            const firstOption = menu.querySelector(
              '.searchable-select-option:not([style*="display: none"])',
            );
            if (firstOption) {
              firstOption.classList.add("is-highlighted");
            }
          }
        }

        function highlightOption(option) {
          menu.querySelectorAll(".searchable-select-option").forEach((opt) => {
            opt.classList.remove("is-highlighted");
          });

          if (option) {
            option.classList.add("is-highlighted");

            const menuHeight = menu.clientHeight;
            const optionRect = option.getBoundingClientRect();
            const menuRect = menu.getBoundingClientRect();

            if (optionRect.top < menuRect.top) {
              menu.scrollTop = optionRect.top - menuRect.top + menu.scrollTop;
            } else if (optionRect.bottom > menuRect.bottom) {
              menu.scrollTop =
                optionRect.bottom - menuRect.bottom + menu.scrollTop;
            }
          }
        }

        control.addEventListener("click", function () {
          if (
            menu.hasAttribute("aria-hidden") &&
            menu.getAttribute("aria-hidden") === "true"
          ) {
            toggleMenu(true);
          } else {
            toggleMenu(false);
          }
        });

        input.addEventListener("input", function () {
          filterOptions(this.value);

          const firstOption = menu.querySelector(
            '.searchable-select-option:not([style*="display: none"])',
          );
          highlightOption(firstOption);
        });

        input.addEventListener("keydown", function (event) {
          switch (event.key) {
            case "Escape":
              toggleMenu(false);
              break;
            case "Tab":
              toggleMenu(false);
              break;
            case "ArrowDown":
              event.preventDefault();
              if (
                !menu.hasAttribute("aria-hidden") ||
                menu.getAttribute("aria-hidden") === "false"
              ) {
                const options = menu.querySelectorAll(
                  '.searchable-select-option:not([style*="display: none"])',
                );
                let currentIndex = -1;
                options.forEach((option, index) => {
                  if (option.classList.contains("is-highlighted")) {
                    currentIndex = index;
                  }
                });

                const nextIndex = (currentIndex + 1) % options.length;
                if (options[nextIndex]) {
                  highlightOption(options[nextIndex]);
                }
              } else {
                toggleMenu(true);
              }
              break;
            case "ArrowUp":
              event.preventDefault();
              if (
                !menu.hasAttribute("aria-hidden") ||
                menu.getAttribute("aria-hidden") === "false"
              ) {
                const options = menu.querySelectorAll(
                  '.searchable-select-option:not([style*="display: none"])',
                );
                let currentIndex = -1;
                options.forEach((option, index) => {
                  if (option.classList.contains("is-highlighted")) {
                    currentIndex = index;
                  }
                });

                let prevIndex = currentIndex - 1;
                if (prevIndex < 0) {
                  prevIndex = options.length - 1;
                }

                if (options[prevIndex]) {
                  highlightOption(options[prevIndex]);
                }
              } else {
                toggleMenu(true);
              }
              break;
            case "Enter":
              event.preventDefault();
              if (
                !menu.hasAttribute("aria-hidden") ||
                menu.getAttribute("aria-hidden") === "false"
              ) {
                const highlightedOption = menu.querySelector(
                  ".searchable-select-option.is-highlighted",
                );
                if (highlightedOption) {
                  const value = highlightedOption.getAttribute("data-value");
                  const text = highlightedOption.textContent.trim();
                  selectOption(value, text);
                }
              }
              break;
          }
        });

        menu.addEventListener("click", function (event) {
          const option = event.target.closest(".searchable-select-option");
          if (option && !option.hasAttribute("aria-disabled")) {
            const value = option.getAttribute("data-value");
            const text = option.textContent.trim();
            selectOption(value, text);
          }
        });

        document.addEventListener("click", handleClickOutside);
      });
  }

  initSearchableSelects();
});
