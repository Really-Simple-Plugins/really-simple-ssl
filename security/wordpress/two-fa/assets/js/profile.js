class Profile extends BaseAuth {
    init() {

        this.assignClickListener('download_codes', this.download_codes);
        this.assignClickListener('two-factor-qr-code', this.copyTextAndShowMessage);
        this.assignClickListener('totp-key', this.copyTextAndShowMessage);

        const qrCodeContainer = this.getElement('qr-code-container');
        const enableCheckbox = this.getElement('two-factor-authentication');
        const tableRowSelection = this.getElement('selection_two_fa');
        const methodSelection = document.querySelectorAll('input[name="preferred_method"]');
        const validationEmail = document.getElementById('rsssl_verify_email');
        const change2faConfig = this.getElement('change_2fa_config');
        let that = this;
        if (qrCodeContainer) {
            qrCodeContainer.style.display = "none";
            if (!enableCheckbox.checked) {
                tableRowSelection.style.display = "none";
                qrCodeContainer.style.display = "none";
            }
        }
        if(enableCheckbox) {
            let parent = this;
            enableCheckbox.addEventListener("change", function () {
                if (this.checked) {
                    tableRowSelection.style.display = "table-row";
                    let selectedMethod = document.querySelector('input[name="preferred_method"]:checked');
                    if (selectedMethod && selectedMethod.value === "totp") {
                        qrCodeContainer.style.display = "block";
                        parent.qr_generator();
                    } else {
                        qrCodeContainer.style.display = "none";
                    }
                } else {
                    tableRowSelection.style.display = "none";
                    qrCodeContainer.style.display = "none";
                    let selectedMethod = document.querySelector('input[name="preferred_method"]:checked');
                    selectedMethod.value = "none";
                }
            });
        }

        if(methodSelection.length > 0 ) {
            let parent = this;
            methodSelection.forEach(function (element) {
                element.addEventListener("change", function () {
                    let selectedMethod = document.querySelector('input[name="preferred_method"]:checked').value;
                    if (selectedMethod === "totp") {
                        if(validationEmail) {
                            validationEmail.style.display = "none";
                        }
                        qrCodeContainer.style.display = "block";
                        parent.qr_generator();
                    } else if(selectedMethod === "email") {
                        qrCodeContainer.style.display = "none";
                        if(validationEmail) {
                            validationEmail.style.display = "table-row";
                        }
                        let data = {
                            action: 'change_method_to_email',
                            provider: selectedMethod,
                            user_id: rsssl_profile.user_id,
                            login_nonce: document.getElementById('rsssl_two_fa_nonce').value,
                            redirect_to: rsssl_profile.redirect_to,
                            profile: true
                        };
                        fetch(rsssl_profile.ajax_url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                            },
                            body: new URLSearchParams(data)
                        })
                            .then(response => response.json())
                            .then(responseData => {
                                // Expected structure: { success: true, data: { message: "Verification code sent", token: ... } }
                                let errorDiv = document.getElementById('login-message');
                                let inPutField = document.getElementById('rsssl-two-factor-email-code');
                                if (inPutField) {
                                    if (!errorDiv) {
                                        errorDiv = document.createElement('p');
                                        errorDiv.classList.add('notice', 'notice-success');
                                        inPutField.insertAdjacentElement('afterend', errorDiv);
                                    }
                                    // Use the message returned from your PHP callback
                                    if (responseData.data.message) {
                                        errorDiv.innerHTML = `<p>${responseData.data.message}</p>`;
                                    } else {
                                        console.error('No message returned from the server.');
                                    }
                                    // Optionally, do something with responseData.data.token if needed.
                                    setTimeout(() => {
                                        errorDiv.remove();
                                    }, 5000);
                                }
                            })
                            .catch(that.logFetchError);
                    } else {
                        qrCodeContainer.style.display = "none";
                    }
                });
            });
        }

        let resendButton = this.getElement('rsssl_resend_code_action');
        if(resendButton !== null) {
            resendButton.addEventListener('click', (event) => {
                event.preventDefault();
                let data = {
                    action: 'resend_email_code_profile',
                    user_id: this.settings.user_id,
                    login_nonce: document.getElementById('rsssl_two_fa_nonce').value,
                    provider: 'email',
                    profile: true
                };
                let ajaxUrl = rsssl_profile.ajax_url;
                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: new URLSearchParams(data)
                })
                    .then(response => response.json())
                    .then(responseData => {
                        // responseData will have the structure: { success: true, data: { message: "..." } }
                        let errorDiv = document.getElementById('login-message');
                        let inPutField = document.getElementById('rsssl-two-factor-email-code');
                        if (inPutField) {
                            if (!errorDiv) {
                                errorDiv = document.createElement('p');
                                errorDiv.classList.add('notice', 'notice-success');
                                inPutField.insertAdjacentElement('afterend', errorDiv);
                            }
                            errorDiv.innerHTML = `<p>${responseData.data.message}</p>`;
                            // Fade out the message after 5 seconds.
                            setTimeout(() => {
                                errorDiv.remove();
                            }, 5000);
                        }
                    })
                    .catch(this.logFetchError);
            });
        }


        if (change2faConfig) {
            change2faConfig.addEventListener('click', function (e) {
                e.preventDefault();
                let inputField = document.createElement('input');
                inputField.setAttribute('type', 'hidden');
                inputField.setAttribute('name', 'change_2fa_config_field');
                inputField.setAttribute('value', 'true');
                document.getElementById('change_2fa_config').insertAdjacentElement('afterend', inputField);
                // we uncheck Enable Two-Factor Authentication
                let enableCheckbox = document.getElementById("two-factor-authentication");
                enableCheckbox.checked = false;
                let profileForm = document.getElementById('your-profile');
                if (profileForm) {
                    profileForm.requestSubmit();
                }
            });
        }
    }
}