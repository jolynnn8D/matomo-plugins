<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\ReactFormAnalytics;

use Faker\Provider\Payment;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Plugins\ReactFormAnalytics\Commons;


/**
 * API for plugin ReactFormAnalytics
 *
 * @method static \Piwik\Plugins\ReactFormAnalytics\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    /* !!IMPORTANT!! The name of your custom dimension in Matomo MUST correspond to this. By default,
       it should be 'Form'. If you choose another name, you need to change the value of this field. */
    public $FORM_DIMENSION_NAME = 'Form';
    public $DIMENSION_KEY;
    public $NUM_OF_VISITORS = 500;
    private $logger;


    public function __construct(\Psr\Log\LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * Returns a DataTable of forms detected on your website along with the following information:
     * number of fields, average time spent on form, information on individual field such as average time
     * spent on field, average clicks on field and number of users who used the field.
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool $segment
     * @return DataTable
     */
    public function getFormsDetected($idSite, $period, $date, $segment = false) {
        $forms = new DataTable();

        $data = \Piwik\API\Request::processRequest('Live.getLastVisitsDetails', array(
            'idSite' => $idSite,
            'period' => $period,
            'date' => $date,
            'segment' => $segment,
            'countVisitorsToFetch' => $this -> NUM_OF_VISITORS,
        ));

        $last = $data -> getFirstRow();
        print_r("User Id: " . $last['userId']);
        // Note: Custom Dimensions must be set up using name in $FORM_DIMENSION_NAME to proceed with the rest
        // of the API.
        $customDimensions = \Piwik\API\Request::processRequest('CustomDimensions.getConfiguredCustomDimensions',
            array('idSite' => $idSite));

        $dimensionId = $this -> retrieveFormDimensionId($customDimensions);
        if($dimensionId == false) {
            $this->logger->error("Custom dimensions have not been set up correctly\n");
            return $forms;
        }
        $this -> DIMENSION_KEY = Commons::DIMENSION_PREFIX . $dimensionId;

        foreach ($data -> getRows() as $visitRow) {
            $actions = $visitRow -> getColumn(Commons::ACTION_KEY);
            $userEvents = []; // Stores forms accessed by a single user
            $currUrl = "";
            foreach($actions as $action) {

                // If the URL of the action changes, it is assumed that the user abandoned the workflow and
                // the user's actions will be cleared out
                $actionUrl = $action['url'];
                $isDifferentUrl = $this -> checkUrlChange($currUrl, $actionUrl);
                if ($isDifferentUrl) {
                    $userEvents = [];
                }
                $currUrl = $actionUrl;

                if ($action['type'] == 'event' && $action['eventCategory'] == 'forms') {
                    $formEvent = $action[Commons::EVENT_ACTION];
                    switch ($formEvent) {
                        case 'detect-form-element':
                            $userEvents = $this -> processDetectFormElementEvent($action, $userEvents);
                            break;
                        case 'focus-in':
                            $userEvents = $this -> processFocusInEvent($action, $userEvents);
                            break;
                        case 'submit':
                            // When the form is submitted, the current data is added to the user events and
                            // a new session is initiated.
                            $forms = $this -> addUserEventsToDataTable($userEvents, $forms);
                            $userEvents = [];
                            break;
                        default:
                            break;
                    }
                }
            }
            $forms = $this -> addUserEventsToDataTable($userEvents, $forms);
        }
        return $forms;
    }

    /**
     * Check for URL change between actions.
     * Returns true the URL changed.
     * @param string $currUrl Current URL that the action was on.
     * @param string $actionUrl Next URL in line.
     * @return bool Returns true if actionUrl is different from currUrl.
     */
    private function checkUrlChange($currUrl, $actionUrl) {
        return $currUrl != $actionUrl;
    }

    /**
     * Adds a detected field to an existing rendered form. If the form has not been initialised previously,
     * a form will be initialised and added.
     * @param array $action The details of the action that detected form elements.
     * @param array $userEvents The current actions that the user has taken, organized by forms.
     * @return array Updated version of user events that includes the latest action.
     */
    private function processDetectFormElementEvent($action, $userEvents) {
        $formName = $action[$this -> DIMENSION_KEY];
        if ($formName == '') {
            return $userEvents;
        }

        if (!array_key_exists($formName, $userEvents)) {
            $userEvents = $this -> initialiseNewForm($formName, $userEvents);
        }

        $userEvents = $this -> addFormField($action, $formName, $userEvents);
        return $userEvents;
    }

    /**
     * Adds the details of a focus-in event to the current user events. In the case of out of order
     * events where the form was not already rendered previously, a detect form event will be triggered
     * first. Returns an updated version of the user's events.
     * @param array $action The details of the action that detected form elements.
     * @param array $userEvents The current actions that the user has taken, organized by forms.
     * @return array Updated version of user events that includes the latest action.
     */
    private function processFocusInEvent($action, $userEvents) {
        $formName = $action[$this -> DIMENSION_KEY];
        if ($formName == Commons::INDIV_FIELD) {
            return $this -> processIndividualFieldEvent($action, $userEvents);
        }
        if (!array_key_exists($formName, $userEvents)) {
            $userEvents = $this -> processDetectFormElementEvent($action, $userEvents);
        }

        $fieldName = $this -> retrieveEventName($action);
        $fields = $userEvents[$formName][Commons::FIELD_KEY];
        if (!array_key_exists($fieldName, $fields)) {
            $userEvents = $this -> processDetectFormElementEvent($action, $userEvents);
        }

        $timeSpent = $this -> retrieveEventTimeSpent($action);
        $userEvents[$formName]['average_time'] += $timeSpent;
        $userEvents[$formName]['users'] = 1;
        $field = $userEvents[$formName][Commons::FIELD_KEY][$fieldName];
        $userEvents[$formName][Commons::FIELD_KEY][$fieldName] = $this -> updateFieldUserEvents($field, $timeSpent);

        return $userEvents;
    }

    /**
     * Adds the details of the focus-in event to the user's events. This function is specifically for forms
     * that are not part of a HTML form structure and is an individual input field.
     * @param array $action The details of the action that detected form elements.
     * @param array $userEvents The current actions that the user has taken, organized by forms.
     * @return array Updated version of user events that includes the latest action.
     */
    private function processIndividualFieldEvent($action, $userEvents) {
        if (!array_key_exists(Commons::INDIV_FIELD, $userEvents)) {
            $userEvents = $this -> initialiseNewForm(Commons::INDIV_FIELD, $userEvents);
        }
        $fieldName = $this -> retrieveEventName($action);
        if ($fieldName == '') {
            $this->logger->warning("Individual field does not have a name and will not be recorded\n");
            return $userEvents;
        }
        $fields = $userEvents[Commons::INDIV_FIELD][Commons::FIELD_KEY];
        if (array_key_exists($fieldName, $fields)) {
            $currField = $fields[$fieldName];
        } else {
            $currField = $this -> initialiseNewField($fieldName);
            $userEvents[Commons::INDIV_FIELD]['nb_fields'] += 1;
        }
        $userEvents = $this -> updateIndivField($currField, $fieldName, $action, $userEvents);
        return $userEvents;
    }

    /**
     * Update the details of an individual input field to the user's events.
     * @param array $currField The stored details of the user's past actions on the field.
     * @param string $fieldName The name of the field.
     * @param array $action The latest details of user's action on the field.
     * @param array $userEvents Updated version of user events that includes the latest action.
     * @return
     */
    private function updateIndivField($currField, $fieldName, $action, $userEvents) {
        $timeSpent = $this->retrieveEventTimeSpent($action);
        $newField = $this -> updateFieldUserEvents($currField, $timeSpent);
        $userEvents[Commons::INDIV_FIELD][Commons::FIELD_KEY][$fieldName] = $newField;
        return $userEvents;
    }

    /**
     * Main calculations for updating statistics and data for a field.
     * Users are set to one as the event is only happening for the current user. Time spent is accumulated as
     * total time spent on the field by the user should be recorded across different actions. Total clicks
     * is also accumulated.
     * @param array $field The information on the field.
     * @param int $timeSpent Time spent on the field in the single action.
     * @return array Updated information on the field.
     */
    private function updateFieldUserEvents($field, $timeSpent) {
        $field['users'] = 1;
        $field['average_time'] += $timeSpent;
        $field['average_clicks'] += 1;
        return $field;
    }

    /**
     * Adds a new form field to an array of forms if it is not already existing.
     * This should always come after making sure that the form that the field belongs to is already existing.
     * @param array $action Details of the action regarding the new field to be added.
     * @param string $formName Name of the form that the field belongs to.
     * @param array $forms The existing array of forms and its fields.
     * @return array Updated array of forms with the new field.
     */
    private function addFormField($action, $formName, $forms) {
        if (!array_key_exists($formName, $forms)) {
            // Validation for existence of form wtih a given name in forms
            return $forms;
        }
        $fieldIndex = $action[Commons::EVENT_VALUE];
        if (!array_key_exists(Commons::EVENT_NAME, $action)) {
            $fieldName = 'Untracked Field ' . $fieldIndex;
        } else {
            $fieldName = $this -> retrieveEventName($action);
        }

        $formItem = $forms[$formName];
        $fields = $formItem[Commons::FIELD_KEY];
        if (array_key_exists($fieldName, $fields)) {
            // This is the case where the form had already been rendered previously
            return $forms;
        }

        $fieldArray = $this -> initialiseNewField($fieldName);
        $fields[$fieldName] = $fieldArray;
        $formItem[Commons::FIELD_KEY] = $fields;
        $formItem['nb_fields'] += 1;
        $forms[$formName] = $formItem;
        return $forms;
    }

    /**
     * Initialisation of a new field with no data.
     * @param string $fieldName Name of the new field.
     * @return array Empty field.
     */
    private function initialiseNewField($fieldName) {
        $fieldArray = array(
            'label' => $fieldName,
            'users' => 0,
            'average_time' => 0,
            'average_clicks' => 0,
        );
        return $fieldArray;
    }

    /**
     * Initialisation of a new form with no data.
     * @param string $formName Name of the form to be initialised.
     * @return array $forms Updated array of forms with the new form.
     */
    private function initialiseNewForm($formName, $forms) {
        $form = array(
                'label' => $formName,
                'views' => 1,
                'users' => 0,
                'average_time' => 0,
                'nb_fields' => 0,
                Commons::FIELD_KEY => []);
        $forms[$formName] = $form;
        return $forms;
    }

    // Functions to integrate user events into the DataTable

    /**
     * Adds a single user's events to a consolidated DataTable of all user's events
     * @param array $userEvents User's current events.
     * @param DataTable $forms DataTable of all user's events.
     * @return DataTable Updated DataTable of all events.
     */
    private function addUserEventsToDataTable($userEvents, $forms) {
        foreach($userEvents as $formName => $formDetails) {
            $formRow = $forms -> getRowFromLabel($formName);
            if ($formRow == false) {
                $forms = $this->initialiseNewDataTableForm($formDetails, $forms);
            } else {
                $this -> updateDataTableMetrics($formDetails, $formRow);
            }
        }
        return $forms;
    }

    /**
     * Iniitialises a new form in a DataTable that had not been previously rendered or interacted with
     * by any prior user.
     * @param array $form Details of the enw form.
     * @param DataTable $forms Current collection of all user's events on forms.
     * @return DataTable Updated collection of all user's events on forms.
     */
    private function initialiseNewDataTableForm($form, $forms) {
        $newForm = new Row(array(
            Row::COLUMNS => array(
                'label' => $form['label'],
                'views' => $form['views'],
                'users' => $form['users'],
                'average_time' => $form['average_time'],
                'nb_fields' => $form['nb_fields'],
                Commons::FIELD_KEY => $form[Commons::FIELD_KEY],
            )
        ));
        $forms -> addRow($newForm);
        return $forms;
    }

    /** Updating each individual metrics of a form.
     * @param array $formDetails Details of the incoming form.
     * @param Row $formRow Current information of the form.
     */
    private function updateDataTableMetrics($formDetails, $formRow) {
        $currViews = $formRow -> getColumn('views');
        $currUsers = intval($formRow -> getColumn('users'));
        $currTimeSpent = intval($formRow -> getColumn('average_time'));
        $currFields = $formRow -> getColumn(Commons::FIELD_KEY);
        $newViews = $currViews + 1;
        $newUsers = $currUsers + $formDetails['users'];
        if ($newUsers != 0) {
            $newTimeSpent = ($currTimeSpent * $currUsers + $formDetails['average_time']) / $newUsers;
        } else {
            $newTimeSpent = $currTimeSpent;
        }

        $newFields = $currFields;
        foreach($formDetails[Commons::FIELD_KEY] as $fieldName => $field) {
            $newFields[$fieldName] = $this->updateFieldMetrics(
                $currFields, $fieldName,
                $field);
        }
        $formRow -> setColumn('views', $newViews);
        $formRow -> setColumn('users', $newUsers);
        $formRow -> setColumn('average_time', $newTimeSpent);
        $formRow -> setColumn(Commons::FIELD_KEY, $newFields);
        return $formRow;
    }

    /**
     * Update metrics of a field.
     * @param array $currFields The metrics of the fields current stored in the DataTable.
     * @param string $fieldName Name of the field which information needs to be added to the DataTable.
     * @param array $incomingField Details of the new field with details to be added to the DataTable.
     * @return array
     */
    private function updateFieldMetrics($currFields, $fieldName, $incomingField) {
        if (!array_key_exists($fieldName, $currFields)) {
            // If there is no information on the field stored, it can simply be returned as it is.
            return $incomingField;
        }
        $targetField = $currFields[$fieldName];
        $currUsers = $targetField['users'];
        $avgTime = $targetField['average_time'];
        $avgClicks = $targetField['average_clicks'];
        $newUsers = $currUsers + $incomingField['users'];
        if ($newUsers !== 0) {
            $newAvgTime = ($avgTime * $currUsers + $incomingField['average_time']) / $newUsers;
            $newAvgClicks = ($avgClicks * $currUsers + $incomingField['average_clicks']) / $newUsers;
        } else {
            $newAvgTime = $avgTime;
            $newAvgClicks = $avgClicks;
        }
        $targetField['users'] = $newUsers;
        $targetField['average_time'] = $newAvgTime;
        $targetField['average_clicks'] = $newAvgClicks;
        return $targetField;
    }

    // Utils

    /**
     * Retrieves the action of the event with key eventAction, e.g focus-in, detect-form-element, submit
     * @param array $action Details of the action
     * @return string|null Returns the nature of the action if available, else null.
     */
    private function retrieveEventAction($action) {
        if(array_key_exists(Commons::EVENT_ACTION, $action)) {
            return $action[Commons::EVENT_ACTION];
        } else {
            return null;
        }
    }

    /**
     * Returns the value of the event if any.
     * @param array $action Details of the action.
     * @return int|float
     */
    private function retrieveEventValue($action) {
        if (array_key_exists(Commons::EVENT_VALUE, $action)) {
            return $action[Commons::EVENT_VALUE];
        } else {
            return 0;
        }
    }

    /**
     * Retrieves the time spent on a particular Matomo action or event.
     * @param array $action Details of the action.
     * @return int Time spent on the event if recorded.
     */
    private function retrieveEventTimeSpent($action) {
        if (array_key_exists(Commons::TIME_KEY, $action)) {
            return $action[Commons::TIME_KEY];
        } else {
            return 0;
        }
    }

    /**
     * Retrieves the name of an event if any.
     * @param array $action Details of the action.
     * @return string Name of the event, else empty string.
     */
    private function retrieveEventName($action) {
        if (array_key_exists(Commons::EVENT_NAME, $action)) {
            $eventName = $action[Commons::EVENT_NAME];
            return $eventName;
        } else {
            return '';
        }
    }

    /**
     * Retrieves data in the custom dimensions set up according to the Form Custom Dimensions.
     * This is necessary for any of the ReactFormAnalytics API to work correctly.
     * @param array $customDimensions List of custom dimensions set up in the user's Matomo.
     * @return int|bool Returns the ID of the custom dimension for Forms if any. Else, returns false.
     */
    private function retrieveFormDimensionId($customDimensions) {
        foreach($customDimensions as $customDimension) {
            if ($customDimension['name'] == $this -> FORM_DIMENSION_NAME) {
                return $customDimension['idcustomdimension'];
            }
        }
        return false;
    }

    private function retrieveUserId($visitor) {
        if (array_key_exists('userId', $visitor)) {
            return $visitor['userId'];
        } else {
            return false;
        }
    }
    /**
     * Returns a Data Table where each form stores an array of users, each with its associated time spent
     * on the form as well as the field on which the user spent the most time on. This allows a distribution
     * graph for each form to be displayed.
     * @param $idSite
     * @param $period
     * @param $date
     * @param bool $segment
     * @return DataTable
     */
    public function getFormTimeDistribution($idSite, $period, $date, $segment = false) {
        $distributions = new DataTable();

        $data = \Piwik\API\Request::processRequest('Live.getLastVisitsDetails', array(
            'idSite' => $idSite,
            'period' => $period,
            'date' => $date,
            'segment' => $segment,
            'countVisitorsToFetch' => $this -> NUM_OF_VISITORS,
        ));

        $customDimensions = \Piwik\API\Request::processRequest('CustomDimensions.getConfiguredCustomDimensions',
            array('idSite' => $idSite));

        $dimensionId = $this -> retrieveFormDimensionId($customDimensions);
        if($dimensionId == false) {
            $this->logger->error("Custom dimensions have not been set up correctly\n");
            return $distributions;
        }

        $this -> DIMENSION_KEY = Commons::DIMENSION_PREFIX . $dimensionId;

        foreach($data -> getRows() as $visitRow) {
            $userId = $this -> retrieveUserId($visitRow);
            $actions = $visitRow -> getColumn(Commons::ACTION_KEY);
            $distributions = $this -> addUserEventsToDistribution($userId, $actions, $distributions);
        }

        return $distributions;
    }

    /**
     * Adds all of an individual user's actions to the distribution DataTable.
     * @param string|bool $actionUserId Custom User ID of the user, if specified, else false.
     * @param array $actions Actions done by a single user.
     * @param DataTable $distributions Stored information on previous user's actions on forms.
     * @return DataTable Updated DataTable with the user's actions.
     */
    private function addUserEventsToDistribution($actionUserId, $actions, $distributions) {
        static $userId = 1;
        if ($actionUserId == false) {
            $actionUserId = $userId;
        }

        foreach($actions as $action) {
            if ($action[Commons::ACTION_TYPE] == 'event' &&
                $action[Commons::EVENT_CATEGORY] == 'forms' &&
                $action[Commons::EVENT_ACTION] == 'focus-in') {

                $formName = $action[$this -> DIMENSION_KEY];
                $formDistribution = $distributions -> getRowFromLabel($formName);

                if ($formDistribution == false) {
                    $distributions = $this -> initialiseFormDistribution($formName, $distributions);
                    $formDistribution = $distributions -> getRowFromLabel($formName);
                }

                $formUsers = $formDistribution -> getColumn('users');
                if (!array_key_exists($actionUserId, $formUsers)) {
                    $formUsers = $this -> initialiseNewUser($actionUserId, $formUsers);
                }
                $formUsers = $this -> addSingleEventToUserInfo($action, $actionUserId, $formUsers);
                $formDistribution -> setColumn('users', $formUsers);
            }
        }

        $userId++;
        return $distributions;
    }

    /**
     * Adds a single action to the array storing the user's actions.
     * @param array $action Details of the user actions.
     * @param integer $userId A unique user ID for the user interacting with the form.
     * @param array $formUsers Current array of users that have interacted with the form.
     * @return array Updated array of user distribution to be stored in the DataTable.
     */
    private function addSingleEventToUserInfo($action, $userId, $formUsers) {
        $actionTime = $this -> retrieveEventTimeSpent($action);
        $fieldName = $this -> retrieveEventName($action);
        if ($fieldName == '') {
            return $formUsers;
        }
        $user = $formUsers[$userId];

        $user[Commons::TIME_KEY] += $actionTime;
        $user['nb_focusin'] += 1;
        $currLongestTime = $user['longest_field_time'];
        if ($actionTime >= $currLongestTime) {
            $user['longest_field'] = $fieldName;
            $user['longest_field_time'] = $actionTime;
        }

        $formUsers[$userId] = $user;
        return $formUsers;
    }

    /**
     * Initialises a DataTable Row for a new form that will contain the distribution's information.
     * @param string $formName Name of the form.
     * @param DataTable $distributions Current DataTable.
     * @return DataTable Updated DataTable with the new form initiatied.
     */
    private function initialiseFormDistribution($formName, $distributions) {
        $newDistribution = new Row(array(
            Row::COLUMNS => array(
                'label' => $formName,
                'users' => [],
            )
        ));
        $distributions -> addRow($newDistribution);
        return $distributions;
    }

    /**
     * Initialises a new user in the form's user distribution.
     * @param int $userId The user's unique id.
     * @param array $formUsers Current distribution of the users of a particular form.
     * @return array Updated distribution of the users of a particular form.
     */
    private function initialiseNewUser($userId, $formUsers) {
        $formUsers[$userId] = array(
            'timeSpent' => 0,
            'nb_focusin' => 0,
            'longest_field' => '',
            'longest_field_time' => 0,
        );
        return $formUsers;
    }
}
