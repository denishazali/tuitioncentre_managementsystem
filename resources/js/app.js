import "./bootstrap";

document.addEventListener("DOMContentLoaded", () => {
  const navigationToggle = document.querySelector("[data-navigation-toggle]");

  const navigation = document.querySelector("[data-navigation]");

  if (navigationToggle && navigation) {
    navigationToggle.addEventListener("click", () => {
      const isOpen = navigation.classList.toggle("open");

      navigationToggle.setAttribute("aria-expanded", String(isOpen));
    });

    navigation.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", () => {
        navigation.classList.remove("open");

        navigationToggle.setAttribute("aria-expanded", "false");
      });
    });

    document.addEventListener("click", (event) => {
      if (
        !navigation.contains(event.target) &&
        !navigationToggle.contains(event.target)
      ) {
        navigation.classList.remove("open");

        navigationToggle.setAttribute("aria-expanded", "false");
      }
    });
  }

  const revealElements = document.querySelectorAll(".reveal");

  if ("IntersectionObserver" in window) {
    const observer = new IntersectionObserver(
      (entries, currentObserver) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) {
            return;
          }

          entry.target.classList.add("visible");
          currentObserver.unobserve(entry.target);
        });
      },
      {
        threshold: 0.1,
      },
    );

    revealElements.forEach((element) => {
      observer.observe(element);
    });
  } else {
    revealElements.forEach((element) => {
      element.classList.add("visible");
    });
  }
});
