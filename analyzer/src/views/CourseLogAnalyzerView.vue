<template>
  <h2>Analyze course: {{ moodleCurrentCourse.shortname }}</h2>
  <br />
  Next step: preprocessing (load excel, join, etc. look for numpy pendant)
  <br />
  <CourseLogFilters
    :filter-options="courseLogFilterOptions"
    @filterSelectionUpdated="updateFilterSelection"
  />
  <br />
  graph 1
  <br />
  graph 2
  <br />
  graph 3
</template>

<script>
// @ is an alias to /src
import CourseLogFilters from "@/components/CourseLogFilters.vue";

import { mapState } from "vuex";

export default {
  name: "CourseLogAnalyzerView",
  components: {
    CourseLogFilters,
  },
  data: function () {
    return {
      courseLogRaw: [],
      courseLogFilterActives: {},
    };
  },
  computed: {
    ...mapState(["moodleUrl", "moodleToken", "moodleCurrentCourse"]),

    courseLogFilterOptions() {
      // init filter object with empty arrays for each field
      const filterCategories = {};
      for (let key in this.courseLogRaw[0]) {
        filterCategories[key] = [];
      }
      const options = this.courseLogRaw.reduce((filters, row) => {
        for (let [key, val] of Object.entries(row)) {
          // add new filter option only if not yet present
          const index = filters[key].findIndex(
            (currentFilterOption) => currentFilterOption == val
          );
          if (index <= -1) {
            filters[key].push(val);
          }
        }

        return filters;
      }, filterCategories);

      // sort all options alphabetically or numerically in ascending order
      for (let key in this.courseLogRaw[0]) {
        if (typeof options[key][0] == "number") {
          options[key].sort((a, b) => a >= b);
        } else {
          options[key].sort();
        }
      }

      return options;
    },

    eventCountPerUser() {
      return this.courseLogRaw.reduce((perUser, row) => {
        perUser[row.userid] = perUser[row.userid] || {};
        perUser[row.userid][row.eventname] =
          (perUser[row.userid][row.eventname] || 0) + 1;
        return perUser;
      }, {});
    },
  },
  methods: {
    fetchCourseData() {
      const moodleDataExportEndpoint = `${this.moodleUrl}/webservice/rest/server.php?wstoken=${this.moodleToken}&wsfunction=local_moodle_ws_la_trace_exporter_get_course_data&moodlewsrestformat=json&courseids[0]=${this.moodleCurrentCourse.courseid}`;

      console.log(moodleDataExportEndpoint);
      fetch(moodleDataExportEndpoint)
        .then((response) => response.json())
        .then((data) => {
          this.courseLogRaw = data;

          console.log("----------------------------------");

          console.log();
        });
    },
    updateFilterSelection(newSelection) {
      this.courseLogFilterActives = newSelection;
    },
  },
  mounted() {
    this.fetchCourseData();
  },
};
</script>
