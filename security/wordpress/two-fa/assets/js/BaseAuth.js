class BaseAuth {
    constructor(root, settings) {
        this.root = root;
        this.settings = settings;
        this.translatableStrings = {
            keyCopied: this.settings.translatables.keyCopied,
            // ... add more strings as needed
        };
    }
    getElement = (id) => document.getElementById(id);

    getCheckedInputValue = (name) => document.querySelector(`input[name="${name}"]:checked`).value;

    /**
     * Performs a fetch operation.
     *
     * @param {string} urlExtension - The URL extension to perform the fetch operation on.
     * @param {Object} data - The data to be sent in the fetch operation.
     * @param {string} [method='POST'] - The HTTP method to be used in the fetch operation. Defaults to 'POST'.
     * @returns {Promise} - A Promise that resolves with the response of the fetch operation.
     */
    performFetchOp = (urlExtension, data, method = 'POST') => {
        let url = this.root + urlExtension;
        let fetchParams = {
            method: method,
            headers: {'Content-Type': 'application/json',},
        };
        if (method === 'POST') {
            fetchParams.body = JSON.stringify(data);
        }
        return fetch(url, fetchParams);
    };

    assignClickListener = (id, callback) => {
        const element = this.getElement(id);
        if (element) {
            element.addEventListener('click', function (e) {
                e.preventDefault();
                callback();
            });
        }
    }



    logFetchError = (error) => console.error('There has been a problem with your fetch operation:', error);

    /**
     * Generates a QR code for Two-Factor Authentication using the TOTP URL.
     * If the TOTP URL is not available, nothing will be generated.
     *
     * @function qr_generator
     * @returns {void} Nothing is returned.
     */
    qr_generator = () => {
        const totp_url = this.settings.totp_data.totp_url;
        if (!totp_url) {
            return;
        }

        let qr = qrcode(0, 'L');
        qr.addData(totp_url);
        qr.make();
        let qrElem = document.querySelector('#two-factor-qr-code a');
        if (qrElem != null) {
            qrElem.innerHTML = qr.createSvgTag(5);
        }
    };

    /**
     * Downloads backup codes as a text file.
     *
     * @function download_codes
     */
    download_codes = () => {
        let TextToCode = this.settings.totp_data.backup_codes;
        let TextToCodeString = '';
        TextToCode.forEach(function (item) {
            TextToCodeString += item + '\n';
        });
        let downloadLink = document.createElement('a');
        downloadLink.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(TextToCodeString));
        downloadLink.setAttribute('download', 'backup_codes.txt');
        downloadLink.style.display = 'none';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    };

    /**
     * This function copies the text from the `totp_data.key` property of the `settings` object
     * using the Clipboard API. It then shows a success message and reverts back to the original display
     * after a specified timeout.
     *
     * @function copyTextAndShowMessage
     * @memberof BaseAuth
     */
    copyTextAndShowMessage = () => {
        let text = this.settings.totp_data.key; // Get the text to be copied

        // Use Clipboard API to copy the text
        navigator.clipboard.writeText(text).then(() => {
            // Change the display of the key
            let originalText = this.getElement('totp-key').innerText;
            this.getElement('totp-key').innerText = this.translatableStrings.keyCopied;
            this.getElement('totp-key').style.color = 'green';

            // Revert back to original text after a timeout
            setTimeout(() => {
                this.getElement('totp-key').innerText = originalText;
                this.getElement('totp-key').style.color = ''; // Reset the color
            }, 2000); // Adjust timeout as needed

        }, function (err) {
            console.error(this.settings.translatables.keyCopiedFailed, err);
        });
    }

}