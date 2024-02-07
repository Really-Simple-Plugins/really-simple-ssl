import * as Flags from './Flags';

const Flag = ({ countryCode, style, title, continent = false }) => {
  if (continent) {
    return( <span title={title}>{countryCode}</span>)
  }
  const FlagComponent = Flags[countryCode];
  return FlagComponent ? <span title={title} ><FlagComponent style={style} /></span> : (
      <span title={title}>{countryCode}</span>
  );
};

export default Flag;