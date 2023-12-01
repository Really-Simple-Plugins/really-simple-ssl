import {memo, useEffect} from "@wordpress/element";
import {__} from "@wordpress/i18n";
import useOnboardingData from "./OnboardingData";
import useFields from "../Settings/FieldsData";
import Host from "../Settings/Host/Host";
import ListItem from "./Items/ListItem";
const StepConfig = () => {
    const { fetchFieldsData, getField, fieldsLoaded, updateField, setChangedField, saveFields} = useFields();
    const {
        currentStep,
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
    let items = currentStep.items ? currentStep.items : [];

    return (
        <>
            <Host field={otherHostsField}/>
            <label>
                <input onChange={ (e) => onChangeCloudFlareHandler(e.target.checked)} type="checkbox" checked={CloudFlareEnabled.value} />{__("I use CloudFlare.","really-simple-ssl")}
            </label>
            <ul>
                {
                    <ul>
                        { items && items.map( (item, index) => <ListItem key={'step-config-'+index} item={item} />) }
                    </ul>
                }
            </ul>
        </>
    );
}
export default memo(StepConfig)