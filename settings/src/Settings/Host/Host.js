import {
    SelectControl,
} from '@wordpress/components';
import {useRef, useEffect, memo} from "@wordpress/element";
import useFields from "../FieldsData";
import useHostData from "./HostData";
import {__} from "@wordpress/i18n";

const Host = ({field}) => {
    const {updateField, setChangedField, saveFields, handleNextButtonDisabled} = useFields();
    const disabled = useRef(false);
    const {fetchHosts, hosts, hostsLoaded} = useHostData();

    useEffect ( () => {
        if ( !hostsLoaded ) {
            fetchHosts();
        }
    }, []);
    const onChangeHandler = async (fieldValue) => {
        //force update, and get new fields.
        handleNextButtonDisabled(true);
        disabled.current = true;
        updateField(field.id, fieldValue);
        setChangedField(field.id, fieldValue);

        await saveFields(true, false);

        handleNextButtonDisabled(false);
        disabled.current = false;
    }

    let loadedHosts = hostsLoaded ? hosts : [];
    let options = [];
    let item = {
        label: __('Optional. You can select your hosting provider if available for specific integrations.', 'really-simple-ssl'),
        value: '',
    };
    options.push(item);
    for (let key in loadedHosts) {
        if (loadedHosts.hasOwnProperty(key)) {
            let item = {};
            item.label = loadedHosts[key].name;
            item.value = key;
            options.push(item);
        }
    }

    return (
          <SelectControl
              className="rsssl-select"
              label={ field.label }
              onChange={ ( fieldValue ) => onChangeHandler(fieldValue) }
              value= { field.value }
              options={ options }
              disabled={disabled.current}
          />
    )
}
export default memo(Host);