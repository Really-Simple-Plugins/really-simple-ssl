import {
  Component,
  Fragment,
} from '@wordpress/element';

class PagePlaceholder extends Component {
  constructor() {
    super(...arguments);
  }

  render() {
    let plugin_url = rsssl_settings.plugin_url;
    return (
        <Fragment>
          <div className="rsssl-header-container">

            <div className="rsssl-header">
              <img className="rsssl-logo"
                   src={plugin_url + 'assets/img/really-simple-ssl-logo.svg'}
                   alt="Really Simple SSL logo"/>
            </div>
          </div>
          <div className="rsssl-content-area rsssl-grid rsssl-dashboard rsssl-page-placeholder">
            <div className="rsssl-grid-item  rsssl-column-2 rsssl-row-2 "></div>
            <div className="rsssl-grid-item rsssl-row-2"></div>
            <div className="rsssl-grid-item rsssl-row-2"></div>
            <div className="rsssl-grid-item  rsssl-column-2"></div>
          </div>
        </Fragment>
    );
  }
}

export default PagePlaceholder;

