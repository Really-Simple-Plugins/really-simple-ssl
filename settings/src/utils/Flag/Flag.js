import * as Flags from './Flags';

const Flag = ({ countryCode, style, title }) => {
  const FlagComponent = Flags[countryCode];
  if(countryCode === 'EU') {
    return <span title={title} ><img src="https://upload.wikimedia.org/wikipedia/commons/b/b7/Flag_of_Europe.svg" alt="EU" style={{width: '16px', height: '12px'}} /></span>
  }
  if (countryCode === 'AN') {
    //Flag of antarctica
    return <span title={title} ><img src="https://upload.wikimedia.org/wikipedia/commons/6/68/Flag_of_the_Antarctic_Treaty.svg" alt="AN" style={{width: '16px', height: '12px'}} /></span>
  }

  if (countryCode === 'AS') {
    //Flag of Asean
    return <span title={title} ><img src="https://upload.wikimedia.org/wikipedia/en/thumb/8/87/Flag_of_ASEAN.svg/1920px-Flag_of_ASEAN.svg.png" alt="AS" style={{width: '16px', height: '12px'}} /></span>
  }

    if (countryCode === 'OC') {
        //Flag of Australia
        return <span title={title} ><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/88/Flag_of_Australia_%28converted%29.svg/1920px-Flag_of_Australia_%28converted%29.svg.png" alt="OC" style={{width: '16px', height: '12px'}} /></span>
    }


  return FlagComponent ? <span title={title} ><FlagComponent style={style} /></span> : (
      <span title={title}>{countryCode}</span>
  );
};

export default Flag;