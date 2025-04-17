@ou @ou_vle @filter @filter_embedquestion
Feature: Add an activity and embed a question inside that activity
  In order to encourage students interacting with ativity and learning from it
  As a teacher
  I need to insert appropriate interactive questions (iCMAs) inside any activity modules

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher  | Terry1    | Teacher1 | teacher1@example.com |
      | student  | Sam1      | Student1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
      | student | C1     | student        |
    And the following "question categories" exist:
      | contextlevel | reference | name          | idnumber |
      | Course       | C1        | Test questions| embed    |
    And the "embedquestion" filter is "on"

  @javascript
  Scenario: Test using the helper script - embed a specific question
    Given the following "questions" exist:
      | questioncategory | qtype     | name           | idnumber |
      | Test questions   | truefalse | First question | test1    |
    When I am on the "Course 1" "filter_embedquestion > test" page logged in as teacher
    And I set the field "Question category" to "Test questions [embed] (1)"
    And I set the field "id_questionidnumber" to "First question"
    And I press "Embed question"
    And ".filter_embedquestion-iframe[title=\"Embedded question 1\"]" "css_element" should exist
    And I switch to "filter_embedquestion-iframe" iframe
    And I click on "True" "radio" in the "The answer is true." "question"
    And I press "Check"
    Then I should see "Correct"
    And I should see "Mark 1.00 out of 1.00"
    And I press "Start again"
    And I should not see "Correct"

    # There was a bug where the score was shown wrong when an attempt was resumed. Test that.
    And I switch to the main frame
    And I reload the page
    And I switch to "filter_embedquestion-iframe" iframe
    And I should see "Marked out of 1.00"

  @javascript
  Scenario: Test using the helper script - embed a question at random
    Given the following "questions" exist:
      | questioncategory | qtype     | name | idnumber |
      | Test questions   | truefalse | Q1   | test1    |
      | Test questions   | truefalse | Q2   | test2    |
      | Test questions   | truefalse | Q3   | test3    |
      | Test questions   | truefalse | Q4   | test4    |
    When I am on the "Course 1" "filter_embedquestion > test" page logged in as teacher
    And I set the field "Question category" to "Test questions [embed] (4)"
    And I set the field "id_questionidnumber" to "Choose an embeddable question from this category randomly"
    And I set the field "Iframe description" to "Embed question for behat testing"
    And I press "Embed question"
    And ".filter_embedquestion-iframe[title=\"Embed question for behat testing\"]" "css_element" should exist
    And I switch to "filter_embedquestion-iframe" iframe
    And I click on "True" "radio" in the "The answer is true." "question"
    And I press "Check"
    Then I should see "Correct"
    And I press "Start again"
    And I should not see "Correct"
    And I click on "True" "radio" in the "The answer is true." "question"

  @javascript
  Scenario: Editing the embedded question, and saving, returns to where the embedded question is, showing the new version
    Given the following "questions" exist:
      | questioncategory | qtype     | name           | idnumber |
      | Test questions   | truefalse | First question | test1    |
    When I am on the "Course 1" "filter_embedquestion > test" page logged in as teacher
    And I set the field "Question category" to "Test questions [embed] (1)"
    And I set the field "id_questionidnumber" to "First question"
    And I press "Embed question"
    And I switch to "filter_embedquestion-iframe" iframe
    And I follow "Edit question"
    And I should see "Editing a True/False question"
    And I set the field "Question text" to "Edited question text."
    And I press "id_submitbutton"
    # Because of the way the test page works, we need to re-select the question.
    Then I should see "Generate the code to embed a question"
    And I set the field "Question category" to "Test questions [embed] (1)"
    And I set the field "id_questionidnumber" to "First question"
    And I press "Embed question"
    And I switch to "filter_embedquestion-iframe" iframe
    And I should see "Edited question text."

  @javascript
  Scenario: Editing the embedded question, and cancelling, returns to where the embedded question is
    Given the following "questions" exist:
      | questioncategory | qtype     | name           | idnumber |
      | Test questions   | truefalse | First question | test1    |
    When I am on the "Course 1" "filter_embedquestion > test" page logged in as teacher
    And I set the field "Question category" to "Test questions [embed] (1)"
    And I set the field "id_questionidnumber" to "First question"
    And I press "Embed question"
    And I switch to "filter_embedquestion-iframe" iframe
    And I follow "Edit question"
    And I should see "Editing a True/False question"
    And I press "Cancel"
    # Because of the way the test page works, we need to re-select the question.
    Then I should see "Generate the code to embed a question"
    And I set the field "Question category" to "Test questions [embed] (1)"
    And I set the field "id_questionidnumber" to "First question"
    And I press "Embed question"
    And I switch to "filter_embedquestion-iframe" iframe
    And I should see "The answer is true."

  @javascript
  Scenario: Test display of Save button for embedded recordrtc question.
    Given the qtype_recordrtc plugin is installed
    When the following "questions" exist:
      | questioncategory | qtype     | name                | idnumber | template |
      | Test questions   | recordrtc | Record AV question  | test1    | audio    |
    And I am on the "Course 1" "filter_embedquestion > test" page logged in as teacher
    And I expand all fieldsets
    And I set the field "Question category" to "Test questions [embed] (1)"
    And I set the field "id_questionidnumber" to "Record AV question"
    And I set the field "How the question behaves" to "Immediate feedback"
    And I press "Embed question"
    And I switch to "filter_embedquestion-iframe" iframe
    And I should see "Save"
