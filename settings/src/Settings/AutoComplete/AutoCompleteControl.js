/*
* The native selectControl doesn't allow disabling per option.
*/

import DOMPurify from "dompurify";
import {Autocomplete} from "@mui/material";
import TextField from '@material-ui/core/TextField';
import './AutoComplete.scss';
import useFields from "../FieldsData";
import { makeStyles } from "@material-ui/styles";

const useStyles = makeStyles(() => ({
    autoComplete: {
        fontSize: "12px"
    }
}));

const AutoCompleteControl = ({field, disabled, value, options, label, onChange }) => {
    let selectDisabled = !Array.isArray(disabled) && disabled;
    const classes = useStyles();

    const {updateField, setChangedField} = useFields();
    console.log(options);
    return (
        <>
            <div className="components-base-control">
                <div className="components-base-control__field">
                    <div data-wp-component="HStack" className="components-flex components-select-control">
                        <Autocomplete
                            classes={{
                                input: classes.autoComplete,
                                option: classes.autoComplete
                            }}
                            disabled={selectDisabled}
                            disablePortal
                            value={value}
                            id={field.id}
                            options={options}
                            getOptionSelected={(option, value) => {
                                return option.value === value.value;
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
                    </div>
                </div>
            </div>
            {field.comment && (
                <div className="rsssl-comment" dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(field.comment) }} ></div>
                /* nosemgrep: react-dangerouslysetinnerhtml */
            )}
        </>
    );
}
export default AutoCompleteControl