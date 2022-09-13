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
    return axios.get(rsssl_settings.site_url+'reallysimplessl/v1/fields/get?'+anchor, config);
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
		}
	}
	return axios.post(rsssl_settings.site_url+'reallysimplessl/v1/fields/set?'+anchor, data, config );
};

export const getBlock = (block) => {
	let config = {
		headers: {
			'X-WP-Nonce': rsssl_settings.nonce,
		}
	}
	return axios.get(rsssl_settings.site_url+'reallysimplessl/v1/block/'+block, config);
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
	return axios.get(rsssl_settings.site_url+'reallysimplessl/v1/tests/'+test+'?state='+state+'&data='+data, config);
};

export const runLetsEncryptTest = (test, id ) => {
	let config = {
		headers: {
			'X-WP-Nonce': rsssl_settings.nonce,
		}
	}
	return axios.get(rsssl_settings.site_url+'reallysimplessl/v1/tests/'+test+'?letsencrypt=1&id='+id, config);
};

export const getOnboarding = (forceRefresh) => {
	let config = {
		headers: {
			'X-WP-Nonce': rsssl_settings.nonce,
		}
	}
	return axios.get(rsssl_settings.site_url+'reallysimplessl/v1/onboarding?forceRefresh='+forceRefresh, config);
}

export const overrideSSLDetection = (data) => {
	let config = {
		headers: {
			'X-WP-Nonce': rsssl_settings.nonce,
		}
	}
	return axios.post(rsssl_settings.site_url+'reallysimplessl/v1/override_ssl_detection', data, config );
};

export const activateSSL = (data) => {
	let config = {
		headers: {
			'X-WP-Nonce': rsssl_settings.nonce,
		}
	}

	return axios.post(rsssl_settings.site_url+'reallysimplessl/v1/activate_ssl', data, config );
}

export const activateSSLNetworkwide = (data) => {
	let config = {
		headers: {
			'X-WP-Nonce': rsssl_settings.nonce,
		}
	}
	return axios.post(rsssl_settings.site_url+'reallysimplessl/v1/activate_ssl_networkwide', data, config );
}

export const onboardingActions = (data) => {
	let config = {
		headers: {
			'X-WP-Nonce': rsssl_settings.nonce,
		}
	}
	return axios.post(rsssl_settings.site_url+'reallysimplessl/v1/onboarding_actions', data, config );
}