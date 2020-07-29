## Matomo React Tracker  

### Dependencies
This tracker builds on the existing Matomo React Integration that can be found [here](https://www.npmjs.com/package/@datapunt/matomo-tracker-react). To use the tracker, you need to install and import the npm package following the instructions in the above link.

### Usage
1. Download the tracker and add it to your React directory, and import it.   
e.g `import * as matomoTracker from "../matomoTracker";`

2. Import `useMatomo` from the existing React Matomo library.  
i.e `import { useMatomo } from '@datapunt/matomo-tracker-react';`  

3. Add the following to your App.js: `const { trackPageView, trackEvent } = useMatomo();`

4. To track pages, add the following line to App.js: `matomoTracker.trackMatomoPages(trackPageView);`

5. To track forms, add the following line to App.js: `matomoTracker.handleMatomoForms(trackEvent, customDimensionId, customUserId)`  
    **Note**: `customDimensionId` is compulsory for form tracking. To set up Custom Dimensions, refer to [this guide](https://matomo.org/docs/custom-dimensions/). The name of the Custom Dimension __must be__ `Form`. The ID is then the integer ID provided by Matomo. customUserId is optional and is used if there is a need to track users by their ID. 

### Implementation
The Matomo tracker uses [EventListeners](https://developer.mozilla.org/en-US/docs/Web/API/EventListener) from WebAPI to detect Focus In as well as Load events. For page view tracking, the tracker simply checks for URL changes whenever there is a Load Event or a Focus In Event (to handle SPAs as Load Events are not always triggered on URL change). Upon URL change, the page tracking function in the existing React Library will be called upon. 

Form tracking is slightly more complicated. To see the implementation for form tracking, check out the documentation under Section 3.1 Tracker of the [ReactFormAnalytics' documentation](https://gitlab.com/kpdoggie/interns/matomo-plugins/-/blob/master/ReactFormAnalytics/docs/index.md).