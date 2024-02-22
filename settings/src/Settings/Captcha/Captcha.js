import React, {useState} from 'react';
import ReCaptcha from './ReCaptcha';
import HCaptcha from './HCaptcha';
import useFields from '../FieldsData';
import useCaptchaData from "./CaptchaData";
import {__} from '@wordpress/i18n';
import {Button} from "@wordpress/components";
import {useEffect} from "@wordpress/element";
import ErrorBoundary from "../../utils/ErrorBoundary";

const Captcha = ({props}) => {
    const {getFieldValue, updateField, saveFields, fetchFields} = useFields();
    const enabled_captcha_provider = getFieldValue('enabled_captcha_provider');
    const siteKey = getFieldValue(`${enabled_captcha_provider}_site_key`);
    const fully_enabled = getFieldValue('captcha_fully_enabled');
    const {verifyCaptcha, setReloadCaptcha, removeRecaptchaScript} = useCaptchaData();
    const [showCaptcha, setShowCaptcha] = useState(false);

    const handleCaptchaResponse = (response) => {
        verifyCaptcha(response).then((response) => {
            setShowCaptcha(false);
            if (response && response.success) {
                updateField('captcha_fully_enabled', true);
                fetchFields();
                saveFields(false, false);
            } else {
                updateField('captcha_fully_enabled', false);
                saveFields(false, false);
            }
        });
    };

    //if we switch to another captcha provider, we need to reset the captcha
    useEffect(() => {
        saveFields(false, false);
    }, [enabled_captcha_provider]);

    useEffect(() => {
        //if the captcha is fully enabled, we don't want to show the captcha anymore
        if (fully_enabled) {
            setShowCaptcha(false);
            // we reload the page to make sure the captcha is not shown anymore.
           saveFields(false, false);
        }
    }, [fully_enabled]);

    useEffect(() => {
        setShowCaptcha(false);
    }, [siteKey]);


    return (
        <div>
            <ErrorBoundary title={__('Reload Captcha' , 'really-simple-ssl')}>
                {enabled_captcha_provider === 'recaptcha' && !fully_enabled && showCaptcha && (
                    <ReCaptcha handleCaptchaResponse={handleCaptchaResponse} />
                )}
                {enabled_captcha_provider === 'hcaptcha' && !fully_enabled && showCaptcha && (
                    <HCaptcha sitekey={siteKey} handleCaptchaResponse={handleCaptchaResponse} captchaVerified={fully_enabled}/>
                )}
                {enabled_captcha_provider !== 'none' && !fully_enabled && (
                    <Button isPrimary={true}
                            text={__('validate CAPTCHA', 'really-simple-ssl')}
                        // style={{display: !showCaptcha? 'none': 'block'}}
                            onClick={() => setShowCaptcha(true)}/>)
                }
            </ErrorBoundary>
        </div>
    );
};

export default Captcha;