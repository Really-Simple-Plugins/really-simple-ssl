import {
  Component,
} from '@wordpress/element';

class Placeholder extends Component {
  constructor() {
    super(...arguments);
  }

  render() {
    return (
      <div class="rsssl-placeholder">
        <div className="post">
          <div className="line"> {this.props.lines} </div>
          {[...Array(this.props.lines)].map((e, i) => <span key={i}></span>)}
        </div>
      </div>
    );
  }
}

export default Placeholder;