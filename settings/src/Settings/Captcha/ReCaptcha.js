import React, {useEffect} from 'react';

const ReCaptcha = ({sitekey, handleCaptchaResponse , captchaVerified}) => {
    const recaptchaCallback = (response) => {
        handleCaptchaResponse(response);
    };

    console.log('sitekey', sitekey);

    useEffect(() => {
        const script = document.createElement('script');

        script.src = `https://www.google.com/recaptcha/api.js?render=explicit&onload=initRecaptcha`;
        script.async = true;
        script.defer = true;

        script.onload = () => {
            console.log(window.grecaptcha);
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
            if (window.grecaptcha) {
                window.grecaptcha.reset();
            }
            const scriptTag = document.querySelector('script[src^="https://www.google.com/recaptcha/api.js"]');
            if (scriptTag) {
                scriptTag.remove();
            }
            console.log('removing recaptcha script');
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