<?php

// definition of interactions based on the plugin for moodle V2
$interactions = array(
  "userview", "courseuser report", "courseview", "courseupdate", "courseenrol", "courseunenrol", "coursereport interactions", "coursereport log", "coursereport live", "coursereport outline",
  "coursereport participation", "coursereport stats", "messagewrite", "messageread", "messageadd contact", "messageremove contact", "messageblock contact", "messageunblock contact", "groupview", "tagupdate",
  "assignmentview", "assignmentadd", "assignmentupdate", "assignmentview submission", "assignmentupload", "chatview", "chatadd", "chatupdate", "chatreport", "chattalk",
  "choiceview", "choiceupdate", "choiceadd", "choicereport", "choicechoose", "choicechoose again", "dataview", "dataadd", "dataupdate", "datarecord delete",
  "datafields add", "datafields update", "datatemplates saved", "datatemplates def", "feedbackstartcomplete", "feedbacksubmit", "feedbackdelete", "feedbackview", "feedbackview all", "folderview",
  "folderview all", "folderupdate", "folderadd", "forumadd", "forumupdate", "forumadd discussion", "forumadd post", "forumupdate post", "forumuser report", "forummove discussion",
  "forumview suscribers", "forumview discussion", "forumview forum", "forumsubscribe", "forumunsubscribe", "glossaryadd", "glossaryupdate", "glossaryview", "glossaryview all", "glossaryadd entry",
  "glossaryupdate entry", "glossaryadd category", "glossaryupdate category", "glossarydelete category", "glossaryapprove entry", "glossaryview entry", "imscpview", "imscpview all", "imscpupdate", "imscpadd",
  "labeladd", "labelupdate", "lessonstart", "lessonend", "lessonview", "pageview", "pageviewall", "pageupdate", "pageadd", "quizadd",
  "quizupdate", "quizview", "quizreport", "quizattempt", "quizsubmit", "quizreview", "quizeditquestions", "quizpreview", "quizstart attempt", "quizclose attempt",
  "quizcontinue attempt", "quizedit override", "quizdelete override", "resourcesview", "resourcesview all", "resourcesupdate", "resourcesadd", "scormview", "scormreview", "scormupdate",
  "scormadd", "surveyadd", "surveyupdate", "surveydownload", "surveyview form", "surveyview graph", "surveyview report", "surveysubmit", "urlview", "urlview all",
  "urlupdate", "urladd", "workshopadd", "workshopupdate", "workshopview", "workshopview all", "workshopadd submission", "workshopupdate submission", "workshopview submission", "workshopadd assessment",
  "workshopupdate assessment", "workshopadd example", "workshopupdate example", "workshopview example", "workshopadd reference assessment", "workshopupdate reference assessment", "workshopadd example assessment", "workshopupdate example assessment", "workshopupdate aggregate grades", "workshopupdate clear aggregated grades",
  "workshopupdate clear assessments", "userlogin", "userlogout"
);

// extracted interaction types and assigned interactions
$interactionCategoryTargets = [
  ["Student-Student", [29, 55, 56, 57, 61, 124]], // ["under role conditions (id 5/9)", [12, 13]]
  ["Student-Teacher", [12, 13, 24, 29, 55, 56, 57, 61, 111, 112, 117, 124]],
  ["Student-Content", [
    19, 20, 23, 26, 36, 36, 38, 39, 40, 41, 47, 48, 49, 50, 51, 52, 53, 59, 62, 65, 66, 67,
    68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 95,
    97, 103, 104, 105, 106, 107, 108, 109, 110, 118, 119, 120, 121, 125
  ]],
  ["Student-System",  [
    0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 14, 15, 16, 17, 18, 25, 27, 28, 30, 31, 32, 33,
    34, 35, 42, 43, 44, 45, 46, 54, 58, 60, 63, 64, 89, 90, 91, 92, 93, 94, 96, 98, 99, 100,
    101, 102, 113, 114, 115, 116, 122, 123, 124, 125, 126, 127, 128, 129, 130, 131, 132, 133,
    134, 135, 136, 137, 138
  ]]
];
$interactionCategoryTwo = [
  ["'Transcontendidos'", [
    19, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 65, 66, 67,
    68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88,
    103, 104, 105, 106, 107, 108, 109, 110, 118, 119, 120, 121
  ]],
  ["'Interaccionesclase'", [
    12, 13, 14, 15, 16, 17, 25, 26, 27, 28, 29, 53, 54, 55,
    56, 57, 58, 59, 60, 61, 62, 63, 64
  ]],
  ["Evaluate Students", [
    20, 21, 22, 23, 24, 30, 31, 32, 33, 34, 35, 89, 90, 91, 92,
    93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 122, 123, 124, 125, 126, 127, 128, 129, 130, 131,
    132, 133, 134, 135, 136, 137, 138, 139, 140
  ]],
  ["Evaluate Course/Teachers", [11, 112, 113, 114, 115, 116, 117]]
];
$interactionCategoryThree = [
  ["Active", [
    3, 4, 5, 12, 14, 15, 16, 17, 19, 21, 22, 24, 26, 27, 29, 31, 32, 34, 35, 37, 38,
    39, 40, 41, 42, 43, 44, 45, 46, 51, 52, 53, 54, 55, 56, 57, 58, 59, 63, 64, 65, 66, 69, 70,
    71, 72, 73, 74, 78, 79, 80, 81, 82, 83, 87, 88, 89, 90, 93, 94, 96, 98, 99, 100, 101, 102,
    105, 106, 109, 110, 111, 112, 113, 117, 120, 121, 122, 123, 126, 127, 129, 130, 131, 132,
    134, 135, 136, 137, 138, 139, 140
  ]],
  ["Passive", [
    0, 1, 2, 6, 7, 8, 9, 10, 11, 13, 18, 20, 23, 25, 28, 30, 33, 36, 47, 48, 49, 50,
    58, 60, 61, 62, 67, 68, 75, 76, 77, 84, 85, 86, 91, 92, 95, 97, 103, 104, 107, 108, 113, 114,
    115, 116, 118, 119, 124, 125, 128, 133
  ]] // TODO: probably by definition, every action that is not active should be passive
];
$interactionCategoryFour = [
  ["'Entrada'", [141]],
  ["'Salida'", [142]],
];

$interactionCategories = [
  $interactionCategoryTargets,
  $interactionCategoryTwo,
  $interactionCategoryThree,
  $interactionCategoryFour
];

// make a print out of all the categories and the associated interactions
$count = 0;
foreach ($interactionCategories as $iCat) {
  $count++;
  echo "<h3>Category " . $count . "</h3>";


  foreach ($iCat as $iType) {
    echo "<h5>Type: " . $iType[0] . "</h5>";
    echo "<ul>";
    foreach ($iType[1] as $i) {
      echo "<li>" . $interactions[$i] . "</li>";
    }
    echo "</ul>";
  }
}
