<template>
  <v-card>
    <v-card-header>Selected Users Course Meta Information</v-card-header>
    <v-container>
      <v-row>
        <v-col
          v-for="user in onlyUsers"
          :key="user.name"
          style="text-align: left"
        >
          <span
            ><b>{{ user.name }}</b></span
          >
          <br />
          <span>Role: {{ user.role }}</span>
        </v-col>
      </v-row>
    </v-container>
  </v-card>
</template>

<script>
import { averageFakeUserId } from "@/utils/constants";

export default {
  name: "UserSelectionDetails",
  props: ["userDetails"],
  computed: {
    onlyUsers() {
      return this.userDetails.filter((u) => u.name != averageFakeUserId);
    },
  },
};
</script>
