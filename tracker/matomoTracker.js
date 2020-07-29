
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

export function handleMatomoForms(trackEvent, customId, userId = "")
{
    window.addEventListener("load", onLoadHandler);
    window.addEventListener("focusin", onFocusInHandler);
    if (userId !== "") {
        window._paq.push(['setUserId', userId]);
    }

    // window.addEventListener("focusout", onFocusOutHandler);
  
    function onLoadHandler(loadEvent) {
        searchForForms(customId, trackEvent);
    }

    function onFocusInHandler(focusInEvent) {
        const targetElement = focusInEvent.target;
        if (isFormURLChange(targetElement.baseURI)) {
            searchForForms(customId, trackEvent);
        }
        if (targetElement.tagName === 'INPUT') {
            handleInputFocusIn(targetElement, customId, trackEvent);
        } else if (targetElement.type === "submit") {
            trackMatomoEventOnSubmit(trackEvent);
        } 
        else {
            if (window._curr_event === "INPUT") {
                trackMatomoEventOnInputOut(trackEvent);
            }
        } 
        window._curr_event = targetElement.tagName;
    }

    function onFocusOutHandler(focusOutEvent) {
        if (focusOutEvent.target.tagName === 'INPUT' && focusOutEvent.target.form !== null) {
          const index = findFormFieldIndex(focusOutEvent.target);
          const name = retrieveSuitableName(focusOutEvent.target);
          const formName = focusOutEvent.target.form.name;
          trackMatomoEventOnFocusOut(name, index, formName, customId, trackEvent);
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

function handleInputFocusIn(targetElement, customId, trackEvent) {
    const name = retrieveSuitableName(targetElement);
    if (targetElement.form !== null) {
        const index = findFormFieldIndex(targetElement);
        const formName = targetElement.form.name;
        trackMatomoEventOnFocusIn(name, index, formName, customId, trackEvent);
    } else {
        // This handles input fields that are not inside forms
        trackMatomoEventOnFocusIn(name, 0, "individual-field", customId, trackEvent);
   }
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
          }
        }
        trackMatomoEventOnDetectForm(trackEvent, form, input_fields);
        for (var k = 0; k < field_ids.length; k++) {
            const fieldName = field_ids[k];
            trackMatomoEventOnDetectFormElement(form.name, fieldName, k+1, customId, trackEvent)
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

function trackMatomoEventOnInputOut(trackEvent) {
    trackEvent({
        category: 'general',
        action: 'focus-in'
    })
}

function trackMatomoEventOnSubmit(trackEvent) {
    trackEvent({
        category: 'forms',
        action: 'submit',
    })
}