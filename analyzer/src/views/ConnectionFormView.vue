<template>
  <h2>Connect to your Moodle instance</h2>
  <v-form>
    <v-container>
      <v-row>
        <v-col>
          <v-text-field v-model="url" label="URL" required></v-text-field>
          <v-text-field
            v-model="username"
            label="Username"
            required
          ></v-text-field>
          <v-text-field
            v-model="password"
            label="Password"
            type="password"
            required
          ></v-text-field>
        </v-col>
      </v-row>
      <v-row>
        <v-col>
          <v-btn
            block
            color="primary"
            @click="establishConnection()"
            class="py-7"
            >Login</v-btn
          >
        </v-col>
      </v-row>
    </v-container>
  </v-form>
</template>

<script>
import { mapState } from "vuex";

export default {
  name: "ConnectionView",
  data: function () {
    return {
      url: "http://localhost:8000",
      username: "teacher",
      password: "teacher",
    };
  },
  computed: {
    ...mapState(["moodleUrl", "moodleToken"]),
  },
  methods: {
    establishConnection() {
      // minimal validation
      if (!(this.url && this.username && this.password)) {
        console.log("Missing content for form");
      }

      // try fetching the token from the moodle instance
      const moodleTokenEndpoint = `${this.url}/login/token.php?username=${this.username}&password=${this.password}&service=loganalyzer`;
      fetch(moodleTokenEndpoint)
        .then((response) => response.json())
        .then((data) => {
          // store meta info for future queries to webservice
          this.$store.commit("setMoodleToken", data.token);
          this.$store.commit("setMoodleUrl", this.url);

          this.$router.push({ name: "selectCourseForm" });
        });
    },
  },
};
</script>
