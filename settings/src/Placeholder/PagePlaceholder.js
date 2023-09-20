import Error from '../utils/Error';
const PagePlaceholder = (props) => {
    return (
        <>
            <div className="rsssl-header-container">
                <div className="rsssl-header">
                    <img className="rsssl-logo"
                         src={rsssl_settings.plugin_url + 'assets/img/really-simple-ssl-logo.svg'}
                         alt="Really Simple SSL logo"/>
                </div>
            </div>
            <div className="rsssl-content-area rsssl-grid rsssl-dashboard rsssl-page-placeholder">
                <div className="rsssl-grid-item  rsssl-column-2 rsssl-row-2 ">
                    {props.error && <Error error={props.error} /> }
                </div>
                <div className="rsssl-grid-item rsssl-row-2"></div>
                <div className="rsssl-grid-item rsssl-row-2"></div>
                <div className="rsssl-grid-item  rsssl-column-2"></div>
            </div>
        </>
    );
}

export default PagePlaceholder;

