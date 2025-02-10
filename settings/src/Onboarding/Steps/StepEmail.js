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

    // Initialize state if needed
    useEffect(() => {
        if (!fieldsLoaded) {
            fetchFieldsData();
        }
    }, []);

    // Set initial email if available
    useEffect(() => {
        const savedEmail = getFieldValue('notifications_email_address');
        if (savedEmail && !email) {
            setEmail(savedEmail);
        }
    }, [fieldsLoaded, getFieldValue, email, setEmail]);

    if (!fieldsLoaded) {
        return null;
    }

    return (
        <div className="rsssl-step-email">
            <div className="rsssl-email-input">
                <input
                    type="email"
                    value={email || ''}
                    placeholder={__("Your email address", "really-simple-ssl")}
                    onChange={(e) => setEmail(e.target.value)}
                />
            </div>
            <div className="rsssl-email-options">
                <label className="rsssl-tips-checkbox">
                    <input
                        onChange={(e) => setIncludeTips(e.target.checked)}
                        type="checkbox"
                        checked={!!includeTips}
                    />
                    <span>{__("Include 6 Tips & Tricks to get started with Really Simple Security.", "really-simple-ssl")}</span>&nbsp;
                    <a
                        href="https://really-simple-ssl.com/legal/privacy-statement/"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        {__("Privacy Statement", "really-simple-ssl")}
                    </a>
                </label>
            </div>
        </div>
    );
}

export default memo(StepEmail)