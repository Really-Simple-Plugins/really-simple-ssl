import {memo, useEffect} from "@wordpress/element";
import {__} from "@wordpress/i18n";
import useOnboardingData from "../OnboardingData";
import useFields from "../../Settings/FieldsData";
import Host from "../../Settings/Host/Host";
import ListItem from "../Items/ListItem";
const StepConfig = ({isModal}) => {
    const { fetchFieldsData, getField, fieldsLoaded, updateField, setChangedField, saveFields} = useFields();
    const {
        currentStep,
    } = useOnboardingData();

    useEffect(() => {
        if ( !fieldsLoaded ) {
            fetchFieldsData();
        }
    }, []);

    let otherHostsField = fieldsLoaded && getField('other_host_type');
    let items = currentStep.items ? currentStep.items : [];

    if ( rsssl_settings.cloudflare ) {
        let cfItem = {
            status: 'success',
            title: "CloudFlare",
            id: 'cf'
        }
        items.unshift(cfItem);
    }

    return (
        <>
            { isModal && <Host field={otherHostsField} showDisabledWhenSaving={false}/>}
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