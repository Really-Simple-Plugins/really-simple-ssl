import {useEffect} from "@wordpress/element";
const HCaptcha = ({ sitekey, handleCaptchaResponse }) => {
    const hcaptchaCallback = (response) => {
        handleCaptchaResponse(response);
    };

    useEffect(() => {
        const script = document.createElement('script');

        script.src = `https://hcaptcha.com/1/api.js?onload=initHcaptcha`;
        script.async = true;
        script.defer = true;

        script.onload = () => {
            if (typeof window.hcaptcha !== 'undefined') {
                window.hcaptcha.render('hcaptchaContainer', {
                    sitekey: sitekey,
                    callback: hcaptchaCallback
                });
            }
        };

        document.body.appendChild(script);

        // Cleanup function
        return () => {
            // Check if hcaptcha is loaded before trying to remove it
            if (window.hcaptcha) {
                window.hcaptcha.reset();
            }
            if (script) {
                script.remove();
            }
        };

    }, [sitekey, handleCaptchaResponse]);

    return (
        <div className="rsssl-captcha"
             style={{display: 'flex', flexDirection: 'column', alignItems: 'center', marginBottom: '20px'}}>
            <div id='hcaptchaContainer'></div>
        </div>
    );
};

export default HCaptcha;