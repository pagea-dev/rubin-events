/**
 * Compact detail view in a modal, without a request and without any framework.
 *
 * Every trigger carries its event data in data attributes, the dialog is an empty shell that gets
 * filled on click. Text is always written with textContent, so event data can never inject markup.
 */
document.addEventListener('DOMContentLoaded', function () {
    const dialogs = document.querySelectorAll('[data-rubin-event-dialog]');

    if (dialogs.length === 0) {
        return;
    }

    const OPEN_CLASS = 'rubin-events-modal-open';

    // data attribute on the trigger -> [data-field] element inside the dialog
    const FIELDS = ['title', 'location', 'teaser', 'description'];

    let titleIdCounter = 0;

    dialogs.forEach(function (dialog) {
        const title = dialog.querySelector('[data-field="title"]');

        // Labelling the dialog needs an id, and one that survives several plugins on a page
        if (title && !title.id) {
            title.id = 'rubin-events-modal-title-' + (++titleIdCounter);
            dialog.setAttribute('aria-labelledby', title.id);
        }

        // Clicking the backdrop closes: on a native dialog the backdrop is part of the element
        // itself, so a click that hits the dialog and not its content came from outside the box
        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) {
                close(dialog);
            }
        });

        dialog.querySelectorAll('[data-rubin-event-dialog-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                close(dialog);
            });
        });

        // Also fires when the dialog is closed with ESC, which the browser handles itself
        dialog.addEventListener('close', function () {
            document.documentElement.classList.remove(OPEN_CLASS);
        });
    });

    // Delegated, because sliders move their slides around after the page has loaded
    document.addEventListener('click', function (event) {
        if (!(event.target instanceof Element)) {
            return;
        }

        const trigger = event.target.closest('[data-rubin-event-modal]');

        if (trigger === null) {
            return;
        }

        event.preventDefault();
        open(dialogFor(trigger), trigger);
    });

    /**
     * The dialog belonging to the plugin the trigger sits in, so several event plugins on one
     * page do not fill each other's modal.
     */
    function dialogFor(trigger) {
        const scope = trigger.closest('.rubin-events');

        return (scope && scope.querySelector('[data-rubin-event-dialog]')) || dialogs[0];
    }

    function open(dialog, trigger) {
        FIELDS.forEach(function (field) {
            setField(dialog, field, (trigger.dataset[field] || '').trim());
        });

        // Start and end come in separately, so events without an end date do not show a dangling dash
        const start = (trigger.dataset.date || '').trim();
        const end = (trigger.dataset.dateEnd || '').trim();
        setField(dialog, 'date', end === '' ? start : start + ' – ' + end);

        setMapLinks(dialog, trigger.dataset.lat, trigger.dataset.lon);

        document.documentElement.classList.add(OPEN_CLASS);

        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            // No native modal support: at least show the box
            dialog.setAttribute('open', '');
        }
    }

    function close(dialog) {
        if (typeof dialog.close === 'function') {
            dialog.close();
        } else {
            dialog.removeAttribute('open');
            document.documentElement.classList.remove(OPEN_CLASS);
        }
    }

    function setField(dialog, field, value) {
        const target = dialog.querySelector('[data-field="' + field + '"]');

        if (target === null) {
            return;
        }

        target.textContent = value;

        // Empty fields hide their whole row, not just the text
        (target.closest('[data-optional]') || target).hidden = value === '';
    }

    function setMapLinks(dialog, lat, lon) {
        const wrapper = dialog.querySelector('[data-map-links]');

        if (wrapper === null) {
            return;
        }

        const hasCoordinates = !Number.isNaN(parseFloat(lat)) && !Number.isNaN(parseFloat(lon));

        wrapper.hidden = !hasCoordinates;

        if (!hasCoordinates) {
            return;
        }

        const osm = dialog.querySelector('[data-map-link="osm"]');
        const google = dialog.querySelector('[data-map-link="google"]');

        if (osm) {
            osm.href = 'https://www.openstreetmap.org/?mlat=' + lat + '&mlon=' + lon
                + '#map=16/' + lat + '/' + lon;
        }

        if (google) {
            google.href = 'https://www.google.com/maps/search/?api=1&query=' + lat + ',' + lon;
        }
    }
});
