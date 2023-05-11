/**
 * This file contains the PostDropdown component.
 *
 * This component displays a dropdown menu that allows the user to select a post
 * from a list of posts fetched from the WordPress database. The selected post
 * is then used to set a value in an options array stored in the WordPress
 * database. The component also allows the user to search for posts by typing
 * in a search box.
 */

import React, { useState, useEffect } from "react";
import { __ } from '@wordpress/i18n';
import TextField from '@material-ui/core/TextField';
import apiFetch from '@wordpress/api-fetch';
import useFields from "./FieldsData";

const PostDropdown = ({ field }) => {
    const [posts, setPosts] = useState([]);
    const [selectedPost, setSelectedPost] = useState("");
    const {updateField, setChangedField} = useFields();
    const [ThemeProvider, setThemeProvider] = useState(null);
    const [theme, setTheme] = useState(null);
    const [Autocomplete, setAutocomplete] = useState(null);
    useEffect( () => {
        import('@material-ui/lab/Autocomplete').then(({ default: Autocomplete }) => {
            setAutocomplete(() => Autocomplete);
        });

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

    // Fetch the list of posts from the WordPress database when the component mounts.
    useEffect(() => {
        apiFetch({ path: '/wp/v2/pages?per_page=100' })
            .then((data) => {
                const formattedData = data.map(post => ({
                    title: post.title.rendered,
                    id: post.id
                }));
                setPosts([{ 'title': "404 (default)", 'id': "404_default" }, ...formattedData]);
            });
    }, []);

    // Fetch the data for the selected post from the WordPress database when the component mounts.
    useEffect(() => {
        if (field.value !== '404_default') {
            apiFetch({ path: `wp/v2/pages/${field.value}` })
                .then((data) => {
                    if (data.title) {
                        setSelectedPost({ 'title': data.title.rendered, 'id': field.value })
                    } else {
                        setSelectedPost({ 'title': "404 (default)", 'id': '404_default' })
                    }
                });
        } else {
            setSelectedPost({ 'title': "404 (default)", 'id': '404_default' })
        }
    }, [field.value]);


    if (!Autocomplete || !ThemeProvider || !theme) {
        return null;
    }

    return (
        <div>
            <label htmlFor="rsssl-filter-post-input">
                {__("Redirect to this post when someone tries to access /wp-admin or /wp-login.php. The default is a 404 page.","really-simple-ssl")}
            </label>
            <ThemeProvider theme={theme}>
                <Autocomplete
                    options={posts}
                    getOptionLabel={(option) => option.title ? option.title : ''}
                    renderInput={(params) => (
                        <TextField
                            {...params}
                            variant="outlined"
                            placeholder={__('Search for a post.','really-simple-ssl')}
                        />
                    )}
                    getOptionSelected={(option, value) => {
                        return option.id === value.id;
                    }}
                    onChange={(event, newValue) => {
                        let value = newValue && newValue.id ? newValue.id : '404_default';
                        updateField(field.id, value);
                        setChangedField( field.id, value );
                    }}
                    value={selectedPost}
                />
            </ThemeProvider>
        </div>
    );
};

export default PostDropdown;