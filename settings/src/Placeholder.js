import {
  Component,
} from '@wordpress/element';

class Placeholder extends Component {
  constructor() {
    super(...arguments);
  }

  render() {
    let lines = this.props.lines;
    if ( !lines ) lines = 1;
    return (
      <div className="rsssl-placeholder">
        <div className="post">
          <div className="line">
            {Array.from({length: lines}).map((item, i) => (<span key={i} ></span>))}
          </div>
        </div>
      </div>
    );
  }
}

export default Placeholder;