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

  function initializeRelatedCards(component) {
    var viewport = component.querySelector('[data-related-cards-viewport]');
    var list = component.querySelector('.related-cards__list');
    var controls = component.querySelector('[data-related-cards-controls]');
    var prevButton = component.querySelector('[data-related-cards-prev]');
    var nextButton = component.querySelector('[data-related-cards-next]');
    var items = list ? list.querySelectorAll('.related-cards__item') : [];
    var activeIndex = 0;

    if (!viewport || !list || !controls || !prevButton || !nextButton || !items.length) {
      return;
    }

    function hasOverflow() {
      return viewport.scrollWidth - viewport.clientWidth > 2;
    }

    function setActiveState(index) {
      for (var i = 0; i < items.length; i++) {
        var item = items[i];
        var link = item.querySelector('.related-cards__link');
        var isActive = i === index;

        item.classList.toggle('is-active', isActive);

        if (link) {
          if (isActive) {
            link.setAttribute('aria-current', 'true');
          } else {
            link.removeAttribute('aria-current');
          }
        }
      }
    }

    function ensureActiveVisible() {
      if (!hasOverflow()) {
        if (viewport.scrollLeft !== 0) {
          viewport.scrollTo({ left: 0, behavior: 'auto' });
        }

        return;
      }

      var activeItem = items[activeIndex];
      if (!activeItem) {
        return;
      }

      var viewportRect = viewport.getBoundingClientRect();
      var itemRect = activeItem.getBoundingClientRect();
      var padding = Math.min(32, Math.max(16, viewport.clientWidth * 0.08));
      var minVisibleLeft = viewportRect.left + padding;
      var maxVisibleRight = viewportRect.right - padding;
      var targetLeft = viewport.scrollLeft;

      if (itemRect.left < minVisibleLeft) {
        targetLeft -= minVisibleLeft - itemRect.left;
      } else if (itemRect.right > maxVisibleRight) {
        targetLeft += itemRect.right - maxVisibleRight;
      }

      var maxScrollLeft = viewport.scrollWidth - viewport.clientWidth;
      targetLeft = Math.max(0, Math.min(maxScrollLeft, targetLeft));

      if (Math.abs(targetLeft - viewport.scrollLeft) > 1) {
        viewport.scrollTo({
          left: targetLeft,
          behavior: 'smooth'
        });
      }
    }

    function updateControls() {
      var maxScrollLeft = viewport.scrollWidth - viewport.clientWidth;
      var overflow = maxScrollLeft > 2;

      controls.hidden = false;
      prevButton.disabled = activeIndex <= 0;
      nextButton.disabled = activeIndex >= items.length - 1;

      component.classList.toggle('is-overflowing', overflow);
    }

    function moveActive(direction) {
      var nextIndex = Math.max(0, Math.min(items.length - 1, activeIndex + direction));

      if (nextIndex === activeIndex) {
        updateControls();
        return;
      }

      activeIndex = nextIndex;
      setActiveState(activeIndex);
      updateControls();
      ensureActiveVisible();
    }

    prevButton.addEventListener('click', function () {
      moveActive(-1);
    });

    nextButton.addEventListener('click', function () {
      moveActive(1);
    });

    list.addEventListener('click', function (event) {
      var clickedItem = event.target.closest('.related-cards__item');
      if (!clickedItem) {
        return;
      }

      for (var i = 0; i < items.length; i++) {
        if (items[i] === clickedItem) {
          activeIndex = i;
          setActiveState(activeIndex);
          updateControls();
          ensureActiveVisible();
          break;
        }
      }
    });

    list.addEventListener('focusin', function (event) {
      var focusedItem = event.target.closest('.related-cards__item');
      if (!focusedItem) {
        return;
      }

      for (var i = 0; i < items.length; i++) {
        if (items[i] === focusedItem) {
          activeIndex = i;
          setActiveState(activeIndex);
          updateControls();
          ensureActiveVisible();
          break;
        }
      }
    });

    window.addEventListener(
      'resize',
      debounce(function () {
        updateControls();
        ensureActiveVisible();
      }, 120)
    );

    setActiveState(activeIndex);
    updateControls();
    ensureActiveVisible();
  }

  document.addEventListener('DOMContentLoaded', function () {
    var components = document.querySelectorAll('[data-related-cards]');
    for (var i = 0; i < components.length; i++) {
      initializeRelatedCards(components[i]);
    }
  });
})();
