(function () {
  function debounce(callback, delay) {
    var timeoutId;

    return function () {
      var args = arguments;
      clearTimeout(timeoutId);
      timeoutId = setTimeout(function () {
        callback.apply(null, args);
      }, delay);
    };
  }

  function getScrollAmount(viewport, list) {
    var firstItem = list.querySelector('.related-cards__item');
    if (!firstItem) {
      return Math.max(viewport.clientWidth * 0.85, 200);
    }

    var styles = window.getComputedStyle(list);
    var gap = parseFloat(styles.columnGap || styles.gap || 0);
    var itemWidth = firstItem.getBoundingClientRect().width + gap;
    var visibleItems = Math.max(1, Math.floor(viewport.clientWidth / Math.max(itemWidth, 1)));

    return Math.max(itemWidth * visibleItems, viewport.clientWidth * 0.85);
  }

  function initializeRelatedCards(component) {
    var viewport = component.querySelector('[data-related-cards-viewport]');
    var list = component.querySelector('.related-cards__list');
    var controls = component.querySelector('[data-related-cards-controls]');
    var prevButton = component.querySelector('[data-related-cards-prev]');
    var nextButton = component.querySelector('[data-related-cards-next]');

    if (!viewport || !list || !controls || !prevButton || !nextButton) {
      return;
    }

    function updateControls() {
      var maxScrollLeft = viewport.scrollWidth - viewport.clientWidth;
      var hasOverflow = maxScrollLeft > 2;
      var atStart = viewport.scrollLeft <= 2;
      var atEnd = viewport.scrollLeft >= maxScrollLeft - 2;

      controls.hidden = !hasOverflow;
      prevButton.disabled = !hasOverflow || atStart;
      nextButton.disabled = !hasOverflow || atEnd;
    }

    function scrollRail(direction) {
      viewport.scrollBy({
        left: getScrollAmount(viewport, list) * direction,
        behavior: 'smooth'
      });
    }

    prevButton.addEventListener('click', function () {
      scrollRail(-1);
    });

    nextButton.addEventListener('click', function () {
      scrollRail(1);
    });

    viewport.addEventListener('scroll', updateControls, { passive: true });
    window.addEventListener('resize', debounce(updateControls, 120));

    updateControls();
  }

  document.addEventListener('DOMContentLoaded', function () {
    var components = document.querySelectorAll('[data-related-cards]');
    for (var i = 0; i < components.length; i++) {
      initializeRelatedCards(components[i]);
    }
  });
})();
