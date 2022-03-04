<template>
  <h2>Analyze course: {{ moodleCurrentCourse.shortname }}</h2>
  <v-container>
    <v-row>
      <v-col cols="4">
        <course-log-filters
          :filter-options="courseLogFilterOptions"
          @filterSelectionUpdated="updateFilterSelection"
      /></v-col>
      <v-col cols="8" v-if="!isLoading">
        <v-row>
          <v-col>
            <plot-user-selection
              :possible-users="courseLogFilterOptions['userid']"
              @userSelectionUpdated="updateUserSelection"
            />
          </v-col>
        </v-row>
        <v-row>
          <v-col>
            <event-distribution-box-plot
              v-if="
                Object.keys(categoryCountPerUser).length > 0 &&
                selectedUserData.length > 0
              "
              :data="categoryCountPerUser"
              :selected-users="selectedUserData"
            />
          </v-col>
        </v-row>
        <v-row>
          <v-col>
            <compare-event-distribution-radar-chart
              v-if="selectedUserData.length > 0"
              :plot-data="selectedUserData"
              :plot-data-categories="Object.values(eventCategories)"
            />
          </v-col>
        </v-row>
        <v-row>
          <v-col>
            <course-log-table :course-log="courseLogFiltered.slice(0, 10)" />
          </v-col>
        </v-row>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
// @ is an alias to /src
import CourseLogFilters from "@/components/CourseLogFilters.vue";
import CourseLogTable from "@/components/CourseLogTable.vue";
import CompareEventDistributionRadarChart from "@/components/CompareEventDistributionRadarChart.vue";
import PlotUserSelection from "@/components/PlotUserSelection.vue";
import EventDistributionBoxPlot from "@/components/EventDistributionBoxPlot.vue";

import { mapState } from "vuex";

import { debounce } from "lodash";

export default {
  name: "CourseLogAnalyzerView",
  components: {
    CourseLogFilters,
    CourseLogTable,
    CompareEventDistributionRadarChart,
    PlotUserSelection,
    EventDistributionBoxPlot,
  },
  data: function () {
    return {
      courseLogRaw: [],
      courseLogFilterActives: {},
      isLoading: true,
      selectedUsersToPlot: [],
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

    selectedUserData() {
      const userData = [];
      this.selectedUsersToPlot.forEach((userid) => {
        userData.push({
          data: Object.values(this.categoryCountPerUser[userid.toString()]),
          name: "Student " + userid,
        });
      });
      return userData;
    },
  },
  methods: {
    fetchCourseData() {
      const moodleDataExportEndpoint = `${this.moodleUrl}/webservice/rest/server.php?wstoken=${this.moodleToken}&wsfunction=local_moodle_ws_la_trace_exporter_get_course_data&moodlewsrestformat=json&courseids[0]=${this.moodleCurrentCourse.courseid}`;
      fetch(moodleDataExportEndpoint)
        .then((response) => response.json())
        .then((data) => {
          this.courseLogRaw = data;
          this.isLoading = false;
        });
    },

    // Use a debounced function to give Vue time to update computed variables based on active filters (reactivity delay)
    updateFilterSelection: debounce(function (newSelection) {
      this.courseLogFilterActives = newSelection;
    }, 100),

    updateUserSelection(newSelection) {
      this.selectedUsersToPlot = newSelection;
    },
  },
  mounted() {
    this.fetchCourseData();
  },
};
</script>
