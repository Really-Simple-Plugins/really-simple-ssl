import getAnchor from "./getAnchor";
import apiFetch from '@wordpress/api-fetch';
/*
 * Makes a get request to the fields list
 *
 * @param {string|boolean} restBase - rest base for the query.
 * @param {object} args
 * @returns {AxiosPromise<any>}
 */

export const getRandomToken = () => {
	return '&token='+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 5);
};

export const getFields = () => {
    //we pass the anchor, so we know when LE is loaded
    let anchor = getAnchor('main');
	let glue = rsssl_settings.site_url.indexOf('?') !==-1 ? '&' : '?';
	return apiFetch( { path: '/reallysimplessl/v1/fields/get'+glue+anchor+'&nonce='+rsssl_settings.rsssl_nonce+getRandomToken() } );
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
    let glue = rsssl_settings.site_url.indexOf('?')!==-1 ? '&' : '?';
    return apiFetch( {
        path: 'reallysimplessl/v1/fields/set'+glue+anchor,
        method: 'POST',
        data: data,
    } );
};

export const getBlock = (block) => {
    let glue = rsssl_settings.site_url.indexOf('?')!==-1 ? '&' : '?';
    return apiFetch( { path: 'reallysimplessl/v1/block/'+block+glue+'nonce='+rsssl_settings.rsssl_nonce+getRandomToken() } );
};

export const runTest = (test, state, data ) => {
	if (data) {
		data = encodeURIComponent(JSON.stringify(data));
	}
    let glue = rsssl_settings.site_url.indexOf('?')!==-1 ? '&' : '?';
    return apiFetch( { path: 'reallysimplessl/v1/tests/'+test+glue+'state='+state+'&nonce='+rsssl_settings.rsssl_nonce+getRandomToken()+'&data='+data } );
};

export const runLetsEncryptTest = (test, id ) => {
    let glue = rsssl_settings.site_url.indexOf('?')!==-1 ? '&' : '?';
    return apiFetch( { path: 'reallysimplessl/v1/tests/'+test+glue+'letsencrypt=1&id='+id+'&nonce='+rsssl_settings.rsssl_nonce+getRandomToken() } );
}

export const doAction = (action, data) => {
    data.nonce = rsssl_settings.rsssl_nonce;
    return apiFetch( {
        path: 'reallysimplessl/v1/do_action/'+action,
        method: 'POST',
        data: data,
    } );
}

export const getOnboarding = (forceRefresh) => {
    let glue = rsssl_settings.site_url.indexOf('?')!==-1 ? '&' : '?';
    return apiFetch( { path: 'reallysimplessl/v1/onboarding'+glue+'forceRefresh='+forceRefresh+'&nonce='+rsssl_settings.rsssl_nonce+getRandomToken() } );
}