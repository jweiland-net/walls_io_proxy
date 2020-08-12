let wallIoSocialWallContainers = [];
let wallIoElements = [];
let wallIoEntriesToShow = [];
let visibleWallIoElements = [];

function showWallIoEntries(wallIoUid)
{
    let wallIoStart = visibleWallIoElements[wallIoUid]; // initially 0
    let wallIoUntil = wallIoStart +  wallIoEntriesToShow[wallIoUid]; // 0 + 8 + 8 + 8...
    for (let x = wallIoStart; x < wallIoUntil; x++) {
        if (wallIoElements[wallIoUid].item(x) !== null) {
            wallIoElements[wallIoUid].item(x).style.display = 'block';
            visibleWallIoElements[wallIoUid]++;
        }
    }

    // If masonry is loaded, jquery is loaded as well. Re-Layout masonry
    if (
        document.getElementsByClassName('masonrygrid').length
        && window.jQuery
    ) {
        let $masonryGrids = $('.masonrygrid');
        if (typeof $masonryGrids.masonry === 'function') {
            $masonryGrids.masonry();
        }
    }

    // Hide "load more" button, if there are no more entries
    if (visibleWallIoElements[wallIoUid] >= wallIoElements[wallIoUid].length) {
        let buttons = wallIoSocialWallContainers[wallIoUid].getElementsByClassName('wallsio-load-more-button');
        for (let x = 0; x < buttons.length; x++) {
            buttons[x].style.display = 'none';
        }
    }
}

for (let x = 0; x < document.getElementsByClassName('socialwall_container').length; x++) {
    let socialWallContainer = document.getElementsByClassName('socialwall_container')[x];
    let wallIoUid = parseInt(socialWallContainer.getAttribute('data-uid'));

    wallIoSocialWallContainers[wallIoUid] = socialWallContainer;
    wallIoElements[wallIoUid] = socialWallContainer.getElementsByClassName('checkin-wrapper');
    wallIoEntriesToShow[wallIoUid] = parseInt(socialWallContainer.getAttribute('data-entries-to-show'));
    visibleWallIoElements[wallIoUid] = 0;

    // First of all hide all entries
    for (let y = 0; y < wallIoElements[wallIoUid].length; y++) {
        wallIoElements[wallIoUid].item(y).style.display = 'none';
    }

    showWallIoEntries(wallIoUid);
}
