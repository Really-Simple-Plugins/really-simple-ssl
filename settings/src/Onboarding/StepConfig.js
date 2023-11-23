import {memo, useEffect} from "@wordpress/element";
import {__} from "@wordpress/i18n";
import useOnboardingData from "./OnboardingData";
import useFields from "../Settings/FieldsData";
import Host from "../Settings/Host/Host";
import ListItems from "./ListItems";
const StepConfig = () => {
    const { fetchFieldsData, getField, fieldsLoaded, updateField, setChangedField, saveFields} = useFields();
    const {
        overrideSSL,
        certificateValid
    } = useOnboardingData();

    useEffect(() => {
        if ( !fieldsLoaded ) {
            fetchFieldsData();
        }
    }, []);

    const onChangeCloudFlareHandler = async (fieldValue) => {
        updateField('cloudflare_enabled', fieldValue);
        setChangedField('cloudflare_enabled', fieldValue);
        await saveFields(true, false);
    }

    let otherHostsField = fieldsLoaded && getField('other_host_type');
    let CloudFlareEnabled = fieldsLoaded && getField('cloudflare_enabled');
    return (
        <>
            <Host field={otherHostsField}/>
            <label>
                <input onChange={ (e) => onChangeCloudFlareHandler(e.target.checked)} type="checkbox" checked={CloudFlareEnabled.value} />{__("I use CloudFlare.","really-simple-ssl")}
            </label>
            <ul>
                <ListItems />
            </ul>
            { !certificateValid &&
                <>
                    <div className="rsssl-modal-description">
                        <a href="#" onClick={ (e) => refreshSSLStatus(e)}>
                            { __("Refresh SSL status", "really-simple-ssl")}
                        </a>.&nbsp;{__("The SSL detection method is not 100% accurate.", "really-simple-ssl")}&nbsp;
                        {__("If you’re certain an SSL certificate is present, and refresh SSL status does not work, please check “Override SSL detection” to continue activating SSL.", "really-simple-ssl")}
                        &nbsp;<label className="rsssl-override-detection-toggle">
                            <input
                                onChange={ (e) => setOverrideSSL(e.target.checked)}
                                type="checkbox"
                                checked={overrideSSL} />
                            {__("Override SSL detection.","really-simple-ssl")}
                        </label>
                    </div>

                </>
            }

        </>
    );
}
export default memo(StepConfig)