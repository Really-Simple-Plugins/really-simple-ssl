const Tip = ({link, content}) => {
    return (
        <div className="rsssl-tips-tricks-element">
            <a href={link} target="_blank" title="{content}">
                <div className="rsssl-icon">
                    <svg aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" height="15">
                        <path fill="var(--rsp-grey-300)" d="M256 512c141.4 0 256-114.6 256-256S397.4 0 256 0S0 114.6 0 256S114.6 512 256 512zM216 336h24V272H216c-13.3 0-24-10.7-24-24s10.7-24 24-24h48c13.3 0 24 10.7 24 24v88h8c13.3 0 24 10.7 24 24s-10.7 24-24 24H216c-13.3 0-24-10.7-24-24s10.7-24 24-24zm40-144c-17.7 0-32-14.3-32-32s14.3-32 32-32s32 14.3 32 32s-14.3 32-32 32z"/>
                    </svg>
                </div>
                <div className="rsssl-tips-tricks-content">{content}</div>
            </a>
        </div>
    )
}
const TipsTricks = () => {
    const items = [
        {
            content: "Definition: What is a Content Security Policy?",
            link: 'https://really-simple-ssl.com/definition/What-is-a-Content-Security-Policy/',
        }, {
            content: "Installing a Let's Encrypt certificate",
            link: 'https://really-simple-ssl.com/install-ssl-certificate/',
        }, {
            content: "Improve Security: (HSTS) HTTP Strict Transport Security",
            link: 'https://really-simple-ssl.com/instructions/about-hsts/',
        }, {
            content: "Improve Security: About vulnerabilities",
            link: 'https://really-simple-ssl.com/instructions/about-vulnerabilities',
        }, {
            content: "Instructions: Debugging with Really Simple SSL",
            link: 'https://really-simple-ssl.com/instructions/debugging/',
        }, {
            content: "Instructions: Configuring Hardening Features",
            link: 'https://really-simple-ssl.com/instructions/about-hardening-features/',
        },
    ];

    return (
        <div className="rsssl-tips-tricks-container">
            {items.map((item, i) => <Tip key={"trick-"+i} link={item.link} content={item.content} /> ) }
        </div>
    );

}
export default TipsTricks