@format @format_usqflexopen
Feature: Courses can be created with USQ Flexi format
  As a course creator
  I need to create courses with Flexi format as the default

  Scenario: Create a course with USQ Flexi format
    Given the following config values are set as admin:
      | config | value | plugin |
      | format | usqflexopen | moodlecourse |
    And I log in as "admin"
    When I navigate to "Courses > Add a new course" in site administration
    And I set the following fields to these values:
      | Course full name | Flexi course |
      | Course short name | Flexi course |
    And I press "Save and display"
    And I am on "Flexi course" course homepage
    Then "ul.usqflexopen" "css_element" should exist
