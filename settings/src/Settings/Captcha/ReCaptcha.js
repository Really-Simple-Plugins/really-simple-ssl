import {useEffect} from "@wordpress/element";
import useFields from '../FieldsData';
import CaptchaData from "./CaptchaData";

/**
 * ReCaptcha functionality.
 *
 * @param {function} handleCaptchaResponse - The callback function to handle the ReCaptcha response.
 * @param {boolean} captchaVerified - Boolean value indicating whether the ReCaptcha is verified or not.
 * @return {JSX.Element} - The ReCaptcha component JSX.
 */
const ReCaptcha = ({ handleCaptchaResponse , captchaVerified}) => {
    const recaptchaCallback = (response) => {
        handleCaptchaResponse(response);
    };

    const {reloadCaptcha, removeRecaptchaScript, setReloadCaptcha} = CaptchaData();
    const {getFieldValue, updateField, saveFields} = useFields();
    const sitekey = getFieldValue('recaptcha_site_key');
    const secret = getFieldValue('recaptcha_secret_key');
    const fully_enabled = getFieldValue('captcha_fully_enabled');

    useEffect(() => {
        const script = document.createElement('script');

        script.src = `https://www.google.com/recaptcha/api.js?render=explicit&onload=initRecaptcha`;
        script.async = true;
        script.defer = true;

        script.onload = () => {
            // We restore the recaptcha script if it was not removed.
            let recaptchaContainer = document.getElementById('recaptchaContainer');
            if (typeof window.grecaptcha !== 'undefined') {
                window.initRecaptcha = window.initRecaptcha || (() => {
                    window.grecaptcha && window.grecaptcha.render(recaptchaContainer, {
                        sitekey: sitekey,
                        callback: recaptchaCallback,
                    });
                });
            }
        };

        document.body.appendChild(script);

    }, [sitekey, handleCaptchaResponse]);

    useEffect(() => {
        // Move cleanup here.
        if (captchaVerified) {
          removeRecaptchaScript();
        }
    }, [captchaVerified]);

    return (
        <div className="rsssl-captcha"
             style={{display: 'flex', flexDirection: 'column', alignItems: 'center', marginBottom: '20px'}} >
            <div id='recaptchaContainer'></div>
        </div>
    );
};

export default ReCaptcha;