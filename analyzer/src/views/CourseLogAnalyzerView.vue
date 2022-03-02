<template>
  <h2>Analyze course: {{ moodleCurrentCourse.shortname }}</h2>
  <br />
  Next step: preprocessing (load excel, join, etc. look for numpy pendant)
  <br />
  <v-container>
    <v-row>
      <CourseLogFilters
        :filter-options="courseLogFilterOptions"
        @filterSelectionUpdated="updateFilterSelection"
      />
    </v-row>
    <v-row class="mt-8">
      <!-- margin top because custom style for multiselect messes up vuetify row layout -->
      <CourseLogTable :course-log="courseLogFiltered" :is-loading="isLoading" />
    </v-row>

    <br />
    graph 1
    <br />
    graph 2
    <br />
    graph 3
  </v-container>
</template>

<script>
// @ is an alias to /src
import CourseLogFilters from "@/components/CourseLogFilters.vue";
import CourseLogTable from "@/components/CourseLogTable.vue";

import { mapState } from "vuex";

import { debounce } from "lodash";

export default {
  name: "CourseLogAnalyzerView",
  components: {
    CourseLogFilters,
    CourseLogTable,
  },
  data: function () {
    return {
      courseLogRaw: [],
      courseLogFilterActives: {},
      isLoading: true,
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

    courseLogFiltered() {
      let courseLog = this.courseLogRaw.slice(); // create a copy of the raw course log
      for (const key in this.courseLogFilterActives) {
        courseLog = courseLog.filter((row) => {
          return (
            this.courseLogFilterActives[key].length == 0 || // prevent filtering by empty array of filter conditions
            this.courseLogFilterActives[key].includes(row[key])
          );
        });
      }
      return courseLog;
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

      fetch(moodleDataExportEndpoint)
        .then((response) => response.json())
        .then((data) => {
          this.courseLogRaw = data;
        });
    },
    // Use a debounced function to give Vue time to update values (reactivity delay)
    updateFilterSelection: debounce(function (newSelection) {
      console.log("Hi");
      this.courseLogFilterActives = newSelection;

      console.log(this.courseLogFilterActives);
      let courseLog = this.courseLogRaw;
      for (const key in this.courseLogFilterActives) {
        courseLog = courseLog.filter((row) => {
          return this.courseLogFilterActives[key].includes(row[key]);
        });
      }
      console.log(courseLog.length);
    }, 100),
  },
  mounted() {
    this.fetchCourseData();
  },
};
</script>
