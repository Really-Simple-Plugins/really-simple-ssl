import {useState, useEffect} from '@wordpress/element';
import SettingsPlaceholder from '../Placeholder/SettingsPlaceholder';
import {in_array} from '../utils/lib';
import SettingsGroup from './SettingsGroup';
import Help from './Help';
import useFields from './FieldsData';
import useMenu from '../Menu/MenuData';
import {__} from '@wordpress/i18n';
import useLetsEncryptData from '../LetsEncrypt/letsEncryptData';
import ErrorBoundary from "../utils/ErrorBoundary";

/**
 * Renders the selected settings
 *
 */
const Settings = () => {
  const [noticesExpanded, setNoticesExpanded] = useState(true);
  const {
    progress,
    fieldsLoaded,
    saveFields,
    fields,
    nextButtonDisabled,
  } = useFields();
  const {
    subMenuLoaded,
    subMenu,
    selectedSubMenuItem,
    selectedMainMenuItem,
    nextMenuItem,
    previousMenuItem,
  } = useMenu();
  const {setRefreshTests} = useLetsEncryptData();
  const toggleNotices = () => {
    setNoticesExpanded(!noticesExpanded);
  };

  const isTestsOnlyMenu = () => {
    const {menu_items: menuItems} = subMenu;
    for (const menuItem of menuItems) {
      if (menuItem.id === selectedSubMenuItem && menuItem.tests_only) {
        return true;
      }
    }
    return false;
  };

  const saveData = async (isSaveAndContinueButton) => {
    if (!isSaveAndContinueButton && isTestsOnlyMenu()) {
      setRefreshTests(true);
    } else if (isSaveAndContinueButton) {
      await saveFields(true, false);
    } else {
     await saveFields(true, true);
    }
  };

  const {menu_items: menuItems} = subMenu;
  if (!subMenuLoaded || !fieldsLoaded || menuItems.length === 0) {
    return (
        <SettingsPlaceholder/>
    );
  }

  let selectedFields = fields.filter(
      field => field.menu_id === selectedSubMenuItem);
  let groups = [];
  for (const selectedField of selectedFields) {
    if (!in_array(selectedField.group_id, groups)) {
      groups.push(selectedField.group_id);
    }
  }

  //convert progress notices to an array useful for the help blocks
  let notices = [];
  for (const notice of progress.notices) {
    let noticeIsLinkedToField = false;

    //notices that are linked to a field. Only in case of warnings.
    if (notice.show_with_options) {
      let noticeFields = selectedFields.filter(
          field => notice.show_with_options.includes(field.id));
      noticeIsLinkedToField = noticeFields.length > 0;
    }
    //notices that are linked to a menu id.
    if (noticeIsLinkedToField || notice.menu_id === selectedSubMenuItem) {
      let help = {};
      help.title = notice.output.title ? notice.output.title : false;
      help.label = notice.output.label;
      help.id = notice.id;
      help.text = notice.output.msg;
      help.url = notice.output.url;
      help.linked_field = notice.show_with_option;
      notices.push(help);
    }
  }

  //help items belonging to a field
  //if field is hidden, hide the notice as well
  for (const notice of selectedFields.filter(
      field => field.help && !field.conditionallyDisabled)) {
    let help = notice.help;
    //check if the notices array already includes this help item
    let existingNotices = notices.filter(
        noticeItem => noticeItem.id && noticeItem.id === help.id);
    if (existingNotices.length === 0) {
      // if (!help.id ) help['id'] = notice.id;
      notices.push(notice.help);
    }
  }
  let continueLink = nextButtonDisabled
      ? `#${selectedMainMenuItem}/${selectedSubMenuItem}`
      : `#${selectedMainMenuItem}/${nextMenuItem}`;
  // let btnSaveText = isTestsOnlyMenu() ? __('Refresh', 'really-simple-ssl') :
  // __('Save', 'really-simple-ssl');
  let btnSaveText = __('Save', 'really-simple-ssl');
  for (const menuItem of menuItems) {
    if (menuItem.id === selectedSubMenuItem && menuItem.tests_only) {
      btnSaveText = __('Refresh', 'really-simple-ssl');
    }
  }

  return (
      <>
        <div className="rsssl-wizard-settings">
          {groups.map((group, i) =>
              <SettingsGroup key={'settingsGroup-' + i} index={i} group={group}
                             fields={selectedFields}/>)
          }
          <div className="rsssl-grid-item-footer-container">
            <ScrollProgress/>
            <div className="rsssl-grid-item-footer">
              <div className={'rsssl-grid-item-footer-buttons'}>
                {/*This will be shown only if current step is not the first one*/}
                {selectedSubMenuItem !== menuItems[0].id &&
                    <a className="rsssl-previous"
                       href={`#${selectedMainMenuItem}/${previousMenuItem}`}>
                      {__('Previous', 'really-simple-ssl')}
                    </a>
                }
                <button
                    className="button button-secondary"
                    onClick={(e) => saveData(false)}>
                  {btnSaveText}
                </button>
                {/*This will be shown only if current step is not the last one*/}
                {selectedSubMenuItem !==
                    menuItems[menuItems.length - 1].id &&
                    <>
                      <button disabled={nextButtonDisabled}
                         className="button button-primary"
                         onClick={(e) => {saveData(true);window.location.href=continueLink;} }>
                        {__('Save and Continue', 'really-simple-ssl')}
                      </button>
                    </>
                }
              </div>
            </div>
          </div>
        </div>
        <div className="rsssl-wizard-help">
              <div className="rsssl-help-header">
                  <div className="rsssl-help-title rsssl-h4">
                      {__("Notifications", "really-simple-ssl")}
                  </div>
                  <div className="rsssl-help-control" onClick={ () => toggleNotices() }>
                      {!noticesExpanded && __("Expand all","really-simple-ssl")}
                      {noticesExpanded && __("Collapse all","really-simple-ssl")}
                  </div>
              </div>
              { notices.map((field, i) => <ErrorBoundary key={'errorboundary-'+i} fallback={"Could not load notices"}>
                      <Help noticesExpanded={noticesExpanded} index={i} help={field} fieldId={field.id}/>
                  </ErrorBoundary>
                  )}

          </div>
      </>

  );

};
export default Settings;

export const ScrollProgress = () => {
  // calculate the scroll progress
  const [scrollProgress, setScrollProgress] = useState(0);
  useEffect(() => {
    window.addEventListener('scroll', () => {
      let scrollableHeight = document.documentElement.scrollHeight -
          document.documentElement.clientHeight;
      let scrollProgressPercentage = Math.round(
          (window.scrollY / scrollableHeight) * 100);
      // start at 5% and end at 100%
      scrollProgressPercentage = Math.max(5, scrollProgressPercentage);
      setScrollProgress(scrollProgressPercentage);
    });
  }, []);

  // if you can't scroll return null
  if (document.documentElement.scrollHeight <=
      document.documentElement.clientHeight) {
    return null;
  }
  return (
      // add width to span
      <span className={'rsssl-grid-item-footer-scroll-progress-container'}>
			<span className={'rsssl-grid-item-footer-scroll-progress'}
            style={{width: scrollProgress + '%'}}>{scrollProgress}%</span>
		</span>
  );
};
