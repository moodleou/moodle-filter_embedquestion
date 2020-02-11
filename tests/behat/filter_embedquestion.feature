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
    And I log in as "teacher"

  @javascript
  Scenario: Test using the helper script - embed a specific question
    Given the following "questions" exist:
      | questioncategory | qtype     | name           | idnumber |
      | Test questions   | truefalse | First question | test1    |
    When I am on the filter test page for "Course 1"
    And I set the field "Question category" to "Test questions [embed] (1)"
    And I set the field "id_questionidnumber" to "First question"
    And I press "Embed question"
    And I switch to "filter_embedquestion-iframe" iframe
    And I click on "True" "radio" in the "The answer is true." "question"
    And I press "Check"
    Then I should see "Correct"
    And I press "Start again"
    And I should not see "Correct"

  @javascript
  Scenario: Test using the helper script - embed a question at random
    Given the following "questions" exist:
      | questioncategory | qtype     | name | idnumber |
      | Test questions   | truefalse | Q1   | test1    |
      | Test questions   | truefalse | Q2   | test2    |
      | Test questions   | truefalse | Q3   | test3    |
      | Test questions   | truefalse | Q4   | test4    |
    When I am on the filter test page for "Course 1"
    And I set the field "Question category" to "Test questions [embed] (4)"
    And I set the field "id_questionidnumber" to "Choose an embeddable question from this category randomly"
    And I press "Embed question"
    And I switch to "filter_embedquestion-iframe" iframe
    And I click on "True" "radio" in the "The answer is true." "question"
    And I press "Check"
    Then I should see "Correct"
    And I press "Start again"
    And I should not see "Correct"
    And I click on "True" "radio" in the "The answer is true." "question"

  @javascript
  Scenario: Links to and from the question bank
    Given the following "questions" exist:
      | questioncategory | qtype     | name           | idnumber |
      | Test questions   | truefalse | First question | test1    |
    When I am on the filter test page for "Course 1"
    And I set the field "Question category" to "Test questions [embed] (1)"
    And I set the field "id_questionidnumber" to "First question"
    And I press "Embed question"
    And I switch to "filter_embedquestion-iframe" iframe
    And I follow "Edit question"
    Then I should see "Editing a True/False question"
    And I press "Cancel"
    And I should see "Generate the code to embed a question"
