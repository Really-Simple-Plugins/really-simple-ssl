import React, { useState, useEffect } from "react";
import { __ } from '@wordpress/i18n';
import Autocomplete from '@material-ui/lab/Autocomplete';
import TextField from '@material-ui/core/TextField';
import apiFetch from '@wordpress/api-fetch';
import * as rsssl_api from "../utils/api";
import { createTheme, ThemeProvider } from '@material-ui/core/styles';

const theme = createTheme({
    typography: {
        fontSize: 12,
        fontWeightMedium: 400,
    },
    overrides: {
        MuiInputBase: {
            root: {
                fontSize: '12px !important',
                fontWeight: '400 !important',
            }
        },
    },
});

const PostDropdown = ({ fields, setFields, updateField }) => {
    const [posts, setPosts] = useState([]);
    const [selectedPost, setSelectedPost] = useState("");
    const [searchTerm, setSearchTerm] = useState("");
    const [changeLoginUrlFailureUrl, setChangeLoginUrlFailureUrl] = useState("");

    // Fetch the value of an RSSSL option in the WordPress database when the component mounts.
    useEffect(() => {
        const changeLoginUrlFailureUrl = fields.find(
            (field) => field.id === "change_login_url_failure_url"
        ).value;
        setChangeLoginUrlFailureUrl(changeLoginUrlFailureUrl);
        setSelectedPost(changeLoginUrlFailureUrl);
    }, [fields]);

    // Fetch the list of posts from the WordPress database when the component mounts.
    useEffect(() => {
        apiFetch({ path: '/wp/v2/posts?per_page=100' })
            .then((data) => {
                const formattedData = data.map(post => ({
                    title: post.title.rendered,
                    id: post.id
                }));
                setPosts([{ title: "404 (default)", id: "404_default" }, ...formattedData]);
            });
    }, []);

    // Fetch the data for the selected post from the WordPress database when the component mounts.
    useEffect(() => {
        console.log(changeLoginUrlFailureUrl);
        if (changeLoginUrlFailureUrl === "404_default" || changeLoginUrlFailureUrl === "404") {
            setSelectedPost("404 (default)");
            return { title: "404 (default)", id: "404_default" };
        } else {
            apiFetch({ path: `wp/v2/posts/${changeLoginUrlFailureUrl}` })
                .then((data) => {
                    if (data.title) {
                        setSelectedPost(data.title.rendered);
                    }
                });
        }
    }, [changeLoginUrlFailureUrl]);


    const handleSearchTermChange = (event, value) => {
        if (value === null) {
            setSelectedPost("");
            setChangeLoginUrlFailureUrl("");
        } else {
            setSelectedPost(value.title);
            setChangeLoginUrlFailureUrl(value.id);
        }

        // Update the value of the `change_login_url_failure_url` field in the `fields` array.
        const updatedFields = fields.map((field) => {
            if (field.id === "change_login_url_failure_url") {
                return {
                    ...field,
                    value: value ? value.id : "",
                };
            } else {
                return field;
            }
        });

        // Update the fields in the parent component's state.
        rsssl_api.setFields(updatedFields);
    };

    const filteredPosts = posts.filter((post) => {
        return post.title.toLowerCase().includes(searchTerm.toLowerCase());
    });

    return (
        <div>
            <label htmlFor="rsssl-filter-post-input">
                {__("Redirect to this post when someone tries to access /wp-admin or /wp-login.php. The default is a 404 page.","really-simple-ssl")}
            </label>
            <ThemeProvider theme={theme}>
                <Autocomplete
                    options={filteredPosts}
                    getOptionLabel={(option) => option.title}
                    renderInput={(params) => (
                        <TextField
                            {...params}
                            variant="outlined"
                            placeholder={__('Search for a post.','really-simple-ssl')}
                        />
                    )}
                    getOptionSelected={(option, value) => {
                        if (value === null) {
                            return option.id === "404_default";
                        } else {
                            return value.title;
                        }
                    }}
                    onChange={handleSearchTermChange}
                    value={
                        selectedPost
                            ? { title: selectedPost, id: changeLoginUrlFailureUrl }
                            : null
                    }
                />
            </ThemeProvider>
        </div>
    );
};

export default PostDropdown;