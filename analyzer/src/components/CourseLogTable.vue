<template>
  <v-card>
    <v-card-header> Log Table Extract (10 items)</v-card-header>
    <v-table v-if="courseLog.length > 0">
      <template v-slot:default>
        <thead>
          <tr>
            <th
              v-for="columnHeader in Object.keys(courseLog[0])"
              :key="'header-' + columnHeader"
            >
              {{ columnHeader }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, index) in courseLog" :key="'row-' + index">
            <td
              v-for="(col, colName) in row"
              :key="'row-' + index + 'col-' + colName"
            >
              <span v-if="colName == 'timecreated'">
                {{ formatTimestampToDate(col) }}</span
              >
              <span v-else>{{ col }}</span>
            </td>
          </tr>
        </tbody>
      </template>
    </v-table>
    <div v-else>
      No data imported or matching the currently selected filters.
    </div>
  </v-card>
</template>

<script>
import { formatTimestampToDate } from "@/utils/helpers";
export default {
  name: "CourseLogTable",
  props: ["courseLog", "isLoading"],
  methods: {
    formatTimestampToDate,
  },
};
</script>
