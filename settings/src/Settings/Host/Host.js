import { useState, useEffect, memo } from "@wordpress/element";
import { ThemeProvider } from '@mui/material/styles';
import useFields from "../FieldsData";
import AutoCompleteControl from "../AutoComplete/AutoCompleteControl";
import useHostData from "./HostData";
import { __ } from "@wordpress/i18n";
import autoCompleteSharedTheme from "../../utils/autoCompleteTheme";
const Host = ({ field, showDisabledWhenSaving = true }) => {
    const { updateField, setChangedField, saveFields, handleNextButtonDisabled } = useFields();
    const [disabled, setDisabled] = useState(false);
    const { fetchHosts, hosts, hostsLoaded } = useHostData();

    useEffect(() => {
        if (!hostsLoaded) {
            fetchHosts();
        }
    }, []);

    useEffect(() => {
        handleNextButtonDisabled(disabled);
    }, [disabled]);

    const onChangeHandler = async (fieldValue) => {
        if (showDisabledWhenSaving) {
            setDisabled(true);
        }
        updateField(field.id, fieldValue);
        setChangedField(field.id, fieldValue);
        await saveFields(true, false);
        setDisabled(false);
    };

    let loadedHosts = hostsLoaded ? hosts : [];
    let options = [];
    let item = {
        label: __('Optional - Select your hosting provider.', 'really-simple-ssl'),
        value: '',
    };
    if (field.value.length === 0) {
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
        <ThemeProvider theme={autoCompleteSharedTheme}>
            <AutoCompleteControl
                className="rsssl-select"
                field={field}
                label={field.label}
                onChange={(fieldValue) => onChangeHandler(fieldValue)}
                value={field.value}
                options={options}
                disabled={disabled}
            />
        </ThemeProvider>
    );
};

export default memo(Host);
