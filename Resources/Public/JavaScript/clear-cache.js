/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
