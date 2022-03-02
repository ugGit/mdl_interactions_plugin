<template>
  <v-card>
    <v-card-header> Events </v-card-header>
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
            <td v-for="(col, i) in row" :key="'row-' + index + 'col-' + i">
              {{ col }}
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
export default {
  name: "CourseLogTable",
  props: ["courseLog", "isLoading"],
};
</script>
