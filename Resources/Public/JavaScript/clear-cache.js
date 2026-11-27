import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import Notification from '@typo3/backend/notification.js';

const BUTTON_SELECTOR = '[data-walls-io-proxy-clear-cache]';

/**
 * The server returns translated notification texts as JSON. This is also the case for error responses (HTTP 4xx/5xx).
 */
const readResult = async (response) => {
  if (!response || typeof response.resolve !== 'function') {
    return null;
  }

  try {
    return await response.resolve();
  } catch {
    return null;
  }
};

const showNotification = (result, button) => {
  if (result && typeof result.title === 'string') {
    const notify = result.success === true ? Notification.success : Notification.error;
    notify(result.title, result.message ?? '');

    return;
  }

  Notification.error(button.dataset.errorTitle ?? '', button.dataset.errorMessage ?? '');
};

const clearCache = async (button) => {
  const formData = new FormData();
  formData.append('contentRecordUid', button.dataset.contentRecordUid ?? '');

  button.disabled = true;
  try {
    const response = await new AjaxRequest(button.dataset.clearCacheUri).post(formData);
    showNotification(await readResult(response), button);
  } catch (error) {
    showNotification(await readResult(error), button);
  } finally {
    button.disabled = false;
  }
};

document.addEventListener('click', (event) => {
  const button = event.target instanceof Element ? event.target.closest(BUTTON_SELECTOR) : null;
  if (button === null) {
    return;
  }

  event.preventDefault();
  clearCache(button);
});
