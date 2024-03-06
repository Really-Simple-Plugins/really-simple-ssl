import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";

const useCaptchaData = create(( set, get ) => ({
    reloadCaptcha: false,
    setReloadCaptcha: ( value ) => set({ reloadCaptcha: value }),
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
    },
    removeRecaptchaScript: async(source = 'recaptcha') => {
        if (window.grecaptcha) {
            window.grecaptcha.reset();
            delete window.grecaptcha;
        }
        const scriptTags = document.querySelectorAll('script[src^="https://www.google.com/recaptcha/api.js"]');
        // For each found script tag
        scriptTags.forEach((scriptTag) => {
            scriptTag.remove(); // Remove it
        });
        const rescriptTags = document.querySelectorAll('script[src^="https://www.google.com/recaptcha/api.js"]');
        // now we check if reCaptcha was still rendered.
        const recaptchaContainer = document.getElementById('recaptchaContainer');
        if (recaptchaContainer) {
            recaptchaContainer.remove();
        }
    },
}));

export default useCaptchaData;