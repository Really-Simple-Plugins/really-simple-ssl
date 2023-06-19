import Error from "../utils/Error";

const Placeholder = (props) => {

  let lines = props.lines;
  if ( !lines ) lines = 4;
  if (props.error) {
    lines = 0;
  }
  return (
      <div className="rsssl-placeholder">
        {props.error && <Error error={props.error} /> }
        {Array.from({length: lines}).map((item, i) => (<div className="rsssl-placeholder-line" key={"placeholder-"+i} ></div>))}
      </div>
  );

}

export default Placeholder;