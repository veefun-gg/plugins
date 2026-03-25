(function () {
  var OVERFLOW_TOLERANCE = 2;

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

  function getItemIndex(items, targetItem) {
    for (var i = 0; i < items.length; i++) {
      if (items[i] === targetItem) {
        return i;
      }
    }

    return -1;
  }

  function initializeRelatedCards(component) {
    var viewport = component.querySelector('[data-related-cards-viewport]');
    var list = component.querySelector('.related-cards__list');
    var controls = component.querySelector('[data-related-cards-controls]');
    var prevButton = component.querySelector('[data-related-cards-prev]');
    var nextButton = component.querySelector('[data-related-cards-next]');
    var pagePrevButton = component.querySelector('[data-related-cards-page-prev]');
    var pageNextButton = component.querySelector('[data-related-cards-page-next]');
    var items = list ? list.querySelectorAll('.related-cards__item') : [];
    var activeIndex = 0;

    if (
      !viewport ||
      !list ||
      !controls ||
      !prevButton ||
      !nextButton ||
      !pagePrevButton ||
      !pageNextButton ||
      !items.length
    ) {
      return;
    }

    function getScrollBehavior() {
      if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return 'auto';
      }

      return 'smooth';
    }

    function getMaxScrollLeft() {
      return Math.max(0, viewport.scrollWidth - viewport.clientWidth);
    }

    function hasOverflow() {
      return getMaxScrollLeft() > OVERFLOW_TOLERANCE;
    }

    function getPageStep() {
      var styles = window.getComputedStyle(list);
      var gap = parseFloat(styles.columnGap || styles.gap || 0);
      var firstItem = items[0];
      var estimatedVisible = 1;
      var maxStep = items.length - 1;

      if (maxStep <= 0) {
        return 1;
      }

      if (firstItem) {
        var itemWidth = firstItem.getBoundingClientRect().width + gap;
        estimatedVisible = Math.max(1, Math.floor((viewport.clientWidth + gap) / Math.max(itemWidth, 1)));
      }

      return Math.min(maxStep, Math.max(4, Math.min(5, estimatedVisible)));
    }

    function setActiveState(index) {
      for (var i = 0; i < items.length; i++) {
        var item = items[i];
        var link = item.querySelector('.related-cards__link');
        var isActive = i === index;

        item.classList.toggle('is-active', isActive);

        if (link) {
          if (isActive) {
            link.setAttribute('aria-current', 'page');
          } else {
            link.removeAttribute('aria-current');
          }
        }
      }
    }

    function focusActiveItem() {
      var activeItem = items[activeIndex];
      var link = activeItem ? activeItem.querySelector('.related-cards__link') : null;

      if (!link) {
        return;
      }

      try {
        link.focus({ preventScroll: true });
      } catch (error) {
        link.focus();
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

      var maxScrollLeft = getMaxScrollLeft();
      targetLeft = Math.max(0, Math.min(maxScrollLeft, targetLeft));

      if (Math.abs(targetLeft - viewport.scrollLeft) > 1) {
        viewport.scrollTo({
          left: targetLeft,
          behavior: getScrollBehavior()
        });
      }
    }

    function updateControls() {
      var overflow = hasOverflow();

      controls.hidden = false;
      prevButton.disabled = activeIndex <= 0;
      nextButton.disabled = activeIndex >= items.length - 1;
      pagePrevButton.hidden = !overflow;
      pageNextButton.hidden = !overflow;
      pagePrevButton.disabled = !overflow || activeIndex <= 0;
      pageNextButton.disabled = !overflow || activeIndex >= items.length - 1;
    }

    function setActiveIndex(index, shouldFocus) {
      var nextIndex = Math.max(0, Math.min(items.length - 1, index));

      if (nextIndex === activeIndex) {
        updateControls();
        ensureActiveVisible();

        if (shouldFocus) {
          focusActiveItem();
        }

        return;
      }

      activeIndex = nextIndex;
      setActiveState(activeIndex);
      updateControls();
      ensureActiveVisible();

      if (shouldFocus) {
        focusActiveItem();
      }
    }

    function moveActive(direction, step, shouldFocus) {
      var distance = typeof step === 'number' ? step : 1;
      setActiveIndex(activeIndex + direction * distance, shouldFocus);
    }

    function activateItem(target) {
      var item = target ? target.closest('.related-cards__item') : null;
      var index = item ? getItemIndex(items, item) : -1;

      if (index < 0) {
        return;
      }

      setActiveIndex(index, false);
    }

    function handleKeydown(event) {
      var key = event.key;
      var navigationTarget =
        event.target.closest('[data-related-cards-viewport]') ||
        event.target.closest('.related-cards__item');

      if (
        !navigationTarget ||
        event.altKey ||
        event.ctrlKey ||
        event.metaKey ||
        event.target.closest('.related-cards__control')
      ) {
        return;
      }

      if (key === 'ArrowLeft') {
        event.preventDefault();
        moveActive(-1, 1, true);
        return;
      }

      if (key === 'ArrowRight') {
        event.preventDefault();
        moveActive(1, 1, true);
        return;
      }

      if (key === 'Home') {
        event.preventDefault();
        setActiveIndex(0, true);
        return;
      }

      if (key === 'End') {
        event.preventDefault();
        setActiveIndex(items.length - 1, true);
        return;
      }

      if (key === 'PageUp' && hasOverflow()) {
        event.preventDefault();
        moveActive(-1, getPageStep(), true);
        return;
      }

      if (key === 'PageDown' && hasOverflow()) {
        event.preventDefault();
        moveActive(1, getPageStep(), true);
      }
    }

    prevButton.addEventListener('click', function () {
      moveActive(-1);
    });

    nextButton.addEventListener('click', function () {
      moveActive(1);
    });

    pagePrevButton.addEventListener('click', function () {
      moveActive(-1, getPageStep());
    });

    pageNextButton.addEventListener('click', function () {
      moveActive(1, getPageStep());
    });

    list.addEventListener('click', function (event) {
      activateItem(event.target);
    });

    list.addEventListener('focusin', function (event) {
      activateItem(event.target);
    });

    component.addEventListener('keydown', handleKeydown);

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
