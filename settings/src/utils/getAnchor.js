/*
 * helper function to delay after a promise
 * @param ms
 * @returns {function(*): Promise<unknown>}
 */
const getAnchor = (level) => {
    let url = window.location.href;
    if (url.indexOf('#') === -1) {
        return false;
    }

    let queryString = url.split('#')[1];
    if (!queryString) {
        return false;
    }

    // Split the query string to handle multiple parameters
    let urlParts = queryString.split('&');

    if (level === 'highlightfield') {
        // Extract highlightfield parameter
        for (let part of urlParts) {
            if (part.startsWith('highlightfield=')) {
                return part.split('=')[1];
            }
        }
        return false;
    }

    // Default behavior for 'anchor' and 'menu'
    let urlPart = urlParts[0];

    if (level === 'anchor') {
        if (urlPart.indexOf('/') === -1) {
            return false;
        } else {
            let urlSegments = urlPart.split('/');
            return urlSegments.length > 2 ? urlSegments[2] : false;
        }
    } else if (level === 'menu') {
        if (urlPart.indexOf('/') === -1) {
            return false;
        } else {
            let urlSegments = urlPart.split('/');
            return urlSegments.length > 1 ? urlSegments[1] : false;
        }
    } else {
        return urlPart.indexOf('/') === -1 ? urlPart : urlPart.split('/')[0];
    }
};

export default getAnchor;
