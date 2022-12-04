@ou @ou_vle @filter @filter_embedquestion
Feature: Fill with correct feature for staff
  In order to view the right answer of the question
  As a teacher
  I need a Fill with correct link to fill the right answer to the question

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher  | Terry1    | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name          | idnumber |
      | Course       | C1        | Test questions| embed    |
    And the following "questions" exist:
      | questioncategory | qtype     | name            | idnumber |
      | Test questions   | truefalse | First question  | test1    |
      | Test questions   | essay     | Second question | test2    |
    And the "embedquestion" filter is "on"

  @javascript
  Scenario: Teacher can see and use the Fill with correct link
    When I am on the "Course 1" "filter_embedquestion > test" page logged in as teacher
    And I set the field "Question category" to "Test questions [embed] (2)"
    And I set the field "id_questionidnumber" to "First question"
    And I press "Embed question"
    And I switch to "filter_embedquestion-iframe" iframe
    When I press "Fill with correct"
    Then the field "True" matches value "1"
    And I press "Check"
    And I should see "Correct"
    And I should see "Mark 1.00 out of 1.00"
    And "Fill with correct" "button" should not exist
    And I press "Start again"
    And I should not see "Correct"
    And the "Fill with correct" "button" should be enabled

  @javascript
  Scenario: Teacher can not see the Fill with correct link for open question
    When I am on the "Course 1" "filter_embedquestion > test" page logged in as teacher
    And I set the field "Question category" to "Test questions [embed] (2)"
    And I set the field "id_questionidnumber" to "Second question"
    And I press "Embed question"
    When I switch to "filter_embedquestion-iframe" iframe
    Then I should not see "Fill with correct"

  @javascript
  Scenario: Teacher can see and use the Question bank link.
    When I am on the "Course 1" "filter_embedquestion > test" page logged in as teacher
    And I set the field "Question category" to "Test questions [embed] (2)"
    And I set the field "id_questionidnumber" to "First question"
    And I press "Embed question"
    And I switch to "filter_embedquestion-iframe" iframe
    And I click on "Question bank" "link"
    Then I should see "First question"
    And ".highlight" "css_element" should exist in the "First question" "table_row"
