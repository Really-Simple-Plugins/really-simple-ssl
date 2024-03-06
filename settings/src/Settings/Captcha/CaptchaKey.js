import React from 'react';

import Icon from "../../utils/Icon";
import useFields from "../FieldsData";
import {TextControl} from "@wordpress/components"; // assuming you're using WordPress components

const CaptchaKey = ({ field, fields, label }) => {
    const { getFieldValue, setChangedField, updateField, saveFields} = useFields();

    let fieldValue = getFieldValue(field.id);
    let captchaVerified = getFieldValue('captcha_fully_enabled');

    const onChangeHandler = async (fieldValue) => {
        setChangedField(field.id, fieldValue);
        setChangedField('captcha_fully_enabled', false);
        updateField(field.id, fieldValue);
        await saveFields(false, false);
    }

    return (
        <>
            <TextControl
                required={field.required}
                placeholder={field.placeholder}
                help={field.comment}
                label={label}
                onChange={(value) => onChangeHandler(value)}
                value={fieldValue}
            />

            <div className="rsssl-email-verified" >
                {Boolean(captchaVerified)
                    ? <Icon name='circle-check' color={'green'} />
                    : <Icon name='circle-times' color={'red'} />
                }
            </div>
        </>
    );
}

export default CaptchaKey;