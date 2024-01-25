/*
* The native selectControl doesn't allow disabling per option.
*/

import DOMPurify from "dompurify";
import {Autocomplete} from "@mui/material";
import TextField from '@material-ui/core/TextField';
import './AutoComplete.scss';
import { makeStyles } from "@material-ui/styles";
import {useEffect, useState} from "react";

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
                isOptionEqualToValue={(option, value) => {
                    return option.value === value;
                }}
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