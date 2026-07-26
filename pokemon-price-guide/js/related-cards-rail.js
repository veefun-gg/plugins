(function () {
  var OVERFLOW_TOLERANCE = 2;
  var STORAGE_KEY = 'ptpRelatedCardsView';
  var VALID_VIEWS = ['slider', 'grid', 'table'];

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

  function isValidView(view) {
    return VALID_VIEWS.indexOf(view) !== -1;
  }

  function getStoredView() {
    try {
      var storedView = window.sessionStorage.getItem(STORAGE_KEY);
      return isValidView(storedView) ? storedView : '';
    } catch (error) {
      return '';
    }
  }

  function storeView(view) {
    try {
      window.sessionStorage.setItem(STORAGE_KEY, view);
    } catch (error) {
      // Storage can be unavailable or blocked; the selected view still works.
    }
  }

  function initializeRelatedCards(component) {
    var viewport = component.querySelector('[data-related-cards-viewport]');
    var list = component.querySelector('.related-cards__list');
    var viewSwitcher = component.querySelector('[data-related-cards-view-switcher]');
    var viewButtons = component.querySelectorAll('[data-related-cards-view-button]');
    var controls = component.querySelector('[data-related-cards-controls]');
    var prevButton = component.querySelector('[data-related-cards-prev]');
    var nextButton = component.querySelector('[data-related-cards-next]');
    var items = list ? list.querySelectorAll('.related-cards__item') : [];
    var currentItem = list ? list.querySelector('.related-cards__link[aria-current="page"]') : null;
    var currentRow = currentItem ? currentItem.closest('.related-cards__item') : null;
    var activeIndex = currentRow ? getItemIndex(items, currentRow) : 0;
    var currentView = 'slider';

    if (
      !viewport ||
      !list ||
      !viewSwitcher ||
      !viewButtons.length ||
      !controls ||
      !prevButton ||
      !nextButton ||
      !items.length
    ) {
      return;
    }

    if (component.getAttribute('data-related-cards-ready') === 'true') {
      return;
    }

    component.setAttribute('data-related-cards-ready', 'true');

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
      return currentView === 'slider' && items.length > 1 && getMaxScrollLeft() > OVERFLOW_TOLERANCE;
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

    function scrollRail(direction) {
      if (!hasOverflow()) {
        return;
      }

      var styles = window.getComputedStyle(list);
      var gap = parseFloat(styles.columnGap || styles.gap || 0);
      var firstItem = items[0];
      var itemStep = firstItem ? firstItem.getBoundingClientRect().width + gap : viewport.clientWidth;
      var targetLeft = viewport.scrollLeft + direction * Math.max(itemStep, 1);

      targetLeft = Math.max(0, Math.min(getMaxScrollLeft(), targetLeft));

      viewport.scrollTo({
        left: targetLeft,
        behavior: getScrollBehavior()
      });
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
      if (currentView !== 'slider') {
        return;
      }

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
      var maxScrollLeft = getMaxScrollLeft();
      var atStart = viewport.scrollLeft <= OVERFLOW_TOLERANCE;
      var atEnd = viewport.scrollLeft >= maxScrollLeft - OVERFLOW_TOLERANCE;

      prevButton.hidden = !overflow || atStart;
      nextButton.hidden = !overflow || atEnd;
      prevButton.disabled = !overflow || atStart;
      nextButton.disabled = !overflow || atEnd;
      controls.hidden = prevButton.hidden && nextButton.hidden;
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
      var item = target && target.closest ? target.closest('.related-cards__item') : null;
      var index = item ? getItemIndex(items, item) : -1;

      if (index < 0) {
        return;
      }

      setActiveIndex(index, false);
    }

    function setView(view, shouldStore) {
      if (!isValidView(view)) {
        view = 'slider';
      }

      currentView = view;
      component.setAttribute('data-related-cards-view', currentView);

      for (var i = 0; i < viewButtons.length; i++) {
        var isSelected = viewButtons[i].getAttribute('data-related-cards-view-button') === currentView;
        viewButtons[i].setAttribute('aria-pressed', isSelected ? 'true' : 'false');
      }

      if (currentView === 'slider') {
        viewport.setAttribute('tabindex', '0');
      } else {
        viewport.removeAttribute('tabindex');
        viewport.scrollLeft = 0;
      }

      if (shouldStore) {
        storeView(currentView);
      }

      window.requestAnimationFrame(function () {
        if (currentView === 'slider') {
          ensureActiveVisible();
        }

        updateControls();
      });
    }

    function handleKeydown(event) {
      var key = event.key;
      var eventTarget = event.target;
      var navigationTarget =
        eventTarget && eventTarget.closest
          ? eventTarget.closest('[data-related-cards-viewport]') ||
            eventTarget.closest('.related-cards__item')
          : null;

      if (
        currentView !== 'slider' ||
        !navigationTarget ||
        event.altKey ||
        event.ctrlKey ||
        event.metaKey ||
        (eventTarget.closest && eventTarget.closest('button'))
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
      scrollRail(-1);
    });

    nextButton.addEventListener('click', function () {
      scrollRail(1);
    });

    list.addEventListener('click', function (event) {
      activateItem(event.target);
    });

    list.addEventListener('focusin', function (event) {
      activateItem(event.target);
    });

    component.addEventListener('keydown', handleKeydown);

    viewport.addEventListener(
      'scroll',
      debounce(function () {
        updateControls();
      }, 50),
      { passive: true }
    );

    for (var i = 0; i < viewButtons.length; i++) {
      viewButtons[i].addEventListener('click', function (event) {
        setView(event.currentTarget.getAttribute('data-related-cards-view-button'), true);
      });
    }

    var images = component.querySelectorAll('.related-cards__media img');
    for (var imageIndex = 0; imageIndex < images.length; imageIndex++) {
      (function (image) {
        function markMissing() {
          image.classList.add('is-missing');
          image.setAttribute('aria-hidden', 'true');
        }

        image.addEventListener('error', markMissing);

        if (image.complete && image.naturalWidth === 0) {
          markMissing();
        }
      })(images[imageIndex]);
    }

    window.addEventListener(
      'resize',
      debounce(function () {
        updateControls();
        ensureActiveVisible();
      }, 120)
    );

    viewSwitcher.hidden = false;
    setView(getStoredView() || 'slider', false);
  }

  document.addEventListener('DOMContentLoaded', function () {
    var components = document.querySelectorAll('[data-related-cards]');
    for (var i = 0; i < components.length; i++) {
      initializeRelatedCards(components[i]);
    }
  });
})();
