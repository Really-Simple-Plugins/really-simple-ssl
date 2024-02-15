import React, {useRef, useEffect, useState} from '@wordpress/element';
import useFields from '../FieldsData';
import useCaptchaData from "./CaptchaData";
import {__} from '@wordpress/i18n';
import {Button} from "@wordpress/components";

let detachedCaptchaHtml = '';

function generateUniqueId() {
    return Date.now() + '_' + Math.floor(Math.random() * 1000);
}

const Captcha = ({field, showDisabledWhenSaving = true}) => {
    const {getFieldValue, updateField} = useFields();
    const [uniqueId, setUniqueId] = useState(generateUniqueId());
    const captchaContainerRef = useRef(null);
    const [loaded, setLoaded] = useState(false);
    const {verifyCaptcha} = useCaptchaData();
    const reCAPTCHAScriptId = 'recaptchaScript';
    const enabled_captcha_provider = getFieldValue('enabled_captcha_provider');
    const fully_enabled = getFieldValue('captcha_fully_enabled');
    const [showCaptcha, setShowCaptcha] = useState(false);

    // Moved out response handling into a separate function
    const handleCaptchaResponse = (response) => {
        verifyCaptcha(response).then((response) => {
            if (response && response.success) {
                updateField('captcha_fully_enabled', true);
            } else {
                updateField('captcha_fully_enabled', false);
            }
        });
    };

    const recaptchaCallback = (response) => {
        handleCaptchaResponse(response);
    };

    const hcaptchaCallback = (response) => {
        handleCaptchaResponse(response);
    };

    function removeRecaptchaScript() {
        let script = document.getElementById(reCAPTCHAScriptId);
        if (script) {
            document.body.removeChild(script);
        }
    }

    function unloadCaptcha() {
        const container = captchaContainerRef.current;
        if (container) {
            // Remove reCAPTCHA script if exists
            removeRecaptchaScript();

            if (window.hcaptcha && typeof window.hcaptcha.reset === "function") {
                window.hcaptcha.reset();
            }
            // Remove all child elements
            while (container.firstChild) {
                container.removeChild(container.firstChild);
            }
        }
    }

    useEffect(() => {
        if (enabled_captcha_provider === 'none') {
            return;
        }
        let script;

        unloadCaptcha(); // Unload existing CAPTCHA


        if (enabled_captcha_provider) {
            if (detachedCaptchaHtml) {  // <-- add this if clause
                // If there's any detached captcha HTML, reinsert it
                captchaContainerRef.current.innerHTML = detachedCaptchaHtml;
            } else {
                script = document.createElement('script');
                script.async = true;
                script.defer = true;

                const site_key = getFieldValue(`${enabled_captcha_provider}_site_key`);
                if (fully_enabled) {
                    return;
                }
                switch (enabled_captcha_provider) {
                    case 'recaptcha':
                        script.src = `https://www.google.com/recaptcha/api.js?render=explicit&onload=initRecaptcha`;
                        //first we check if the recaptcha script is already loaded
                        // if (typeof window.grecaptcha !== 'undefined') {
                        //     window.initRecaptcha();
                        // }
                        window.initRecaptcha = window.initRecaptcha || (() => {
                            window.grecaptcha && window.grecaptcha.render(captchaContainerRef.current, {
                                sitekey: site_key,
                                callback: recaptchaCallback,
                            });
                        });
                        break;
                    case 'hcaptcha':
                        script.src = `https://hcaptcha.com/1/api.js?onload=initHcaptcha`;
                        window.initHcaptcha = window.initHcaptcha || (() => {
                            window.hcaptcha && window.hcaptcha.render(captchaContainerRef.current, {
                                sitekey: site_key,
                                callback: hcaptchaCallback,
                            });
                        });
                        break;
                    default:
                        break;
                }

                document.body.appendChild(script);
            }

        }

        // Cleanup function
        return () => {
            script && script.remove();
            // Detach the captcha HTML upon unmounting
            // detachedCaptchaHtml = captchaContainerRef.current.innerHTML;
            unloadCaptcha(); // Ensure CAPTCHA is unloaded
        };
    }, [enabled_captcha_provider, uniqueId, fully_enabled]);

    useEffect(() => {
        setUniqueId(generateUniqueId());
    }, [enabled_captcha_provider]);

    return (
        <>
            {enabled_captcha_provider !== 'none' && !fully_enabled && (
                <div className="rsssl-captcha"
                    style={{display: showCaptcha? 'flex': 'none', flexDirection: 'column', alignItems: 'center', marginBottom: '20px'}}
                >
                    <div ref={captchaContainerRef} key={uniqueId} id={uniqueId}></div>
                </div>
            )}
                <Button isPrimary={true}
                        text={__('validate CAPTCHA', 'really-simple-ssl')}
                        // style={{display: !showCaptcha? 'none': 'block'}}
                    onClick={() => setShowCaptcha(true)} />
        </>
    );
};

export default Captcha;