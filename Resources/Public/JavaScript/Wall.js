(() => {
  const CONTAINER_SELECTOR = '.socialwall_container';
  const ENTRY_SELECTOR = '.checkin-wrapper';
  const LOAD_MORE_SELECTOR = '.wallsio-load-more-button';

  /**
   * Show the next entries of a wall. The first entries are shown on initialization.
   */
  class Wall {
    constructor(container) {
      this.container = container;
      this.entries = Array.from(container.querySelectorAll(ENTRY_SELECTOR));
      this.entriesToShow = parseInt(container.dataset.entriesToShow ?? '', 10) || 8;
      this.visibleEntries = 0;

      // First of all hide all entries
      this.entries.forEach((entry) => {
        entry.style.display = 'none';
      });

      this.showNextEntries();
    }

    showNextEntries() {
      const until = this.visibleEntries + this.entriesToShow;
      for (let index = this.visibleEntries; index < until && index < this.entries.length; index++) {
        this.entries[index].style.display = 'block';
        this.visibleEntries++;
      }

      this.relayout();
      this.hideLoadMoreButtonIfFinished();
    }

    /**
     * Let integrators re-layout their grid (e.g. Masonry) after new entries were made visible
     */
    relayout() {
      const grid = this.container.querySelector('.masonrygrid');
      if (grid !== null && typeof window.Masonry === 'function' && typeof window.Masonry.data === 'function') {
        window.Masonry.data(grid)?.layout();
      }

      this.container.dispatchEvent(new CustomEvent('walls-io-proxy:entries-shown', { bubbles: true }));
    }

    hideLoadMoreButtonIfFinished() {
      if (this.visibleEntries < this.entries.length) {
        return;
      }

      this.container.querySelectorAll(LOAD_MORE_SELECTOR).forEach((button) => {
        button.style.display = 'none';
      });
    }
  }

  const initialize = () => {
    document.querySelectorAll(CONTAINER_SELECTOR).forEach((container) => {
      const wall = new Wall(container);
      container.querySelectorAll(LOAD_MORE_SELECTOR).forEach((button) => {
        button.addEventListener('click', () => wall.showNextEntries());
      });
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize);
  } else {
    initialize();
  }
})();
