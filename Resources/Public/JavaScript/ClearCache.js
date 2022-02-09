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

/**
 * Module: TYPO3/CMS/WallsIoProxy/ClearCache
 *
 * Provides ClearCache option for each WallsIo-Plugin
 */
define(['jquery', 'TYPO3/CMS/Backend/Notification'], function($, Notification) {
    'use strict';

    $(".wallsIoProxyClearCache").on("click", function() {
        let $clearCacheButton = $(this);
        $.get(
            $clearCacheButton.data("clearCacheUri")
        ).done(function(result) {
            if (result === "1") {
                Notification.success(
                    "Cache cleared",
                    "Cache of Content Record with UID " + $clearCacheButton.data("contentRecordUid") + " successfully cleared"
                );
            } else {
                Notification.error(
                    "Cache clearing failed",
                    "The Cache of Content Record UID " + $clearCacheButton.data("contentRecordUid") + " could not be cleared."
                );
            }
        }).fail(function() {
            Notification.error(
                "Request Error",
                "URI to clear cache for walls_io_proxy throws an error"
            );
        });
    });
});
