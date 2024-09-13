import {memo, useEffect} from "@wordpress/element";
import {__} from "@wordpress/i18n";
import useOnboardingData from "../OnboardingData";
import useFields from "../../Settings/FieldsData";

const StepEmail = () => {
    const { fetchFieldsData, getFieldValue, fieldsLoaded} = useFields();
    const {
        email,
        setEmail,
        includeTips,
        setIncludeTips,
    } = useOnboardingData();

    useEffect(() => {
        if ( !fieldsLoaded ) {
            fetchFieldsData();
        }
    }, []);

    useEffect( () => {
        if (getFieldValue('notifications_email_address') !== '' && email==='') {
            setEmail(getFieldValue('notifications_email_address'))
        }
    }, [])
    return (
        <>
            <div>
                <input type="email" value={email} placeholder={__("Your email address", "really-simple-ssl")} onChange={(e) => setEmail(e.target.value)} />
            </div>
            <div>
                <label>
                    <input onChange={ (e) => setIncludeTips(e.target.checked)} type="checkbox" checked={includeTips} />{__("Include 6 Tips & Tricks to get started with Really Simple Security.","really-simple-ssl")}&nbsp;<a href="https://really-simple-ssl.com/legal/privacy-statement/" target="_blank">{__("Privacy Statement", "really-simple-ssl")}</a>
                </label>
            </div>
        </>
    );
}
export default memo(StepEmail)