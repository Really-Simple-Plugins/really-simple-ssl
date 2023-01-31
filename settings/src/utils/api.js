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
        let url = rsssl_settings.admin_ajax_url;
        let xhr = new XMLHttpRequest();
        xhr.open('POST', url);
        xhr.onload = function () {
            let response = JSON.parse(xhr.response);
            if (xhr.status >= 200 && xhr.status < 300) {
                resolve(response);
            } else {
                reject({
                    status: xhr.status,
                    statusText: xhr.statusText
                });
            }
        };
        xhr.onerror = function () {
            reject({
                status: xhr.status,
                statusText: xhr.statusText
            });
        };
        requestData.push(path);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.send(requestData);
    });

}

const ajaxGet = (path) => {
    return new Promise(function (resolve, reject) {
        let url = rsssl_settings.admin_ajax_url;
        url+='&rest_action='+path.replace('?', '&');

        let xhr = new XMLHttpRequest();
        xhr.open('GET', url);
        xhr.onload = function () {
            let response = JSON.parse(xhr.response);
            if (xhr.status >= 200 && xhr.status < 300) {
                resolve(response);
            } else {
                reject({
                    status: xhr.status,
                    statusText: xhr.statusText
                });
            }
        };
        xhr.onerror = function () {
            reject({
                status: xhr.status,
                statusText: xhr.statusText
            });
        };
        xhr.send();
    });

}


/**
 * if the site is loaded over https, but the site url is not https, force to use https anyway, because otherwise we get mixed content issues.
 * @returns {*}
 */
const siteUrl = () => {
	if ( window.location.protocol === "https:" && rsssl_settings.site_url.indexOf('https://')===-1 ) {
		return rsssl_settings.site_url.replace('http://', 'https://');
	}
	return  rsssl_settings.site_url;
}

const invalidDataError = (apiResponse) => {
    let response = {}
    let error = {};
    let data = {};
    data.status = 500;
    error.code = 'invalid_data';
    error.data = data;
    error.message = apiResponse;
    response.error = error;
    return response;
}

const apiGet = (path) => {
    return ajaxGet(path);

    if ( usesPlainPermalinks() ) {
        let config = {
            headers: {
                'X-WP-Nonce': rsssl_settings.nonce,
            }
        }
        return axios.get(siteUrl()+path, config ).then(
            ( response ) => {
                if (!response.data.success) {
                    return invalidDataError(response.data)
                }
                return response.data;
            }
        ).catch((error) => {
            let data = {};
            data.error = error;
            return data;
        });
    } else {
        return apiFetch( { path: path } ).then((response) => {
            if ( !response.success ) {
                console.log(path+" resulted in invalid response because of missing success prop");
                return invalidDataError(response);
            }
            return response;
        }).catch((error) => {
            let data = {};
            data.error = error;
            return data;
        });
    }
}

const apiPost = (path, data) => {
    return ajaxPost(path, data);

    if ( usesPlainPermalinks() ) {
        let config = {
            headers: {
                'X-WP-Nonce': rsssl_settings.nonce,
            }
        }
    	return axios.post(siteUrl()+path, data, config ).then( ( response ) => {return response.data;}).catch((error) => {
            let data = {};
            data.error = error;
            return data;
        });
    } else {
        return apiFetch( {
            path: path,
            method: 'POST',
            data: data,
        } ).catch((error) => {
            let data = {};
            data.error = error;
            return data;
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
	let nonce = {'nonce':rsssl_settings.rsssl_nonce};
	data.push(nonce);
    return apiPost('reallysimplessl/v1/fields/set'+glue()+anchor, data);
};

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
    if (typeof data === 'undefined') data = {};
    data.nonce = rsssl_settings.rsssl_nonce;
    return apiPost('reallysimplessl/v1/do_action/'+action, data);
}

export const getOnboarding = (forceRefresh) => {
    return apiGet('reallysimplessl/v1/onboarding'+glue()+'forceRefresh='+forceRefresh+getNonce());
}