import getAnchor from "./getAnchor";
import axios from 'axios';
import apiFetch from '@wordpress/api-fetch';

/*
 * Makes a get request to the fields list
 *
 * @param {string|boolean} restBase - rest base for the query.
 * @param {object} args
 * @returns {AxiosPromise<any>}
 */

export const getNonce = () => {
    return '&nonce='+rsssl_settings.rsssl_nonce+'&token='+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 5);
};

const usesPlainPermalinks = () => {
    return rsssl_settings.site_url.indexOf('?') !==-1;
};

const ajaxPost = (path, requestData) => {
    return new Promise(function (resolve, reject) {
        let url = siteUrl('ajax');
        let xhr = new XMLHttpRequest();
        xhr.open('POST', url );
        xhr.onload = function () {
            let response;
            try {
                response = JSON.parse(xhr.response);
            } catch (error) {
                resolve(invalidDataError(xhr.response, 500, 'invalid_data') );
            }
            if (xhr.status >= 200 && xhr.status < 300) {
                resolve(response);
            } else {
                resolve(invalidDataError(xhr.response, xhr.status, xhr.statusText) );
            }
        };
        xhr.onerror = function () {
            resolve(invalidDataError(xhr.response, xhr.status, xhr.statusText) );
        };

        let data = {};
        data['path'] = path;
        data['data'] = requestData;
        data = JSON.stringify(data, stripControls);
        xhr.setRequestHeader('Content-Type', 'application/json; charset=UTF-8');
        xhr.send(data);
    });
}

/**
 * All data elements with 'Control' in the name are dropped, to prevent:
 * TypeError: Converting circular structure to JSON
 * @param key
 * @param value
 * @returns {any|undefined}
 */
const stripControls = (key, value) => {
    if (!key){return value}
    if (key && key.includes("Control")) {
        return undefined;
    }
    if (typeof value === "object") {
        return JSON.parse(JSON.stringify(value, stripControls));
    }
    return value;
}

const ajaxGet = (path) => {
    return new Promise(function (resolve, reject) {
        let url = siteUrl('ajax');
        url+='&rest_action='+path.replace('?', '&');

        let xhr = new XMLHttpRequest();
        xhr.open('GET', url);
        xhr.onload = function () {
            let response;
            try {
                response = JSON.parse(xhr.response);
            } catch (error) {
                resolve(invalidDataError(xhr.response, 500, 'invalid_data') );
            }
            if (xhr.status >= 200 && xhr.status < 300) {
                if ( !response.hasOwnProperty('request_success') ) {
                    resolve(invalidDataError(xhr.response, 500, 'invalid_data') );
                }
                resolve(response);
            } else {
                resolve(invalidDataError(xhr.response, xhr.status, xhr.statusText) );
            }
        };
        xhr.onerror = function () {
            resolve(invalidDataError(xhr.response, xhr.status, xhr.statusText) );
        };
        xhr.send();
    });

}


/**
 * if the site is loaded over https, but the site url is not https, force to use https anyway, because otherwise we get mixed content issues.
 * @returns {*}
 */
const siteUrl = (type) => {
    let url;
    if (typeof type ==='undefined') {
        url = rsssl_settings.site_url;
    } else {
        url = rsssl_settings.admin_ajax_url
    }
    if ( window.location.protocol === "https:" && url.indexOf('https://')===-1 ) {
        return url.replace('http://', 'https://');
    }
    return  url;
}


const invalidDataError = (apiResponse, status, code ) => {
    let response = {}
    let error = {};
    let data = {};
    data.status = status;
    error.code = code;
    error.data = data;
    error.message = apiResponse;
    response.error = error;
    return response;
}

const apiGet = (path) => {

    if ( usesPlainPermalinks() ) {
        let config = {
            headers: {
                'X-WP-Nonce': rsssl_settings.nonce,
            }
        }
        return axios.get(siteUrl()+path, config ).then(
            ( response ) => {
                if (!response.data.request_success) {
                    return ajaxGet(path);
                }
                return response.data;
            }
        ).catch((error) => {
            //try with admin-ajax
            return ajaxGet(path);
        });
    } else {
        return apiFetch( { path: path } ).then((response) => {
            if ( !response.request_success ) {
                return ajaxGet(path);
            }
            return response;
        }).catch((error) => {
            return ajaxGet(path);
        });
    }
}

const apiPost = (path, data) => {
    if ( usesPlainPermalinks() ) {
        let config = {
            headers: {
                'X-WP-Nonce': rsssl_settings.nonce,
            }
        }
        return axios.post(siteUrl()+path, data, config ).then( ( response ) => {return response.data;}).catch((error) => {
            return ajaxPost(path, data);
        });
    } else {
        return apiFetch( {
            path: path,
            method: 'POST',
            data: data,
        } ).catch((error) => {
            return ajaxPost(path, data);
        });
    }
}

const glue = () => {
    return rsssl_settings.site_url.indexOf('?')!==-1 ? '&' : '?'
}

export const getFields = () => {
    //we pass the anchor, so we know when LE is loaded
    let anchor = getAnchor('main');
    return apiGet('reallysimplessl/v1/fields/get'+glue()+anchor+getNonce(), 'GET');
};

/*
 * Post our data to the back-end
 * @param data
 * @returns {Promise<AxiosResponse<any>>}
 */
export const setFields = (data) => {
    //we pass the anchor, so we know when LE is loaded
    let anchor = getAnchor('main');
    let nonce = {'nonce': rsssl_settings.rsssl_nonce};
    data.push(nonce);
    return apiPost('reallysimplessl/v1/fields/set' + glue() + anchor, data);
}

export const runTest = (test, state, data ) => {
    if ( !state ){
        state = false;
    }
	if ( !data ) {
		data = false;
	}
    data = encodeURIComponent(JSON.stringify(data));
    return apiGet('reallysimplessl/v1/tests/'+test+glue()+'state='+state+getNonce()+'&data='+data)
};

export const runLetsEncryptTest = (test, id ) => {
    return apiGet('reallysimplessl/v1/tests/'+test+glue()+'letsencrypt=1&id='+id+getNonce());
}

export const doAction = (action, data) => {
    const newData = { ...data };
    newData.nonce = rsssl_settings.rsssl_nonce;
    return apiPost('reallysimplessl/v1/do_action/'+action, newData);
}
