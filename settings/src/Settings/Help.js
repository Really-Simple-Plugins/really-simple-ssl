import Icon from "../utils/Icon";
import { __ } from '@wordpress/i18n';
/**
 * Render a help notice in the sidebar
 */
const Help = (props) => {
    let notice = props.help;
    if ( !notice.title ){
        notice.title = notice.text;
        notice.text = false;
    }
    let openStatus = props.noticesExpanded ? 'open' : '';
    //we can use notice.linked_field to create a visual link to the field.

    let target = notice.url && notice.url.indexOf("really-simple-ssl.com") !==-1 ? "_blank" : '_self';
    return (
        <>
            { notice.title && notice.text &&
                <details className={"rsssl-wizard-help-notice rsssl-" + notice.label.toLowerCase()} open={openStatus}>
                    <summary>{notice.title} <Icon name='chevron-down' /></summary>
                    {/*some notices contain html, like for the htaccess notices. A title is required for those options, otherwise the text becomes the title. */}
                    <div dangerouslySetInnerHTML={{__html:notice.text}}></div>
                    {notice.url && <div className="rsssl-help-more-info"><a target={target} href={notice.url}>{__("More info", "really-simple-ssl")}</a></div>}
                </details>
            }
            { notice.title && !notice.text &&
                <div className={"rsssl-wizard-help-notice rsssl-" + notice.label.toLowerCase()}><p>{notice.title}</p></div>
            }
        </>
    );
}

export default Help