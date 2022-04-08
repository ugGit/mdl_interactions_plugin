<template>
  <v-card>
    <v-card-header>Overall Event Distribution</v-card-header>
    <vue-highcharts
      type="chart"
      :options="chartOptions"
      :redrawOnUpdate="true"
      :oneToOneUpdate="true"
      :animateOnUpdate="true"
    />
    <p class="text-caption py-5">
      *The criteria for outliers at the lower boundary is set to: Q1 - (Q3 - Q1)
      <br />
      The same ratio applies for the upper boundary with: Q3 + (Q3 - Q1)
    </p>
  </v-card>
</template>

<script>
import VueHighcharts from "vue3-highcharts";
import HighCharts from "highcharts";
import HighchartsMore from "highcharts/highcharts-more";

HighchartsMore(HighCharts);

import * as bp from "@/utils/boxplot";

export default {
  name: "EventDistributionBoxPlot",
  props: ["data", "selectedUsers"],
  components: {
    VueHighcharts,
  },
  computed: {
    // inspired by: https://stackoverflow.com/a/30896483/6691953
    chartOptions() {
      //build the data and add the series to the chart
      const boxData = [];

      // get labels from input data (i.e. categories)
      const labels = Object.keys(this.data[Object.keys(this.data)[0]]);
      // transform input data to arrays containing values of each category
      const transformedData = labels.map((l) => {
        return Object.values(this.data).map((user) => {
          return user[l];
        });
      });
      // calculate box plot values
      for (let i = 0; i < transformedData.length; i++) {
        const boxValues = bp.getBoxValues(transformedData[i]);
        boxValues.values.x = i;
        const boxOutliers = bp.getOutliers(
          transformedData[i],
          boxValues.values.low,
          boxValues.values.high
        );
        boxValues.values.outliers = boxOutliers.map((x) => [i, x]); // TODO: place this properly, meaning if on values or somewhere else
        boxData.push(boxValues.values);
      }

      return {
        chart: {
          type: "boxplot",
        },

        title: {
          text: "", // hack to not display any title
        },

        legend: {
          verticalAlign: "top",
          layout: "horizontal",
        },

        xAxis: {
          categories: labels,
          title: {
            text: "Categories",
          },
        },

        yAxis: {
          title: {
            text: "Nbr of Interactions",
          },
        },

        series: [
          // config data for quartile and median
          bp.generateQuartileAndMedianPlotConfigData(boxData),
          // config data for outliers
          bp.generateOutlierPlotConfigData(boxData),
          // config data for first selected user
          bp.generateSelectedUserPlotConfigData(
            this.selectedUsers[0],
            HighCharts.getOptions().colors[0]
          ),
          // config data for second selected user
          bp.generateSelectedUserPlotConfigData(
            this.selectedUsers[1],
            HighCharts.getOptions().colors[3]
          ),
        ],
      };
    },
  },
};
</script>
