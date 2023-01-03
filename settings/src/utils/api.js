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

const apiGet = (path) => {
    if ( usesPlainPermalinks() ) {
        let config = {
            headers: {
                'X-WP-Nonce': rsssl_settings.nonce,
            }
        }
        return axios.get(rsssl_settings.site_url+path, config ).then( ( response ) => {return response.data;})
    } else {
        return apiFetch( { path: path } );
    }
}

const apiPost = (path, data) => {
    if ( usesPlainPermalinks() ) {
        let config = {
            headers: {
                'X-WP-Nonce': rsssl_settings.nonce,
            }
        }
    	return axios.post(rsssl_settings.site_url+path, data, config ).then( ( response ) => {return response.data;});
    } else {
        return apiFetch( {
            path: path,
            method: 'POST',
            data: data,
        } );
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

export const getBlock = (block) => {
    return apiGet('reallysimplessl/v1/block/'+block+glue()+getNonce());
};

export const runTest = (test, state, data ) => {
	if ( data ) {
		data = encodeURIComponent(JSON.stringify(data));
	}
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