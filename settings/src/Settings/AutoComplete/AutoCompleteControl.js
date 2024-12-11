/*
* The native selectControl doesn't allow disabling per option.
*/

import DOMPurify from "dompurify";
import {Autocomplete} from "@mui/material";
import TextField from '@material-ui/core/TextField';
import './AutoComplete.scss';
import { makeStyles } from "@material-ui/styles";

const useStyles = makeStyles(() => ({
    autoComplete: {
        fontSize: "12px"
    }
}));

const AutoCompleteControl = ({field, disabled, value, options, label, onChange }) => {
    let selectDisabled = !Array.isArray(disabled) && disabled;
    const classes = useStyles();

    return (
        <>
            <Autocomplete
                classes={{
                    input: classes.autoComplete,
                    option: classes.autoComplete
                }}
                disabled={selectDisabled}
                disablePortal
                value={ value }
                id={field.id}
                options={options}
                isOptionEqualToValue={(option, value) => {
                    const optionValue = typeof option.value === "string" ? option.value.toLowerCase() : option.value;
                    const valueValue = typeof value.value === "string" ? value.value.toLowerCase() : value.value;
                    return optionValue === valueValue;
                }}
                getOptionLabel={(option) => {
                    if ( option && option.label ) {
                        return option.label;
                    }
                    const selectedOption = options.find( item => item.value === option );
                    if ( selectedOption ) {
                        return selectedOption.label;
                    }
                    return option;
                } }
                onChange={(event, newValue) => {
                    let value = newValue && newValue.value ? newValue.value : '';
                    onChange(value);
                }}
                renderInput={(params) => <TextField {...params}
                    label={label}
                    margin="normal"
                    variant="outlined"
                    fullWidth
                />}
            />
        </>
    );
}
export default AutoCompleteControl