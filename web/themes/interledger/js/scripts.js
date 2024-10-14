// Site navigation CSS classes
const siteLinksWrapper = document.querySelector("[data-nav-wrapper]");
const wideNavMinWidth = window.matchMedia("(min-width: 1060px)");
handleNavDisplayStyles(wideNavMinWidth);
wideNavMinWidth.addEventListener("change", handleNavDisplayStyles);

const siteNavToggle = document.getElementById("siteNavToggle");
const siteNavLinks = document.getElementById("siteNavLinks");
const menuIcon = document.getElementById("menuIcon");

if (document.contains(siteNavToggle)) {
  siteNavToggle.addEventListener("click", handleMobileNavToggle, false);
}

function handleMobileNavToggle(event) {
  siteLinksWrapper.classList.toggle("offscreen");
  if (siteLinksWrapper.getAttribute("class").includes("offscreen")) {
    menuIcon.classList.remove("open");
  } else {
    menuIcon.classList.add("open");
  }
}

function handleNavDisplayStyles(event) {
  if (event.matches) {
    siteLinksWrapper.classList.remove("offscreen");
  } else {
    if (siteLinksWrapper) {
      flashPrevention(siteLinksWrapper);
    }
    siteLinksWrapper.classList.add("offscreen");
  }
}

function flashPrevention(element) {
  element.setAttribute("style", "display:none");
  setTimeout(() => {
    element.removeAttribute("style");
  }, 10);
}

// Site sub-navigation toggle
const siteNav = document.querySelector(".site-nav__links");

if (document.contains(siteNav)) {
  const subLinks = document.querySelectorAll(".has-submenu > a");

  subLinks.forEach((subLink) => {
    subLink.setAttribute("aria-expanded", false);
    subLink.addEventListener("click", function (event) {
      const clickedSubLink = event.target;
      umami.track(clickedSubLink.dataset.linkLabel);
      const allSubLinks = Array.from(subLinks);
      const notClickedLinks = allSubLinks.filter(function (otherLink) {
        return otherLink !== clickedSubLink;
      });
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

  siteNav.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      resetSubMenus();
    }
  });

  document.addEventListener("click", function (event) {
    if (isClickOutside(event, subLinks)) {
      resetSubMenus();
    }
  });

  function resetSubMenus() {
    subLinks.forEach((subLink) => {
      subLink.setAttribute("aria-expanded", "false");
    });
  }
}

function isClickOutside(event, nodeList) {
  let clickedInsideTarget = false;
  Array.from(nodeList).forEach(function (element) {
    if (element.contains(event.target)) {
      clickedInsideTarget = true;
    }
  });
  return !clickedInsideTarget;
}

// Hero video controls
const videoToggle = document.getElementById("hero__video-toggle");

if (document.contains(videoToggle)) {
  const heroVideo = document.querySelector(".hero video");
  videoToggle.addEventListener("click", function () {
    console.log(this.className);
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

    button.addEventListener("click", function () {
      if (this.getAttribute("aria-expanded") === "true") {
        this.setAttribute("aria-expanded", "false");
      } else {
        this.setAttribute("aria-expanded", "true");
      }
    });
  });
}
