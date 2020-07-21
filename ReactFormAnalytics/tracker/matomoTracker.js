
export function trackMatomoPages(trackPageView)
{
    window.addEventListener("load", onLoadHandler);
    window.addEventListener("focusin", onFocusInHandler);

    function onLoadHandler(loadEvent) {
        if (isURLChange(loadEvent.target.baseURI)) {
            trackPageView();
        }
    }

    function onFocusInHandler(focusInEvent) {
        if (isURLChange(focusInEvent.target.baseURI)) {
            trackPageView();
        }
    }
}

export function handleMatomoForms(trackEvent, customId)
{
    window.addEventListener("load", onLoadHandler);
    window.addEventListener("focusin", onFocusInHandler);
    window.addEventListener("focusout", onFocusOutHandler);

    function onLoadHandler(loadEvent) {
        console.log(document.forms);
        searchForForms(customId, trackEvent);
    }

    function onFocusInHandler(focusInEvent) {
        if (isFormURLChange(focusInEvent.target.baseURI)) {
            searchForForms(customId, trackEvent);
        }
        console.log(focusInEvent)
        if (focusInEvent.target.tagName === 'INPUT') {
            console.log("DETECTED FOCUS IN EVENT ON INPUT FIELD ");
            const name = retrieveSuitableName(focusInEvent.target);
            if (focusInEvent.target.form !== null) {
                const index = findFormFieldIndex(focusInEvent.target);
                console.log("YOU CLICKED ON THE No. " + index + " FIELD")
                const formName = focusInEvent.target.form.name;
                trackMatomoEventOnFocusIn(name, index, formName, customId, trackEvent);
            } else {
                trackMatomoEventOnFocusIn(name, 0, "individual-field", customId, trackEvent);
            }
        }


    }

    function onFocusOutHandler(focusOutEvent) {
        console.log(focusOutEvent)
        if (focusOutEvent.target.tagName === 'INPUT' && focusOutEvent.target.form !== null) {
            console.log("DETECTED FOCUS OUT EVENT ON INPUT FIELD");
            const index = findFormFieldIndex(focusOutEvent.target);
            const name = retrieveSuitableName(focusOutEvent.target);
            const formName = focusOutEvent.target.form.name;
            //   trackMatomoEventOnFocusOut(name, index, formName, customId, trackEvent);
        }
    }
}

function searchForForms(customId, trackEvent) {
    const pageForms = document.forms;
    parseForms(pageForms, customId, trackEvent);
}

function retrieveSuitableName(target) {
    var levels = 7;
    while (levels > 0) {
        var name = target.attributes.getNamedItem('name');
        if (name != null) {
            console.log("Found NAME: " + name.value);
            return name.value;
        } else {
            target = target.parentNode;
            levels--;
        }
    }
    if (levels === 0) {
        console.warn("No name was found for a field. This field will not be tracked by Matomo.");
    }
    return "";
}
function parseForms(pageForms, customId, trackEvent) {
    for (var i = 0; i < pageForms.length; i++) {
        const form = pageForms.item(i);
        const elements = form.elements;
        var input_fields = 0;
        const field_ids = [];

        for (var j = 0; j < elements.length; j++) {
            if (elements[j].tagName === "INPUT") {
                input_fields += 1;
                const name = retrieveSuitableName(elements[j]);
                field_ids.push(name);
                console.log("FOUND FORM FIELD" + input_fields);
                console.log(elements[j]);
            }
        }
        console.log("FOUND FORM WITH NAME " + form.name + " AND " + input_fields + " fields");
        trackMatomoEventOnDetectForm(trackEvent, form, input_fields);
        for (var k = 0; k < field_ids.length; k++) {
            trackMatomoEventOnDetectFormElement(form.name, field_ids[k], k+1, customId, trackEvent)
        }
    }
}

function findFormFieldIndex(target) {
    const elements = target.form.elements;
    var index = 0;
    for (var i = 0; i < elements.length; i++) {
        if (elements[i].tagName == "INPUT") {
            index += 1;
        }
        if (target == elements[i]) {
            return index;
        }
    }
    return 0;
}
function isURLChange(newUrl) {
    const currentUrl = window._curr_url;
    window._curr_url = newUrl;
    return !(currentUrl === newUrl);
}

function isFormURLChange(newUrl) {
    const currentUrl = window._curr_form_url;
    window._curr_form_url = newUrl;
    return !(currentUrl === newUrl);
}

function trackMatomoEventOnDetectFormElement(formName, elementId, index, customId, trackEvent) {
    trackEvent({
        category: 'forms',
        action: 'detect-form-element',
        name: elementId,
        value: index,
        customDimensions: [
            {
                id: customId,
                value: formName,
            },
        ],
    })
}

function trackMatomoEventOnDetectForm(trackEvent, form, noOfFormFields) {
    trackEvent({
        category: 'forms',
        action: 'detect-form',
        name: form.name,
        value: noOfFormFields
    })
}
function trackMatomoEventOnFocusIn(fieldName, index, formName, customId, trackEvent) {
    trackEvent({
        category: 'forms',
        action: 'focus-in',
        name: fieldName,
        value: index,
        customDimensions: [
            {
                id: customId,
                value: formName,
            }
        ]
    });
}

function trackMatomoEventOnFocusOut(fieldName, index, formName, customId, trackEvent) {
    trackEvent({
        category: 'forms',
        action: 'focus-out',
        name: fieldName,
        value: index,
        customDimensions: [
            {
                id: customId,
                value: formName,
            },
        ],
    })
}
