class Onboarding extends BaseAuth {

    init() {
        const translatableStrings = {
            keyCopied: 'Key copied',
        };

        let endpoints = ['do_not_ask_again', 'skip_onboarding'];
        let that = this;

        endpoints.forEach(endpoint => {
            let endpointsElement = this.getElement(endpoint);
            if (endpointsElement !== null) {
                endpointsElement.addEventListener('click', (event) => { // Use arrow function here
                    event.preventDefault();
                    // we call the performFetchOp method and then log the response
                    this.performFetchOp(`/${endpoint}`, this.settings)
                        .then(response => response.json())
                        // We log the data and redirect to the redirect_to URL
                        .then(data => window
                            .location
                            .href = data.redirect_to)
                        // We catch any errors and log them
                        .catch(this.logFetchError);
                });
            }
        });

        let endpointElem = this.getElement('rsssl_continue_onboarding');
        const handleClick = (event) => {
            event.preventDefault();
            let urlExtension = '';
            let selectedProvider = this.getCheckedInputValue('preferred_method');
            if (selectedProvider === 'email') {
                let data = {
                    provider: selectedProvider,
                    redirect_to: this.settings.redirect_to,
                    user_id: this.settings.user_id,
                    login_nonce: this.settings.login_nonce
                };
                urlExtension = '/save_default_method_email';
                this.performFetchOp(urlExtension, data)
                    .then(response => response.json())
                    .then(data => {
                        this.getElement('rsssl_step_one_onboarding').style.display = 'none';
                        const validation_check = document.getElementById("rsssl_step_three_onboarding");
                        validation_check.style.display = "block";
                        // Removing the 'click' event listener from the rsssl_continue_onboarding id button
                        endpointElem.addEventListener('click', (event) => handleValidation(event, data));
                        endpointElem.removeEventListener('click', handleClick);

                    })
                    .catch(that.logFetchError);
            } else if (selectedProvider === 'totp') {
                // Hiding step one and showing step two
                this.getElement('rsssl_step_one_onboarding').style.display = 'none';
                // We hide this element
                endpointElem.style.display = 'none';
                this.getElement('rsssl_step_two_onboarding').style.display = 'block';
            }
        }

        const handleValidation = async (event, data) => {
            event.preventDefault();
            let selectedProvider = this.getCheckedInputValue('preferred_method');
            let urlExtension = '/' + data.validation_action;
            let sendData = {
                user_id: this.settings.user_id,
                login_nonce: this.settings.login_nonce,
                redirect_to: this.settings.redirect_to,
                token: document.getElementById('rsssl-authcode').value,
                provider: selectedProvider
            };
            let response;
            try {
                response = await this.performFetchOp(urlExtension, sendData);
            } catch (err) {
                console.log('Fetch Error: ', err);
            }
            if (response && !response.ok) {
                let error = await response.json();
                this.displayTwoFaOnboardingError(error.error);
            }
            if (response && response.ok) {
                let data = await response.json();
                window.location.href = data.redirect_to;
            }
        };

        if (endpointElem !== null) {
            endpointElem.addEventListener('click', handleClick);
        }

        let totpSubmit = this.getElement('two-factor-totp-submit');
        if (totpSubmit !== null) {
            totpSubmit.addEventListener('click', async (event) => {
                event.preventDefault();
                let authCode = document.getElementById('two-factor-totp-authcode').value;
                let key = this.settings.totp_data.key;
                let selectedProvider = this.getCheckedInputValue('preferred_method');
                let sendData = {
                    'two-factor-totp-authcode': authCode,
                    provider: selectedProvider,
                    key: key,
                    redirect_to: this.settings.redirect_to,
                    user_id: this.settings.user_id,
                    login_nonce: this.settings.login_nonce
                };
                try {
                    let response = await this.performFetchOp('/save_default_method_totp', sendData);
                    if (!response.ok) {
                        let error = await response.json();
                            this.displayTwoFaOnboardingError(error.error);
                    } else {
                        let data = await response.json();
                        window.location.href = data.redirect_to;
                    }
                } catch (error) {
                    this.logFetchError(error);
                }
            });
        }

        let resendButton = this.getElement('rsssl-two-factor-email-code-resend');
        if(resendButton !== null) {
            resendButton.addEventListener('click', (event) => {
                event.preventDefault();
                let data = {
                    user_id: this.settings.user_id,
                    login_nonce: this.settings.login_nonce,
                    provider: 'email'
                };
                this.performFetchOp('/resend_email_code', data)
                    .then(response => response.json())
                    .then(data => {
                        this.displayTwoFaOnboardingError(data.message);
                    })
                    .catch(this.logFetchError);
            });
        }

        let downloadButton = this.getElement('download_codes');

        downloadButton.addEventListener('click', (e) => {
            e.preventDefault();
            this.download_codes();
        });

        this.getElement('two-factor-qr-code').addEventListener('click', function (e) {
            e.preventDefault();
            that.copyTextAndShowMessage();
        });

        this.getElement('totp-key').addEventListener('click', function (e) {
            e.preventDefault();
            that.copyTextAndShowMessage();
        });
        if (document.readyState === 'complete') {
            this.qr_generator();

        } else {
            this.qr_generator();
        }
    }

    displayTwoFaOnboardingError(error) {
        let loginForm = document.getElementById('two_fa_onboarding_form');
        if (loginForm) {
            let errorDiv = document.getElementById('login-message');
            if(!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.id = 'login-message';
                errorDiv.className = 'notice notice-error message';
                loginForm.insertAdjacentElement('beforebegin', errorDiv);
            }
            errorDiv.innerHTML = `<p>${error}</p>`;
            setTimeout(() => {
                // removing the error box from the loginForm
                errorDiv.remove();
            }, 5000);
        }
    }
}