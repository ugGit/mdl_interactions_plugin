<template>
  <h2>Select a course to import</h2>
  <v-container>
    <v-list v-if="courses.length > 0">
      <v-list-subheader>Available courses for your user</v-list-subheader>
      <v-list-item
        v-for="(course, i) in courses"
        :key="i"
        :value="course"
        active-color="primary"
        style="border-bottom: 1px solid grey"
        @click="goToAnalyzingPage(course)"
        ><v-list-item-title v-text="course.shortname"></v-list-item-title
      ></v-list-item>
    </v-list>
    <div v-else>No course was found.</div>
  </v-container>
</template>

<script>
import { mapState } from "vuex";

export default {
  name: "SelectCourseFormView",
  data: function () {
    return {
      courses: [],
    };
  },
  computed: {
    ...mapState(["moodleUrl", "moodleToken"]),
  },
  methods: {
    getAvailableCourses() {
      const moodleAvailableCoursesEndpoint = `${this.moodleUrl}/webservice/rest/server.php?wstoken=${this.moodleToken}&wsfunction=local_moodle_ws_la_trace_exporter_get_available_courses&moodlewsrestformat=json`;
      fetch(moodleAvailableCoursesEndpoint)
        .then((res) => res.json())
        .then((res) => {
          this.courses = res;
        });
    },
    goToAnalyzingPage(courseProxy) {
      const course = Object.assign({}, courseProxy);
      this.$store.commit("setMoodleCurrentCourse", course);
      this.$router.push({ name: "selectEventMappingFileForm" });
    },
  },
  mounted() {
    this.getAvailableCourses();
  },
};
</script>
