import React from 'react';
import ReCaptcha from './ReCaptcha';
import HCaptcha from './HCaptcha';
import useFields from '../FieldsData';
import useCaptchaData from "./CaptchaData";
import {__} from '@wordpress/i18n';
import {useEffect, useState} from "@wordpress/element";
import ErrorBoundary from "../../utils/ErrorBoundary";
import Button from "../Button";

const Captcha = ({props}) => {
    const {getFieldValue, updateField, saveFields, getField} = useFields();
    const enabled_captcha_provider = getFieldValue('enabled_captcha_provider');
    const siteKey = getFieldValue(`${enabled_captcha_provider}_site_key`);
    const secretKey = getFieldValue(`${enabled_captcha_provider}_secret_key` );
    const fully_enabled = getFieldValue('captcha_fully_enabled');
    const {verifyCaptcha, setReloadCaptcha, removeRecaptchaScript} = useCaptchaData();
    const [showCaptcha, setShowCaptcha] = useState(false);
    const [buttonEnabled, setButtonEnabled] = useState(false);



    const handleCaptchaResponse = (response) => {
        verifyCaptcha(response).then((response) => {
            if (response && response.success) {
                updateField('captcha_fully_enabled', 1);
                saveFields(false, false, true);
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
        if (fully_enabled) {
            updateField('captcha_fully_enabled', 1);
            saveFields(false, false);
        }
    }, [fully_enabled]);

    useEffect(() => {
        setShowCaptcha(false);
        //based on the provider the keys need certain length if hcapthca the length is 36 and recapthca 40
        switch (enabled_captcha_provider) {
            case 'recaptcha':
                if (siteKey.length === 40 && secretKey.length === 40) {
                    setButtonEnabled(true);
                } else {
                    setButtonEnabled(false);
                }
                break;
            case 'hcaptcha':
                if (siteKey.length === 36 && secretKey.length === 35) {
                   setButtonEnabled(true);
                } else {
                    setButtonEnabled(false);
                }
                break;
        }
    }, [siteKey, secretKey, enabled_captcha_provider]);


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
                    <button
                            disabled={!buttonEnabled}
                            className={`button button-primary ${!buttonEnabled ? 'rsssl-learning-mode-disabled' : ''}`}
                        // style={{display: !showCaptcha? 'none': 'block'}}
                            onClick={() => setShowCaptcha(true)}> {__('Validate CAPTCHA', 'really-simple-ssl')} </button>)
                }
            </ErrorBoundary>
        </div>
    );
};

export default Captcha;