/**
 * Class for evaluating React conditions on a field.
 * If the current field has no react_conditions key, it is considered enabled by
 * default.
 */
export class ReactConditions {
    /**
     * Creates an instance of ReactConditions.
     *
     * @param {object} currentField - The field being evaluated.
     * @param {Array<object>} allFields - All field objects available for
     * evaluation.
     */
    constructor(currentField, allFields) {
        this.currentField = currentField;
        this.allFields = allFields;
        this.disabledReason = this._getDefaultDisabledReason();
        this.fieldConditionKeyBlocklist = ['disabledTooltipText'];
    }

    /**
     * Evaluates the react_conditions for the current field.
     *
     * @returns {boolean} Returns true if the conditions pass or if no
     * react_conditions are present; otherwise, false.
     */
    isEnabled() {
        if (!this.currentField.react_conditions) return true;
        return this._evaluateGroup(this.currentField.react_conditions);
    }

    /**
     * Get the reason why the field is conditionally disabled. This reason is
     * stored in the field condition like so:
        [
            send_notifications_email' => 1,
            'disabledTooltipText' => 'foobar',
        ]
     */
    getDisabledTooltipText() {
        return this.disabledReason;
    }

    /**
     * Get the default disabled reason for the field from the config.
     * @returns {string|*|string}
     * @private
     */
    _getDefaultDisabledReason() {
        return ((
            this.currentField.hasOwnProperty('disabled')
            && this.currentField.hasOwnProperty('disabledTooltipText')
            && this.currentField.disabled
        ) ? this.currentField.disabledTooltipText : '');
    }

    /**
     * Recursively evaluates a group of conditions.
     *
     * @param {object} group - A conditions group with an optional 'relation'
     * property (AND/OR) and subconditions.
     * @returns {boolean} True if the group evaluates to true.
     */
    _evaluateGroup(group) {

        const relation = group.relation ? group.relation.toUpperCase() : 'AND';

        // Evaluate each subcondition (ignoring the 'relation' key).
        const subResults = Object.keys(group)
            .filter(key => key !== 'relation')
            .map(key => {
                const subCondition = group[key];
                if (typeof subCondition === 'object' && subCondition.hasOwnProperty('relation')) {
                    return this._evaluateGroup(subCondition);
                }
                if (typeof subCondition === 'object') {
                    return this._evaluateFieldConditions(subCondition);
                }
                return false;
            });

        return relation === 'AND'
            ? subResults.every(result => result)
            : subResults.some(result => result);
    }

    /**
     * Evaluates a set of field conditions.
     *
     * @param {object} conditionObject - An object mapping field keys to
     * expected values.
     * @returns {boolean} True if every field condition passes.
     */
    _evaluateFieldConditions(conditionObject) {
        // Skip the keys in the fieldConditionKeyBlocklist
        let allowedKeysOfGroup = Object.keys(conditionObject).filter(key => (!this.fieldConditionKeyBlocklist.includes(key)));
        let fieldEnabledBasedOnGroupedCondition = allowedKeysOfGroup.every(rawFieldKey =>
            this._checkFieldCondition(rawFieldKey, conditionObject[rawFieldKey])
        );

        // If the field will be disabled due to this group of conditions, set
        // the disabled reason. No reason given? Then set an empty string to
        // override the default disabledTooltipText from the field config.
        // Example: field.id = '404_blocking_threshold' in path:
        // settings/config/fields/firewall.php
        if (fieldEnabledBasedOnGroupedCondition === false) {
            this.disabledReason = (conditionObject.hasOwnProperty('disabledTooltipText') ? conditionObject.disabledTooltipText : '');
        }

        return fieldEnabledBasedOnGroupedCondition;
    }

    /**
     * Evaluates an individual field condition.
     *
     * @param {string} rawFieldKey - The field key, possibly prefixed with "!"
     * to indicate inversion.
     * @param {*} expectedValue - The expected value for the condition.
     * @returns {boolean} True if the field condition is met.
     */
    _checkFieldCondition(rawFieldKey, expectedValue) {
        const isInverted = rawFieldKey.startsWith('!');
        const fieldKey = isInverted ? rawFieldKey.substring(1) : rawFieldKey;
        const field = this.allFields.find(field => field.id === fieldKey);
        if (!field) return false;
        const conditionResult = this._evaluateField(field, expectedValue);
        return isInverted ? !conditionResult : conditionResult;
    }

    /**
     * Evaluates the condition for a specific field based on its type.
     *
     * @param {object} field - The field object.
     * @param {*} expectedValue - The expected value.
     * @returns {boolean} True if the field's actual value meets the expected
     * condition.
     */
    _evaluateField(field, expectedValue) {

        const actualValue = field.value;

        switch (field.type) {
            case 'text_checkbox':
                return actualValue && actualValue.show === expectedValue;
            case 'checkbox':
                return actualValue == expectedValue;
            case 'multicheckbox': {
                const expectedArray = Array.isArray(expectedValue) ? expectedValue : [expectedValue];
                return Array.isArray(actualValue) && actualValue.some(val => expectedArray.includes(val));
            }
            case 'radio':
                return Array.isArray(expectedValue)
                    ? expectedValue.includes(actualValue)
                    : expectedValue === actualValue;
            default:
                if (expectedValue === true) {
                    return actualValue === 1 || actualValue === '1' || actualValue === true;
                }
                if (expectedValue === false) {
                    return actualValue === 0 || actualValue === '0' || actualValue === false;
                }
                if (typeof expectedValue === 'string' && expectedValue.includes('EMPTY')) {
                    return Array.isArray(actualValue) ? actualValue.length === 0 : !actualValue;
                }
                return String(actualValue).toLowerCase() === String(expectedValue).toLowerCase();
        }
    }
}