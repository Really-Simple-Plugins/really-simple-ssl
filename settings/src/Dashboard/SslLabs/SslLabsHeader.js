import { __ } from '@wordpress/i18n';
const SslLabsHeader = () => {
    return (
        <>
            <h3 className="rsssl-grid-title rsssl-h4">{  __( "Status", 'really-simple-ssl' ) }</h3>
            <div className="rsssl-grid-item-controls">
                 <span className="rsssl-header-html"> {__( "Powered by Qualys", 'really-simple-ssl' )}</span>
            </div>
        </>
    )
}

export default SslLabsHeader;