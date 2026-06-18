"use client";

import { useEffect } from "react";

const FADE_SELECTOR = ".fade-in, .fade-in-left, .fade-in-right, .slide-up";

function startCounter(numberEl: HTMLElement, target: number) {
  let start: number | null = null;
  const duration = 2000;

  function easeOutQuart(t: number) {
    return 1 - Math.pow(1 - t, 4);
  }

  function update(timestamp: number) {
    if (start === null) start = timestamp;
    const elapsed = timestamp - start;
    const progress = Math.min(elapsed / duration, 1);
    const current = Math.round(easeOutQuart(progress) * target);
    
    // Formatting for Paraguay (uses dot as thousands separator)
    numberEl.textContent = current.toLocaleString("es-PY");
    
    if (progress < 1) {
      requestAnimationFrame(update);
    }
  }

  requestAnimationFrame(update);
}

export default function ScrollAnimations() {
  useEffect(() => {
    const intersectionObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("visible");
            intersectionObserver.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.05 }
    );

    const observeAll = () => {
      document
        .querySelectorAll(FADE_SELECTOR)
        .forEach((el) => intersectionObserver.observe(el));
    };

    // Separate observer for counters
    const counterObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const el = entry.target as HTMLElement;
            const targetStr = el.dataset.stat;
            // Robust selector for stat numbers (works with CSS modules)
            const numberEl = el.querySelector('[class*="statNumber"]') as HTMLElement | null;
            
            if (targetStr && numberEl) {
              // Prevent multiple animations if MutationObserver re-triggers
              el.removeAttribute("data-stat");
              startCounter(numberEl, parseInt(targetStr, 10));
              counterObserver.unobserve(el);
            }
          }
        });
      },
      { threshold: 0.1 }
    );

    const observeStats = () => {
      document.querySelectorAll("[data-stat]").forEach((el) => {
        counterObserver.observe(el);
      });
    };

    // Initial observation
    observeAll();
    observeStats();

    // Re-observe when DOM changes (e.g., client-side navigation)
    const mutationObserver = new MutationObserver(() => {
      observeAll();
      observeStats();
    });
    
    mutationObserver.observe(document.body, {
      childList: true,
      subtree: true,
    });

    return () => {
      intersectionObserver.disconnect();
      mutationObserver.disconnect();
      counterObserver.disconnect();
    };
  }, []);

  return null;
}
