import {
    useState, useEffect, useRef
} from '@wordpress/element';
import TaskElement from "./../TaskElement";
import useProgress from "./ProgressData";
import {__} from "@wordpress/i18n";

const ProgressBlock = (props) => {
    const {percentageCompleted, progressText, filter, notices, progressLoaded, getProgressData, error} = useProgress();
  useEffect( () => {
      getProgressData();

    }, [] );

    const getStyles = () => {
        return Object.assign(
            {},
            {width: percentageCompleted+"%"},
        );
    }

    let progressBarColor = '';
    if ( percentageCompleted<80 ) {
        progressBarColor += 'rsssl-orange';
    }

  if ( !progressLoaded || error ) {
    return (
        <div className="rsssl-progress-block">
          <div className="rsssl-progress-bar">
            <div className="rsssl-progress">
              <div className={'rsssl-bar rsssl-orange'} style={getStyles()}></div>
            </div>
          </div>

          <div className="rsssl-progress-text">
            <h1 className="rsssl-progress-percentage">
              0%
            </h1>
            <h5 className="rsssl-progress-text-span">
              {__('Loading...', 'really-simple-ssl')}
            </h5>
          </div>

          <div className="rsssl-scroll-container">
            <div className="rsssl-task-element">
              <span className={'rsssl-task-status rsssl-loading'}>{__('Loading...', 'really-simple-ssl')}</span>
              <p className="rsssl-task-message">{__('Loading...', 'really-simple-ssl')}</p>
            </div>
          </div>
        </div>
    );
  }

    let noticesOutput = notices;
    if ( filter==='remaining' ) {
        noticesOutput = noticesOutput.filter(function (notice) {
            return notice.output.status==='open' || notice.output.status==='warning';
        });
    }

    return (
        <div className="rsssl-progress-block">
            <div className="rsssl-progress-bar">
                <div className="rsssl-progress">
                    <div className={'rsssl-bar ' + progressBarColor} style={getStyles()}></div>
                </div>
            </div>

            <div className="rsssl-progress-text">
              <AnimatedPercentage percentageCompleted={percentageCompleted} />
                <h5 className="rsssl-progress-text-span">
                    {progressText}
                </h5>
            </div>

            <div className="rsssl-scroll-container">
                {noticesOutput.map((notice, i) => <TaskElement key={"task-"+i} notice={notice}/>)}
            </div>
        </div>
    );

}
export default ProgressBlock;

export const AnimatedPercentage = ({ percentageCompleted }) => {
  const [displayedPercentage, setDisplayedPercentage] = useState(0);
  // useRef previous percentageCompleted
  const prevPercentageCompleted = useRef(0);
  const easeOutCubic = (t) => {
    return 1 - Math.pow(1 - t, 3);
  };

  useEffect(() => {
    const startPercentage = prevPercentageCompleted.current;
    const animationDuration = 1000;
    const startTime = Date.now();

    const animatePercentage = () => {
      const elapsedTime = Date.now() - startTime;
      const progress = Math.min(elapsedTime / animationDuration, 1);
      const easedProgress = easeOutCubic(progress);

      const newPercentage = Math.min(startPercentage + (percentageCompleted - startPercentage) * easedProgress, percentageCompleted);

      if (progress < 1) {
        // update displayedPercentage
        setDisplayedPercentage(newPercentage);
        prevPercentageCompleted.current = percentageCompleted;
      } else {
        // update prevPercentageCompleted to the new percentageCompleted
        clearInterval(animationInterval);
      }
    };

    const animationInterval = setInterval(animatePercentage, 16);
    return () => clearInterval(animationInterval);
  }, [percentageCompleted]);

  return (
      <h1 className="rsssl-progress-percentage">
        {Math.round(displayedPercentage)}%
      </h1>
  );
};