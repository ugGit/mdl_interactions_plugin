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
        const boxValues = this.getBoxValues(transformedData[i]);
        boxValues.values.x = i;
        const boxOutliers = this.getOutliers(
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
  methods: {
    getOutliers(data, lowerFence, upperFence) {
      const outliers = [];
      for (var i = 0; i < data.length; i++) {
        if (data[i] < lowerFence || data[i] > upperFence) {
          outliers.push(data[i]);
        }
      }
      return outliers;
    },

    getBoxValues(data) {
      var boxData = {},
        min = Math.min.apply(Math, data),
        max = Math.max.apply(Math, data),
        q1 = this.getPercentile(data, 25),
        median = this.getPercentile(data, 50),
        q3 = this.getPercentile(data, 75),
        iqr = q3 - q1,
        lowerFence = q1 - iqr * 1.5,
        upperFence = q3 + iqr * 1.5;

      boxData.values = {};
      boxData.values.low = min < lowerFence ? lowerFence : min;
      boxData.values.q1 = q1;
      boxData.values.median = median;
      boxData.values.q3 = q3;
      boxData.values.high = max > upperFence ? upperFence : max;
      return boxData;
    },

    //get any percentile from an array
    getPercentile(data, percentile) {
      data.sort(this.numSort);
      var index = (percentile / 100) * data.length;
      var result;
      if (Math.floor(index) == index) {
        result = (data[index - 1] + data[index]) / 2;
      } else {
        result = data[Math.floor(index)];
      }
      return result;
    },

    //because .sort() doesn't sort numbers correctly
    numSort(a, b) {
      return a - b;
    },
  },
};
</script>
