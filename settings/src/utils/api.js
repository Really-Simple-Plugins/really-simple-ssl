import axios from 'axios';

/**
 * Makes a get request to the fields list
 *
 * @param {string|boolean} restBase - rest base for the query.
 * @param {object} args
 * @returns {AxiosPromise<any>}
 */

export const getFields = () => {
	let config = {
		headers: {
			'X-WP-Nonce': rsssl_settings.nonce,
		}
	}
    return axios.get(rsssl_settings.site_url+'reallysimplessl/v1/fields/get', config);
};

/**
 * Post our data to the back-end
 * @param data
 * @returns {Promise<AxiosResponse<any>>}
 */
export const setFields = (data) => {
	let config = {
		headers: {
			'X-WP-Nonce': rsssl_settings.nonce,
		}
	}
	return axios.post(rsssl_settings.site_url+'reallysimplessl/v1/fields/set', data, config );
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

export const getOnboarding = () => {
	let config = {
		headers: {
			'X-WP-Nonce': rsssl_settings.nonce,
		}
	}
	return axios.get(rsssl_settings.site_url+'reallysimplessl/v1/onboarding', config);
}


export const overrideSSLDetection = (override_ssl_checked) => {
	const { ajax_url, ajax_nonce } = rsssl_settings;

	let formData = new FormData();
	formData.append("action", "update_ssl_detection_overridden_option")
	formData.append("_ajax_nonce", ajax_nonce)
	formData.append("security", ajax_nonce)
	formData.append("override_ssl_checked", override_ssl_checked)

	return axios.post(ajax_url, formData)
}
