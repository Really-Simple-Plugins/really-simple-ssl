import {useRef, useEffect, memo} from "@wordpress/element";
import useFields from "../FieldsData";
import AutoCompleteControl from "../AutoComplete/AutoCompleteControl";
import useHostData from "./HostData";
import {__} from "@wordpress/i18n";

const Host = ({field, showDisabledWhenSaving=true}) => {
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
        if (showDisabledWhenSaving) {
            disabled.current = true;
        }
        updateField(field.id, fieldValue);
        setChangedField(field.id, fieldValue);

        await saveFields(true, false);

        handleNextButtonDisabled(false);
        disabled.current = false;
    }

    let loadedHosts = hostsLoaded ? hosts : [];
    let options = [];
    let item = {
        label: __('Optional - Select your hosting provider.', 'really-simple-ssl'),
        value: '',
    };
    if ( field.value.length === 0 ) {
        options.push(item);
    }
    for (let key in loadedHosts) {
        if (loadedHosts.hasOwnProperty(key)) {
            let item = {};
            item.label = loadedHosts[key].name;
            item.value = key;
            options.push(item);
        }
    }

    return (
          <AutoCompleteControl
              className="rsssl-select"
              field={field}
              label={ field.label }
              onChange={ ( fieldValue ) => onChangeHandler(fieldValue) }
              value= { field.value }
              options={ options }
              disabled={disabled.current}
          />
    )
}
export default memo(Host);