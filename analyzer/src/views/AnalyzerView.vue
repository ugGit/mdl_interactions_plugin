<template>
  <h2>Analyze course: {{ moodleCurrentCourse.shortname }}</h2>
  <br />
  Next step: preprocessing (load excel, join, etc. look for numpy pendant)
  <br />
  filter components (copy from django app)
  <br />
  graph 1
  <br />
  graph 2
  <br />
  graph 3
</template>

<script>
// @ is an alias to /src
// import HelloWorld from "@/components/HelloWorld.vue";

import { mapState } from "vuex";

export default {
  name: "AnalyzerView",
  components: {
    // HelloWorld,
  },
  data: function () {
    return {
      courseLog: [],
    };
  },
  computed: {
    ...mapState(["moodleUrl", "moodleToken", "moodleCurrentCourse"]),
  },
  methods: {
    fetchCourseData() {
      const moodleDataExportEndpoint = `${this.moodleUrl}/webservice/rest/server.php?wstoken=${this.moodleToken}&wsfunction=local_moodle_ws_la_trace_exporter_get_course_data&moodlewsrestformat=json&courseids[0]=${this.moodleCurrentCourse.courseid}`;

      console.log(moodleDataExportEndpoint);
      fetch(moodleDataExportEndpoint)
        .then((response) => response.json())
        .then((data) => (this.courseLog = data));
    },
  },
  mounted() {
    this.fetchCourseData();
    console.log(this.courseLog);
  },
};
</script>
