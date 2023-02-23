import React, { useState, useEffect } from "react";
import { __ } from '@wordpress/i18n';
import * as rsssl_api from "../utils/api";

const PostDropdown = () => {
    const [posts, setPosts] = useState([]);
    const [selectedPost, setSelectedPost] = useState("");
    const [defaultPost, setDefaultPost] = useState({ title: { rendered: "404 (default)" }, id: "404_default" });
    const [searchTerm, setSearchTerm] = useState("");

    const baseUrl = rsssl_settings.site_url;

    useEffect(() => {
        // Fetch the value of the "change_login_url_failure_url" option
        rsssl_api.getFields().then((response) => {
            const changeLoginUrlFailureUrl = response.fields.find(
                (field) => field.id === "change_login_url_failure_url"
            ).value;
            setSelectedPost(changeLoginUrlFailureUrl);
        });
    }, []);

    useEffect(() => {
        const endpoint = `${baseUrl}wp/v2/posts?per_page=100`;
        fetch(endpoint)
            .then((response) => response.json())
            .then((data) => {
                // remove any existing post with id "404_default"
                const filteredData = data.filter(post => post.id !== "404_default");
                // add default post to beginning of posts state
                setPosts([defaultPost, ...filteredData]);
            });
    }, [baseUrl]);


    useEffect(() => {
        // Fetch the value of the "change_login_url_failure_url" option
        rsssl_api.getFields().then((response) => {
            const changeLoginUrlFailureUrl = response.fields.find(
                (field) => field.id === "change_login_url_failure_url"
            ).value;
            setSelectedPost(changeLoginUrlFailureUrl);

            // Fetch the post data for the selected post
            const endpoint = `${baseUrl}wp/v2/posts/${changeLoginUrlFailureUrl}`;
            fetch(endpoint)
                .then((response) => response.json())
                .then((data) => {
                    if (data.title) {
                        setSelectedPost(data.title.rendered);
                    }
                });
        });
    }, []);

    const filteredPosts = searchTerm
        ? posts.filter((post) =>
            post.title.rendered.toLowerCase().includes(searchTerm)
        )
        : posts.filter((post) => {
            if (post.id === "404_default") {
                return defaultPost;
            }
            return post;
        });

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

    return (
        <div>
            <label htmlFor="rsssl-filter-post-input">
                {__("Redirect to this post when someone tries to access /wp-admin or /wp-login.php. The default is a 404 page","really-simple-ssl")}
            </label>
            <div style={{ position: "relative" }}>
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