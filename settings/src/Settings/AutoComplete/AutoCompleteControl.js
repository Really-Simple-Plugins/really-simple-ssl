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

    const [ThemeProvider, setThemeProvider] = useState(null);
    const [theme, setTheme] = useState(null);
    const [selectedValue, setSelectedValue] = useState('');

    useEffect( () => {
        //get value object from selected string
        let foundOption = options.find(option => option.value === value );
        if (!foundOption) {
            foundOption = options.find(option => option.value === '');
        }
        setSelectedValue(foundOption);
    },[]);

    useEffect( () => {


        import ('@material-ui/core/styles').then(({ createTheme,  ThemeProvider }) => {
            setThemeProvider(() => ThemeProvider);
            setTheme(() => createTheme({
                    typography: {
                        fontSize: 12,
                        fontFamily: 'inherit',
                    },
                    overrides: {
                        MuiInputBase: {
                            root: {
                                fontSize: '12px',
                                fontFamily: 'inherit',
                                height: '40px',
                            }
                        },
                        MuiList: {
                            root: {
                                fontSize: '8px',
                            }
                        },
                        MuiAutocomplete: {
                            inputRoot: {
                                '& .MuiAutocomplete-input': {
                                    padding: '0 !important',
                                    border: 0,
                                },
                                flexWrap: 'inherit',
                            },
                            popper: {
                                fontSize: '12px',
                            },
                            paper: {
                                fontSize: '12px',
                            },
                            option: {
                                fontSize: '12px',
                            },
                            root: {
                                padding: 0,
                            }
                        },
                    },
                })
            );
        });

    }, []);

    if (!Autocomplete || !ThemeProvider || !theme) {
        return null;
    }

    console.log('selectedValue',selectedValue);
    console.log('value',value);
    return (
        <>
            <ThemeProvider theme={theme}>

            <Autocomplete
                classes={{
                    input: classes.autoComplete,
                    option: classes.autoComplete
                }}
                disabled={selectDisabled}
                disablePortal
                value={ value }
                open={ selectedValue && selectedValue.value && selectedValue.value.length >= 3 }
                id={field.id}
                options={options}
                getOptionLabel={(option) => {
                    console.log("option $3", option)
                    if ( option && option.label ) {
                        return option.label;
                    }
                    const selectedOption = options.find( item => item.value === option );
                    if ( selectedOption ) {
                        return selectedOption.label;
                    }
                    return option;
                } }
                // isOptionEqualToValue={(option, value) => {
                //     return option.value === value.value;
                // }}
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
            </ThemeProvider>
        </>
    );
}
export default AutoCompleteControl