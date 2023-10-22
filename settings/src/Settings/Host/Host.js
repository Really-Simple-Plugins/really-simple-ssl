import {
    SelectControl,
} from '@wordpress/components';
import {useRef, useEffect, memo} from "@wordpress/element";
import useFields from "../FieldsData";
import useHostData from "./HostData";

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
        let field = field;
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
    for (let key in loadedHosts) {
        if (loadedHosts.hasOwnProperty(key)) {
            let item = {};
            item.label = loadedHosts[key].name;
            item.value = key;
            options.push(item);
        }
    }

    console.log(hosts);
    console.log(options);

    return (
          <SelectControl
              label={ field.label }
              onChange={ ( fieldValue ) => onChangeHandler(fieldValue) }
              value= { field.value }
              options={ options }
              disabled={disabled.current}
          />
    )
}
export default memo(Host);