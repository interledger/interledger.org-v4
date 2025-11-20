// Function declarations
function flashPrevention(element) {
  element.setAttribute("style", "display:none");
  setTimeout(() => {
    element.removeAttribute("style");
  }, 10);
}

function handleMobileNavToggle() {
  const siteLinksWrapper = document.querySelector("[data-nav-wrapper]");
  const menuIcon = document.getElementById("menuIcon");
  siteLinksWrapper.classList.toggle("offscreen");
  if (siteLinksWrapper.getAttribute("class").includes("offscreen")) {
    menuIcon.classList.remove("open");
  } else {
    menuIcon.classList.add("open");
  }
}

function handleNavDisplayStyles(event) {
  const siteLinksWrapper = document.querySelector("[data-nav-wrapper]");
  if (event.matches) {
    siteLinksWrapper.classList.remove("offscreen");
  } else {
    if (siteLinksWrapper) {
      flashPrevention(siteLinksWrapper);
    }
    siteLinksWrapper.classList.add("offscreen");
  }
}

function resetSubMenus(subLinks) {
  subLinks.forEach((subLink) => {
    subLink.setAttribute("aria-expanded", "false");
  });
}

function isClickOutside(event, nodeList) {
  let clickedInsideTarget = false;
  Array.from(nodeList).forEach((element) => {
    if (element.contains(event.target)) {
      clickedInsideTarget = true;
    }
  });
  return !clickedInsideTarget;
}

// Site navigation CSS classes
const wideNavMinWidth = window.matchMedia("(min-width: 1160px)");
handleNavDisplayStyles(wideNavMinWidth);
wideNavMinWidth.addEventListener("change", handleNavDisplayStyles);

const siteNavToggle = document.getElementById("siteNavToggle");

if (document.contains(siteNavToggle)) {
  siteNavToggle.addEventListener("click", handleMobileNavToggle, false);
}

// Site sub-navigation toggle
const siteNav = document.querySelector(".site-nav__links");

if (document.contains(siteNav)) {
  const subLinks = document.querySelectorAll(".has-submenu > a");

  subLinks.forEach((subLink) => {
    subLink.setAttribute("aria-expanded", false);
    subLink.addEventListener("click", (event) => {
      const clickedSubLink = event.target;
      // eslint-disable-next-line no-undef
      umami.track(clickedSubLink.dataset.linkLabel);
      const allSubLinks = Array.from(subLinks);
      const notClickedLinks = allSubLinks.filter((otherLink) => otherLink !== clickedSubLink);
      notClickedLinks.forEach((link) => {
        link.setAttribute("aria-expanded", false);
      });
      if (clickedSubLink.getAttribute("aria-expanded") === "true") {
        clickedSubLink.setAttribute("aria-expanded", "false");
      } else {
        clickedSubLink.setAttribute("aria-expanded", "true");
      }
      event.preventDefault();
      return false;
    });
  });

  siteNav.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      resetSubMenus(subLinks);
    }
  });

  document.addEventListener("click", (event) => {
    if (isClickOutside(event, subLinks)) {
      resetSubMenus(subLinks);
    }
  });
}

// Hero video controls
const videoToggle = document.getElementById("hero__video-toggle");

if (document.contains(videoToggle)) {
  const heroVideo = document.querySelector(".hero video");
  videoToggle.addEventListener("click", function handleVideoToggle() {
    if (videoToggle.className === "is-playing") {
      heroVideo.pause();
      this.classList.toggle("is-playing");
    } else {
      heroVideo.play();
      this.classList.toggle("is-playing");
    }
  });
}

// FAQ accordion effect
const faq = document.querySelector(".faq");

if (document.contains(faq)) {
  const buttons = document.querySelectorAll("[data-faq-toggle]");
  buttons.forEach((button) => {
    button.setAttribute("aria-expanded", false);

    button.addEventListener("click", function handleFaqToggle() {
      if (this.getAttribute("aria-expanded") === "true") {
        this.setAttribute("aria-expanded", "false");
      } else {
        this.setAttribute("aria-expanded", "true");
      }
    });
  });
}
