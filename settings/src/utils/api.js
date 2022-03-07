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
			'X-WP-Nonce': rsp_react.nonce,
		}
	}
    return axios.get(rsp_react.site_url+'reallysimplessl/v1/fields/get', config);
};

/**
 * Post our data to the back-end
 * @param data
 * @returns {Promise<AxiosResponse<any>>}
 */
export const setFields = (data) => {
	let config = {
		headers: {
			'X-WP-Nonce': rsp_react.nonce,
		}
	}
	return axios.post(rsp_react.site_url+'reallysimplessl/v1/fields/set', data, config );
};

