import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";

const useCaptchaData = create(( set, get ) => ({
    verifyCaptcha: async ( responseToken ) => {
        try {
            const response = await rsssl_api.doAction('verify_captcha', { responseToken: responseToken });

            // Handle the response
            if ( !response ) {
                console.error('No response received from the server.');
                return;
            }
            return response;
        } catch (error) {
            console.error('Error:', error);
        }
    }
}));

export default useCaptchaData;