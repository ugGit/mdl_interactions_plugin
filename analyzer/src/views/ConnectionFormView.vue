<template>
  <v-form>
    <h2>Connect to your Moodle instance</h2>
    <v-container>
      <v-row>
        <v-col>
          <v-text-field v-model="url" label="URL" required></v-text-field>
          <v-text-field
            v-model="username"
            label="username"
            required
          ></v-text-field>
          <v-text-field
            v-model="password"
            label="password"
            type="password"
            required
          ></v-text-field>
        </v-col>
      </v-row>
      <v-row>
        <v-col>
          <v-btn block color="success" @click="establishConnection()"
            >Login</v-btn
          >
        </v-col>
      </v-row>
    </v-container>
  </v-form>
</template>

<script>
export default {
  name: "ConnectionView",
  data: function () {
    return {
      valid: false,
      url: "",
      username: "",
      password: "",
    };
  },
  methods: {
    establishConnection: () => {
      // minimal validation
      if (!(this.url && this.username && this.password)) {
        console.log("Missing content for form");
      }
    },
  },
};
</script>
