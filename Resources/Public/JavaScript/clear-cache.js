import $ from 'jquery';
import Notification from "@typo3/backend/notification.js";

$(() => {
  $(".wallsIoProxyClearCache").on("click", function () {
    const $clearCacheButton = $(this);

    $.get($clearCacheButton.data("clearCacheUri"))
      .done((result) => {
        const uid = $clearCacheButton.data("contentRecordUid");

        if (result === "1") {
          Notification.success(
            "Cache cleared",
            `Cache of Content Record with UID ${uid} successfully cleared`
          );
        } else {
          Notification.error(
            "Cache clearing failed",
            `The Cache of Content Record UID ${uid} could not be cleared.`
          );
        }
      })
      .fail(() => {
        Notification.error(
          "Request Error",
          "URI to clear cache for walls_io_proxy throws an error"
        );
      });
  });
});
