<template>
  <h2>Analyze course: {{ moodleCurrentCourse.shortname }}</h2>
  <br />
  Next step: preprocessing (load excel, join, etc. look for numpy pendant)
  <br />
  <v-container>
    <v-row>
      <v-col cols="4">
        <CourseLogFilters
          :filter-options="courseLogFilterOptions"
          @filterSelectionUpdated="updateFilterSelection"
      /></v-col>
      <v-col cols="8">
        <CourseLogTable :course-log="courseLogFiltered" :is-loading="isLoading"
      /></v-col>
    </v-row>
    <v-row> </v-row>
    <v-row class="mt-8">
      <!-- margin top because custom style for multiselect messes up vuetify row layout -->
    </v-row>
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
      tmpData: [
        {
          type: "scatterpolar",
          r: [39, 28, 8, 7, 28, 39],
          theta: ["A", "B", "C", "D", "E", "A"],
          fill: "toself",
        },

        {
          type: "scatterpolar",
          r: [1.5, 10, 39, 31, 15, 1.5],
          theta: ["A", "B", "C", "D", "E", "A"],
          fill: "toself",
          name: "Group B",
        },
      ],
      tmpLayout: {
        polar: {
          radialaxis: {
            visible: true,
            range: [0, 50],
          },
        },

        showlegend: true,
      },
    };
  },
  computed: {
    ...mapState([
      "moodleUrl",
      "moodleToken",
      "moodleCurrentCourse",
      "eventMappings",
      "eventCategories",
    ]),

    courseLogFilterOptions() {
      // init filter object with empty arrays for each field
      const filterCategories = {};
      for (let key in this.courseLogFiltered[0]) {
        filterCategories[key] = [];
      }
      const options = this.courseLogFiltered.reduce((filters, row) => {
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
      for (let key in this.courseLogFiltered[0]) {
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
      const ecpu = {};
      for (let i = 0; i < this.courseLogFiltered.length; i++) {
        ecpu[this.courseLogFiltered[i].userid] =
          ecpu[this.courseLogFiltered[i].userid] || {};
        ecpu[this.courseLogFiltered[i].userid][
          this.courseLogFiltered[i].eventname
        ] =
          (ecpu[this.courseLogFiltered[i].userid][
            this.courseLogFiltered[i].eventname
          ] || 0) + 1;
      }
      return ecpu;
    },

    categoryCountPerUser() {
      const ccPerUser = {};
      for (const key in this.eventCountPerUser) {
        const userInitialized = {};
        for (let j = 0; j < this.eventCategories.length; j++) {
          userInitialized[this.eventCategories[j]] = 0;
        }
        ccPerUser[key] = userInitialized;
      }
      for (const userKey in this.eventCountPerUser) {
        for (const [eventnameKey, count] of Object.entries(
          this.eventCountPerUser[userKey]
        )) {
          // get event category for current event
          const eventCategory = this.eventMappings[eventnameKey].newlc;
          // add to catgeory count for current user (except for undefined values)
          if (eventCategory) {
            ccPerUser[userKey][eventCategory] += count;
          }
        }
      }
      return ccPerUser;
    },
  },
  methods: {
    fetchCourseData() {
      const moodleDataExportEndpoint = `${this.moodleUrl}/webservice/rest/server.php?wstoken=${this.moodleToken}&wsfunction=local_moodle_ws_la_trace_exporter_get_course_data&moodlewsrestformat=json&courseids[0]=${this.moodleCurrentCourse.courseid}`;
      fetch(moodleDataExportEndpoint)
        .then((response) => response.json())
        .then((data) => {
          this.courseLogRaw = data;

          console.log("--------");
          console.log(this.categoryCountPerUser["2"]);

          const d = {
            type: "scatterpolar",
            r: Object.values(this.categoryCountPerUser["2"]),
            theta: this.eventCategories,
            fill: "toself",
          };
          const b = {
            type: "scatterpolar",
            r: Object.values(this.categoryCountPerUser["2"]),
            theta: this.eventCategories,
            fill: "toself",
          };
          this.tmpData = [d, b];
        });
    },

    // Use a debounced function to give Vue time to update values (reactivity delay)
    updateFilterSelection: debounce(function (newSelection) {
      this.courseLogFilterActives = newSelection;
    }, 100),
  },
  mounted() {
    this.fetchCourseData();
  },
};
</script>
