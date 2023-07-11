import React, { useState } from "react";
import * as rsssl_api from "../utils/api";
import useFields from "./FieldsData";

const VerificationInput = ({ field }) => {
    const [input, setInput] = useState("");
    const [valid, setValid] = useState(null);
    const [loading, setLoading] = useState(false);
    const { updateField, setChangedField } = useFields();

    const checkCode = async () => {
        setLoading(true);
        try {
            // replace `/api/get_code` with your actual API endpoint
            rsssl_api.doAction('verify_email', { field, input }).then((response) => {
                // console.log(response);
                const verificationCode = response; // Assume code comes in 'code' key

                if (input === verificationCode) {
                    setValid(true);
                } else {
                    setValid(false);
                }
            }).catch(err => {
                console.error(err);
                setValid(false);
            }).finally(() => {
                setLoading(false);
            });
        } catch (err) {
            console.error(err);
            setValid(false);
            setLoading(false);
        }
    };

    const handleChange = (e) => {
        setInput(e.target.value);
    };

    return (
        <div>
            <label htmlFor="rsssl-verify-email">
                {field.label}
            </label>
            <input
                type="text"
                maxLength="6"
                value={input}
                onChange={handleChange}
            />
            <button onClick={checkCode} disabled={loading}>
                {loading ? "Verifying..." : "Verify"}
            </button>
            {valid === true && <div>Code is valid!</div>}
            {valid === false && <div>Code is invalid!</div>}
        </div>
    );
}

export default VerificationInput;