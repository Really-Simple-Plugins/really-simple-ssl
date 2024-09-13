import {addUrlRef} from "../../utils/AddUrlRef";

const Tip = ({link, content}) => {
    return (
        <div className="rsssl-tips-tricks-element">
            <a href={link} target="_blank" rel="noopener noreferrer" title={content}>
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
            content: "Why WordPress is (in)secure",
            link: 'https://really-simple-ssl.com/why-wordpress-is-insecure/',
        }, {
            content: "Always be ahead of vulnerabilities",
            link: 'https://really-simple-ssl.com/staying-ahead-of-vulnerabilities/',
        }, {
            content: "Harden your website's security",
            link: 'https://really-simple-ssl.com/hardening-your-websites-security/',
        }, {
            content: "Login protection as essential security",
            link: 'https://really-simple-ssl.com/login-protection-as-essential-security/',
        }, {
            content: "Protect site visitors with Security Headers",
            link: 'https://really-simple-ssl.com/protecting-site-visitors-with-security-headers',
        }, {
            content: "Enable an efficient and performant firewall",
            link: 'https://really-simple-ssl.com/enable-an-efficient-and-performant-firewall/',
        },
    ];

    return (
        <div className="rsssl-tips-tricks-container">
            {items.map((item, i) => <Tip key={"trick-"+i} link={addUrlRef(item.link)} content={item.content} /> ) }
        </div>
    );

}
export default TipsTricks