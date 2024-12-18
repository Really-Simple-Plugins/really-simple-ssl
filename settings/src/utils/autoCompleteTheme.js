// theme.js
import { createTheme } from '@mui/material/styles';

const autoCompleteSharedTheme = createTheme({
    typography: {
        fontSize: 12,
        fontFamily: 'inherit',
    },
    components: {
        MuiAutocomplete: {
            styleOverrides: {
                inputRoot: {
                    '& .MuiAutocomplete-input': {
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
            },
        },
        MuiInputBase: {
            styleOverrides: {
                root: {
                    fontSize: '12px',
                    fontFamily: 'inherit',
                    height: '40px',
                },
            },
        },
        MuiList: {
            styleOverrides: {
                root: {
                    fontSize: '8px',
                },
            },
        },
    },
});

export default autoCompleteSharedTheme;