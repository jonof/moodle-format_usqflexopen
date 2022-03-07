@format @format_usqflexopen
Feature: Sections can be configured in usqflexopen format
  As a teacher
  I need to set section types in usqflexopen format

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format  | coursedisplay | numsections | startdate  |
      | Course 1 | C1        | usqflexopen | 0             | 10          | 1613311200 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name           | intro                 | course | idnumber | section |
      | page     | Test page name | Test page description | C1     | page1    | 0       |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

  Scenario: View the default name of the general section in usqflexopen format
    When I edit the section "0"
    Then the field "Custom" matches value "0"
    And the field "New value for Section name" matches value "General"

  Scenario: Edit the default name of the general section in usqflexopen format
    When I edit the section "0" and I fill the form with:
      | Custom | 1 |
      | New value for Section name | This is the general section |
    Then I should see "This is the general section" in the "li#section-0" "css_element"

  Scenario: Set the first section type to Getting Started
    When I edit the section "1" and I fill the form with:
      | Section type | Getting started |
    Then I should see "Getting started" in the "li#section-1 .sectionname > span" "css_element"
    And I should see "Getting started" in the "li#section-1 .sectionname .sectiontype" "css_element"

  Scenario: Edit the default name of a Getting Started section in usqflexopen format
    When I edit the section "1" and I fill the form with:
      | Section type | Getting started |
      | Custom | 1 |
      | New value for Section name | This is the Getting Started section |
    Then I should see "This is the Getting Started section" in the "li#section-1 .sectionname > span" "css_element"
    And I should see "Getting started" in the "li#section-1 .sectionname .sectiontype" "css_element"

  Scenario: Set the first section type to Assessment
    When I edit the section "1" and I fill the form with:
      | Section type | Assessment |
    Then I should see "Assessment 1" in the "li#section-1 .sectionname > span" "css_element"
    And I should see "Assessment" in the "li#section-1 .sectionname .sectiontype" "css_element"

  Scenario: Edit the default name of an Assessment section in usqflexopen format
    When I edit the section "1" and I fill the form with:
      | Section type | Assessment |
      | Custom | 1 |
      | New value for Section name | This is the Assessment section |
    Then I should see "This is the Assessment section" in the "li#section-1 .sectionname > span" "css_element"
    And I should see "Assessment" in the "li#section-1 .sectionname .sectiontype" "css_element"

  Scenario: Set the first section type to Week
    When I edit the section "1" and I fill the form with:
      | Section type | Week |
    Then I should see "15 February - 21 February" in the "li#section-1 .sectionname > span" "css_element"
    Then I should see "Week 1" in the "li#section-1 .sectionname span.weeknum" "css_element"
    And I should see "Week" in the "li#section-1 .sectionname .sectiontype" "css_element"

  Scenario: Edit the default name of a Week section in usqflexopen format
    When I edit the section "1" and I fill the form with:
      | Section type | Week |
      | Custom | 1 |
      | New value for Section name | This is the Week section |
    Then I should see "This is the Week section" in the "li#section-1 .sectionname > span" "css_element"
    Then I should see "Week 1: 15 February - 21 February" in the "li#section-1 .sectionname span.weeknum" "css_element"
    And I should see "Week" in the "li#section-1 .sectionname .sectiontype" "css_element"

  Scenario: Set the first section type to Topic
    When I edit the section "1" and I fill the form with:
      | Section type | Topic |
    Then I should see "Topic 1" in the "li#section-1 .sectionname > span" "css_element"
    And I should see "Topic" in the "li#section-1 .sectionname .sectiontype" "css_element"

  Scenario: Edit the default name of a Topic section in usqflexopen format
    When I edit the section "1" and I fill the form with:
      | Section type | Topic |
      | Custom | 1 |
      | New value for Section name | This is the Topic section |
    Then I should see "This is the Topic section" in the "li#section-1 .sectionname > span" "css_element"
    And I should see "Topic" in the "li#section-1 .sectionname .sectiontype" "css_element"

  Scenario: Configure a complicated arrangement of section types
    Given I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | Default section type | Week |
      | Show 'Getting started' sections | 1 |
      | Show 'Assessment' sections | 1 |
    And I press "Save and display"
    And I edit the section "1" and I fill the form with:
      | Section type | Getting started |
    And I edit the section "2" and I fill the form with:
      | Section type | Assessment |
    And I edit the section "5" and I fill the form with:
      | Section type | Topic |
    When I turn editing mode off
    Then I should see "General" in the "li#section-0" "css_element"
    And I should see "Getting started" in the "li#section-1" "css_element"
    And I should see "Assessment 1" in the "li#section-2" "css_element"
    And I should see "15 February - 21 February" in the "li#section-3" "css_element"
    And I should see "Week 1" in the "li#section-3 .weeknum" "css_element"
    And I should see "22 February - 28 February" in the "li#section-4" "css_element"
    And I should see "Week 2" in the "li#section-4 .weeknum" "css_element"
    And I should see "Topic 1" in the "li#section-5" "css_element"
    And I should see "1 March - 7 March" in the "li#section-6" "css_element"
