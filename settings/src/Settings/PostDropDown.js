import React, { useState, useEffect } from "react";
import * as rsssl_api from "../utils/api";

const PostDropdown = () => {
    const [posts, setPosts] = useState([]);
    const [selectedPost, setSelectedPost] = useState("404");
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
            .then((data) => setPosts(data));
    }, [baseUrl]);

    const handleSelectChange = (event) => {
        const selectedPostId = event.target.value;
        setSelectedPost(selectedPostId);
        setSearchTerm("");

        // Update the change_login_url_failure_url field in rsssl_settings
        const fieldsToUpdate = [
            {
                id: "change_login_url_failure_url",
                value: selectedPostId,
            },
        ];
        rsssl_api.setFields(fieldsToUpdate);
    };

    const filteredPosts = searchTerm
        ? posts.filter((post) =>
            post.title.rendered.toLowerCase().includes(searchTerm)
        )
        : posts;

    return (
        <div>
            <label htmlFor="post-input">Select or search a post:</label>
            <select
                id="post-input"
                value={selectedPost}
                onChange={handleSelectChange}
            >
                <option value="">Select a post</option>
                <option value="404">404 (default)</option>
                {filteredPosts.map((post) => (
                    <option key={post.id} value={post.id}>
                        {post.title.rendered}
                    </option>
                ))}
            </select>
        </div>
    );
};

export default PostDropdown;