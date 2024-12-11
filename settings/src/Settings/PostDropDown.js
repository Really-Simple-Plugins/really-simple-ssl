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
import { ThemeProvider } from '@mui/material/styles';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import autoCompleteSharedTheme from '../utils/autoCompleteTheme';
import AutoCompleteControl from "../settings/AutoComplete/AutoCompleteControl";
import useFields from "./FieldsData";

const PostDropdown = ({ field }) => {
    const [posts, setPosts] = useState([]);
    const [selectedPost, setSelectedPost] = useState("");
    const { updateField, setChangedField } = useFields();

    useEffect(() => {
        // Fetch posts data when the component mounts
        apiFetch({ path: '/wp/v2/pages?per_page=100' }).then((data) => {
            const formattedData = data.map(post => ({
                label: post.title.rendered,
                value: post.id
            }));
            setPosts([{ label: "404 (default)", value: "404_default" }, ...formattedData]);
        });
    }, []);

    useEffect(() => {
        if (field.value !== "404_default") {
            apiFetch({ path: `wp/v2/pages/${field.value}` })
                .then((data) => {
                    if (data.title) {
                        setSelectedPost({ label: data.title.rendered, value: field.value });
                    } else {
                        setSelectedPost({ label: "404 (default)", value: "404_default" });
                    }
                });
        } else {
            setSelectedPost({ label: "404 (default)", value: "404_default" });
        }
    }, [field.value]);

    const handleChange = (newValue) => {
        const value = newValue ? newValue : '404_default';
        updateField(field.id, value);
        setChangedField(field.id, value);
    };

    return (
        <ThemeProvider theme={autoCompleteSharedTheme}>
            <div>
                <label htmlFor="rsssl-filter-post-input">
                    {__("Redirect to this post when someone tries to access /wp-admin or /wp-login.php. The default is a 404 page.", "really-simple-ssl")}
                </label>
                <AutoCompleteControl
                    className="rsssl-select"
                    field={field}
                    label={__("Search for a post.", "really-simple-ssl")}
                    value={selectedPost}
                    options={posts}
                    onChange={handleChange}
                    disabled={false}
                />
            </div>
        </ThemeProvider>
    );
};

export default PostDropdown;
