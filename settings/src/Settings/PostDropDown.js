/**
 * React component for a post dropdown selector.
 *
 * This component renders a dropdown that allows the user to select a post and update an option change_login_url_failure_url in the RSSSL settings.
 * The dropdown includes a search field that allows the user to filter the available posts by title.
 *
 * @return {JSX.Element} The rendered React component.
 */

import React, { useState, useEffect } from "react";
import { __ } from '@wordpress/i18n';
import * as rsssl_api from "../utils/api";

const PostDropdown = () => {
    // State variables for managing the component's state.
    const [posts, setPosts] = useState([]);
    const [selectedPost, setSelectedPost] = useState("");
    const [searchTerm, setSearchTerm] = useState("");
    const [changeLoginUrlFailureUrl, setChangeLoginUrlFailureUrl] = useState("");

    const baseUrl = rsssl_settings.site_url;

    // Fetch the value of an RSSSL option in the WordPress database when the component mounts.
    useEffect(() => {
        // Fetch the value of the "change_login_url_failure_url" option
        rsssl_api.getFields().then((response) => {
            const changeLoginUrlFailureUrl = response.fields.find(
                (field) => field.id === "change_login_url_failure_url"
            ).value;
            setChangeLoginUrlFailureUrl(changeLoginUrlFailureUrl);
            setSelectedPost(changeLoginUrlFailureUrl);
        });
    }, []);

    // Fetch the list of posts from the WordPress database when the component mounts or when the `baseUrl` changes.
    useEffect(() => {
        const endpoint = `${baseUrl}wp/v2/posts?per_page=100`;
        fetch(endpoint)
            .then((response) => response.json())
            .then((data) => {
                // add default post to beginning of posts state so the default always shows as the first option
                setPosts([{ title: { rendered: "404 (default)" }, id: "404_default" }, ...data]);
            });
    }, [baseUrl]);

    // Fetch the data for the selected post from the WordPress database when the component mounts.
    useEffect(() => {
        const endpoint = `${baseUrl}wp/v2/posts/${changeLoginUrlFailureUrl}`;
        fetch(endpoint)
            .then((response) => response.json())
            .then((data) => {
                if (data.title) {
                    setSelectedPost(data.title.rendered);
                }
            });
    }, [changeLoginUrlFailureUrl]);

    // Filter the posts by title based on the current search term.
    const filteredPosts = searchTerm
        ? posts.filter((post) =>
            post.title.rendered.toLowerCase().includes(searchTerm)
        )
        : posts.filter((post) => {
            if (post.id === "404_default") {
                return { title: { rendered: "404 (default)" }, id: "404_default" };
            }
            return post;
        });

    // Handle changes to the search term in the search field.
    const handleSearchTermChange = (event) => {
        const inputValue = event.target.value;
        setSearchTerm(inputValue);
        if (inputValue === "" || selectedPost !== "") {
            setSelectedPost("");
            setSearchTerm("");
        }
        if (event.type === "keydown" && event.key === "backspace") {
            event.target.value = "";
            setSelectedPost("");
        }
    };

    // Render the component's UI.
    return (
        <div>
            <label htmlFor="rsssl-filter-post-input">
                {__("Redirect to this post when someone tries to access /wp-admin or /wp-login.php. The default is a 404 page.","really-simple-ssl")}
            </label>
            <div style={{ position: "relative", width: "350px" }}>
                <input
                    type="text"
                    id="post-input"
                    value={selectedPost !== "" ? selectedPost : searchTerm !== "" ? searchTerm : ""}
                    onChange={handleSearchTermChange}
                    placeholder={selectedPost !== "" ? "" : __("Search for a post","really-simple-ssl")}
                />
                {filteredPosts.length > 0 && !selectedPost && (
                    <div
                        style={{
                            top: "100%",
                            left: 0,
                            width: "100%",
                            border: "1px solid #ccc",
                            borderTop: "none",
                            backgroundColor: "#fff",
                            zIndex: 1,
                        }}
                    >
                        {filteredPosts.map((post) => (
                            <div
                                key={post.id}
                                style={{
                                    padding: "8px",
                                    cursor: "pointer",
                                    borderBottom: "1px solid #ccc",
                                }}
                                onClick={() => {
                                    setSelectedPost(post.title.rendered);
                                    setSearchTerm("");
                                    const fieldsToUpdate = [
                                        {
                                            id: "change_login_url_failure_url",
                                            value: post.id,
                                        },
                                    ];
                                    rsssl_api.setFields(fieldsToUpdate);
                                }}
                            >
                                {post.title.rendered}
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
};

export default PostDropdown;