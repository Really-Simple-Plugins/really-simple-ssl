Feature: Clean up duplicate post meta values

  Scenario: Clean up duplicate post meta values.
    Given a WP install
    And a session_no file:
      """
      n
      """
    And a session_yes file:
      """
      y
      """

    When I run `wp post meta add 1 foo bar`
    Then STDOUT should be:
      """
      Success: Added custom field.
      """

    When I run the previous command again
    Then the return code should be 0

    When I run the previous command again
    Then the return code should be 0

    When I run `wp post meta list 1 --keys=foo`
    Then STDOUT should be a table containing rows:
      | post_id | meta_key | meta_value |
      | 1       | foo      | bar        |
      | 1       | foo      | bar        |
      | 1       | foo      | bar        |

    When I run `wp post meta clean-duplicates 1 foo < session_no`
    # Check for contains only, as the string contains a trailing space.
    Then STDOUT should contain:
      """
      Are you sure you want to delete 2 duplicate meta values and keep 1 valid meta value? [y/n]
      """

    When I run `wp post meta list 1 --keys=foo --format=count`
    Then STDOUT should be:
      """
      3
      """

    When I run `wp post meta clean-duplicates 1 foo < session_yes`
    Then STDOUT should contain:
      """
      Cleaned up duplicate 'foo' meta values.
      """

    When I try the previous command again
    Then STDOUT should contain:
      """
      Success: Nothing to clean up: found 1 valid meta value and 0 duplicates.
      """

    When I try `wp post meta clean-duplicates 1 food`
    Then STDERR should be:
      """
      Error: No meta values found for 'food'.
      """
    And the return code should be 1
