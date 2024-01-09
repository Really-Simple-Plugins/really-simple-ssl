const Hyperlink = (props) => {
    let label_pre = '';
    let label_post = '';
    let link_text = '';

    // Split the text around '%s' if it exists
    if (props.text.indexOf('%s') !== -1) {
        let parts = props.text.split(/%s/);
        label_pre = parts[0];
        link_text = parts[1];
        label_post = parts[2];
    } else {
        link_text = props.text;
    }

    // Use the passed className or default to 'rsssl-link'
    let className = props.className ? props.className : 'rsssl-link';

    // Include rel attribute in the anchor tag
    return (
        <>
            {label_pre}
            <a
                className={className}
                target={props.target}
                rel={props.rel} // Add the rel attribute here
                href={props.url}
            >
                {link_text}
            </a>
            {label_post}
        </>
    );
}

export default Hyperlink;