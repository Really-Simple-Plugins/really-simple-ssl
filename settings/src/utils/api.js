import axios from 'axios';
import getAnchor from "./getAnchor";

/*
 * Makes a get request to the fields list
 *
 * @param {string|boolean} restBase - rest base for the query.
 * @param {object} args
 * @returns {AxiosPromise<any>}
 */

export const getFields = () => {
    //we pass the anchor, so we know when LE is loaded
    let anchor = getAnchor('main');
	let config = {
		headers: {
			'X-WP-Nonce': rsssl_settings.nonce,
		}
	}

	let glue = rsssl_settings.site_url.indexOf('?') !==-1 ? '&' : '?';
    return axios.get(rsssl_settings.site_url+'reallysimplessl/v1/fields/get'+glue+anchor+'&nonce='+rsssl_settings.rsssl_nonce+getRandomToken(), config);
};

/*
 * Post our data to the back-end
 * @param data
 * @returns {Promise<AxiosResponse<any>>}
 */
export const setFields = (data) => {
    //we pass the anchor, so we know when LE is loaded
    let anchor = getAnchor('main');
	let config = {
		headers: {
			'X-WP-Nonce': rsssl_settings.nonce,
			'rsssl-nonce': rsssl_settings.nonce,
		}
	}
	let nonce = {'nonce':rsssl_settings.rsssl_nonce};
	data.push(nonce);
	return axios.post(rsssl_settings.site_url+'reallysimplessl/v1/fields/set?'+anchor, data, config );
};

export const getBlock = (block) => {
	let config = {
		headers: {
			'X-WP-Nonce': rsssl_settings.nonce,
		}
	}
	return axios.get(rsssl_settings.site_url+'reallysimplessl/v1/block/'+block+'?nonce='+rsssl_settings.rsssl_nonce, config);
};

export const runTest = (test, state, data ) => {
	let config = {
		headers: {
			'X-WP-Nonce': rsssl_settings.nonce,
		}
	}
	if (data) {
		data = encodeURIComponent(JSON.stringify(data));
	}
	return axios.get(rsssl_settings.site_url+'reallysimplessl/v1/tests/'+test+'?state='+state+'&nonce='+rsssl_settings.rsssl_nonce+'&data='+data, config);
};

export const runLetsEncryptTest = (test, id ) => {
	let config = {
		headers: {
			'X-WP-Nonce': rsssl_settings.nonce,
		}
	}

	return axios.get(rsssl_settings.site_url+'reallysimplessl/v1/tests/'+test+'?letsencrypt=1&id='+id+'&nonce='+rsssl_settings.rsssl_nonce, config);
}

export const doAction = (action, data) => {
	let config = {
		headers: {
			'X-WP-Nonce': rsssl_settings.nonce,
		}
	}
    data.nonce = rsssl_settings.rsssl_nonce;
	return axios.post(rsssl_settings.site_url+'reallysimplessl/v1/do_action/'+action, data, config );
}

export const getOnboarding = (forceRefresh) => {
	let config = {
		headers: {
			'X-WP-Nonce': rsssl_settings.nonce,
		}
	}
	return axios.get(rsssl_settings.site_url+'reallysimplessl/v1/onboarding?forceRefresh='+forceRefresh+'&nonce='+rsssl_settings.rsssl_nonce, config);
}